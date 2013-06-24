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
 * Comparing Page revisions.
 * @package Anwiki
 * @version $Id: action_diff.php 302 2010-09-12 13:45:20Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_diff extends AnwActionGlobal {
	
	function run() 
	{
		try
		{
			$nPageId = (int)AnwEnv::_GET("page");
			if ($nPageId <= 0)
			{
				throw new AnwBadCallException();
			}
			
			//find TO revision
			$nRevToChangeId = (int)AnwEnv::_GET("revto");
			if ($nRevToChangeId <= 0)
			{
				throw new AnwBadCallException();
			}
			else
			{
				$oPageRevTo = AnwPage::getPageByChangeId($nPageId, $nRevToChangeId);
			}
			
			//find FROM revision
			$nRevFromChangeId = (int)AnwEnv::_GET("revfrom");
			if ($nRevFromChangeId <= 0)
			{
				try
				{
					$oPageRevFrom = $oPageRevTo->getPreviousArchive();
				}
				catch(AnwArchiveNotFoundException $e)
				{
					//if TO revision is already the last...
					$oPageRevFrom = $oPageRevTo;
				}
			}
			else
			{
				$oPageRevFrom = AnwPage::getPageByChangeId($nPageId, $nRevFromChangeId);
			}
			
			
			if (!$oPageRevTo) throw new AnwBadCallException("page revision TO not found :".$nRevToChangeId);
			if (!($oPageRevTo->getContent() instanceof AnwContentPage)) throw new AnwUnexpectedException("error getcontent for page revision TO :".$nRevToChangeId);
			if (!$oPageRevFrom) throw new AnwBadCallException("page revision FROM not found :".$nRevFromChangeId);
			if (!($oPageRevFrom->getContent() instanceof AnwContentPage)) throw new AnwUnexpectedException("error getcontent for page revision FROM :".$nRevFromChangeId);
			
			// check permissions
			$oPageRevFrom->checkGlobalAndViewActionAllowed($this->getName());
			$oPageRevTo->checkGlobalAndViewActionAllowed($this->getName());
			
			$oContentXmlFrom = $oPageRevFrom->getContent()->toXml();
			$oContentXmlTo = $oPageRevTo->getContent()->toXml();
		}
		catch (AnwBadPageNameException $e)
		{
			$this->error($this->g_("err_badcall"));
		}
		catch (AnwBadCallException $e)
		{
			$this->error($this->g_("err_badcall"));
		}
		catch (AnwPageNotFoundException $e)
		{
			$this->error($this->g_("err_badcall"));
		}
		catch (AnwArchiveNotFoundException $e)
		{
			$this->error($this->g_("err_badcall"));
		}
		
		$this->setTitle( $this->t_("title", array("pagename"=>$oPageRevTo->getName())) );
		
		$oDiffs = new AnwDiffs($oContentXmlFrom, $oContentXmlTo);
		
		if ( !AnwCurrentSession::getUser()->isPhpEditionAllowed() )
		{
			$oDiffs->hidePhpCode();
		}
		else
		{
			$oDiffs->showPhpCode();
		}
		
		$this->out .= $this->tpl()->beforeDiffs($this->linkMe(), $oPageRevFrom, $oPageRevTo, $oPageRevTo->getActivePage());
		if ($oPageRevFrom->getChangeId() == $oPageRevTo->getChangeId())
		{
			$this->out .= $this->tpl()->drawNotice($this->t_("notice_same"));
		}
		if ($oPageRevFrom->getChangeId() > $oPageRevTo->getChangeId())
		{
			$this->out .= $this->tpl()->drawNotice($this->t_("notice_reverse"));
		}
		
		$this->renderDiffs($oDiffs);
	}
	
	//recursive function
	private function renderDiffs($oDiffs)
	{
		$aoDiffs = $oDiffs->getAllDiffs();
		
		foreach ($aoDiffs as $oDiff)
		{
			$sDiffType = $oDiff->getDiffType();
			switch($sDiffType)
			{
				case AnwDiff::TYPE_ADDED:
					$this->out .= $this->tpl()->diffAdded($oDiff);
					break;
					
				case AnwDiff::TYPE_DELETED:
					$this->out .= $this->tpl()->diffDeleted($oDiff);
					break;
					
				case AnwDiff::TYPE_KEPT:
					$this->out .= $this->tpl()->diffKept($oDiff);
					break;
					
				case AnwDiff::TYPE_EDITED:
					if (!$oDiff->isEmpty()) $this->out .= $this->tpl()->diffEdited($oDiff);
					break;
					
				case AnwDiff::TYPE_DIFFS:
					$sContentHtmlDir = $this->g_("local_html_dir");
					$this->out .= $this->tpl()->diffContainerOpen($sContentHtmlDir, $oDiff);
					$this->renderDiffs($oDiff); //recursive call
					$this->out .= $this->tpl()->diffContainerClose($oDiff);
					break;
					
				case AnwDiff::TYPE_MOVED:
					$this->out .= $this->tpl()->diffMoved($oDiff);
					break;
			}
		}
	}
}
?>