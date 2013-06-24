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
 * ContentClass definition.
 * @package Anwiki
 * @version $Id: class_contentclass_page.php 370 2011-08-28 09:52:23Z jejem $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwStructuredContentFieldsContainerPage extends AnwStructuredContentFieldsContainer
{
	const FIRSTWORDS_LENGTH=30;
	
	final function rebuildContentFromXml($sDefaultValue)
	{
		return AnwContentPage::rebuildContentFromXml($this, $sDefaultValue);
	}
}

interface AnwCachedOutputKeyDynamic
{
	function getCachedOutputKeyDynamic();
}

abstract class AnwContentClassPage extends AnwStructuredContentFieldsContainerPage implements AnwStructuredContentClass
{
	private $asIndexedFields = array();
	
	/*protected*/ function addContentField($oContentField)
	{
		parent::addContentField($oContentField);
		
		// indexes are no longer initialized here,
		// in order to allow contentclasses to set indexes for contentfields later.
		//
		// In example:
		// -- $oContentField = new AnwContentFieldPage_daterange( self::FIELD_DATE );
		// -- $this->addContentField($oContentField);
		// --
		// -- // set index for subcontentfields AFTER calling addContentField()
		// -- $oContentFieldBegin = $oContentField->getContentField(AnwIPage_daterange::FIELD_BEGIN);
		// -- $oContentFieldBegin->indexAs(self::IDX_DATEBEGIN);
		// -- $oContentFieldBegin->setTranslatable(false);
		//
		// Contentclasses cannot set index of subcontentfields before addContentField()
		// because subcontentfield can't be accessed before contentfield initialization,
		// which is done by addContentField()
	}
	
	/**
	 * Initialize the index for the contentfield and eventually it's subcontentfields.
	 */
	private static function initContentFieldIndexRecursive($oContentFieldsContainer, $oBaseContentFieldsContainer=null)
	{
		if ($oBaseContentFieldsContainer == null)
		{
			$oBaseContentFieldsContainer = $oContentFieldsContainer;
		}
		
		// we may have indexed subcontentfields!
		$aoContentFields = $oContentFieldsContainer->getContentFields();
		foreach ($aoContentFields as $oContentField)
		{
			// indexes are only referenced at contentclass level, 
			// but may be assigned to childs of composed contentfields
			if ($oContentField instanceof AnwStructuredContentField_composed)
			{
				//recursive call
				self::initContentFieldIndexRecursive($oContentField, $oBaseContentFieldsContainer);
			}
			else
			{
				if ($oContentField->isIndexed())
				{
					$sFieldName = $oContentField->getName();
					$sIndexName = $oContentField->getIndexName();
					if (in_array($sIndexName, $oBaseContentFieldsContainer->asIndexedFields))
					{
						throw new AnwUnexpectedException("Index name ".$sIndexName." already used, cannot be used for ".$sFieldName);
					}
					$oBaseContentFieldsContainer->asIndexedFields[] = $sIndexName;
				}
			}
		}
	}
	
	function getIndexes()
	{
		if ($this->asIndexedFields == null)
		{
			// only initialize indexes *after* all (sub)contentfields are loaded correctly 
			self::initContentFieldIndexRecursive($this, $this);
		}
		return $this->asIndexedFields;
	}
	
	//to override
	abstract function init();
	
	abstract function toHtml($oContent, $oPage);
	
	abstract function toFeedItem($oContent, $oPage);
	
	function onChange($oPage, $oPreviousContent=null){}
	
	function getCachedOutputExpiry(){ return AnwCache::EXPIRY_UNLIMITED; }
	
	final function __construct($sContentClassName, $bIsAddon)
	{
		$this->initComponent($sContentClassName, $bIsAddon);
	}
	
	public function getLabel()
	{
		return $this->t_("contentclass_".$this->getName()."_label");
	}
	
	protected static function debug($sMessage)
	{
		return AnwDebug::log("(AnwContentClass)".$sMessage);
	}
	
	
	
	//overridable
	function pubcall($sArg, $oContent, $oPage)
	{
		return "pubcall_undefined";
	}
	
	static function pubcallOperator($sArg, $sValue, $sLang, $asOperatorArgs=array())
	{
		switch($sArg)
		{
			case "firstwords":
				$sReturn = AnwXml::xmlGetUntranslatedTxt($sValue, false);
				
				$nFirstWordsLength = self::FIRSTWORDS_LENGTH;
				if (isset($asOperatorArgs[0])&&(intval($asOperatorArgs[0])>0))
				{
					$nFirstWordsLength = intval($asOperatorArgs[0]);
				}
				
				if (strlen($sReturn)>self::FIRSTWORDS_LENGTH)
				{
					$sReturn = AnwUtils::firstWords($sValue, $nFirstWordsLength).'...';
				}
				return $sReturn;
				break;
			case "date":
				$sReturn = Anwi18n::date($sValue, $sLang);
				return $sReturn;
				break;
			case "datetime":
				$sReturn = Anwi18n::dateTime($sValue, $sLang);
				return $sReturn;
				break;
			case "year":
				$sReturn = AnwUtils::date("Y", $sValue);
				return $sReturn;
				break;
			case "monthyear":
				$sReturn = AnwUtils::date("M Y", $sValue);
				return $sReturn;
				break;
			case "count":
				$sReturn = count($sValue);
				return $sReturn;
				break;
			case "len":
				$sReturn = strlen(trim($sValue));
				return $sReturn;
				break;
			case "skipuntr":
				$sReturn = AnwUtils::renderUntr($sValue);
				return $sReturn;
				break;
				
			// numeric maths
			case "add": case "sub": case "mul": case "div": case "pow":
				if (isset($asOperatorArgs[0]))
				{
					$nNumber = intval($asOperatorArgs[0]);
				}
				switch($sArg)
				{
					case "add":
						$sReturn = intval($sValue) + $nNumber; 
						break;
					case "sub":
						$sReturn = intval($sValue) - $nNumber; 
						break;
					case "mul":
						$sReturn = intval($sValue) * $nNumber; 
						break;
					case "div":
						$sReturn = intval($sValue) / $nNumber; 
						break;
					case "pow":
						$sReturn = intval($sValue) ^ $nNumber; 
						break;
				}
				return $sReturn;
				break;
			
			default:
				$sReturn = AnwPlugins::vhook('contentclass_pubcalloperator_default', $sValue, $sArg);
				return $sReturn;
				break;
		}
	}
	
	function getComponentName()
	{
		 return 'contentclass_'.$this->getName();
	}
		
	static function getComponentsRootDir()
	{
		return ANWDIR_CONTENTCLASSES;
	}
	static function getComponentsDirsBegin()
	{
		return 'contentclass_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_CONTENTCLASS;
	}
	
	static function discoverEnabledComponents()
	{
		return AnwComponent::globalCfgModulesContentClasses();
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'contentclass');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}
		
		$sFile = 'contentclass_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwContentClassPage%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oContentClass = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'contentclass', $oContentClass);
		
		return $oContentClass;
	}
	
	static function loadComponentInterface($sName)
	{
		$sFile = 'contentclass_'.$sName.'-interface.php';
		$sDir = self::getComponentDir($sName);
		try
		{
			list($sFileDefault, $bIsAddon) = AnwUtils::getFileDefault($sFile, $sDir);
			loadApp ($sFileDefault);
		}
		catch(AnwFileNotFoundException $e)
		{
			//no interface found for this class
		}
	}
	
	
}

?>
