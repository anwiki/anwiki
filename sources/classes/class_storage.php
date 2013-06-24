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
 * Anwiki Storage manager.
 * @package Anwiki
 * @version $Id: class_storage.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwStorage
{
	private static $oDriver;
	
	//------------------------------------------------
	// LAST CHANGES
	//------------------------------------------------
	
	static function getLastChanges($nLimit=false, $nStart, $asDisplayLangs, $asDisplayClasses, $amChangeTypes, $oPage=null, $oPageGroup=null)
	{
		self::debug("Getting last ".$nLimit." changes".($oPage ? " for page ".$oPage->getName() : "").($oPageGroup ? " for pagegroup ".$oPageGroup->getId() : "")."...");
		return self::getDriver()->getLastChanges($nLimit, $nStart, $asDisplayLangs, $asDisplayClasses, $amChangeTypes, $oPage, $oPageGroup);
	}
	
	static function createChange($oChange)
	{
		self::debug("Saving a new change...");
		return self::getDriver()->createChange($oChange);
	}
	
	static function getUntranslatedPages($asLangs, $asContentClasses)
	{
		self::debug("Getting untranslated pages...");
		return self::getDriver()->getUntranslatedPages($asLangs, $asContentClasses);
	}
	
	
	//------------------------------------------------
	// PAGES AND ARCHIVES
	//------------------------------------------------
	
	static function isAvailablePageName($sPageName)
	{
		self::debug("Searching if pagename is available : ".$sPageName);
		return self::getDriver()->isAvailablePageName($sPageName);
	}
	
	static function createPage($oPage, $oChange, $fCallbackSetId)
	{
		self::debug("Creating a new page : ".$oPage->getName());
		self::getDriver()->createPage($oPage, $oChange, $fCallbackSetId);
		self::debug("PageId : ".$oPage->getId());
		
		AnwCache::clearCacheFromPageGroup($oPage->getPageGroup()); //error id not set...
	}
	
	static function restoreArchive($oPageArchived, $oChange, $fCallbackSetNonArchive)
	{
		self::debug("Restoring an archive : ".$oPageArchived->getName());
		self::getDriver()->restoreArchive($oPageArchived, $oChange, $fCallbackSetNonArchive);
		
		AnwCache::clearCacheFromPageGroup($oPageArchived->getPageGroup());
	}
	
	static function updatePage($oPage, $oChange)
	{
		self::debug("Updating a page : ".$oPage->getName());
		self::getDriver()->updatePage($oPage, $oChange);
		
		AnwCache::clearCacheFromPageGroup($oPage->getPageGroup());
	}
	
	static function renamePage($oPage, $oChange, $sOldName)
	{
		self::debug("Renaming a page : ".$oPage->getName());
		self::getDriver()->renamePage($oPage, $oChange);
		
		//clear cache from old name (even if it seems that old pagename is still referenced in pagegroup)
		AnwCache::clearCacheFromPageByName($sOldName);
		AnwCache::clearCacheFromPageGroup($oPage->getPageGroup());
	}
	
	static function changeLangPage($oPage, $oChange)
	{
		self::debug("Changing lang for a page : ".$oPage->getLang());
		self::getDriver()->changeLangPage($oPage, $oChange);
		
		AnwCache::clearCacheFromPageGroup($oPage->getPageGroup());
	}
	
	/*static function savePageDraft($oDraft)
	{
		self::debug("Saving a draft for page : ".$oDraft->getPage()->getName());
		self::getDriver()->savePageDraft($oDraft);
	}
	
	static function deletePageDraft($oDraft)
	{
		self::debug("Deleting a draft");
		self::getDriver()->deletePageDraft($oDraft);
	}
	
	static function getPageDrafts($oPage)
	{
		self::debug("Getting drafts for page : ".$oPage->getId());
		return self::getDriver()->getPageDrafts($oPage);
	}*/
	
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	static function getPage($nPageId, $bSkipLoadingTranslationsContent=false, $bSkipLoadingContent=false)
	{
		self::debug("getPage : ".$nPageId);	
		if ($nPageId <= 0) throw new AnwUnexpectedException("getPage on pageid <=0");
		
		$bCachingEnabled = AnwComponent::globalCfgCachePagesEnabled();
		$sCacheKey = $bSkipLoadingTranslationsContent.'-'.$bSkipLoadingContent;
		try
		{
			if (!$bCachingEnabled) throw new AnwCacheNotFoundException();
			$oPage = AnwCache::getCachedPageById($nPageId, $sCacheKey);
		}
		catch(AnwCacheNotFoundException $e)
		{
			$oPage = self::getDriver()->getPage($nPageId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
			if ($bCachingEnabled) AnwCache::putCachedPage($oPage, $sCacheKey);
		}
		
		return $oPage;
	}
	
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	static function getPageByName($sPageName, $bSkipLoadingTranslationsContent=false, $bSkipLoadingContent=false, $sLoadedLang=null)
	{
		self::debug("getPageByName : ".$sPageName);
		
		
		$bCachingEnabled = AnwComponent::globalCfgCachePagesEnabled();
		$sCacheKey = $bSkipLoadingTranslationsContent.'-'.$bSkipLoadingContent.'-'.$sLoadedLang;
		try
		{
			if (!$bCachingEnabled) throw new AnwCacheNotFoundException();
			$oPage = AnwCache::getCachedPageByName($sPageName, $sCacheKey);
		}
		catch(AnwCacheNotFoundException $e)
		{
			$oPage = self::getDriver()->getPageByName($sPageName, $bSkipLoadingTranslationsContent, $bSkipLoadingContent, $sLoadedLang);
			if ($bCachingEnabled) AnwCache::putCachedPage($oPage, $sCacheKey);
		}
		return $oPage;
	}
	
	static function getPageArchive($nPageId, $nChangeId, $bSkipLoadingTranslationsContent=false, $bSkipLoadingContent=false)
	{
		self::debug("Getting an archived page : ".$nPageId." (".$nChangeId.")");
		$oPage = self::getDriver()->getPageArchive($nPageId, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
		return $oPage;
	}
	
	static function getPageArchiveByName($sPageName, $nChangeId, $bSkipLoadingTranslationsContent=false, $bSkipLoadingContent=false)
	{
		self::debug("Getting an archived page : ".$sPageName." (".$nChangeId.")");
		$oPage = self::getDriver()->getPageArchiveByName($sPageName, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
		return $oPage;
	}
	
	static function getLastPageRevision($nPageId)
	{
		self::debug("Getting last revision for page : ".$nPageId);
		
		//TODO
		$bSkipLoadingTranslationsContent = false;
		$bSkipLoadingContent = false;
		
		$bCachingEnabled = AnwComponent::globalCfgCachePagesEnabled();
		$sCacheKey = $bSkipLoadingTranslationsContent.'-'.$bSkipLoadingContent;
		try
		{
			//try to read it from cache in case of current page exists
			if (!$bCachingEnabled) throw new AnwCacheNotFoundException();
			$oLastPageRevision = AnwCache::getCachedPageById($nPageId, $sCacheKey);
		}
		catch(AnwCacheNotFoundException $e)
		{
			$oLastPageRevision = self::getDriver()->getLastPageRevision($nPageId);
			//dont put it in cache as it may be an archive...
		}
		return $oLastPageRevision;
	}
	
	static function getPageArchivePrevious($oPage, $nMaxChangeId, $bSkipLoadingTranslationsContent=false, $bSkipLoadingContent=false)
	{
		self::debug("Getting the previous archive for page : ".$oPage->getId()." (".$nMaxChangeId.")");
		$oPage = self::getDriver()->getPageArchivePrevious($oPage, $nMaxChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
		return $oPage;
	}
	
	static function getPageGroupPreviousArchives($nReferenceChangeId, $oPageGroup, $bSkipLoadingContent)
	{
		self::debug("Getting the previous archives ".($oPageGroup ? "for PageGroup: ".$oPageGroup->getId() : "for Page: ".$oPage->getId())." (reference Change: ".$nReferenceChangeId.")");
		$aoPages = self::getDriver()->getPageGroupPreviousArchives($nReferenceChangeId, $oPageGroup, $bSkipLoadingContent);
		return $aoPages;
	}
	
	static function deletePage($oPage, $oChange)
	{
		self::debug("Deleting a page : ".$oPage->getName());
		self::getDriver()->deletePage($oPage, $oChange);
		
		AnwCache::clearCacheFromPageGroup($oPage->getPageGroup());
	}
	
	static function getPageGroupsLinking($oPageGroupLinked)
	{
		self::debug("Getting linking pages...");
		return self::getDriver()->getPageGroupsLinking($oPageGroupLinked);
	}
	
	static function fetchPagesByClass($asPatterns=array(), $oContentClass, $asLangs=array(), $nLimit=0, $sSortUser, $sOrder, $asFilters=array())
	{
		if ($oContentClass === null) throw new AnwUnexpectedException("null contentclass for fetchPagesByClass");
		
		self::debug("Fetching pages by class... (".implode($asPatterns)." / $nLimit / $sSortUser / $sOrder)");
		return self::getDriver()->fetchPagesByClass($asPatterns, $oContentClass, $asLangs, $nLimit, $sSortUser, $sOrder, $asFilters);
	}
	
	static function fetchPages($asPatterns, $aoContentClasses, $asLangs=array(), $nLimit, $sSortUser, $sOrder)
	{
		self::debug("Fetching pages... (".implode($asPatterns)." / $nLimit / $sSortUser / $sOrder)");
		return self::getDriver()->fetchPages($asPatterns, $aoContentClasses, $asLangs, $nLimit, $sSortUser, $sOrder);
	}
	
	
	//------------------------------------------------
	// PAGE GROUPS
	//------------------------------------------------
	
	static function createPageGroup($oPageGroup)
	{
		self::debug("Creating a pagegroup...");
		$nPageGroupId = self::getDriver()->createPageGroup($oPageGroup);
		self::debug("New PageGroup id : ".$nPageGroupId);
		return $nPageGroupId;
	}
	
	static function updatePageGroup($oPageGroup)
	{
		self::debug("Updating a pagegroup : #".$oPageGroup->getId());
		self::getDriver()->updatePageGroup($oPageGroup);
		
		AnwCache::clearCacheFromPageGroup($oPageGroup);
	}
	
	/**
	 * @throws AnwPageGroupNotFoundException
	 */
	static function getPageGroup($nPageGroupId)
	{
		$oPageGroup = self::getDriver()->getPageGroup($nPageGroupId);
		return $oPageGroup;
	}
	
	static function getPageGroups($bSkipLoadingContent=false, $asLangs=null, $asContentClasses=null)
	{
		self::debug("Getting all pagegroups...");
		return self::getDriver()->getPageGroups($bSkipLoadingContent, $asLangs, $asContentClasses);
	}

	
	//------------------------------------------------
	// LOCKS
	//------------------------------------------------
	static function lockPage($nLockType, $oPage, $oSession)
	{
		self::debug("lockPage... (".$nLockType.")");
		return self::getDriver()->lockPage($nLockType, $oPage, $oSession);
	}
	
	static function unlockPage($oPage, $oSession)
	{
		self::debug("unlockPage...");
		self::getDriver()->unlockPage($oPage, $oSession);
	}
	
	
	//------------------------------------------------
	// TRANSACTIONS
	//------------------------------------------------
	
	static function transactionStart()
	{
		self::getDriver()->transactionStart();
	}
	static function transactionCommit()
	{
		self::getDriver()->transactionCommit();
	}
	static function transactionRollback()
	{
		self::getDriver()->transactionRollback();
	}
	
	
	//------------------------------------------------
	// DRIVERS MANAGEMENT
	//------------------------------------------------
	
	/**
	 * @throws AnwUnexpectedException
	 */
	static function loadDriver()
	{
		AnwDebug::startbench("Storage driver init");
		self::$oDriver = AnwStorageDriver::loadComponent(AnwComponent::globalCfgDriverStorage());
		AnwDebug::stopbench("Storage driver init");
	}
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function getDriver()
	{
		if (!self::$oDriver)
		{
			self::loadDriver();
			if (!self::$oDriver) throw new AnwUnexpectedException("No storage driver loaded");
		}
		return self::$oDriver;
	}
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwStorage)".$sMessage);
	}
}

?>