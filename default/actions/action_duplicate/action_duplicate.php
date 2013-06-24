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
 * Duplicating a page, eventually with its translations.
 * @package Anwiki
 * @version $Id: action_duplicate.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_duplicate extends AnwActionPage
{
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function preinit()
	{
		//we need to load all contents to check pagegroup synchro
		$this->getoPage()->setSkipLoadingTranslationsContent(false);
		$this->checkPageExists();
	}
	
	function run()
	{
		$this->getoPage()->checkPageGroupSynchronized();
		
		$this->setTitle( $this->t_('title', array('pagename'=>$this->getPageName())) );
		
		if (AnwEnv::_POST("abort"))
		{
			AnwUtils::redirect( AnwUtils::link($this->getoPage()) );
		}
		else if (AnwEnv::_POST("submit"))
		{
			$this->saveDuplication();
		}
		else
		{
			$this->showForm();
		}
	}
	
	function showForm($sError=false)
	{
		$sLink = AnwUtils::link($this->getoPage(), "duplicate");
		$sCurrentPageName = $this->getoPage()->getName();
		$sCurrentPageLang = $this->getoPage()->getLang();
		
		$aoTranslations = $this->getoPage()->getPageGroup()->getPages($this->getoPage());
		
		$this->out .= $this->tpl()->duplicateStart($sLink, $sCurrentPageName, $sCurrentPageLang, $sError);
		
		$sInputName = $this->getInputName($this->getoPage());
		$this->out .= $this->tpl()->duplicateRowCurrent($sCurrentPageLang, $sCurrentPageName, $sInputName);
		
		foreach ($aoTranslations as $oTranslation)
		{
			$sTranslationPageName = $oTranslation->getName();
			$sInputName = $this->getInputName($oTranslation);
			$sCheckBoxName = $this->getChkName($oTranslation);
			
			$this->out .= $this->tpl()->duplicateRow($oTranslation->getLang(), $sTranslationPageName, $sInputName, $sCheckBoxName);
		}
		
		$this->out .= $this->tpl()->duplicateStop();
	}
	
	private function getInputName($oTranslation)
	{
		return 'duplicate_name_'.$oTranslation->getId();
	}
	
	private function getChkName($oTranslation)
	{
		return 'duplicate_'.$oTranslation->getId();
	}
	
	private function saveDuplication()
	{
		$this->out .= $this->tpl()->beginProcessDuplication($this->getoPage()->getName(), $this->getoPage()->getLang());
		
		$aoTranslations = $this->getoPage()->getPageGroup()->getPages($this->getoPage());
		
		//which pages should we duplicate?
		$aaPagesForDuplication = array();
		$oPage = $this->getoPage();
		$aaPagesForDuplication[] = array(
			'LANG' => $oPage->getLang(),
			'NAME' => AnwEnv::_POST($this->getInputName($oPage), ""),
			'CONTENT' => $oPage->getContent()->toXmlString()
		);
		
		foreach ($aoTranslations as $oTranslation)
		{
			if (AnwEnv::_POST($this->getChkName($oTranslation)))
			{
				$aaPagesForDuplication[] = array(
					'LANG' => $oTranslation->getLang(),
					'NAME' => AnwEnv::_POST($this->getInputName($oTranslation), ""),
					'CONTENT' => $oTranslation->getContent()->toXmlString()
				);
			}
		}
		
		
		AnwStorage::transactionStart();
		try
		{
			$oFirstPage = null;
			$oContentClass = $this->getoPage()->getPageGroup()->getContentClass();
			
			//duplicate each page
			foreach ($aaPagesForDuplication as $asPageForDuplication)
			{
				$asNotices = array();
				
				$sPageName = $asPageForDuplication['NAME'];
				$sPageLang = $asPageForDuplication['LANG'];
				$sPageContent = $asPageForDuplication['CONTENT'];
			
				$asNotices = $this->checkPermissions($sPageName, $sPageLang, $sPageContent);
				
				if (count($asNotices) == 0)
				{
					$oContent = $oContentClass->rebuildContentFromXml($sPageContent);
					
					if (!$oFirstPage)
					{
						$oPage = AnwPage::createNewPage($oContentClass, $sPageName, $sPageLang, "", $oContent);
						$oFirstPage = $oPage;
					}
					else
					{
						$oPage = $oFirstPage->createNewTranslation($sPageName, $sPageLang, "", $oContent);
					}
					
					$this->out .= $this->tpl()->rowProcessDuplication_success($oPage->link());
				}
				else
				{
					$this->out .= $this->tpl()->rowProcessDuplication_failed($sPageName, $sPageLang, $asNotices);
				}
			}
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		$this->out .= $this->tpl()->endProcessDuplication();
	}
	
	protected function checkPermissions($sPageName, $sPageLang, $sPageContent)
	{
		$asNotices = array();
		
		//check that page don't exist
		if (!AnwPage::isAvailablePageName($sPageName))
		{
			$asNotices[] = $this->t_("notice_exists");
		}
		
		//check PHP permission
		if (AnwUtils::contentHasPhpCode($sPageContent) && !AnwCurrentSession::getUser()->isPhpEditionAllowed())
		{
			$asNotices[] = $this->t_("notice_php");
		}
		
		//check JS permission
		if (AnwUtils::contentHasJsCode($sPageContent) && !AnwCurrentSession::getUser()->isJsEditionAllowed())
		{
			$asNotices[] = $this->t_("notice_js");
		}
		
		//check ACL permission : create and edit
		if (!AnwCurrentSession::isActionAllowed($sPageName, "create", $sPageLang) 
			||!AnwCurrentSession::isActionAllowed($sPageName, "edit", $sPageLang))
		{
			$asNotices[] = $this->t_("notice_acl");
		}
		
		return $asNotices;
	}
	
}


?>