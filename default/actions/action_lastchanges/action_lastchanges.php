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
 * Viewing last changes.
 * @package Anwiki
 * @version $Id: action_lastchanges.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_lastchanges extends AnwActionGlobal implements AnwConfigurable, AnwHarmlessAction
{
	const CFG_LIMIT = "lastchanges_limit";
	const ACTION_HISTORY = "history";
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$oContentField = new AnwContentFieldSettings_integer(self::CFG_LIMIT);
		$oContentField->setValueMin(5);
		$aoSettings[] = $oContentField;
		return $aoSettings;
	}
	
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	//@Override
	protected function checkActionAllowed()
	{
		// are we in "page history" mode?
		$oPageForHistory = $this->getPageForHistory();
		if ($oPageForHistory)
		{
			//only check permissions for action "history" when coming from this action.
			//TODO not very clean, maybe find a better way than redirecting from action history
			$oPageForHistory->checkActionAllowed(self::ACTION_HISTORY);
		}
		else
		{
			return parent::checkActionAllowed();
		}
	}
	
	/**
	 * Returns the pagegroup requested for history.
	 */
	protected function getPageGroupForHistory()
	{
		static $oPageGroupForHistory = null, $bInitialized=false;
		
		// only initialize first time for performances saving
		if (!$bInitialized)
		{
			$nPageGroupId = (int)AnwEnv::_GET("pagegroup");
			if ($nPageGroupId > 0)
			{
				$oPageGroupTmp = new AnwPageGroup($nPageGroupId);
				if ($oPageGroupTmp->exists())
				{
					$oPageGroupForHistory = $oPageGroupTmp;
				}
			}
		}
		return $oPageGroupForHistory;
	}
	
	/**
	 * If we come from action "history", returns the page requested.
	 */
	protected function getPageForHistory()
	{
		static $oPageForHistory = null, $bInitialized=false;
		
		// only initialize first time for performances saving
		if (!$bInitialized)
		{
			// only look for page if no pagegroup was requested - important for checkActionAllowed()
			if (!$this->getPageGroupForHistory())
			{
				$nPageId = (int)AnwEnv::_GET("page");
				if ($nPageId > 0)
				{
					try
					{
						$oPageTmp = AnwPage::getLastPageRevision($nPageId);
					}
					catch(AnwPageNotFoundException $e)
					{
						throw new AnwBadCallException();
					}
					
					$oPageTmp->setSkipLoadingContent(true);
					if ($oPageTmp->exists())
					{
						$oPageForHistory = $oPageTmp;
					}
				}
			}
			$bInitialized = true;
		}
		return $oPageForHistory;
	}
	
	function run()
	{
		//get a page history ?	
		$oPage = null;
		$oPageGroup = null;
		
		$oPageGroup = $this->getPageGroupForHistory();
		if (!$oPageGroup)
		{
			// did we requested a page history?
			$oPage = $this->getPageForHistory();
		}
		
		//page title
		if ($oPage)
		{
			$sTitle = $this->t_("history_t", array("pagename"=>$oPage->getName()));
		}
		else if ($oPageGroup)
		{
			$sTitle = $this->t_("history_pagegroup_t", array("pagegroupid"=>$oPageGroup->getId()));
		}
		else
		{
			$sTitle = $this->t_("title");
		}
		$this->setTitle($sTitle);
		
		//filter change types
		$amAllChangeTypes = AnwChange::getChangeTypes();
		$amDisplayChangeTypes = array();
		foreach ($amAllChangeTypes as $mChangeType)
		{
			if (AnwEnv::_GET("ct_".$mChangeType))
			{
				$amDisplayChangeTypes[] = $mChangeType;
			}
		}
		if (count($amDisplayChangeTypes) == 0)
		{
			$amDisplayChangeTypes = $amAllChangeTypes;
			
			if (!$oPage)
			{
				$amDisplayChangeTypes = AnwUtils::array_remove($amDisplayChangeTypes, AnwChange::TYPE_PAGE_EDITION_DEPLOY);
				$amDisplayChangeTypes = AnwUtils::array_remove($amDisplayChangeTypes, AnwChange::TYPE_PAGE_UPDATELINKS);
			}
		}
		
		//initialize filters
		list($asAllLangs, $asDisplayLangs) = $this->filterLangs(array("view"), true);
		list($asAllClasses, $asDisplayClasses) = $this->filterContentClasses();
		
		$nDefaultDisplayModeGrouped = 1;
		//disable filters if a page is selected
		if($oPage || $oPageGroup)
		{
			$asDisplayLangs = $asAllLangs;
			$asDisplayClasses = $asAllClasses;
			$nDefaultDisplayModeGrouped = 0; //show in detailled mode by default
		}
		
		//display mode
		$bGrouped = AnwEnv::_GET("fg", $nDefaultDisplayModeGrouped);
		
		//limit
		$nLimit = $this->cfg(self::CFG_LIMIT);
		if ($bGrouped) $nLimit*=2; //TODO
		
		$nStart = (int)AnwEnv::_GET("s", 0);
		$nStartPrev = $nStart - $nLimit;
		$nStartNext = $nStart + $nLimit;
		
		//get last changes
		$aoChanges = AnwStorage::getLastChanges($nLimit, $nStart, $asDisplayLangs, $asDisplayClasses, $amDisplayChangeTypes, $oPage, $oPageGroup);
		if ($bGrouped)
		{
			$aoChanges = AnwSimilarChanges::groupSimilarChanges($aoChanges);
		}
		
		//check permissions
		foreach ($aoChanges as $i => $oChange)
		{
			if ( ($oChange->getPage() && !$oChange->getPage()->isActionAllowed("view")) 
				|| !AnwCurrentSession::isActionAllowed($oChange->getPageName(), "view", $oChange->getPageLang()))
			{
				unset($aoChanges[$i]);
			}
		}
		
		if (AnwEnv::_GET("feed"))
		{
			$this->showFeed($aoChanges);
		}
		else
		{
			$this->showHtml($aoChanges, $amAllChangeTypes, $amDisplayChangeTypes, $asAllLangs, $asDisplayLangs, $asAllClasses, $asDisplayClasses, $nStartPrev, $nStartNext, $sTitle, $bGrouped, $oPage, $oPageGroup);
		}
	}
	
		
	function showHtml($aoChanges, $amAllChangeTypes, $amDisplayChangeTypes, $asAllLangs, $asDisplayLangs, $asAllClasses, $asDisplayClasses, $nStartPrev, $nStartNext, $sTitle, $bGrouped, $oPage, $oPageGroup)
	{
		$this->out .= $this->tpl()->lastchangesHeader($sTitle);
		
		$sUrl = AnwEnv::_SERVER('REQUEST_URI');
		$sUrl = preg_replace("/&s=([0-9]*)/", "", $sUrl);
		
		//rss link
		$sRssLink = $sUrl;
		$sRssLink .= '&feed=rss2'; //without start
		$this->head( $this->tpl()->headRss($sRssLink) );
		
		$this->out .= $this->tpl()->filterBefore($this->linkMe());
		
		$nPageId = ( $oPage ? $oPage->getId() : null );
		$nPageGroupId = ( $oPageGroup ? $oPageGroup->getId() : null );
		$bShowHistoryColumn = ($oPage ? false : true);
		
		//disable filters if a page is selected
		if (!$nPageId)
		{
			//filter lang
			$this->out .= $this->tpl()->filterLangs($asAllLangs, $asDisplayLangs);
		}
		if (!$nPageId && !$nPageGroupId)
		{
			//filter contentclass
			$this->out .= $this->tpl()->filterClass($asAllClasses, $asDisplayClasses);
		}
		
		//filter changes types
		$this->out .= $this->tpl()->filterChangeTypes($amAllChangeTypes, $amDisplayChangeTypes);
		
		//display mode
		$sHistoryPageGroupLink = false;
		if ($oPage && AnwCurrentSession::isActionGlobalAllowed($this->getName()))
		{
			$sHistoryPageGroupLink = AnwEnv::_SERVER('REQUEST_URI');
			$sHistoryPageGroupLink = preg_replace("$&page=([0-9]*)$", "", $sHistoryPageGroupLink);
			$sHistoryPageGroupLink = preg_replace("$&pagegroup=([0-9]*)$", "", $sHistoryPageGroupLink);
			$sHistoryPageGroupLink .= '&pagegroup='.$oPage->getPageGroup()->getId();
		}
		$this->out .= $this->tpl()->filterAfter($bGrouped, $nPageId, $nPageGroupId, $sRssLink, $sHistoryPageGroupLink);
		
		
		//nav
		$sLatestLink = "";
		$sPrevLink = "";
		if ($nStartPrev >= 0)
		{
			$sPrevLink = $sUrl.'&s='.$nStartPrev;
			if ($nStartPrev > 0)
			{
				$sLatestLink = $sUrl.'&s=0';
			}
		}
		$sNextLink = $sUrl.'&s='.$nStartNext;
		
		$this->out .= $this->tpl()->nav($sLatestLink, $sPrevLink, $sNextLink, $bShowHistoryColumn);
		
		foreach ($aoChanges as $i => $oChange)
		{
			$sType = AnwChange::changeTypei18n($oChange->getType());
			
			//links
			$sLnkPage = '<span class="pageid">#'.$oChange->getPageId().'</span>';
			$sLnkDiff = '-';
			if ($oChange->activePageExists())
			{
				$sLnkPage = $oChange->getActivePage()->link(); //active link, if it exists
			}
			
			//diffs link
			if ($oChange->isGlobalAndViewActionAllowed('diff'))
			{
				if ($oChange->isDiffAvailable())
				{
					$sImgDiff = AnwUtils::xQuote(AnwUtils::pathImg("diff.gif"));
					$sAltDiff = AnwUtils::xQuote(self::g_("change_diff_link"));
					$sLnkDiff = AnwUtils::xQuote(AnwUtils::alink("diff", array("page"=>$oChange->getPageId(), "revto"=>$oChange->getChangeId())));
					$sLnkDiff = <<<EOF
<a href="$sLnkDiff" title="$sAltDiff"><img src="$sImgDiff" alt="$sAltDiff"/></a>
EOF;
				}
			}
				
			//history link
			$sLnkHistory = false;
			if ($bShowHistoryColumn)
			{
				$sLnkHistory = " - ";
				if ($oChange->isActionAllowed('history'))
				{
					$sImgHistory = AnwUtils::xQuote(AnwUtils::pathImg("history.gif"));
					$sAltHistory = AnwUtils::xQuote($this->t_("change_history_link"));
					$sLnkHistory = AnwUtils::xQuote(AnwUtils::alink("lastchanges", array("page"=>$oChange->getPageId())));
					$sLnkHistory = <<<EOF
<a href="$sLnkHistory" title="$sAltHistory"><img src="$sImgHistory" alt="$sAltHistory"/></a>
EOF;
				}
			}
			
			//revert link
			$sLnkRevert = " - ";
			if ($oChange->isGlobalAndViewActionAllowed('revert'))
			{
				if ($oChange->isRevertAvailable())
				{
					$sImgRevert = AnwUtils::xQuote(AnwUtils::pathImg("revert.gif"));
					$sAltRevert = AnwUtils::xQuote(self::t_("change_revert_link"));				
					$sLnkRevert = AnwUtils::xQuote(AnwUtils::alink("revert", array("page"=>$oChange->getPageId(), "revto"=>$oChange->getChangeId()))); //we pass pageid instead of pagegroupid for better performances...
					$sLnkRevert = <<<EOF
<a href="$sLnkRevert" title="$sAltRevert"><img src="$sImgRevert" alt="$sAltRevert"/></a>
EOF;
				}
			}
				
			//output
			$this->out .= $this->tpl()->lastchangesLine(
				Anwi18n::dateTime($oChange->getTime()),
				$sType,
				$oChange->getComment(),
				$oChange->getInfo(),
				$oChange->getUser()->getDisplayName(),
				$sLnkPage,
				$sLnkDiff,
				$sLnkHistory,
				$sLnkRevert,
				$oChange->getPageName(),
				$oChange->getPageLang()
			);
		}
		
		$this->out .= $this->tpl()->lastchangesFooter();
	}
	
	
	function showFeed($aoChanges)
	{
		$sFeedTitle = $this->t_("title")." - ".self::globalCfgWebsiteName();
		$oFeed = new AnwFeed( AnwEnv::_GET("feed"), $sFeedTitle, AnwUtils::aLinkAbsolute("lastchanges") );
		
		foreach ($aoChanges as $oChange)
		{
			$sType = AnwChange::changeTypei18n($oChange->getType());
			
			$sPageLink = "";
			
			if ($oChange->activePageExists())
			{
				$oActivePage = $oChange->getActivePage();
				$sPageLink = AnwUtils::linkAbsolute($oActivePage); //active link, if it exists
				$sPageTitle = $oActivePage->getName().' ('.$oActivePage->getLang().')'; //show old name from the change					
			}
			else
			{
				$sPageTitle = '(DEL)'.$oChange->getPageName().' ('.$oChange->getPageLang().')';
			}
			
			//add to feed
			$sUserDisplayName = $oChange->getUser()->getDisplayName();
			$sItemTitle = $sPageTitle.' - '.$sType.' - '.$sUserDisplayName;
			$oFeedItem = new AnwFeedItem($sItemTitle, $sPageLink);
			$oFeedItem->setDate( $oChange->getTime() );
			$oFeedItem->setAuthor($sUserDisplayName);
			$oFeed->addItem($oFeedItem);
		}
		
		$oFeed->output();
	}
}

?>