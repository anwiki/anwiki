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
 * Storage driver: MySQL.
 * @package Anwiki
 * @version $Id: storagedriver_mysql.php 332 2010-09-19 22:41:02Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwStorageDriverDefault_mysql extends AnwStorageDriver implements AnwConfigurable, AnwInitializable
{
	const CFG_MYSQL = "mysql";
	
	const CHANGE_TYPE_PAGE_CREATION=1;
	const CHANGE_TYPE_PAGE_CREATION_TRANSLATION=2;
	const CHANGE_TYPE_PAGE_EDITION=3;
	const CHANGE_TYPE_PAGE_EDITION_DEPLOY=4;
	const CHANGE_TYPE_PAGE_TRANSLATION=5;
	const CHANGE_TYPE_PAGE_DELETION=6;
	const CHANGE_TYPE_PAGE_RENAME=7;
	const CHANGE_TYPE_PAGE_CHANGELANG=8;
	const CHANGE_TYPE_PAGE_UPDATELINKS=9;
	const CHANGE_TYPE_PAGE_REVERT=10;
	
	private $oDb; //database
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$oContentField = new AnwContentFieldSettings_mysqlconnexion(self::CFG_MYSQL);
		$oContentField->setMandatory(true);
		$aoSettings[] = $oContentField;
		return $aoSettings;
	}
	
	//------------------------------------------------
	// LAST CHANGES
	//------------------------------------------------
	
	function createChange($oChange)
	{
		static $bPurged; //only run one purge by hit
		
		//special case for page creation: Page is set but id doesn't have the PageId yet - it will be set later
		$bIsPageCreation = ( !$oChange->getPage() || !$oChange->getPageId() ? true : false );
		
		$nChangeId = $this->doCreateChange($oChange, $bIsPageCreation);
		
		if (!$bPurged)
		{
			//remove old changes
			$nMinKeepTime = time() - self::globalCfgHistoryExpiration();
			$nMinRevisions = self::globalCfgHistoryMinRevisions();
			
			/*$sQuery = "DELETE a.* FROM `#PFX#pagearchive` a INNER JOIN `#PFX#change` c ON c.ChangePage=a.PageId, ".
					"(" . //find old changes which are not related to active pages
								"SELECT ccc.ChangeId FROM `#PFX#change` ccc " .
								"WHERE ccc.ChangeTime < ".$this->db()->inttosql($nMinKeepTime)." " . //older than expiry delay
								"AND NOT EXISTS (SELECT ppp.PageId FROM `#PFX#page` ppp WHERE ppp.PageChange=ccc.ChangeId) " . //not related to active pages
					") AS t, " .
					"( " . //keep at least n changes having revision content for each page
								"SELECT cc.ChangePage FROM `#PFX#change` cc " .
								"INNER JOIN `#PFX#pagearchive` aa ON cc.ChangePage=aa.PageId " .  //we want to count only revisions, not active page
								"WHERE cc.ChangeTime >= ".$this->db()->inttosql($nMinKeepTime)." " . //future record number after deletion
								"GROUP BY cc.ChangePage " .
								"HAVING COUNT(cc.ChangeId) >= " .$this->db()->inttosql($nMinRevisions).
					") AS tt " .
					"WHERE c.ChangeId=t.ChangeId " .
					"AND c.ChangePage=tt.ChangePage";*/
			
			
			//this is probably the most complex request from Anwiki :-)
			//any suggestion to optimize it is welcome.
			$sQuery = "SELECT c.ChangeId FROM `#PFX#change` c " .
					"WHERE c.ChangeId IN ".
					"(" . //find old changes which are not related to active pages
								"SELECT ccc.ChangeId FROM `#PFX#change` ccc " .
								"WHERE ccc.ChangeTime < ".$this->db()->inttosql($nMinKeepTime)." " . //older than expiry delay
								"AND NOT EXISTS (SELECT ppp.PageId FROM `#PFX#page` ppp WHERE ppp.PageChange=ccc.ChangeId) " . //not related to active pages
					") " .
					"AND c.ChangeId <= " .
					"( " . //keep at least n changes having revision content for each page
					
							//we are browsing, time after time, each changes sharing ChangePage coming from main request
							//we already know that these pages have at least one related change which is old.
							//we need to know untill which biggest ChangeId for this page can we delete - with letting at least n revisions for this page 
								"SELECT MAX(cc.ChangeId) FROM `#PFX#change` cc " .
								"WHERE cc.ChangePage = c.ChangePage " . //we browse PageId found in previous subrequest (pages having old changes)
								"AND EXISTS (" .
									//check that count(revisions with ChangeId greater than cc.ChangeId) >= ...
									"SELECT ChangeId FROM `#PFX#change` cccc " .
									"INNER JOIN `#PFX#pagearchive` aaaa ON cccc.ChangePage=aaaa.PageId " .  //we want to count available revisions
									"WHERE cccc.ChangePage=cc.ChangePage " .
									"AND cccc.ChangeId > cc.ChangeId " .
									"GROUP BY cccc.ChangePage " .
									"HAVING COUNT( cccc.ChangeId ) >= " .$this->db()->inttosql($nMinRevisions)." " .
								") ".
					")";
			
			$q = $this->db()->query($sQuery);
			
			$amChangeIds = array();
			while ($oData = $this->db()->fto($q))
			{
				$amChangeIds[] = $this->db()->inttosql($oData->ChangeId);
			}
			$this->db()->free($q);
			
			$sChangeIds = implode(', ', $amChangeIds);
			unset($amChangeIds);
			
			$this->transactionStart();
			try
			{
				//TODO: delete doesn't work directly from the previous request because of MySQL strange error: "You can't specify target table ... for update in FROM clause"
				if ($sChangeIds)
				{
					$sQuery = "DELETE FROM `#PFX#pagearchive` WHERE PageChange IN(".$sChangeIds.")";			
					$this->db()->query($sQuery);
					
					$sQuery = "DELETE FROM `#PFX#change` WHERE ChangeId IN(".$sChangeIds.")";			
					$this->db()->query($sQuery);
				}
				
				self::debug("Purged ".$this->db()->affected_rows()." old changes prior to ".date("d/m/Y H:i:s", $nMinKeepTime));
				
				
				
				
				//remove old archives : archives which are not related to an existing change
				/*$sQuery = "DELETE a.* FROM `#PFX#pagearchive` a WHERE NOT EXISTS ( SELECT c.ChangeId FROM `#PFX#change` c WHERE c.ChangeId=a.PageChange ) AND NOT EXISTS ( SELECT p.PageId FROM `#PFX#page` p WHERE p.PageChange=a.PageChange )";
				$this->db()->query($sQuery);
				$this->db()->affected_rows();
				self::debug("Purged ".$this->db()->affected_rows()." old orphean pagearchives");*/
				
				
				
				// don't purge pagegroup during page creation, or the pagegroup being created will be purged!
				if (!$bIsPageCreation)
				{
					//remove old pagegroups : pagegroups which are not related to any page or pagearchive
					$sQuery = "SELECT g.PageGroupId FROM `#PFX#pagegroup` g " .
							"WHERE NOT EXISTS ( SELECT a.PageId FROM `#PFX#pagearchive` a WHERE a.PageGroup=g.PageGroupId ) AND NOT EXISTS ( SELECT p.PageId FROM `#PFX#page` p WHERE p.PageGroup=g.PageGroupId )";
					$q = $this->db()->query($sQuery);
				
					$amPageGroupIds = array();
					while ($oData = $this->db()->fto($q))
					{
						$amPageGroupIds[] = $this->db()->inttosql($oData->PageGroupId);
					}
					$this->db()->free($q);
					$sPageGroupIds = implode(', ', $amPageGroupIds);
					unset($amPageGroupIds);
					
					if ($sPageGroupIds)
					{
						$sQuery = "DELETE FROM `#PFX#pagegrouplink` " .
								"WHERE PageGroupLinkFrom IN(".$sPageGroupIds.") " .
								"OR PageGroupLinkTo IN(".$sPageGroupIds.") ";
						$this->db()->query($sQuery);
						
						$sQuery = "DELETE FROM `#PFX#pagegroup` WHERE PageGroupId IN(".$sPageGroupIds.") ";				
						$this->db()->query($sQuery);
						$this->db()->affected_rows();
						self::debug("Purged ".$this->db()->affected_rows()." old orphean pagegroups");
					}
					else
					{
						self::debug("No old orphean pagegroup");
					}
				}
				
				$this->transactionCommit();
			}
			catch(AnwException $e)
			{
				$this->transactionRollback();
				throw $e;
			}			
		}
		return $nChangeId;
	}
	
	private function doCreateChange($oChange, $bIsPageCreation)
	{
		$nChangeType = self::changeTypeToSql( $oChange->getType() );
		$nChangeUser = self::userToSql($oChange->getUser());
		$sChangeUserName = $oChange->getUser()->getDisplayName();
		$nChangeTime = $oChange->getTime();
		$sChangeComment = $oChange->getComment();
		$sChangeInfo = $oChange->getRawInfo();
		
		//special case for page creation: we don't know the pageId yet, it will be set later
		$nChangePage = ( !$bIsPageCreation ? $oChange->getPage()->getId() : 0 );
		$sChangePageName = $oChange->getPageName();
		$sChangePageLang = $oChange->getPageLang();
		$sChangeIp = $oChange->getIp();
		
		$asSqlInsert = array(
			"ChangeType" 	=> $this->db()->inttosql($nChangeType),
			"ChangeUser" 	=> $this->db()->inttosql($nChangeUser, true),
			"ChangeUserName"	=>	$this->db()->strtosql($sChangeUserName),
			"ChangeTime" 	=> $this->db()->inttosql($nChangeTime),
			"ChangeComment" => $this->db()->strtosql($sChangeComment, true),
			"ChangeInfo" => $this->db()->strtosql($sChangeInfo, true),
			"ChangePage" 	=> $this->db()->inttosql($nChangePage),
			"ChangePageName" 	=> $this->db()->strtosql($sChangePageName),
			"ChangePageLang" 	=> $this->db()->strtosql($sChangePageLang),
			"ChangeIp"	=>	$this->db()->strtosql($sChangeIp)
		);
		$this->db()->do_insert($asSqlInsert, "change");
		$nChangeId = $this->db()->insert_id();
		self::debug("ChangeId: ".$nChangeId);
		return $nChangeId;
	}
	
	function getLastChanges($nLimit=false, $nStart, $asLangs, $asContentClasses, $amChangeTypes, $oPage=null, $oPageGroup=null)
	{
		//optimized : don't read PageContent, returns empty content instead 
		$sQuery = "SELECT DISTINCT ".
				self::_changeFieldsSelect() . ", " .
				self::_pageFieldsSelect('NULL as PageContent', 'a.').
				", g.PageGroupContentClass " .
				", pa.PageId as IsActivePage" .
				", pe.PageId as ActivePageExists " .
				"FROM `#PFX#change` " .
				
				//following jointures are 100% sure
				"INNER JOIN `#PFX#pagearchive` ae ON (ae.PageId=ChangePage) " . //to have a 100% sure jointure for pagegroup
				"INNER JOIN `#PFX#pagegroup` g ON (ae.PageGroup=g.PageGroupId) " . //for filtering contentclasses
				
				//following jointure may be null - depends if it's an archive or a page and if it has active page
				"LEFT JOIN `#PFX#pagearchive` a ON (a.PageChange=ChangeId) " . //may be null depending on change type (ie: deletion)
				"LEFT JOIN `#PFX#page` pa ON (pa.PageChange=a.PageChange) " . //if line found in archive table, to know if the page is active or archived
				"LEFT JOIN `#PFX#page` pe ON (pe.PageId=ChangePage) " . //to know if activeExists() for better performances (avoids trying to load active pages when they don't exist)
				"WHERE 1=1 ";
		
		if ($asLangs)
		{
			foreach ($asLangs as $i => $sLang)
			{
				$asLangs[$i] = $this->db()->strtosql($sLang);
			}
			$sLangs = implode(',', $asLangs);
			$sQuery .= "AND (a.PageLang IN (".$sLangs.") OR ChangePageLang IN (".$sLangs.")) ";
		}
		
		if ($asContentClasses)
		{
			foreach ($asContentClasses as $i => $sContentClass)
			{
				$asContentClasses[$i] = $this->db()->strtosql($sContentClass);
			}
			$sContentClasses = implode(',', $asContentClasses);
			$sQuery .= "AND g.PageGroupContentClass IN (".$sContentClasses.") ";
		}
		
		if ($amChangeTypes)
		{
			foreach ($amChangeTypes as $i=>$mChangeType)
			{
				$amChangeTypes[$i] = self::changeTypeToSql($mChangeType);
			}
			$sChangeTypes = implode(',', $amChangeTypes);
			$sQuery .= " AND ChangeType IN (".$sChangeTypes.")";
		}
		
		if ($oPage)
		{
			$sQuery .= " AND ChangePage=".$this->db()->inttosql($oPage->getId());
		}
		else if ($oPageGroup)
		{
			$sQuery .= " AND g.PageGroupId=".$this->db()->inttosql($oPageGroup->getId());
		}
		$sQuery .= " ORDER BY ChangeId DESC";
		
		if ($nStart < 0) $nStart = 0;
		if ($nLimit)
		{
			$sQuery .= " LIMIT ".$this->db()->inttosql($nStart).','.$this->db()->inttosql($nLimit);
		}
		//end filter options
		
		$q = $this->db()->query($sQuery);
		
		$aoChanges = array();
		
		while ($oData = $this->db()->fto($q))
		{
			if ($oData->PageId)
			{
				if ($oData->IsActivePage)
				{
					//page is still alive
					$oChangePage = self::getPageFromData($oData);
					$bActiveExists = true;
				}
				else
				{
					//page is archived
					$bActiveExists = ($oData->ActivePageExists ? true : false);
					$oChangePage = self::getPageFromData($oData, true, $bActiveExists);
				}
			}
			else
			{
				//no page revision directly related to this change (ie: deletions)
				$oChangePage = null;
				$bActiveExists = ($oData->ActivePageExists ? true : false);
				
				/*
				//quick hack so that actions (such as lastchanges) can do ACLs tests on this object, even if it's incomplete
				$nPageId = $nChangePage; //warning TODO
				$sPageName = $sChangePageName;
				$sPageLang = $sChangePageLang;
				$nPageTime = $nChangeTime;
				$nPageChangeId = $nChangeId;
				$sPageContent = null;
				$bPageTranslated = null;
				$nPageTranslatedPercent = null;
				
				$bExists = false; //experimental
				$bArchive = true;
				$bActiveExists = ($oData->ActivePageExists ? true : false);
				
				//warning, we are constructing a page with a valid ID but which doesn't exist
				$oChangePage = AnwPage::rebuildPage($nPageId, $sPageName, $oPageGroup, $sPageLang, $nPageTime, $nPageChangeId, $sPageContent, $bPageTranslated, $nPageTranslatedPercent, $bArchive, $bActiveExists, $bExists);
				*/
			}
			
			//create Change object
			$oChange = self::getChangeFromData($oData, $oChangePage, $bActiveExists);
			
			//add it to changes array
			$aoChanges[$oChange->getChangeId()] = $oChange;
		}
		$this->db()->free($q);
		
		return $aoChanges;
	}
	
	function getUntranslatedPages($asLangs, $asContentClasses)
	{
		//optimized : don't read PageContent, returns empty content instead 
		$sQuery = "SELECT " .
				//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, NULL AS PageContent, PageTranslated, PageTranslatedPercent, PageGroupContentClass ".
				self::_pageFieldsSelect('NULL AS PageContent'). " " .
				", PageGroupContentClass ".
				"FROM `#PFX#page` " .
				"INNER JOIN `#PFX#pagegroup` ON PageGroup=PageGroupId " .
				"WHERE PageTranslated = ".$this->db()->inttosql(0)." ";
				
		if ($asLangs)
		{
			foreach ($asLangs as $i => $sLang)
			{
				$asLangs[$i] = $this->db()->strtosql($sLang);
			}
			$sLangs = implode(',', $asLangs);
			$sQuery .= "AND PageLang IN (".$sLangs.") ";
		}
		
		if ($asContentClasses)
		{
			foreach ($asContentClasses as $i => $sContentClass)
			{
				$asContentClasses[$i] = $this->db()->strtosql($sContentClass);
			}
			$sContentClasses = implode(',', $asContentClasses);
			$sQuery .= "AND PageGroupContentClass IN (".$sContentClasses.") ";
		}
		
		$sQuery .= "ORDER BY PageChange DESC";
		
		$aoPages = array();
		
		$q = $this->db()->query($sQuery);
		while ($oData = $this->db()->fto($q))
		{
			$oPage = self::getPageFromData($oData);
			$aoPages[] = $oPage;
		}
		$this->db()->free($q);
		return $aoPages;
	}
	
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function changeTypeToSql($nChangeType)
	{
		switch ($nChangeType)
		{
			case AnwChange::TYPE_PAGE_CREATION:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_CREATION;
				break;
			
			case AnwChange::TYPE_PAGE_CREATION_TRANSLATION:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_CREATION_TRANSLATION;
				break;
				
			case AnwChange::TYPE_PAGE_EDITION:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_EDITION;
				break;
				
			case AnwChange::TYPE_PAGE_EDITION_DEPLOY:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_EDITION_DEPLOY;
				break;
				
			case AnwChange::TYPE_PAGE_TRANSLATION:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_TRANSLATION;
				break;
			
			case AnwChange::TYPE_PAGE_DELETION:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_DELETION;
				break;
				
			case AnwChange::TYPE_PAGE_RENAME:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_RENAME;
				break;
			
			case AnwChange::TYPE_PAGE_CHANGELANG:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_CHANGELANG;
				break;
			
			case AnwChange::TYPE_PAGE_UPDATELINKS:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_UPDATELINKS;
				break;
			
			case AnwChange::TYPE_PAGE_REVERT:
				$nSqlChangeType = self::CHANGE_TYPE_PAGE_REVERT;
				break;
				
			default:
				throw new AnwUnexpectedException("Unknown ChangeType : ".$nChangeType);
				break;
		}
		return $nSqlChangeType;
	}
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function changeTypeFromSql($nSqlChangeType)
	{
		switch ($nSqlChangeType)
		{
			case self::CHANGE_TYPE_PAGE_CREATION:
				$nChangeType = AnwChange::TYPE_PAGE_CREATION;
				break;
			
			case self::CHANGE_TYPE_PAGE_CREATION_TRANSLATION:
				$nChangeType = AnwChange::TYPE_PAGE_CREATION_TRANSLATION;
				break;
				
			case self::CHANGE_TYPE_PAGE_EDITION:
				$nChangeType = AnwChange::TYPE_PAGE_EDITION;
				break;
				
			case self::CHANGE_TYPE_PAGE_EDITION_DEPLOY:
				$nChangeType = AnwChange::TYPE_PAGE_EDITION_DEPLOY;
				break;
				
			case self::CHANGE_TYPE_PAGE_TRANSLATION:
				$nChangeType = AnwChange::TYPE_PAGE_TRANSLATION;
				break;
			
			case self::CHANGE_TYPE_PAGE_DELETION:
				$nChangeType = AnwChange::TYPE_PAGE_DELETION;
				break;
				
			case self::CHANGE_TYPE_PAGE_RENAME:
				$nChangeType = AnwChange::TYPE_PAGE_RENAME;
				break;
			
			case self::CHANGE_TYPE_PAGE_CHANGELANG:
				$nChangeType = AnwChange::TYPE_PAGE_CHANGELANG;
				break;
			
			case self::CHANGE_TYPE_PAGE_UPDATELINKS:
				$nChangeType = AnwChange::TYPE_PAGE_UPDATELINKS;
				break;
			
			case self::CHANGE_TYPE_PAGE_REVERT:
				$nChangeType = AnwChange::TYPE_PAGE_REVERT;
				break;
				
			default:
				throw new AnwUnexpectedException("Unknown SqlChangeType : ".$nSqlChangeType);
				break;
		}
		return $nChangeType;
	}
	
	
	//------------------------------------------------
	// PAGES AND ARCHIVES
	//------------------------------------------------
	
	function isAvailablePageName($sPageName)
	{
		$sQuery = "SELECT PageId FROM `#PFX#page` WHERE PageName=".$this->db()->strtosql($sPageName)." LIMIT 1";
		$q = $this->db()->query($sQuery);
		
		if ($this->db()->num_rows($q) == 1)
		{
			$bReturn = false;
		}
		else
		{
			$bReturn = true;
		}
		$this->db()->free($q);
		
		return $bReturn;
	}
	
	function createPage($oPage, $oChange, $fCallbackSetId)
	{
		//save first the page group, so we can get it's ID later
		//$oPage->getPageGroup()->save($nContentClass);
		
		$this->transactionStart();
		try
		{		
			//create change - with a zero PageId, which will be updated later
			$nChangeId = $oChange->create();
			
			//now create the page
			$sPageName = $oPage->getName();
			$nPageGroup = $oPage->getPageGroup()->getId();
			$sPageLang = $oPage->getLang();
			$nPageTime = $oPage->getTime();
			$sPageContent = $oPage->getContent()->toXmlString();
			$bPageTranslated = $oPage->isTranslated();
			$nPageTranslatedPercent = $oPage->getTranslatedPercent();
			$nPageCreation = $oPage->getCreationTime();
			
			$asSqlInsert = array(
				"PageName" 		=> $this->db()->strtosql($sPageName),
				"PageGroup" 	=> $this->db()->inttosql($nPageGroup),
				"PageLang" 		=> $this->db()->strtosql($sPageLang),
				"PageTime" 		=> $this->db()->inttosql($nPageTime),
				"PageChange"	=> $this->db()->inttosql($nChangeId),
				"PageContent" 	=> $this->db()->strtosql($sPageContent),
				"PageTranslated"=> $this->db()->booltosql($bPageTranslated),
				"PageTranslatedPercent"	=>	$this->db()->inttosql($nPageTranslatedPercent),
				"PageCreationTime"	=> $this->db()->inttosql($nPageCreation)
			);
			$this->db()->do_insert($asSqlInsert, "page");
			$nPageId = $this->db()->insert_id();
			
			//special case - set page id for the change
			$asSqlUpdate = array(
				"ChangePage" 	=> $this->db()->inttosql($nPageId)
			);
			$this->db()->do_update($asSqlUpdate, "change", "WHERE ChangeId = ".$this->db()->inttosql($nChangeId));
			
			//set page id
			$oPage->$fCallbackSetId($nPageId);
			
			//copy to archives table
			$this->archivePage($nPageId);
			
			//update indexes
			$this->updatePageIndexes($oPage);
			
			//update outgoing links
			$this->updatePageOutgoingLinks($oPage);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	function restoreArchive($oPageArchived, $oChange, $fCallbackSetNonArchive)
	{
		$this->transactionStart();
		try
		{
			//create change
			$nChangeId = $oChange->create();
			
			//restore the archived page, preserving page id
			//we suppose here that there is no active page having this id, only archived pages have this id (after deletion) and we want to restore it.
			$nPageId = $oPageArchived->getId();
			$sPageName = $oPageArchived->getName();
			$nPageGroup = $oPageArchived->getPageGroup()->getId();
			$sPageLang = $oPageArchived->getLang();
			$nPageTime = $oPageArchived->getTime();
			$sPageContent = $oPageArchived->getContent()->toXmlString();
			$bPageTranslated = $oPageArchived->isTranslated();
			$nPageTranslatedPercent = $oPageArchived->getTranslatedPercent();
			$nPageCreation = $oPageArchived->getCreationTime();
			
			$asSqlInsert = array(
				"PageId" 		=> $this->db()->inttosql($nPageId),
				"PageName" 		=> $this->db()->strtosql($sPageName),
				"PageGroup" 	=> $this->db()->inttosql($nPageGroup),
				"PageLang" 		=> $this->db()->strtosql($sPageLang),
				"PageTime" 		=> $this->db()->inttosql($nPageTime),
				"PageChange" 	=> $this->db()->inttosql($nChangeId),
				"PageContent" 	=> $this->db()->strtosql($sPageContent),
				"PageTranslated"=> $this->db()->booltosql($bPageTranslated),
				"PageTranslatedPercent"	=>	$this->db()->inttosql($nPageTranslatedPercent),
				"PageCreationTime"	=> $this->db()->inttosql($nPageCreation)
			);
			$this->db()->do_insert($asSqlInsert, "page");
			
			//copy to archives table
			$this->archivePage($nPageId);
			
			//set non-archive status
			$oPageArchived->$fCallbackSetNonArchive();
			
			//update indexes
			$this->updatePageIndexes($oPageArchived);
			
			//update outgoing links
			$this->updatePageOutgoingLinks($oPageArchived);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	function updatePage($oPage, $oChange)
	{
		$this->transactionStart();
		try
		{
			//create change
			$nChangeId = $oChange->create();
			
			//now update the page
			$nPageId = $oPage->getId();
			//$sPageName = $oPage->getName();
			$nPageGroup = $oPage->getPageGroup()->getId();
			$sPageLang = $oPage->getLang();
			$nPageTime = $oPage->getTime();
			$sPageContent = $oPage->getContent()->toXmlString();
			$bPageTranslated = $oPage->isTranslated();
			$nPageTranslatedPercent = $oPage->getTranslatedPercent();
			
			$asSqlUpdate = array(
				"PageGroup" 	=> $this->db()->inttosql($nPageGroup),
				"PageLang" 		=> $this->db()->strtosql($sPageLang),
				"PageTime" 		=> $this->db()->inttosql($nPageTime),
				"PageChange" 	=> $this->db()->inttosql($nChangeId),
				"PageContent" 	=> $this->db()->strtosql($sPageContent),
				"PageTranslated"=> $this->db()->booltosql($bPageTranslated),
				"PageTranslatedPercent"	=>	$this->db()->inttosql($nPageTranslatedPercent)
			);
			$this->db()->do_update($asSqlUpdate, "page", "WHERE PageId = ".$this->db()->inttosql($nPageId));
			
			//copy to archives table
			$this->archivePage($nPageId);
			
			//update indexes
			$this->updatePageIndexes($oPage);
			
			//update outgoing links
			$this->updatePageOutgoingLinks($oPage);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	private function updatePageIndexes($oPage)
	{
		$this->transactionStart();
		try
		{
			$nPageId = $oPage->getId();
		
			//delete old indexes
			$sQuery = "DELETE FROM `#PFX#pageindex` WHERE IndexPage=".$this->db()->inttosql($nPageId);
			$this->db()->query($sQuery);
			
			//insert new indexes
			$asIndexes = $oPage->getPageGroup()->getContentClass()->getIndexes();
			foreach ($asIndexes as $sIndexName)
			{
				$asIndexedValues = $oPage->getContent()->getContentFieldIndexedValues($sIndexName);
				foreach ($asIndexedValues as $sIndexedValue)
				{
					$sIndexedValue = substr($sIndexedValue, 0, AnwUtils::MAXLEN_INDEXVALUE);
					$asSqlInsert = array(
						"IndexField"	=> $this->db()->strtosql($sIndexName),
						"IndexPage" 	=> $this->db()->inttosql($nPageId),
						"IndexValue"	=> $this->db()->strtosql($sIndexedValue)
					);
					$this->db()->do_insert($asSqlInsert, "pageindex");
				}
			}
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	
	private function updatePageOutgoingLinks($oPage)
	{
		$this->transactionStart();
		try
		{			
			$nPageGroupId = $oPage->getPageGroup()->getId();
			
			//delete old outgoing links
			$sQuery = "DELETE FROM `#PFX#pagegrouplink` WHERE PageGroupLinkFrom=".$this->db()->inttosql($nPageGroupId);
			$this->db()->query($sQuery);
			
			//find new outgoing links referring to the wiki
			$aoOutgoingLinks = $oPage->getOutgoingLinks();
			$anLinkedPageGroups = array();
			foreach ($aoOutgoingLinks as $sOutgoingLink => $oPageTarget)
			{
				$nLinkedPageGroupId = $oPageTarget->getPageGroup()->getId();
				$anLinkedPageGroups[] = $this->db()->inttosql($nLinkedPageGroupId);
			}
			$sSqlLinkedPageGroupIds = implode(',', $anLinkedPageGroups);
			
			//insert new outgoing links
			if ($sSqlLinkedPageGroupIds != "")
			{
				$sQuery = "INSERT INTO `#PFX#pagegrouplink` (PageGroupLinkFrom, PageGroupLinkTo) " .
						"SELECT DISTINCT ".$this->db()->inttosql($nPageGroupId)." as PageGroupFrom, PageGroupId FROM `#PFX#pagegroup` WHERE PageGroupId IN (".$sSqlLinkedPageGroupIds.")";
				$this->db()->query($sQuery);
			}
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	function renamePage($oPage, $oChange)
	{
		$this->transactionStart();
		try
		{			
			//create change
			$nChangeId = $oChange->create();
			
			//rename the page
			$nPageId = $oPage->getId();
			$nPageTime = $oPage->getTime();
			$sPageName = $oPage->getName();
			
			//update current page
			$asSqlUpdate = array(
				"PageTime" 		=> $this->db()->inttosql($nPageTime),
				"PageChange"	=> $this->db()->inttosql($nChangeId),
				"PageName" 		=> $this->db()->strtosql($sPageName)
			);
			$this->db()->do_update($asSqlUpdate, "page", "WHERE PageId = ".$this->db()->inttosql($nPageId));
			
			//copy to archives table
			$this->archivePage($nPageId);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	function changeLangPage($oPage, $oChange)
	{
		$this->transactionStart();
		try
		{			
			//create change
			$nChangeId = $oChange->create();
			
			//change lang of the page
			$nPageId = $oPage->getId();
			$sPageLang = $oPage->getLang();
			$nPageTime = $oPage->getTime();
			
			//update current page
			$asSqlUpdate = array(
				"PageLang" 		=> $this->db()->strtosql($sPageLang),
				"PageTime" 		=> $this->db()->inttosql($nPageTime),
				"PageChange" 	=> $this->db()->inttosql($nChangeId)
			);
			$this->db()->do_update($asSqlUpdate, "page", "WHERE PageId = ".$this->db()->inttosql($nPageId));
			
			//copy to archives table
			$this->archivePage($nPageId);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	/*
	function savePageDraft($oDraft)
	{
		$nDraftPage = $oDraft->getPage()->getId();
		$nDraftTime = $oDraft->getTime();
		$nDraftUser = self::userToSql($oDraft->getUser());
		$nDraftUserName = $oDraft->getUser()->getDisplayName();
		$sDraftContent = $oDraft->getContent();
		$sDraftComment = $oDraft->getComment();
		
		$asSqlInsert = array(
			"DraftPage" 	=> $this->db()->inttosql($nDraftPage),
			"DraftTime" 	=> $this->db()->inttosql($nDraftTime),
			"DraftUser" 	=> $this->db()->inttosql($nDraftUser, true),
			"DraftUserName"	=> $this->db()->strtosql($nDraftUserName),
			"DraftContent" 	=> $this->db()->strtosql($sDraftContent),
			"DraftComment" 	=> $this->db()->strtosql($sDraftComment, true)
		);
		$this->db()->do_insert($asSqlInsert, "pagedraft");
	}
	
	
	function deletePageDraft($oDraft)
	{
		$nDraftPage = $oDraft->getPage()->getId();
		$nDraftTime = $oDraft->getTime();
		
		$sQuery = "DELETE FROM `#PFX#pagedraft` WHERE DraftPage=".$this->db()->inttosql($nDraftPage)." AND DraftTime=".$this->db()->inttosql($nDraftTime);
		$this->db()->query($sQuery);
	}
	
	function getPageDrafts($oPage)
	{
		$nPageId = $oPage->getId();
		$sQuery = "SELECT DraftTime, DraftUser, DraftUserName, DraftContent, DraftComment " .
				"FROM `#PFX#pagedraft` " .
				"WHERE DraftPage=".$this->db()->inttosql($nPageId);
		$mRes = $this->db()->query($sQuery);
		
		$aoDrafts = array();
		while ($oData = $this->db()->fto($mRes))
		{
			$nDraftTime = $oData->DraftTime;
			$oDraftUser = self::sqlToUser($oData->DraftUser, $oData->DraftUserName);
			$sDraftContent = $oData->DraftContent;
			$sDraftComment = $oData->DraftComment;
			$aoDrafts[$nDraftTime] = new AnwPageDraft($oPage, $nDraftTime, $oDraftUser, $sDraftContent, $sDraftComment);
		}
		return $aoDrafts;
	}*/
	
	protected static function _pageFields()
	{
		return array('PageId', 'PageName', 'PageGroup', 'PageLang', 'PageTime', 'PageChange', 'PageTranslated', 'PageTranslatedPercent', 'PageCreationTime');
	}
	
	protected static function _pageFieldsSelect($sSelectContent=false, $sTable="", $sAliasPrefix="")
	{
		$sFieldsString = "";
		
		$asFields = self::_pageFields();
		if (!$sSelectContent)
		{
			$asFields[] = 'PageContent';
		}
		
		if ($sAliasPrefix)
		{
			foreach ($asFields as $i => $sField)
			{
				$asFields[$i] = $sTable.$sField.' as '.$sAliasPrefix.$sField;
			}
		}
		else
		{
			if ($sTable)
			{
				foreach ($asFields as $i => $sField)
				{
					$asFields[$i] = $sTable.$sField;
				}
			}
		}
		
		if ($sSelectContent)
		{
			$asFields[] = $sSelectContent;
		}
		
		$sFieldsString = implode(', ', $asFields)." ";
		return $sFieldsString;
	}
	
	protected static function _changeFields()
	{
		return array('ChangeId', 'ChangeType', 'ChangeUser', 'ChangeUserName', 'ChangePage', 'ChangeTime', 'ChangeComment', 'ChangeInfo', 'ChangePageName', 'ChangePageLang', 'ChangeIp');
	}
	
	protected static function _changeFieldsSelect($sTable="", $sAliasPrefix="")
	{
		$sFieldsString = "";
		
		$asFields = self::_changeFields();
		
		if ($sAliasPrefix)
		{
			foreach ($asFields as $i => $sField)
			{
				$asFields[$i] = $sTable.$sField.' as '.$sAliasPrefix.$sField;
			}
		}
		else
		{
			if ($sTable)
			{
				foreach ($asFields as $i => $sField)
				{
					$asFields[$i] = $sTable.$sField;
				}
			}
		}
		
		$sFieldsString = implode(', ', $asFields)." ";
		return $sFieldsString;
	}
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getPageByName($sPageName, $bSkipLoadingTranslationsContent, $bSkipLoadingContent, $sLoadedLang)
	{
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		else if ($bSkipLoadingTranslationsContent)
		{
			if ($sLoadedLang)
			{
				$sSelectContent = 'CASE WHEN PageLang='.$this->db()->strtosql($sLoadedLang).' THEN PageContent ELSE NULL END AS PageContent';
			}
			else
			{
				$sSelectContent = 'CASE WHEN PageName='.$this->db()->strtosql($sPageName).' THEN PageContent ELSE NULL END AS PageContent';
			}
		}
		$sQuery = "SELECT PageGroupId, PageGroupContentClass, " .
				self::_pageFieldsSelect($sSelectContent).
				//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, ".$sSelectContent.", PageTranslated, PageTranslatedPercent ".
				"FROM `#PFX#pagegroup` " .
				"INNER JOIN `#PFX#page` ON PageGroup=PageGroupId ".
				"WHERE PageGroupId = " .
				"( SELECT PageGroup FROM `#PFX#page` WHERE PageName=".$this->db()->strtosql($sPageName).") " .
				"ORDER BY PageLang ASC";
		
		$oaPageGroups = self::getPageGroupsFromQuery($sQuery);
		if (count($oaPageGroups) < 1)
		{
			throw new AnwPageNotFoundException();
		}
		$oPageGroup = array_pop($oaPageGroups);
		$aoPages = $oPageGroup->getPages();
		
		foreach ($aoPages as $oPage)
		{
			if ($oPage->getName() == $sPageName)
			{
				return $oPage;
			}
		}
		throw new AnwPageNotFoundException();
	}
	
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getPage($nPageId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent)
	{
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		else if ($bSkipLoadingTranslationsContent)
		{
			$sSelectContent = 'CASE WHEN PageId='.$this->db()->inttosql($nPageId).' THEN PageContent ELSE NULL END AS PageContent';
		}
		
		$sQuery = "SELECT PageGroupId, PageGroupContentClass, " .
				self::_pageFieldsSelect($sSelectContent).
				"FROM `#PFX#pagegroup` " .
				"INNER JOIN `#PFX#page` ON PageGroup=PageGroupId ".
				"WHERE PageGroupId = " .
				"( SELECT PageGroup FROM `#PFX#page` WHERE PageId=".$this->db()->inttosql($nPageId).")";
		
		$oaPageGroups = self::getPageGroupsFromQuery($sQuery);
		if (count($oaPageGroups) < 1)
		{
			throw new AnwPageNotFoundException();
		}
		$oPageGroup = array_pop($oaPageGroups);
		$aoPages = $oPageGroup->getPages();
		
		foreach ($aoPages as $oPage)
		{
			if ($oPage->getId() == $nPageId)
			{
				return $oPage;
			}
		}
		throw new AnwPageNotFoundException();
	}
	
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getPageArchiveByName($sPageName, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent)
	{
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		else if ($bSkipLoadingTranslationsContent)
		{
			$sSelectContent = 'CASE WHEN PageName='.$this->db()->strtosql($sPageName).' THEN PageContent ELSE NULL END AS PageContent';
		}
		$sQuery = "SELECT " .
				self::_pageFieldsSelect($sSelectContent).
				", PageGroupContentClass " .
				"FROM `#PFX#pagearchive` a " .
				"INNER JOIN `#PFX#pagegroup` ON a.PageGroup=PageGroupId " .
				"WHERE a.PageName = ".$this->db()->strtosql($sPageName)." " .
				"AND a.PageChange = ".$this->db()->inttosql($nChangeId)." " .
				"AND NOT EXISTS(SELECT p.PageId FROM `#PFX#page` p WHERE p.PageChange=a.PageChange) " .
				"LIMIT 1"; //just to be sure we don't return an active page...
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwPageNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		$oPage = self::getPageFromData($oData, true);
		return $oPage;
	}
	
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getPageArchive($nPageId, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent)
	{
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		else if ($bSkipLoadingTranslationsContent)
		{
			$sSelectContent = 'CASE WHEN PageId='.$this->db()->inttosql($nPageId).' THEN PageContent ELSE NULL END AS PageContent';
		}
		$sQuery = "SELECT " .
				self::_pageFieldsSelect($sSelectContent).
				", PageGroupContentClass " .
				"FROM `#PFX#pagearchive` a " .
				"INNER JOIN `#PFX#pagegroup` ON a.PageGroup=PageGroupId " .
				"WHERE a.PageId = ".$this->db()->inttosql($nPageId)." " .
				"AND a.PageChange = ".$this->db()->inttosql($nChangeId) . " ".
				"AND NOT EXISTS(SELECT p.PageId FROM `#PFX#page` p WHERE p.PageChange=a.PageChange) " .
				"LIMIT 1"; //just to be sure we don't return an active page...
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwPageNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		$oPage = self::getPageFromData($oData, true);
		return $oPage;
	}
	
	/**
	 * @throws AnwPageNotFoundException
	 */
//	function getChangeForPageArchive($oPageArchive)
//	{
//		$sQuery = "SELECT " .self::_changeFieldsSelect() .
//				"FROM `#PFX#change` " .
//				"WHERE ChangeId = " . $this->db()->inttosql($oPageArchive->getChangeId()) ." ".
//				"LIMIT 1";
//		
//		$q = $this->db()->query($sQuery);
//		if ($this->db()->num_rows($q) != 1)
//		{
//			throw new AnwPageNotFoundException();
//		}
//		
//		$oData = $this->db()->fto($q);
//		$this->db()->free($q);
//		
//		$oChange = self::getChangeFromData($oData, $oPageArchive, -1);
//		return $oChange;
//	}
	
	function getLastPageRevision($nPageId)
	{
		$sQuery = "SELECT " .
				self::_pageFieldsSelect('NULL AS PageContent', 'a.').
				", PageGroupContentClass " .
				", p.PageId as IsActivePage " .
				"FROM `#PFX#pagearchive` a " .
				"INNER JOIN `#PFX#pagegroup` ON a.PageGroup=PageGroupId " .
				"LEFT JOIN `#PFX#page` p ON p.PageChange=a.PageChange " . //just to know if it's an archive or not
				"WHERE a.PageId=".$this->db()->inttosql($nPageId)." ".
				"ORDER BY PageChange DESC " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwPageNotFoundException();
		}
		
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		if ($oData->IsActivePage)
		{
			//page is still active
			$oPage = self::getPageFromData($oData);
		}
		else
		{
			//page is archived
			$oPage = self::getPageFromData($oData, true, false); //if the last revision is an archive, it means that this page doesnt have any active revision
		}
		return $oPage;
	}
	
	function getPageArchivePrevious($oPage, $nMaxChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent)
	{
		$nPageId = $oPage->getId();
		
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		else if ($bSkipLoadingTranslationsContent)
		{
			$sSelectContent = 'CASE WHEN PageId='.$this->db()->inttosql($nPageId).' THEN PageContent ELSE NULL END AS PageContent';
		}
		
		$sQuery = "SELECT " .
				self::_pageFieldsSelect($sSelectContent).
				", PageGroupContentClass " .
				"FROM `#PFX#pagearchive` a " .
				"INNER JOIN `#PFX#pagegroup` ON PageGroup=PageGroupId " .
				"WHERE PageId = ".$this->db()->inttosql($nPageId)." " .
				"AND PageChange < ".$this->db()->inttosql($nMaxChangeId)." " .
				"AND NOT EXISTS(SELECT p.PageId FROM `#PFX#page` p WHERE p.PageChange=a.PageChange) ". //just to be sure we don't return an active page...
				"ORDER BY PageChange DESC " .
				"LIMIT 1";
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwArchiveNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		$oPage = self::getPageFromData($oData, true);
		return $oPage;
	}
	
	function getPageGroupPreviousArchives($nReferenceChangeId, $oPageGroup, $bSkipLoadingContent)
	{
		$sSelectContent = 'a.PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		$sQuery = "SELECT " .
				self::_pageFieldsSelect($sSelectContent, 'a.').", ".
				self::_changeFieldsSelect()." ".
				", PageGroupContentClass " .
				", pe.PageId as ActivePageExists " .
				"FROM `#PFX#pagegroup` " .
				"INNER JOIN `#PFX#pagearchive` a ON PageGroup=PageGroupId " .
				"INNER JOIN `#PFX#change` ON a.PageChange=ChangeId " .
				"LEFT JOIN `#PFX#page` pe ON (pe.PageId=ChangePage) " . //to know if activeExists() for better performances (avoids trying to load active pages when they don't exist)
				"WHERE ChangeId = (" .
					"SELECT c.ChangeId " .
					"FROM `#PFX#change` c " .
					"WHERE a.PageId=c.ChangePage " .
					"AND ChangeId < ".$this->db()->inttosql($nReferenceChangeId)." " . //select the revision just before the given change
					"ORDER BY ChangeId DESC LIMIT 1" .
				") " .
				"AND NOT EXISTS(SELECT p.PageId FROM `#PFX#page` p WHERE p.PageChange=a.PageChange) "; //we don't want to return active page
		if ($oPageGroup)
		{
			$nPageGroupId = $oPageGroup->getId();
			$sQuery .= "AND PageGroupId = ".$this->db()->inttosql($nPageGroupId)." ";
		}
		$sQuery .= "ORDER BY PageLang ASC";
		
		$q = $this->db()->query($sQuery);
		//even if num_rows is 0, it's ok
		
		$aoPages = array();
		while (	$oData = $this->db()->fto($q) )
		{
			//we shouldn't have duplicated rows but just in case of...
			if (isset($aoPages[$oData->PageLang]))
			{
				throw new AnwUnexpectedException("getPageArchivesForRevert - duplicate lang found");
			}
			$oPage = self::getPageFromData($oData, true);
			// we build the change but don't use it now. It's automatically wired to the page object.
			$bActiveExists = ($oData->ActivePageExists ? true : false);
			$oChange = self::getChangeFromData($oData, $oPage, $bActiveExists);
			$aoPages[$oPage->getLang()] = $oPage;			
		}
		$this->db()->free($q);
		return $aoPages;
	}
	
	private function archivePage($nPageId)
	{
		$this->transactionStart();
		try
		{			
			//firstly try to delete existing row
			/*$sQuery = "DELETE FROM `#PFX#pagearchive` WHERE Pageid = ".$this->db()->strtosql($nPageId)." AND PageChange = (" .
					"SELECT PageChange FROM `#PFX#page` WHERE PageId=".$this->db()->strtosql($nPageId)." )";
			$this->db()->query($sQuery);*/
			
			$sQuery = "INSERT INTO `#PFX#pagearchive` (" .
					//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, PageContent, PageTranslated, PageTranslatedPercent" .
					self::_pageFieldsSelect().
					") " .
					"SELECT " .
					//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, PageContent, PageTranslated, PageTranslatedPercent " .
					self::_pageFieldsSelect().
					"FROM `#PFX#page` " .
					"WHERE PageId=".$this->db()->strtosql($nPageId);
			$this->db()->query($sQuery);
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	private static function getPageFromData($oDataPage, $bArchive=false, $bActiveExists=-1)
	{
		$sContentClass = $oDataPage->PageGroupContentClass;
		$oPageGroup = new AnwPageGroup($oDataPage->PageGroup, $sContentClass);
		
		$nPageId = $oDataPage->PageId;
		$sPageName = $oDataPage->PageName;
		$sPageLang = $oDataPage->PageLang;
		$nPageTime = $oDataPage->PageTime;
		$nPageChangeId = $oDataPage->PageChange;
		$sPageContent = $oDataPage->PageContent; //may be null if performances+memory boost
		$bPageTranslated = (bool)$oDataPage->PageTranslated;
		$nPageTranslatedPercent = $oDataPage->PageTranslatedPercent;
		$nPageCreationTime = $oDataPage->PageCreationTime;
		
		if (!$bArchive)
		{
			$bActiveExists = true;
		}
		
		$oPage = AnwPage::rebuildPage($nPageId, $sPageName, $oPageGroup, $sPageLang, $nPageTime, $nPageChangeId, $sPageContent, $bPageTranslated, $nPageTranslatedPercent, $nPageCreationTime, $bArchive, $bActiveExists);
		return $oPage;
	}
	
	private static function getChangeFromData($oData, $oChangePage, $bActiveExists=-1)
	{
		$nChangeId = $oData->ChangeId;
		$nChangeType = self::changeTypeFromSql($oData->ChangeType);
		$oChangeUser = self::sqlToUser($oData->ChangeUser, $oData->ChangeUserName);
		$nChangePage = $oData->ChangePage;
		$nChangeTime = $oData->ChangeTime;
		$sChangeComment = $oData->ChangeComment;
		$sChangeInfo = $oData->ChangeInfo;
		$sChangePageName = $oData->ChangePageName;
		$sChangePageLang = $oData->ChangePageLang;
		$sIp = $oData->ChangeIp;
		
		$oChange = AnwChange::rebuildChange($nChangeType, $oChangePage, $bActiveExists, $nChangePage, $nChangeTime, $sChangeComment, $sChangeInfo, $sChangePageName, $sChangePageLang, $nChangeId, $oChangeUser, $sIp);
		return $oChange;
	}
	
	function deletePage($oPage, $oChange)
	{
		$this->transactionStart();
		try
		{
			//create change
			$oChange->create(); //we won't use id of this change
			
			$nPageId = $oPage->getId();
			
			$asQueries = array();
			$asQueries[] = "DELETE FROM `#PFX#pageindex` WHERE IndexPage=".$this->db()->inttosql($nPageId);
			//$asQueries[] = "DELETE FROM `#PFX#pagelink` WHERE PageLinkFrom=".$this->db()->inttosql($nPageId)." OR PageLinkTo=".$this->db()->inttosql($nPageId);
			$asQueries[] = "DELETE FROM `#PFX#lock` WHERE LockPage=".$this->db()->inttosql($nPageId);
			$asQueries[] = "DELETE FROM `#PFX#page` WHERE PageId=".$this->db()->inttosql($nPageId)." ";
			//$asQueries[] = "DELETE FROM `#PFX#pagedraft` WHERE DraftPage=".$this->db()->inttosql($nPageId);
			
			foreach ($asQueries as $sQuery)
			{
				$this->db()->query($sQuery);
			}
			
			//no need to archive anything, we already had last version in archives table
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
	}
	
	function getPageGroupsLinking($oPageGroupLinked)
	{
		$nPageGroupLinkedId = $oPageGroupLinked->getId();
		$sQuery = "SELECT PageGroupId, PageGroupContentClass, " .
				self::_pageFieldsSelect().
				"FROM `#PFX#pagegroup` " .
				"INNER JOIN `#PFX#page` ON PageGroup=PageGroupId ".
				"WHERE PageGroupId IN " .
				"( SELECT PageGroupLinkFrom FROM `#PFX#pagegrouplink` WHERE PageGroupLinkTo=".$this->db()->inttosql($nPageGroupLinkedId).") " .
				"ORDER BY PageLang ASC";
		
		$oaPageGroups = self::getPageGroupsFromQuery($sQuery);
		return $oaPageGroups;
	}
	
	function fetchPagesByClass($asPatterns=array(), $oContentClass, $asLangs=array(), $nLimit=0, $sSortUser, $sOrder, $asFilters)
	{
		//local sort <> no need to join pageindex table
		$asLocalSorts = array(AnwUtils::SORT_BY_NAME, AnwUtils::SORT_BY_TIME);
		if (in_array($sSortUser, $asLocalSorts))
		{
			$bIsLocalSort = true;
			if ($sSortUser == AnwUtils::SORT_BY_TIME)
			{
				$sSortSql = 'p.PageChange';
			}
			else
			{
				$sSortSql = 'p.PageName';
			}
		}
		else //index sort
		{
			$bIsLocalSort = false;
			$sSortSql = 'i.IndexValue';
			$sSortFieldName = $sSortUser;
		}
		$sOrderSql = ( $sOrder == AnwUtils::SORTORDER_DESC ? "DESC" : "ASC" );
		
		//index filters
		$nbFilter = 0;
		$sFilterSqlJoins = '';
		$sFilterSqlWheres = '';
		foreach ($asFilters as $asFilterInfos)
		{
			$nbFilter++;
			$sFilterField = $asFilterInfos['FIELD'];
			$sFilterOperatorSql = self::filterOperatorToSql($asFilterInfos['OPERATOR']);
			$sFilterValue = $asFilterInfos['VALUE'];
			$sFilterValue = str_replace('*', '%', $sFilterValue);
			$sTmpJoinName = 'filter'.$nbFilter;
			$sFilterSqlJoins .= "INNER JOIN `#PFX#pageindex` ".$sTmpJoinName." ON ".$sTmpJoinName.".IndexPage=p.PageId ";
			$sFilterSqlWheres .= "AND (".$sTmpJoinName.".IndexField = ".$this->db()->strtosql($sFilterField)
								." AND ".$sTmpJoinName.".IndexValue ".$sFilterOperatorSql." ".$this->db()->strtosql($sFilterValue).") ";
		}
		
		$sSqlFrom = "";
		$sSqlWhere = "";
		if (!$bIsLocalSort)
		{
			$sSqlFrom .= "INNER JOIN `#PFX#pageindex` i ON i.IndexPage=p.PageId ";
			$sSqlWhere .= "AND i.IndexField = ".$this->db()->strtosql($sSortFieldName)." ";
		}
		if ($nbFilter > 0)
		{
			$sSqlFrom .= $sFilterSqlJoins;
			$sSqlWhere .= $sFilterSqlWheres;
		}
		
		$sSqlWhere .= "AND PageGroupContentClass = ".$this->db()->strtosql($oContentClass->getName())." " .
						"ORDER BY ".$sSortSql." ".$sOrderSql;
		
		$aoPages = $this->_fetchPages($asPatterns, $asLangs, $nLimit, $sSqlWhere, $sSqlFrom);
		return $aoPages;
	}
	
	private static function filterOperatorToSql($sOperator)
	{
		if ($sOperator == AnwUtils::FILTER_OP_LIKE)
		{
			return 'LIKE';
		}
		else if ($sOperator == AnwUtils::FILTER_OP_EQUALS)
		{
			return '=';
		}
		else if ($sOperator == AnwUtils::FILTER_OP_LT)
		{
			return '<';
		}
		else if ($sOperator == AnwUtils::FILTER_OP_GT)
		{
			return '>';
		}
		else if ($sOperator == AnwUtils::FILTER_OP_LE)
		{
			return '<=';
		}
		else if ($sOperator == AnwUtils::FILTER_OP_GE)
		{
			return '>=';
		}
		else
		{
			throw new AnwUnexpectedException("Unknown filter operator: ".$sOperator);
		}
	}
	
	function fetchPages($asPatterns=array(), $aoContentClasses=array(), $asLangs=array(), $nLimit=0, $sLocalSort, $sOrder)
	{		
		//local sort <> no need to join pageindex table
		$asLocalSorts = array(AnwUtils::SORT_BY_NAME=>'p.PageName', AnwUtils::SORT_BY_TIME=>'p.PageTime');
		if (isset($asLocalSorts[$sLocalSort]))
		{
			$sSortSql = $asLocalSorts[$sLocalSort];
		}
		else //index sort
		{
			throw new AnwUnexpectedException("Unknown local sort for fetchPages");
		}
		$sOrderSql = ( $sOrder == AnwUtils::SORTORDER_DESC ? "DESC" : "ASC" );
		
		$sSqlWhere = "";
		
		//contentclasses
		if (count($aoContentClasses)>0)
		{
			$asContentClassesSecured = array();
			foreach ($aoContentClasses as $oContentClass)
			{
				$asContentClassesSecured[] = $this->db()->strtosql($oContentClass->getName());
			}
			$sAllContentClasses = implode(',',$asContentClassesSecured);
			unset($asContentClassesSecured);
			
			$sSqlWhere .= "AND PageGroupContentClass IN (".$sAllContentClasses.") ";
		}
		
		$sSqlWhere .= "ORDER BY ".$sSortSql." ".$sOrderSql;
		
		$aoPages = $this->_fetchPages($asPatterns, $asLangs, $nLimit, $sSqlWhere);
		return $aoPages;
	}
	
	private function _fetchPages($asPatterns, $asLangs, $nLimit, $sSqlWhere, $sSqlFrom="")
	{		
		//langs
		$sSqlLang = "";
		if (count($asLangs)>0)
		{
			$asLangsSecured = array();
			foreach ($asLangs as $sLang)
			{
				$asLangsSecured[] = $this->db()->strtosql($sLang);
			}
			$sAllLangs = implode(',',$asLangsSecured);
			unset($asLangsSecured);
			$sSqlLang = "AND p.PageLang IN (".$sAllLangs.") ";
		}
		
		//patterns
		$sPatternSql = "";
		if (count($asPatterns)>0)
		{
			//important: we filter on "pagegroup" level, not on page level.
			//that way, "match=en/news/*" will match any translation of english pages such as "en/news/blah""
			$sPatternSql = "AND EXISTS ( SELECT PageId FROM `#PFX#page` pp WHERE pp.PageGroup=PageGroupId AND (1=0";
			foreach ($asPatterns as $sPattern)
			{
				$sPattern = str_replace('*', '%', $sPattern);
				$sPatternSql .= " OR pp.PageName LIKE ".$this->db()->strtosql($sPattern);
			}
			$sPatternSql .= ") )";
		}
		
		$sQuery = "SELECT DISTINCT " .
				//"p.PageId, p.PageName, p.PageGroup, p.PageLang, p.PageTime, p.PageChange, p.PageContent, p.PageTranslated, p.PageTranslatedPercent" .
				self::_pageFieldsSelect(false, 'p.').
				", PageGroupContentClass " .
				"FROM `#PFX#page` p INNER JOIN `#PFX#pagegroup` ON p.PageGroup=PageGroupId " .
				$sSqlFrom." " .
				"WHERE 1=1 " .
				$sSqlLang .
				$sPatternSql." " .
				$sSqlWhere . " ";
				
		if ($nLimit > 0)
		{
			$nRealLimit = ( count($asLangs)>0 ? $nLimit*count($asLangs) : $nLimit );
			$sQuery .= "LIMIT ".$this->db()->inttosql($nRealLimit);
		}
		
		$q = $this->db()->query($sQuery);
		
		$amTmpByPagegroup = array();//TODO quick hack to not get same page in different languages and manage langs priority
		$aoSelectedPages = array();//real pages we want, sorted in right order
		$nIndicePreserveOrder = 0;
		while ($oData = $this->db()->fto($q))
		{
			if (!isset($amTmpByPagegroup[$oData->PageGroup]) || array_search($oData->PageLang,$asLangs) < array_search($aoSelectedPages[$amTmpByPagegroup[$oData->PageGroup]]->getLang(),$asLangs))
			{
				$oPage = self::getPageFromData($oData);
				if (isset($amTmpByPagegroup[$oData->PageGroup]))
				{
					//we found a lang having better priority
					unset($aoSelectedPages[$amTmpByPagegroup[$oData->PageGroup]]);
				}
				$amTmpByPagegroup[$oData->PageGroup] = $nIndicePreserveOrder; //just save the index in aoSelectedPages
				$aoSelectedPages[$nIndicePreserveOrder] = $oPage; //save page here
				$nIndicePreserveOrder++;
			}
		}
		$this->db()->free($q);
		
		$aoPages = array();
		$iCount = 0;
		foreach ($aoSelectedPages as $oPage)
		{
			$aoPages[] = $oPage;
			$iCount++;
			if ($iCount == $nLimit)
			{
				break;
			}
		}
		return $aoPages;
	}
	
	
	//------------------------------------------------
	// PAGE GROUPS
	//------------------------------------------------
	
	function createPageGroup($oPageGroup)
	{
		//create the pagegroup
		$asSqlInsert = array(
			"PageGroupContentClass" => $this->db()->strtosql($oPageGroup->getContentClassName())
		);
		$a = $this->db()->do_insert($asSqlInsert, "pagegroup");
		$nPageGroupId = $this->db()->insert_id();
		
		return $nPageGroupId;
	}
	
	function updatePageGroup($oPageGroup)
	{
		//nothing to do!
	}
	
	
	/**
	 * @throws AnwPageGroupNotFoundException
	 */
	function getPageGroup($nPageGroupId)
	{
		//get info for the group and it's related pages
		$sQuery = "SELECT PageGroupId, PageGroupContentClass, " .
				//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, PageContent, PageTranslated, PageTranslatedPercent ".
				self::_pageFieldsSelect().
				"FROM `#PFX#pagegroup` " .
				"LEFT JOIN `#PFX#page` ON PageGroup=PageGroupId ".
				"WHERE PageGroupId = ".$this->db()->inttosql($nPageGroupId)." " .
				"ORDER BY PageLang ASC";
		
		$aoPageGroups = self::getPageGroupsFromQuery($sQuery);

		if (count($aoPageGroups) < 1)
		{
			throw new AnwPageGroupNotFoundException();
		}
		
		$oPageGroup = array_pop($aoPageGroups);
		return $oPageGroup;
	}
	
	private function getPageGroupsFromQuery($sQuery)
	{
		$q = $this->db()->query($sQuery);
		
		$aaoDatasPageGroup = array();
		while ($oData = $this->db()->fto($q))
		{
			$nPageGroupId = $oData->PageGroupId;
			if (!isset($aaoDatasPageGroup[$nPageGroupId]))
			{
				$aaoDatasPageGroup[$nPageGroupId] = array();
			}
			$aaoDatasPageGroup[$nPageGroupId][] = $oData;
		}
		$this->db()->free($q);
		
		$aoPageGroups = array();
		foreach ($aaoDatasPageGroup as $aoDataPageGroup)
		{
			$oPageGroup = self::getPageGroupFromData($aoDataPageGroup);
			$nPageGroupId = $oPageGroup->getId();
			$aoPageGroups[$nPageGroupId] = $oPageGroup;
		}
		
		return $aoPageGroups;
	}
	
	private static function getPageGroupFromData($aoDataPageGroup)
	{
		$nPageGroupId = $aoDataPageGroup[0]->PageGroupId;
		$nPageGroupContentClass = $aoDataPageGroup[0]->PageGroupContentClass;
		
		$aoPages = array();
		foreach ($aoDataPageGroup as $oDataPage)
		{
			if ($oDataPage->PageId)
			{
				//warning : PageContent may be NULL when using performance+memory boost
				$oPage = self::getPageFromData($oDataPage);
				$sPageLang = $oPage->getLang();
				$aoPages[$sPageLang] = $oPage;
			}
		}
		$oPageGroup = AnwPageGroup::rebuildPageGroup($nPageGroupId, $nPageGroupContentClass, $aoPages);
		return $oPageGroup;
	}
	
	function getPageGroups($bSkipLoadingContent, $asLangs, $asContentClasses)
	{
		//skip pagegroup with only archived pages
		//$sQuery = "SELECT DISTINCT PageGroupId, PageGroupContentClass FROM `#PFX#pagegroup` g INNER JOIN `#PFX#page` p ON p.PageGroup=g.PageGroupId";
		$sSelectContent = 'PageContent';
		if ($bSkipLoadingContent)
		{
			$sSelectContent = 'NULL AS PageContent';
		}
		
		$sQuery = "SELECT PageGroupId, PageGroupContentClass, " .
				//"PageId, PageName, PageGroup, PageLang, PageTime, PageChange, $sSelectContent, PageTranslated, PageTranslatedPercent ".
				self::_pageFieldsSelect($sSelectContent).
				"FROM `#PFX#pagegroup` " .
				"INNER JOIN `#PFX#page` ON PageGroup=PageGroupId ";
				
		if ($asLangs)
		{
			foreach ($asLangs as $i => $sLang)
			{
				$asLangs[$i] = $this->db()->strtosql($sLang);
			}
			$sLangs = implode(',', $asLangs);
			$sQuery .= "AND PageLang IN (".$sLangs.") ";
		}
		
		if ($asContentClasses)
		{
			foreach ($asContentClasses as $i => $sContentClass)
			{
				$asContentClasses[$i] = $this->db()->strtosql($sContentClass);
			}
			$sContentClasses = implode(',', $asContentClasses);
			$sQuery .= "AND PageGroupContentClass IN (".$sContentClasses.") ";
		}
		
		$sQuery .= "ORDER BY PageLang, PageName ASC";
		
		$aoPageGroups = self::getPageGroupsFromQuery($sQuery);
		/*
		
		$q = $this->db()->query($sQuery);
		
		$aoPageGroups = array();
		
		while ($oData = $this->db()->fto($q))
		{
			$nPageGroupId = $oData->PageGroupId;
			
			//create PageGroup object
			$oContentClass = AnwContentClasses::getContentClass($oData->PageGroupContentClass);
			$oPageGroup = new AnwPageGroup($nPageGroupId);
						
			//add it to PageGroups array
			$aoPageGroups[] = $oPageGroup;
		}*/
		return $aoPageGroups;
	}
	

	
	//------------------------------------------------
	// LOCKS
	//------------------------------------------------
	
	function lockPage($nLockType, $oPage, $oSession)
	{
		$nPageId = $oPage->getId();
		$nPageGroupId = $oPage->getPageGroup()->getId();
		if ($nLockType == AnwLock::TYPE_PAGEGROUP)
		{
			$sQuery = "SELECT LockPage, LockUser, LockUserName, LockSession, LockTime, LockTimeLast, LockType " .
					"FROM `#PFX#lock` l INNER JOIN `#PFX#page` p ON l.LockPage=p.PageId " .
					"WHERE ( " .
						//nobody should be locking the pagegroup
						"p.PageGroup = ".$this->db()->inttosql($nPageGroupId)." " .
						"AND LockType = ".$this->db()->inttosql(AnwLock::TYPE_PAGEGROUP)." " .
					") ";
		}
		else if ($nLockType == AnwLock::TYPE_PAGEONLY)
		{
			$sQuery = "SELECT LockPage, LockUser, LockUserName, LockSession, LockTime, LockTimeLast, LockType " .
				"FROM `#PFX#lock` l INNER JOIN `#PFX#page` p ON l.LockPage=p.PageId " .
				"WHERE (" .
					"( " .
						//nobody should be locking the pagegroup
						"p.PageGroup = ".$this->db()->inttosql($nPageGroupId)." " .
						"AND LockType = ".$this->db()->inttosql(AnwLock::TYPE_PAGEGROUP)." " .
					") " .
					//nobody should be locking the page
					"OR (" .
						"LockPage = ".$this->db()->inttosql($nPageId)." " .
						"AND LockType = ".$this->db()->inttosql(AnwLock::TYPE_PAGEONLY)." " .
					")" .
				") ";
		}
		
		$oLock = $this->doLockPageForAction($sQuery, $oPage, $oSession, $nLockType);
		return $oLock;
	}
	
	function unlockPage($oPage, $oSession)
	{
		$nLockPage = $oPage->getId();
		$sLockSession = $oSession->getId();
		
		$sQuery = "DELETE FROM `#PFX#lock` " .
				"WHERE LockPage=".$this->db()->inttosql($nLockPage)." " .
				"AND LockSession=".$this->db()->strtosql($sLockSession);
		$this->db()->query($sQuery);
	}
	
	private function doLockPageForAction($sQuerySelect, $oPage, $oSession, $nLockType)
	{
		$this->transactionStart();
		try
		{			
			//purge expired locks
			$nTimeLimit = time() - self::globalCfgLocksExpiry();
			$sQuery = "DELETE FROM `#PFX#lock` WHERE LockTimeLast < ".$this->db()->inttosql($nTimeLimit);
			$this->db()->query($sQuery);
			
			//look at existing locks
			$q = $this->db()->query($sQuerySelect);
			
			$oExistingLock = false;
			$aoLocks = array();
			while ($oData = $this->db()->fto($q))
			{
				if ($oData->LockSession == $oSession->getId())
				{
					//user has already a lock
					$oExistingLock = self::getLockFromData($oData);
				}
				else
				{
					//conflicting lock
					$aoLocks[] = self::getLockFromData($oData);
				}
			}
			$this->db()->free($q);
			
			if ( count($aoLocks) > 0)
			{
				throw new AnwLockException($aoLocks);
			}
			
			if ($oExistingLock)
			{
				$this->debug("Updating an existing lock");
				$oExistingLock->setTimeLast( time() );
				self::updateLock($oExistingLock, $oSession);
			}
			else
			{
				$this->debug("Creating a new lock");
				$oExistingLock = new AnwLock($oPage, $oSession->getUser(), time(), time(), $nLockType);
				self::createLock($oExistingLock, $oSession);
			}
			
			$this->transactionCommit();
		}
		catch(AnwException $e)
		{
			$this->transactionRollback();
			throw $e;
		}
		return $oExistingLock;
	}
	
	private function updateLock($oLock, $oSession)
	{
		$nLockTimeLast = $oLock->getLockTimeLast();
		$nLockPage = $oLock->getLockPage()->getId();
		$sLockSession = $oSession->getId();
		$nLockType = $oLock->getLockType();
		
		$asSqlUpdate = array(
			"LockTimeLast" => $this->db()->inttosql($nLockTimeLast)
		);
		$sSqlWhere = "WHERE LockPage=".$this->db()->inttosql($nLockPage)." " .
				"AND LockSession=".$this->db()->strtosql($sLockSession)." ".
				"AND LockType=".$this->db()->inttosql($nLockType);
		$this->db()->do_update($asSqlUpdate, "lock", $sSqlWhere);
	}
	
	private function createLock($oLock, $oSession)
	{
		$nLockTime = $oLock->getLockTime();
		$nLockTimeLast = $oLock->getLockTimeLast();
		$nLockPage = $oLock->getLockPage()->getId();
		$nLockSession = $oSession->getId();
		$nLockUser = self::userToSql($oSession->getUser());
		$sLockUserName = $oSession->getUser()->getDisplayName();
		$nLockType = $oLock->getLockType();
		
		$asSqlInsert = array(
			"LockTime" => $this->db()->inttosql($nLockTime),
			"LockTimeLast" => $this->db()->inttosql($nLockTimeLast),
			"LockPage" => $this->db()->inttosql($nLockPage),
			"LockSession" => $this->db()->strtosql($nLockSession),
			"LockUser" => $this->db()->inttosql($nLockUser, true),
			"LockUserName" => $this->db()->strtosql($sLockUserName),
			"LockType" => $this->db()->inttosql($nLockType)
		);
		$this->db()->do_insert($asSqlInsert, "lock");
	}
	
	private static function getLockFromData($oData)
	{
		$oLockPage = new AnwPageById($oData->LockPage);
		$oLockPage->setSkipLoadingContent(true);
		$oLockUser = self::sqlToUser($oData->LockUser, $oData->LockUserName);
		$nLockTime = $oData->LockTime;
		$nLockTimeLast = $oData->LockTimeLast;
		$nLockType = $oData->LockType;
		$oLock = new AnwLock($oLockPage, $oLockUser, $nLockTime, $nLockTimeLast, $nLockType);
		return $oLock;
	}
	
	
	
	//------------------------------------------------
	// PRIVATE
	//------------------------------------------------
	
	private static function userToSql($oUser)
	{
		if ($oUser instanceof AnwUserAnonymous)
		{
			//anonymous user
			$nId = null;
		}
		else
		{
			//real user
			$nId = $oUser->getId();
		}
		return $nId;
	}
	
	private static function sqlToUser($nId, $sName=null)
	{
		if ($nId == null)
		{
			//anonymous user
			$oUser = new AnwUserAnonymous();
		}
		else
		{
			//real user
			$oUser = new AnwUserById($nId, $sName);
		}
		return $oUser;
	}
	
	
	//------------------------------------------------
	// INITIALIZE
	//------------------------------------------------
	function initializeComponent()
	{
		self::transactionStart();
		try
		{			
			$asQ = array();
			
			$asQ[] = "CREATE TABLE `#PFX#page`        ( PageId INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, PageChange INTEGER UNSIGNED NOT NULL, PageName VARCHAR(".AnwPage::MAXLEN_PAGENAME.") NOT NULL, PageGroup INTEGER UNSIGNED NOT NULL, PageLang VARCHAR(".Anwi18n::MAXLEN_LANG.") NOT NULL, PageTime INTEGER UNSIGNED NOT NULL, PageContent LONGTEXT NULL, PageTranslated TINYINT(1) UNSIGNED NOT NULL, PageTranslatedPercent TINYINT(3) UNSIGNED NOT NULL, PageCreationTime INTEGER UNSIGNED NOT NULL, PRIMARY KEY(PageId), UNIQUE(PageName), UNIQUE(PageChange), INDEX(PageTime), INDEX(PageLang), INDEX(PageGroup), INDEX(PageTranslated), INDEX(PageTranslatedPercent), INDEX(PageCreationTime), UNIQUE PageGroupPageLang (PageGroup,PageLang) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			$asQ[] = "CREATE TABLE `#PFX#pagearchive` ( PageId INTEGER UNSIGNED NOT NULL,                PageChange INTEGER UNSIGNED NOT NULL, PageName VARCHAR(".AnwPage::MAXLEN_PAGENAME.") NOT NULL, PageGroup INTEGER UNSIGNED NOT NULL, PageLang VARCHAR(".Anwi18n::MAXLEN_LANG.") NOT NULL, PageTime INTEGER UNSIGNED NOT NULL, PageContent LONGTEXT NULL, PageTranslated TINYINT(1) UNSIGNED NOT NULL, PageTranslatedPercent TINYINT(3) UNSIGNED NOT NULL, PageCreationTime INTEGER UNSIGNED NOT NULL, PRIMARY KEY (PageChange), INDEX(PageId), INDEX(PageTime), INDEX(PageLang), INDEX(PageGroup), INDEX(PageTranslated), INDEX(PageTranslatedPercent), INDEX(PageCreationTime) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			$asQ[] = "CREATE TABLE `#PFX#pageindex`   ( IndexField VARCHAR(50) NOT NULL, IndexPage INTEGER UNSIGNED NOT NULL, IndexValue VARCHAR(".AnwUtils::MAXLEN_INDEXVALUE.") NOT NULL, INDEX(IndexField), INDEX(IndexPage), INDEX(IndexValue) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			$asQ[] = "CREATE TABLE `#PFX#pagegrouplink`    ( PageGroupLinkFrom INTEGER UNSIGNED NOT NULL, PageGroupLinkTo INTEGER UNSIGNED NOT NULL, PRIMARY KEY (PageGroupLinkFrom, PageGroupLinkTo), INDEX(PageGroupLinkFrom), INDEX(PageGroupLinkTo) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			$asQ[] = "CREATE TABLE `#PFX#change`      ( ChangeId INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, ChangeType TINYINT(2) UNSIGNED NOT NULL, ChangeUser INTEGER UNSIGNED NULL, ChangeUserName VARCHAR(255) NOT NULL, ChangeTime INTEGER UNSIGNED NOT NULL, ChangeComment VARCHAR(".AnwChange::MAXLEN_COMMENT.") NULL, ChangeInfo VARCHAR(50) NULL, ChangePage INTEGER UNSIGNED NOT NULL, ChangePageName VARCHAR(".AnwPage::MAXLEN_PAGENAME.") NOT NULL, ChangePageLang VARCHAR(".Anwi18n::MAXLEN_LANG.") NOT NULL, ChangeIp VARCHAR(20) NOT NULL, PRIMARY KEY(ChangeId), INDEX(ChangeType), INDEX(ChangeTime), INDEX(ChangePage) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			$asQ[] = "CREATE TABLE `#PFX#pagegroup`   ( PageGroupId INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, PageGroupContentClass VARCHAR(".AnwUtils::MAXLEN_CONTENTCLASS.") NOT NULL, PRIMARY KEY(PageGroupId), INDEX(PageGroupContentClass) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			//$asQ[] = "CREATE TABLE `#PFX#pagedraft`        ( DraftPage INTEGER UNSIGNED NOT NULL, DraftTime INTEGER UNSIGNED NOT NULL, DraftUser INTEGER UNSIGNED NULL, DraftUserName VARCHAR(255) NOT NULL, DraftContent MEDIUMTEXT NULL, DraftComment VARCHAR(".AnwChange::MAXLEN_COMMENT.") NULL, PRIMARY KEY(DraftPage, DraftTime), INDEX(DraftPage) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci`";
			$asQ[] = "CREATE TABLE `#PFX#lock`        ( LockPage INTEGER UNSIGNED NOT NULL, LockType TINYINT(2) UNSIGNED NOT NULL, LockSession VARCHAR(32) NOT NULL, LockTime INTEGER UNSIGNED NOT NULL, LockTimeLast INTEGER UNSIGNED NOT NULL, LockUser INTEGER UNSIGNED NULL, LockUserName VARCHAR(255) NOT NULL, PRIMARY KEY(LockPage, LockSession, LockType), UNIQUE(LockPage,LockType), INDEX(LockType), INDEX(LockSession), INDEX(LockTimeLast) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci` ENGINE=InnoDB";
			
			
			$asQ[] = "ALTER TABLE `#PFX#page` ADD FOREIGN KEY(PageGroup) REFERENCES `#PFX#pagegroup` (PageGroupId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			$asQ[] = "ALTER TABLE `#PFX#page` ADD FOREIGN KEY(PageChange) REFERENCES `#PFX#change` (ChangeId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			$asQ[] = "ALTER TABLE `#PFX#pagearchive` ADD FOREIGN KEY(PageGroup) REFERENCES `#PFX#pagegroup` (PageGroupId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			$asQ[] = "ALTER TABLE `#PFX#pagearchive` ADD FOREIGN KEY(PageChange) REFERENCES `#PFX#change` (ChangeId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			$asQ[] = "ALTER TABLE `#PFX#pageindex` ADD FOREIGN KEY(IndexPage) REFERENCES `#PFX#page` (PageId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			$asQ[] = "ALTER TABLE `#PFX#pagegrouplink` ADD FOREIGN KEY(PageGroupLinkFrom) REFERENCES `#PFX#pagegroup` (PageGroupId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			$asQ[] = "ALTER TABLE `#PFX#pagegrouplink` ADD FOREIGN KEY(PageGroupLinkTo) REFERENCES `#PFX#pagegroup` (PageGroupId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			//$asQ[] = "ALTER TABLE `#PFX#change` ADD FOREIGN KEY(ChangePage) REFERENCES `#PFX#pagearchive` (PageId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			$asQ[] = "ALTER TABLE `#PFX#lock` ADD FOREIGN KEY(LockPage) REFERENCES `#PFX#page` (PageId) ON DELETE RESTRICT ON UPDATE RESTRICT";
			
			$sInitializationLog = "";
			
			//execute queries
			foreach ($asQ as $sQ)
			{
				$sInitializationLog .= $sQ."<br/>";
				$this->db()->query($sQ);
			}
			
			self::transactionCommit();
			
			return $sInitializationLog;
		}
		catch(AnwException $e)
		{
			self::transactionRollback();
			throw $e;
		}
	}
	
	function getSettingsForInitialization()
	{
		$oMysqlSettings = $this->cfg(self::CFG_MYSQL);
		return AnwComponent::g_editcontent("contentfield_mysqlconnexion_collapsed", array(
							'user' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_USER],
							'host' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_HOST],
							'database' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_DATABASE],
							'prefix' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PREFIX]
						));
	}
	
	function isComponentInitialized()
	{
		return ($this->db()->table_exists("page") 
				|| $this->db()->table_exists("pagearchive") 
				|| $this->db()->table_exists("pagearchive") 
				|| $this->db()->table_exists("pageindex") 
				|| $this->db()->table_exists("pagegrouplink") 
				|| $this->db()->table_exists("change") 
				|| $this->db()->table_exists("pagegroup") 
				|| $this->db()->table_exists("lock"));
	}
	
	//------------------------------------------------
	// TRANSACTIONS
	//------------------------------------------------
	
	function transactionStart()
	{
		$this->db()->transactionStart();
	}
	function transactionCommit()
	{
		$this->db()->transactionCommit();
	}
	function transactionRollback()
	{
		$this->db()->transactionRollback();
	}
	
	//------------------------------------------------
	
	private function db()
	{
		if (!$this->oDb)
		{
			$oMysqlSettings = $this->cfg(self::CFG_MYSQL);
			$this->oDb = AnwMysql::getInstance(
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_USER], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PASSWORD], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_HOST], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_DATABASE],
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PREFIX]
			);
		}
		return $this->oDb;
	}
}

?>