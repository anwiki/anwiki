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
 * Creating a new Page.
 * @package Anwiki
 * @version $Id: action_create.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_create extends AnwActionPage
{
	function checkActionAllowed()
	{
		//we don't know the lang for the moment
		if (!AnwCurrentSession::isActionAllowed($this->getoPage()->getName(), 'create', -1))
		{
			throw new AnwAclException("You are not allowed to execute this action on this page");
		}
	}
	
	function preinit()
	{
		$this->getoPage()->setSkipLoadingContent(true);
	}
	
	function run()
	{
		if ($this->getoPage()->exists())
		{
			AnwUtils::redirect( AnwUtils::link($this->getoPage() ) );
		}
		
		if ( AnwEnv::_POST("lang") && AnwEnv::_POST("contentclass") )
		{	
			$this->createPageProcess(AnwEnv::_POST("lang"), AnwEnv::_POST("contentclass"));
		}
		else
		{
			$this->createForm( AnwEnv::_GET("lang") );
		}
	}
	
	private function createForm($sCreateLang)
	{
		$this->setTitle($this->t_("title", array('pagename'=>$this->getPageName())));
		
		//primary lang for page creation
		$sDefaultLang = self::globalCfgLangDefault();
		$asAllLangs = self::globalCfgLangs();
		
		if (!in_array($sCreateLang, $asAllLangs))
		{
			$sCreateLang = $sDefaultLang;
		}
		
		$asAvailableLangs = self::globalCfgLangs();
		foreach ($asAvailableLangs as $i => $sAvailableLang)
		{
			if (!AnwCurrentSession::isActionAllowed($this->getoPage()->getName(), 'create', $sAvailableLang))
			{
				unset($asAvailableLangs[$i]);
			}
		}
		
		$this->out .= $this->tpl()->startForm(
			$this->getPageName(), 
			AnwUtils::link($this->getoPage(), "create"),
			$sCreateLang,
			$asAvailableLangs,
			AnwContentClasses::getContentClasses()
		);
		
		foreach ($asAvailableLangs as $sAvailableLang)
		{
			if ($sAvailableLang!=$sCreateLang)
			{
				//find default translation name
				$sPageNameDefault = AnwPage::buildTranslationNameDefault($this->getPageName(), $sCreateLang, $sAvailableLang);
				$sInputName = $this->getInputName($sAvailableLang);
				$sCheckBoxName = $this->getChkName($sAvailableLang);
				
				$this->out .= $this->tpl()->translationRow($sAvailableLang, $sPageNameDefault, $sCheckBoxName, $sInputName);
			}
			else
			{
				$this->out .= $this->tpl()->translationRowCurrent($sCreateLang, $this->getPageName());
			}
		}
		
		$this->out .= $this->tpl()->endForm(
			AnwUtils::link(self::globalCfgHomePage())
		);
	}
	
	private function createPageProcess($sLang, $sContentClass)
	{	
		$sPageName = $this->getPageName();
		
		if (!AnwCurrentSession::isActionAllowed($sPageName, 'create', $sLang))
		{
			throw new AnwAclException("permission create denied");
		}
		
		$oContentClass = AnwContentClasses::getContentClass($sContentClass);
		
		AnwStorage::transactionStart();
		try
		{		
			//create page
			$oPage = AnwPage::createNewPage($oContentClass, $sPageName, $sLang);
			
			//should we create translations for this new page?
			$asAvailableLangs = $oPage->getPageGroup()->getAvailableLangs();
				
			//check permissions : translate
			foreach ($asAvailableLangs as $sLang)
			{
				if (AnwEnv::_POST($this->getChkName($sLang)))
				{
					$sTranslationName = AnwEnv::_POST($this->getInputName($sLang), "");
					if (!AnwCurrentSession::isActionAllowed($sTranslationName, 'translate', $sLang))
					{
						throw new AnwAclException("permission translate denied");
					}
				}
			}
			
			foreach ($asAvailableLangs as $sLang)
			{
				if (AnwEnv::_POST($this->getChkName($sLang)))
				{
					$sTranslationName = AnwEnv::_POST($this->getInputName($sLang), "");
					//create translation
					$oPageTranslation = $oPage->createNewTranslation($sTranslationName, $sLang);
				}
			}
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		
		AnwUtils::redirect(AnwUtils::link($oPage, "edit"));
	}
	
	protected function getChkName($sLang)
	{
		return "create_lang_chk_".$sLang;
	}
	
	protected function getInputName($sLang)
	{
		return "create_lang_pagename_".$sLang;
	}

}

?>