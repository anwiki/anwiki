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
 * Definition of a change made to a page. 
 * A page revision is always associated to a change, for history purpose.
 * @package Anwiki
 * @version $Id: class_change.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwChange{
	protected $nType;
	protected $nTime;
	protected $sComment;
	protected $nPageId; //page ID related to this change, even if no specific revision concerned by this change
	protected $oPage; //page revision related to this change
	protected $bActivePageExists = -1;
	protected $sInfo;
	protected $sPageName;
	protected $sPageLang;
	
	protected $nChangeId;
	protected $oUser;
	protected $sIp;
	
	const TYPE_PAGE_CREATION=1;
	const TYPE_PAGE_CREATION_TRANSLATION=2;
	const TYPE_PAGE_EDITION=3;
	const TYPE_PAGE_EDITION_DEPLOY=4;
	const TYPE_PAGE_TRANSLATION=5;
	const TYPE_PAGE_DELETION=6;
	const TYPE_PAGE_RENAME=7;
	const TYPE_PAGE_CHANGELANG=8;
	const TYPE_PAGE_UPDATELINKS=9;
	const TYPE_PAGE_REVERT=10;
	
	const MAXLEN_COMMENT = 150;
	
	/**
	 * @throws AnwBadCommentException
	 */
	function __construct($nType, $oPage, $nTime, $sComment, $sInfo="", $sPageName="", $sPageLang="")
	{
		if (!self::isValidComment($sComment))
		{
			throw new AnwBadCommentException();
		}
		$this->nType = $nType;
		$this->nTime = $nTime;
		$this->sComment = $sComment;
		if ($oPage) //may be null when rebuilding a delete change
		{
			if ($oPage->exists()) //may not exist when creating a page
			{
				$this->nPageId = $oPage->getId();
			}
			$this->oPage = $oPage;
		}
		$this->sInfo = $sInfo;
		$this->sPageName = ($sPageName ? $sPageName : ($oPage ? $oPage->getName() : "") );
		$this->sPageLang = ($sPageLang ? $sPageLang : ($oPage ? $oPage->getLang() : "") );
		
		$this->oUser = AnwCurrentSession::getUser();
		$this->sIp = AnwCurrentSession::getIp();
	}
	
	function create()
	{
		if ($this->nChangeId)
		{
			throw new AnwUnexpectedException("Change already existing");
		}
		$this->nChangeId = AnwStorage::createChange($this);
		$this->oPage->_change_cbk_setchange($this);
		return $this->nChangeId;
	}
	
	function getType()
	{
		return $this->nType;
	}
	
	function getUser()
	{
		return $this->oUser;
	}
	
	function getTime()
	{
		return $this->nTime;
	}
	
	function getComment()
	{
		return $this->sComment;
	}
	
	function getRawInfo()
	{
		return $this->sInfo;
	}
	
	function getInfo($bFormat=true)
	{
		if ($this->getType()==self::TYPE_PAGE_REVERT)
		{
			$sDateTime = Anwi18n::dateTime($this->sInfo);
			return AnwComponent::g_("changes_revert_info",array('datetime'=>$sDateTime));
		}
		else if ($this->getType()==self::TYPE_PAGE_EDITION_DEPLOY)
		{
			$sPageName = $this->sInfo;
			return AnwComponent::g_("changes_edition_deploy_info",array('pagename'=>AnwUtils::xText($sPageName)));
		}
		return AnwUtils::xText($this->sInfo);
	}
	
	function getPageId()
	{
		return $this->nPageId;
	}
	
	function getPage()
	{
		return $this->oPage;
	}
	
	function getActivePage()
	{
		if ($this->bActivePageExists === false)
		{
			return null;
		}
		
		if ($this->oPage)
		{
			return $this->oPage->getActivePage();
		}
		
		$oPage = new AnwPageById($this->getPageId());
		if ($oPage->exists())
		{
			return $oPage;
		}
		
		return null;
	}
	
	function activePageExists()
	{
		if ($this->bActivePageExists === -1) //first call to this function
		{
			$this->bActivePageExists = ($this->getActivePage() ? true : false);
		}
		return $this->bActivePageExists;
	}
	
	function getPageName()
	{
		return $this->sPageName;
	}
	
	function getPageLang()
	{
		return $this->sPageLang;
	}
	
	function getIp()
	{
		return $this->sIp;
	}
	
	function getChangeId()
	{
		if (!$this->nChangeId)
		{
			throw new AnwUnexpectedException("no change ID");
		}
		return $this->nChangeId;
	}
	
	function isRevertAvailable()
	{
		// we can't revert a deployed content edition, because of pagegroup content desynchronization
		return ($this->getType() != AnwChange::TYPE_PAGE_EDITION_DEPLOY);
	}
	
	function isDiffAvailable()
	{
		// we can't diff on new or deleted pages
		return !in_array($this->getType(), array(AnwChange::TYPE_PAGE_CREATION, AnwChange::TYPE_PAGE_CREATION_TRANSLATION, AnwChange::TYPE_PAGE_DELETION));
	}
	
	// --------------------------------------------
	
	function isActionAllowed($sAction)
	{
		try 
		{
			$this->checkActionAllowed($sAction);
			return true;
		}
		catch(AnwAclException $e)
		{
			return false;
		}
	}
	
	function isGlobalAndViewActionAllowed($sAction)
	{
		try 
		{
			$this->checkGlobalAndViewActionAllowed($sAction);
			return true;
		}
		catch(AnwAclException $e)
		{
			return false;
		}
	}
	
	function checkActionAllowed($sAction)
	{
		// important: ask directly AnwCurrentSession, as getPage() may be null
		if (!AnwCurrentSession::isActionAllowed($this->getPageName(), $sAction, $this->getPageLang()))
		{
			throw new AnwAclException("permission denied on page action: ".$sAction);
		}
	}
	
	function checkGlobalAndViewActionAllowed($sGlobalAction)
	{
		// check global action allowed
		// important: ask directly AnwCurrentSession, as getPage() may be null
		if (!AnwCurrentSession::isActionGlobalAllowed($sGlobalAction))
		{
			throw new AnwAclException("permission denied on global action: ".$sGlobalAction);
		}
		
		// check view action allowed
		$this->checkActionAllowed('view');
	}
	
	// --------------------------------------------
	
	static function isValidComment($sComment)
	{
		return (strlen($sComment) < self::MAXLEN_COMMENT && strip_tags($sComment) == $sComment);
	}
	
	static function rebuildChange($nType, $oPage, $bActivePageExists, $nPageId, $nTime, $sComment, $sInfo, $sPageName, $sPageLang, $nChangeId, $oUser, $sIp)
	{
		$oChange = new AnwChange($nType, $oPage, $nTime, $sComment, $sInfo, $sPageName, $sPageLang);
		$oChange->nPageId = $nPageId;
		$oChange->nChangeId = $nChangeId;
		$oChange->oUser = $oUser;
		$oChange->sIp = $sIp;
		if ($oChange->oPage)
		{
			$oChange->oPage->_change_cbk_setchange($oChange);
		}
		$oChange->bActivePageExists = $bActivePageExists;
		return $oChange;
	}
	
	static function getChangeTypes()
	{
		return array(
			self::TYPE_PAGE_CREATION,
			self::TYPE_PAGE_CREATION_TRANSLATION,
			self::TYPE_PAGE_EDITION,
			self::TYPE_PAGE_EDITION_DEPLOY,
			self::TYPE_PAGE_TRANSLATION,
			self::TYPE_PAGE_DELETION,
			self::TYPE_PAGE_RENAME,
			self::TYPE_PAGE_CHANGELANG,
			self::TYPE_PAGE_REVERT,
			self::TYPE_PAGE_UPDATELINKS
		);
	}
	
	static function changeTypei18n($mChangeType)
	{
		$sType = "";
		switch ($mChangeType)
		{
			case AnwChange::TYPE_PAGE_CREATION: 			
				$sType = AnwComponent::g_("changes_creation"); 
				break;
			case AnwChange::TYPE_PAGE_CREATION_TRANSLATION: 
				$sType = AnwComponent::g_("changes_newtranslation"); 
				break;
			case AnwChange::TYPE_PAGE_EDITION: 				
				$sType = AnwComponent::g_("changes_edition"); 
				break;
			case AnwChange::TYPE_PAGE_EDITION_DEPLOY: 		
				$sType = AnwComponent::g_("changes_edition_deploy"); 
				break;
			case AnwChange::TYPE_PAGE_TRANSLATION: 			
				$sType = AnwComponent::g_("changes_translation"); 
				break;
			case AnwChange::TYPE_PAGE_DELETION: 			
				$sType = AnwComponent::g_("changes_deletion"); 
				break;
			case AnwChange::TYPE_PAGE_RENAME: 			
				$sType = AnwComponent::g_("changes_rename"); 
				break;
			case AnwChange::TYPE_PAGE_CHANGELANG: 			
				$sType = AnwComponent::g_("changes_changelang"); 
				break;
			case AnwChange::TYPE_PAGE_UPDATELINKS: 			
				$sType = AnwComponent::g_("changes_updatelinks"); 
				break;
			case AnwChange::TYPE_PAGE_REVERT: 			
				$sType = AnwComponent::g_("changes_revert"); 
				break;
		}
		return $sType;
	}
}

