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
 * AnwContentClassSettings definition.
 * @package Anwiki
 * @version $Id: class_contentclass_settings.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwStructuredContentFieldsContainerSettings extends AnwStructuredContentFieldsContainer
{
	//intermediate class for future evolutions
	
	final function rebuildContentFromXml($sDefaultValue)
	{
		return AnwContentSettings::rebuildContentFromXml($this, $sDefaultValue);
	}	
	
	function hasMandatorySettings()
	{
		$aoSettings = $this->getContentFields();
		foreach ($aoSettings as $oSetting)
		{
			if ($oSetting->isMandatory())
			{
				return true;
			}
			if ($oSetting instanceof AnwStructuredContentField_composed)
			{
				if ($oSetting->hasMandatorySettings())
				{
					return true;
				}
			}
		}
		return false;
	}
}

class AnwContentClassSettings extends AnwStructuredContentFieldsContainerSettings implements AnwStructuredContentClass
{
	private $oComponent;
	
	function __construct($oComponent)
	{
		if (!($oComponent instanceof AnwConfigurable)) throw new AnwUnexpectedException("Component should implement AnwConfigurable");
		$this->oComponent = $oComponent;
	}
	
	function getComponent()
	{
		return $this->oComponent;
	}
	
	//TODO : architecture problem, AnwContentClassSettings shouldnt be a component but it inherits it from AnwStructuredContentFieldsContainer
	//so we just define required abstract functions, but it will never be used
	function getComponentName()
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
		
	static function getComponentsRootDir()
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
	static function getComponentsDirsBegin()
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName;
	}
		
	static function getMyComponentType()
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
	
	static function discoverEnabledComponents()
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
	
	static function loadComponent($sName)
	{
		throw new AnwUnexpectedException("ContentClassSettings is not a component");
	}
}

?>