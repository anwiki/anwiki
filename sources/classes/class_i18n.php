<?php
/**
 * Anwiki is a multilingual content management system <http://www.anwiki.com>
 * Copyright (C) 2007-2009 Antoine Walter <http://www.anw.fr>
 * 
 * Anwiki is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 * 
 * Anwiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Anwiki.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Anwiki's translation files and internationalization management.
 * @package Anwiki
 * @version $Id: class_i18n.php 256 2010-03-10 20:50:10Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class Anwi18n
{
	private static $translations;
	private static $aaLoadedTranslationNames = array();
	private static $asQueuedTranslationNames = array();
	private static $asTranslationDirectories = array();
	
	const TRANSLATIONS_PREFIX_LOCAL = "local_";
	const LANG_DEFAULT = "en"; //the only lang for which we are sure that translation files are complete
	
	const MINLEN_LANG=2;
	const MAXLEN_LANG=10; /* used by users and storage drivers */
	
	
	//default lang : session
	static function dateTime($nTimestamp, $sLang=false, $nTimezone=false)
	{
		if (!$sLang)
		{
			$sLang = AnwCurrentSession::getLang();
		}
		return AnwUtils::date(AnwComponent::g_("local_datetime", array(), $sLang), $nTimestamp, $nTimezone);
	}
	
	//default lang : session
	static function date($nTimestamp, $sLang=false, $nTimezone=false)
	{
		if (!$sLang)
		{
			$sLang = AnwCurrentSession::getLang();
		}
		return AnwUtils::date(AnwComponent::g_("local_date", array(), $sLang), $nTimestamp, $nTimezone);
	}
	
	//--------------------------------
	
	
	static function t_($id, $params, $sPrefix, $sLang=false, $sValueIfNotFound=false)
	{
		if ($sLang == false)
		{
			if (self::isLocalTranslation($id))
			{
				$sLang = AnwAction::getActionLang();
			}
			else
			{
				$sLang = AnwCurrentSession::getLang();
			}
		}
		
		$translation = "";
		if (!isset(self::$translations[$sLang][$sPrefix][$id]))
		{
			//translation is not yet loaded, search in queued translations files
			AnwDebug::log("(Anwi18n) looking for: '".$id."' (".$sLang.")");
			
			if (count(@self::$asQueuedTranslationNames[$sPrefix])>0)
			{
				//we have unloaded translations, load it now
				foreach (self::$asQueuedTranslationNames[$sPrefix] as $sTranslationName => $null)
				{
					unset(self::$asQueuedTranslationNames[$sPrefix][$sTranslationName]); //avoid recursive loops				
					self::loadTranslationsFromLang($sTranslationName, $sLang);
				}
			}
		}
		
		if (!isset(self::$translations[$sLang][$sPrefix][$id]))
		{
			if (count(@self::$aaLoadedTranslationNames[$sPrefix])>0)
			{
				//make sure we loaded corresponding translation files in the requested language
				foreach (self::$aaLoadedTranslationNames[$sPrefix] as $sTranslationName => $null)
				{
					if (!isset(self::$aaLoadedTranslationNames[$sPrefix][$sTranslationName][$sLang]))
					{
						self::loadTranslationsFromLang($sTranslationName, $sLang);
					}
				}
			}
		}
		if (isset(self::$translations[$sLang][$sPrefix][$id]))
		{
			//translation is already loaded			
			$translation = self::$translations[$sLang][$sPrefix][$id];
			
			//parse parameters: parameters names start with % and end with %
			foreach ($params as $find => $replace)
			{
				$find ='%'.$find.'%';
				$translation = str_replace($find, $replace, $translation);
			}
		}
		else
		{
			if ($sLang != self::LANG_DEFAULT)
			{
				//try in default language
				return self::t_($id, $params, $sPrefix, self::LANG_DEFAULT, $sValueIfNotFound);
			}
			$translation = ($sValueIfNotFound===false ? '#'.$id.'#' : $sValueIfNotFound);
			AnwDebug::log("! Missing translation : $id"); //print $sPrefix.'!'.$id.'!';print_r(self::$translations);exit;
		}
		return $translation;
	}
	
	/**
	 * Load a translation file.
	 */
	static function loadTranslationsFromPath($sTranslationName, $sDir, $bIsAddon, $sPrefix)
	{
		//we delay translations loading in order to:
		//1) reduce memory usage as much as possible
		//2) avoid a recursive loop with getCurrentSession()->rebuildUser()->hookIsValidXXX()->loadPlugins()->loadTranslations()->getCurrentSession()
		
		self::$asQueuedTranslationNames[$sPrefix][$sTranslationName] = true;
		self::$asTranslationDirectories[$sTranslationName] = array($sDir, $bIsAddon, $sPrefix);
		AnwDebug::log("Queued translation file: ".$sTranslationName." (".$sPrefix.")");
	}
	
	private static function loadTranslationsFromLang($sTranslationName, $sLang)
	{
		//retrieve infos about this translation file
		if ( !isset(self::$asTranslationDirectories[$sTranslationName]) )
		{
			throw new AnwUnexpectedException("no translationDirectory for ".$sTranslationName);
		}
		list($sDir, $bIsAddon, $sPrefix) = self::$asTranslationDirectories[$sTranslationName];
		
		if (isset(self::$aaLoadedTranslationNames[$sPrefix][$sTranslationName][$sLang]))
		{
			return;
		}
		
		self::$aaLoadedTranslationNames[$sPrefix][$sTranslationName][$sLang] = 1;
		$sTranslationFile = $sTranslationName.".lang.".$sLang.".php";
		
		
		//load default translations
		$sFileName = ($bIsAddon ? ANWPATH_ADDONS : ANWPATH_DEFAULT).$sDir.$sTranslationFile;
		if (file_exists($sFileName))
		{
			self::loadTranslationsFromFile($sFileName, $sLang, $sPrefix, $sTranslationName);
		}
		
		//override translations if exists
		$sFileName = ANWPATH_OVERRIDE.$sDir.$sTranslationFile;
		if (file_exists($sFileName))
		{
			self::loadTranslationsFromFile($sFileName, $sLang, $sPrefix, $sTranslationName);
		}
	}
	
	private static function loadTranslationsFromFile($sFileName, $sLang, $sPrefix, $sTranslationName)
	{
		$lang = array(); //$lang is defined in the translation file
		
		AnwDebug::log("Loading translation file : ".$sFileName);
		(require_once($sFileName)) or die("Unable to load language file : ".$sFileName);
		foreach ($lang as $sTranslationId => $sTranslationValue)
		{
			if ($sLang == AnwCurrentSession::getLang())
			{
				//we store ANY translationid for session lang
				self::$translations[$sLang][$sPrefix][$sTranslationId] = $sTranslationValue;
			}
			else
			{
				//for other langs, we only store LOCAL translations
				if ( self::isLocalTranslation($sTranslationId) )
				{
					self::$translations[$sLang][$sPrefix][$sTranslationId] = $sTranslationValue;
				}
				//special case for default language: 
				//we exceptionnaly load NON-LOCAL translations if the translation isn't loaded for current session lang.
				//even if current session is still not already loaded but translation exists, we can't know it in advance so we store it in default lang just in case of.
				//it seems more efficient to do that (storing it in default lang), than loading the whole session lang file in advance, which we may never use.
				else if ( !isset(self::$translations[AnwCurrentSession::getLang()][$sPrefix][$sTranslationId]) )
				{
					self::$translations[$sLang][$sPrefix][$sTranslationId] = $sTranslationValue;
				}
			}
		}
	}
	
	private static function isLocalTranslation($sTranslationId)
	{
		return (substr($sTranslationId,0,strlen(self::TRANSLATIONS_PREFIX_LOCAL)) == self::TRANSLATIONS_PREFIX_LOCAL);
	}
	
	
	//------------------------------------
	
	/*
	static function parseTranslations($str){
		//finds patterns {(traduction_id)} or {(traducion_id|param1=value1,param2=value2)}
		return preg_replace_callback("/\{\((.*?)\)\}/", array('self','cbk_t'), $str);
	}
	
	//callback to scan translations patterns in templates
	static function cbk_t($matches){
		$id = $matches[1];
		$args=split('\|',$id);
		$id=$args[0];
		$params=isset($args[1]) ? $args[1] : false;
		
		$paramsarray = array();
		//insert parameters into the translation string
		if($params){
			//search for pairs 'param=>value'
			$params=explode(',',$params); //TODO:bug when ',' in translation value...
			foreach($params as $p){
				$x=explode('=>',$p);	//x[0]:param, x[1]:value
				$paramsarray[$x[0]] = $x[1];
			}
		}
		$translation = self::t($id, $paramsarray);
		return $translation;
	}*/
	
	static function isValidLang($sLang)
	{
		$nLen = strlen($sLang);
		return ($nLen >= self::MINLEN_LANG && $nLen <= self::MAXLEN_LANG && self::langExists($sLang) && strip_tags($sLang) == $sLang);
	}
	
	static function langExists($sLang)
	{
		return in_array($sLang, AnwComponent::globalCfgLangs());
	}
	
	//-----------------------------------
	// MISC
	//-----------------------------------
	
	static function langName($sLang)
	{
		return AnwComponent::g_("lang_".$sLang);
	}
	
	static function srcFlag($sLang)
	{
		return AnwUtils::pathImg("flags/".strtolower($sLang).".png");
	}
	
	static function imgFlag($sLang)
	{
		
		$sLangName = self::langName($sLang);
		$sSrc = self::srcFlag($sLang);
		$sImg = "<img src=\"$sSrc\" alt=\"$sLangName\" title=\"$sLangName\"/>";
		return $sImg;
	}
	
	static function timezoneName($nTimezone)
	{
		$sTimezone = 
		$sOffset = ($nTimezone>=0 ? '+'.sprintf("%02d", $nTimezone) : '-'.sprintf("%02d", abs($nTimezone)));
		return AnwComponent::g_("timezone_gmt_name", array('offset' => $sOffset));
	}
	
	static function g_err_need_write_file($sFileName)
	{
		return AnwComponent::g_("err_need_write_file", array('filename'=>$sFileName, 'processuser'=>AnwEnv::getProcessUser(), 'processgroup'=>AnwEnv::getProcessGroup()));
	}
}

?>