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
 * ContentClasses manager.
 * @package Anwiki
 * @version $Id: class_contentclasses.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwContentClasses
{
	private static $osContentClasses = array();
	private static $bAllContentClassesLoaded;
	
	static function getContentClasses()
	{
		if (!self::$bAllContentClassesLoaded)
		{
			self::loadContentClasses();
			self::$bAllContentClassesLoaded = true;
		}
		return self::$osContentClasses;
	}
	
	static function getContentClass($sContentClassName)
	{
		$sContentClassName = strtolower($sContentClassName);
		if (!self::isContentClassLoaded($sContentClassName))
		{
			self::loadContentClass($sContentClassName);
		}
		return self::$osContentClasses[$sContentClassName];
	}
	
	private static function loadContentClasses()
	{
		$asEnabledContentClasses = AnwComponent::globalCfgModulesContentClasses();
		foreach ($asEnabledContentClasses as $sContentClassName)
		{
			$sContentClassName = strtolower($sContentClassName);
			if (!self::isContentClassLoaded($sContentClassName))
			{
				self::loadContentClass($sContentClassName);
			}
		}
	}
	
	public static function loadContentClass($sContentClassName) //used by anwiki__autoload
	{
		if (self::isContentClassLoaded($sContentClassName))
		{
			throw new AnwUnexpectedException("ContentClass ".$sContentClassName." is already defined");
		}
		
		$oContentClass = AnwContentClassPage::loadComponent($sContentClassName);
		
		self::$osContentClasses[$sContentClassName] = $oContentClass;
		$oContentClass->init(); //AFTER adding to $osContentClasses !!
		
		AnwPlugins::hook("contentclass_init", $oContentClass);
		AnwPlugins::hook("contentclass_init_byname_".$oContentClass->getName(), $oContentClass);
	}
	
	public static function loadContentClassInterface($sContentClassName) //used by anwiki__autoload
	{
		if (class_exists('AnwIContentClassPageDefault_'.$sContentClassName, false)) //disable __autoload
		{
			throw new AnwUnexpectedException("Interface for ContentClass ".$sContentClassName." is already defined");
		}		
		AnwContentClassPage::loadComponentInterface($sContentClassName);
	}
	
	private static function isContentClassLoaded($sContentClassName)
	{
		return ( isset(self::$osContentClasses[$sContentClassName]) );
	}
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwContentClass)".$sMessage);
	}
}

?>