class AnwSimilarChanges extends AnwChange
{
	private $aoSimilarChanges;
	
	function __construct($aoSimilarChanges)
	{
		$this->aoSimilarChanges = $aoSimilarChanges;
		
		$oDisplayedChange = $aoSimilarChanges[0];
		parent::__construct($oDisplayedChange->getType(), $oDisplayedChange->getPage(), 
							$oDisplayedChange->getTime(), $oDisplayedChange->getComment(), 
							$oDisplayedChange->getInfo(), 
							$oDisplayedChange->getPageName(), $oDisplayedChange->getPageLang());
		
		$this->nChangeId = $oDisplayedChange->getChangeId();
		$this->oUser = $oDisplayedChange->getUser();
		$this->sIp = $oDisplayedChange->getIp();
		if (!$oDisplayedChange->getPage())
		{
			$this->nPageId = $oDisplayedChange->getPageId();
		}
	}
	
	
	static function groupSimilarChanges($aoChanges)
	{
		$aoCollapsedChanges = array();
		$aoBufferSimilarChanges = array();
		
		$nLastUserId=null;
		$nLastChangeType=null;
		$nLastPageId=null;
		foreach ($aoChanges as $oChange)
		{
			
			$oChangeUser = $oChange->getUser();
			$oChangePage = $oChange->getPage();
			$nChangeType = $oChange->getType();
			if ($nLastChangeType == $nChangeType 
				&& $oChangeUser && $oChangeUser instanceof AnwUserReal && $nLastUserId == $oChangeUser->getId() 
				&& ( in_array($nChangeType, array(AnwChange::TYPE_PAGE_CREATION_TRANSLATION))
					|| $oChangePage && $oChangePage->exists() && $nLastPageId == $oChangePage->getId() )
				)
			{
				$aoBufferSimilarChanges[] = $oChange;
			}
			else
			{
				if (count($aoBufferSimilarChanges)>0)
				{
					if (count($aoBufferSimilarChanges)>1)
					{
						$oGroupedChange = new AnwSimilarChanges($aoBufferSimilarChanges);
						$aoCollapsedChanges[] = $oGroupedChange;
					}
					else
					{
						$aoCollapsedChanges[] = $aoBufferSimilarChanges[0];
					}
					$aoBufferSimilarChanges = array();
				}
				$aoBufferSimilarChanges[] = $oChange;
				
				$nLastUserId = $oChangeUser && $oChangeUser instanceof AnwUserReal ? $oChangeUser->getId() : 0;
				$nLastChangeType = $nChangeType;
				$nLastPageId = ($oChangePage && $oChangePage->exists() ? $oChangePage->getId() : 0);
			}
		}
		if (count($aoBufferSimilarChanges)>0)
		{
			if (count($aoBufferSimilarChanges)>1)
			{
				$oGroupedChange = new AnwSimilarChanges($aoBufferSimilarChanges);
				$aoCollapsedChanges[] = $oGroupedChange;
			}
			else
			{
				$aoCollapsedChanges[] = $aoBufferSimilarChanges[0];
			}
			$aoBufferSimilarChanges = array();
		}
		return $aoCollapsedChanges;
	}
	
	function getSimilarChanges()
	{
		return $this->aoSimilarChanges;
	}
	
	function getComment()
	{
		return AnwUtils::xText(parent::getComment()).' <span class="change_similars">('.AnwComponent::g_("change_similars", array('count'=>count($this->aoSimilarChanges))).')</span>';
	}
}

?>