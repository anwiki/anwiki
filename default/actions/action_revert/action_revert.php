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
 * Reverting back to an old revision.
 * @package Anwiki
 * @version $Id: action_revert.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_revert extends AnwActionGlobal {
	
	function run() 
	{
		//find TO revision
		try
		{
			//retrieve pagegroup
			$nPageGroupId = (int)AnwEnv::_GET("pagegroup", AnwEnv::_POST("pagegroup"));
			if (!$nPageGroupId)
			{
				//special case: we may have passed a pageid (ie: from lastchanges, for better performances avoiding loading pagegroup info)
				$nPageId = (int)AnwEnv::_GET("page");
				if ($nPageId)
				{
					$oExistingPageTmp = AnwPage::getLastPageRevision($nPageId); //high queries consuming, not 100% cached...
					if ($oExistingPageTmp->exists())
					{
						$nPageGroupId = $oExistingPageTmp->getPageGroup()->getId();
					}
					unset($oExistingPageTmp);
				}
				
				if (!$nPageGroupId)
				{
					throw new AnwBadCallException();
				}
			}
			
			$oPageGroup = new AnwPageGroup($nPageGroupId);
			
			if (!$oPageGroup->exists())
			{
				throw new AnwBadCallException("pagegroup not found for revert");
			}
			
			//get valid changeid
			$aoPageGroupChangesById = AnwStorage::getLastChanges(false, 0, null, null, null, null, $oPageGroup);
			$nRevToChangeId = (int)AnwEnv::_GET("revto", AnwEnv::_POST("revto")); //may be null when coming from action_revertpage			
			if (!$nRevToChangeId || !array_key_exists($nRevToChangeId, $aoPageGroupChangesById))
			{
				//get last changeid from this pagegroup
				$oChangeReference = reset($aoPageGroupChangesById);
				$nRevToChangeId = $oChangeReference->getChangeId();
			}
		}
		catch(AnwException $e)
		{
			throw new AnwBadCallException();
		}
		
		$this->setTitle( $this->t_("title") );
		
		$aaRevertPlan = $this->generateRevertPlan($oPageGroup, $nRevToChangeId);
		
		if (AnwEnv::_POST("submit") && $nRevToChangeId > 0)
		{
			$this->doRevert($oPageGroup, $aaRevertPlan);
		}
		else
		{
			$this->showFormRevert($oPageGroup, $aaRevertPlan, $nRevToChangeId);
		}
	}
	
	protected function getPagesForRevertById($oPageGroup, $nRevToChangeId)
	{
		//simulate the revert
		$aoPagesForRevertTmp = AnwStorage::getPageGroupPreviousArchives($nRevToChangeId, $oPageGroup, false); //the changeId is excluded
		$aoPagesForRevertById = array();
		foreach ($aoPagesForRevertTmp as $oPageRev)
		{
			// check permission...
			//TODO people may not understand if they get "permission denied" error,
			// due to missing permissions on some translations
			$oPageRev->checkGlobalAndViewActionAllowed($this->getName());
			
			$aoPagesForRevertById[$oPageRev->getId()] = $oPageRev;
		}
		unset($aoPagesForRevertTmp);
		return $aoPagesForRevertById;
	}
	
	protected function getTranslationsById($oPageGroup)
	{
		//get current translations by id
		$aoTranslationsTmp = $oPageGroup->getPages();
		$aoTranslationsById = array();
		foreach ($aoTranslationsTmp as $oTranslation)
		{
			$aoTranslationsById[$oTranslation->getId()] = $oTranslation;
		}
		unset($aoTranslationsTmp);
		return $aoTranslationsById;
	}
	
	protected function generateRevertPlan($oPageGroup, $nRevToChangeId)
	{
		$aaRevertPlan = array('REVERT'=>array(), 'DELETE'=>array(), 'RESTORE'=>array(), 'KEEP'=>array());
		
		$aoPagesForRevertById = $this->getPagesForRevertById($oPageGroup, $nRevToChangeId);
		$aoTranslationsById = $this->getTranslationsById($oPageGroup);		
		
		foreach ($aoPagesForRevertById as $nPageId => $oPageForRevert)
		{
			if (isset($aoTranslationsById[$nPageId]))
			{
				$oCurrentTranslation = $aoTranslationsById[$nPageId];
				if ($oCurrentTranslation->getChangeId()<$nRevToChangeId)
				{
					$aaRevertPlan['KEEP'][] = $oCurrentTranslation;
				}
				else
				{
					$aaRevertPlan['REVERT'][] = array($oCurrentTranslation, $oPageForRevert);
				}
			}
			else
			{
				$aaRevertPlan['RESTORE'][] = $oPageForRevert;
			}
		}
		
		foreach ($aoTranslationsById as $nPageId => $oPage)
		{
			if (!isset($aoPagesForRevertById[$nPageId]))
			{
				if ($oPage->getChangeId()<$nRevToChangeId)
				{
					$aaRevertPlan['KEEP'][] = $oPage;
				}
				else
				{
					$aaRevertPlan['DELETE'][] = $oPage;
				}
			}
		}
		return $aaRevertPlan;
	}
	
	private function showFormRevert($oPageGroup, $aaRevertPlan, $nRevToChangeId)
	{
		$aoChanges = array();
		$aoChangesUnfiltered = AnwStorage::getLastChanges(false, 0, null, null, null, null, $oPageGroup);
		foreach ($aoChangesUnfiltered as $oChange)
		{
			// only keep "revertable" changes
			if ($oChange->isRevertAvailable())
			{
				$aoChanges[] = $oChange;
			}
		}
		
		$sHistoryPageGroupLink = false;
		if (AnwCurrentSession::isActionGlobalAllowed("lastchanges"))
		{
			$sHistoryPageGroupLink = AnwUtils::aLink("lastchanges", array("pagegroup"=>$oPageGroup->getId()));
		}
		$this->out .= $this->tpl()->formRevert($this->linkMe(array("pagegroup"=>$oPageGroup->getId())), $aoChanges, $nRevToChangeId, $sHistoryPageGroupLink);
		
		foreach ($aaRevertPlan['DELETE'] as $oPageForDelete)
		{
			$this->out .= $this->tpl()->simulateDelete($oPageForDelete->getLang(), $oPageForDelete->getName());
		}
		
		foreach ($aaRevertPlan['REVERT'] as $aoRevertPages)
		{
			$oPageCurrent = $aoRevertPages[0];
			$oPageForRevert = $aoRevertPages[1];
			
			if ($oPageCurrent->isGlobalAndViewActionAllowed('diff'))
			{
				$sImgDiff = AnwUtils::xQuote(AnwUtils::pathImg("diff.gif"));
				$sAltDiff = AnwUtils::xQuote(self::g_("change_diff_link"));
				$sLnkDiff = AnwUtils::xQuote(AnwUtils::link($oPageCurrent, "diff", array("page"=>$oPageCurrent->getId(), "revfrom"=>$oPageCurrent->getChangeId(), "revto"=>$oPageForRevert->getChangeId())));
				$sLnkDiff = <<<EOF
<a href="$sLnkDiff" title="$sAltDiff" target="_blank"><img src="$sImgDiff" alt="$sAltDiff"/></a>
EOF;
			}
			else
			{
				$sLnkDiff = '';
			}
			$this->out .= $this->tpl()->simulateRevert($oPageCurrent->getLang(), $oPageCurrent->getName(), $oPageForRevert, $sLnkDiff);
		}
		
		foreach ($aaRevertPlan['RESTORE'] as $oPageForRestore)
		{
			$this->out .= $this->tpl()->simulateCreate($oPageForRestore);
		}
		
		foreach ($aaRevertPlan['KEEP'] as $oPageForKeep)
		{
			$this->out .= $this->tpl()->simulateKeep($oPageForKeep->getLang(), $oPageForKeep->getName());
		}
		
		$this->out .= $this->tpl()->end();
	}
	
	private function doRevert($oPageGroup, $aaRevertPlan)
	{
		$nTime = time();
		
		// simulation
		
		$aoAllFutureContents = array();
		
		foreach ($aaRevertPlan['REVERT'] as $aoRevertPages)
		{
			$oPageCurrent = $aoRevertPages[0];
			$oPageForRevert = $aoRevertPages[1];
			
			if (isset($aoAllFutureContents[$oPageForRevert->getLang()])) throw new AnwUnexpectedException("already have a content for this lang");			
			$aoAllFutureContents[$oPageForRevert->getLang()] = $oPageForRevert->getContent();
		}
		
		foreach ($aaRevertPlan['RESTORE'] as $oPageForRestore)
		{
			if (isset($aoAllFutureContents[$oPageForRestore->getLang()])) throw new AnwUnexpectedException("already have a content for this lang");
			$aoAllFutureContents[$oPageForRestore->getLang()] = $oPageForRestore->getContent();
		}
		
		foreach ($aaRevertPlan['KEEP'] as $oPageKept)
		{
			if (isset($aoAllFutureContents[$oPageKept->getLang()])) throw new AnwUnexpectedException("already have a content for this lang");
			$aoAllFutureContents[$oPageKept->getLang()] = $oPageKept->getContent();
		}
		
		// make sure that everything is in order... (or throws an exception)
		AnwPage::checkSimilarContents($aoAllFutureContents);
		
		
		// now, apply changes
		
		AnwStorage::transactionStart();
		try
		{
			//important, firstly delete pages which needs it, to avoid conflicts when reverting or creating pages
			foreach ($aaRevertPlan['DELETE'] as $oPageForDelete)
			{
				$sChangeComment = "delete for revert";
				$oPageForDelete->delete($nTime, $sChangeComment);
			}
			
			foreach ($aaRevertPlan['REVERT'] as $aoRevertPages)
			{
				$oPageCurrent = $aoRevertPages[0];
				$oPageForRevert = $aoRevertPages[1];
				
				$sChangeComment = "revert to old revision";
				$oPageCurrent->revertToRevision($oPageForRevert, $nTime, $sChangeComment);
			}
			
			foreach ($aaRevertPlan['RESTORE'] as $oPageForRestore)
			{
				$sChangeComment = "restore for revert";
				$oPageForRestore->restoreArchive($nTime, $sChangeComment);
			}
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		
		// redirect to reverted page if possible
		$oPageGroup->refresh();
		if (count($oPageGroup->getPages())>0)
		{
			$oPageRedirect = $oPageGroup->getPreferedPage();
			AnwUtils::redirect(AnwUtils::link($oPageRedirect));
		}
		else
		{
			// no page available, go home
			AnwUtils::redirect();
		}
	}
}
?>