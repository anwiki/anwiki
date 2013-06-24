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
 * A PageGroup is a group of Pages having the same Content structure, but different languages.
 * @package Anwiki
 * @version $Id: class_pagegroup.php 289 2010-09-09 23:46:39Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwPageGroup
{
	private $nId;
	private $sContentClass;
	private $aoPages = array();
	
	private $bExists; //does the pagegroup exist
	private $bInfoLoaded = false; //did we load existing pagegroup's info
	
	function __construct($nPageGroupId=null, $sContentClass=null)
	{
		$this->nId = $nPageGroupId;
		$this->sContentClass = $sContentClass; //optionnal - will be dynamically loaded if not providen
	}
	
	function __sleep()
	{
		$asUnusedVars = array('bExists', 'bInfoLoaded');
		return AnwUtils::instanceVars(get_object_vars($this), $asUnusedVars);
	}
	
	function __wakeup()
	{
		$this->bExists = true;
		$this->bInfoLoaded = true;
	}
	
	
	//------------------------------------------------
	// SETTERS
	//------------------------------------------------
	
	/**
	 * @throws AnwUnexpectedException
	 */
	function addPage($oPage)
	{
		$this->loadInfo();
		
		$sPageLang = $oPage->getLang();
			
		if (isset($this->aoPages[$sPageLang]))
		{
			throw new AnwUnexpectedException("Can't add a page to a group which has already one for this lang");
		}
		
		$this->aoPages[$sPageLang] = $oPage;
	}
		
	
	//------------------------------------------------
	// ACCESSORS
	//------------------------------------------------
	
	/**
	 * @throws AnwPageGroupNotFoundException
	 */
	function getId()
	{
		if (!$this->nId) throw new AnwPageGroupNotFoundException();
		return $this->nId;
	}
	
	/**
	 * It's recommanded to use $oPageGroup->getContentClassName() instead of $oPageGroup->getContentClass()->getName().
	 * In some case, it will avoid to load the whole ContentClass mechanism and result in better performances.
	 */
	function getContentClassName()
	{
		if (!$this->sContentClass) $this->loadInfo();
		if (!$this->sContentClass) throw new AnwUnexpectedException("Content Class not set");
		return $this->sContentClass;
	}
	
	function getContentClass()
	{
		$oContentClass = AnwContentClasses::getContentClass($this->getContentClassName());
		return $oContentClass;
	}
	
	function exists()
	{
		$this->loadInfo();
		return $this->bExists;
	}
	
	/**
	 * Returns every pages of the PageGroup.
	 */
	function getPages( $oPageExcluded=null )
	{
		$this->loadInfo();
		$aoPages = $this->aoPages;
		
		if ($oPageExcluded)
		{
			$sPageExcludedLang = $oPageExcluded->getLang();
			if ($aoPages[$sPageExcludedLang]->getName() == $oPageExcluded->getName())
			{
				unset($aoPages[$sPageExcludedLang]);
			}
		}
		return $aoPages;
	}
	
	function getPreferedPage($sPreferedLang = false, $oPageExcluded=null)
	{
		$aoPages = $this->getPages($oPageExcluded);
		
		$asPreferedLangs = AnwComponent::globalCfgLangs();
		if ($sPreferedLang)
		{
			array_unshift($asPreferedLangs, $sPreferedLang);
		}
		foreach ($asPreferedLangs as $sLang)
		{
			if (isset($aoPages[$sLang]))
			{
				return $aoPages[$sLang];
			}
		}
		
		throw new AnwUnexpectedException("getPreferedPage: no page found in any lang");
	}
	
	function hasLang($sLang)
	{
		$aoPages = $this->getPages();
		return isset($aoPages[$sLang]);
	}
	
	function getAvailableLangs()
	{
		$asAvailableLangs = AnwComponent::globalCfgLangs();
		$asPages = $this->getPages();
		foreach ($asAvailableLangs as $i=>$sLang)
		{
			if (isset($asPages[$sLang]))
			{
				unset($asAvailableLangs[$i]);
			}
		}
		return $asAvailableLangs;
	}
	
	function getPageGroupsLinking()
	{
		return AnwStorage::getPageGroupsLinking($this);
	}
	
	//------------------------------------------------
	// STORAGE
	//------------------------------------------------
	
	static function rebuildPageGroup($sPageGroupId, $sContentClass, $aoPages)
	{
		$oPageGroup = new AnwPageGroup($sPageGroupId, $sContentClass);
		$oPageGroup->aoPages = $aoPages;
		$oPageGroup->bExists = true;
		$oPageGroup->bInfoLoaded = true;
		
		//set again pagegroup for related pages, so these pages won't load again group data
		foreach ($aoPages as $oPage)
		{
			$oPage->rebuildPage_setGroup($oPageGroup);
		}
		
		return $oPageGroup;
	}
	
	/**
	 * Refresh pagegroup informations.
	 */
	function refresh()
	{
		$this->bInfoLoaded = false;
		$this->loadInfo();
	}
	
	/**
	 * Load group's pages information from meta file.
	 */
	private function loadInfo()
	{
		if ($this->bInfoLoaded)
		{
			return;
		}
		
		try
		{
			//get PageGroup from storage system
			if (!$this->nId)
			{
				throw new AnwPageGroupNotFoundException();
			}
			AnwDebug::log("(AnwPageGroup)Loading PageGroup info...");
			
			$oPageGroup = AnwStorage::getPageGroup($this->nId);
			
			//PageGroup exists, update it's attributes
			$this->bExists = true;
			$this->sContentClass = $oPageGroup->getContentClassName();
			$this->aoPages = $oPageGroup->getPages();
		}
		catch (AnwPageGroupNotFoundException $e)
		{
			//PageGroup doesn't exist
			$this->bExists = false;
			$this->aoPages = array();
		}
		
		$this->bInfoLoaded = true;
	}
	
	function create($oContentClass)
	{
		if ($this->exists())
		{
			throw new AnwUnexpectedException("An existing pagegroup can't be created");
		}
		$this->sContentClass = $oContentClass->getName();
		$this->nId = AnwStorage::createPageGroup($this); //this will set the group id
		$this->bExists = true;
	}
	
	function update($oContentClass=null)
	{
		if (!$this->exists())
		{
			throw new AnwUnexpectedException("A non-existing pagegroup can't be saved");
		}
		AnwStorage::updatePageGroup($this);
	}
	
	function deletePages($nTime=null)
	{
		if (!$nTime) $nTime = time();
		$aoPages = $this->getPages();
		
		AnwStorage::transactionStart();
		try
		{
			foreach ($aoPages as $oPage)
			{
				$oPage->delete($nTime);
			}
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
}
?>