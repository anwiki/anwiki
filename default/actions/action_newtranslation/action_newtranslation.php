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
 * Creating a new translation for a PageGroup.
 * @package Anwiki
 * @version $Id: action_newtranslation.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_newtranslation extends AnwActionPage
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
			$this->saveTranslation();
		}
		else
		{
			$this->showForm();
		}
	}
	
	function showForm($sError=false)
	{
		$sLink = AnwUtils::link($this->getoPage(), "newtranslation");
		$sCurrentPageName = $this->getoPage()->getName(); 
		
		$asAvailableLangs = $this->getoPage()->getPageGroup()->getAvailableLangs();
		
		$this->out .= $this->tpl()->newTranslationStart($sLink, $sCurrentPageName, $this->getoPage()->getLang(), $sError);
		
		foreach ($asAvailableLangs as $sAvailableLang)
		{
			$sPageNameDefault = $this->getoPage()->getTranslationNameDefault($sAvailableLang);
			$sInputName = $this->getInputName($sAvailableLang);
			$sCheckBoxName = $this->getChkName($sAvailableLang);
			
			//check permission : translate
			if (AnwCurrentSession::isActionAllowed($sPageNameDefault, 'translate', $sAvailableLang))
			{
				$this->out .= $this->tpl()->newTranslationRow($sAvailableLang, $sPageNameDefault, $sInputName, $sCheckBoxName);
			}
		}
		
		$this->out .= $this->tpl()->newTranslationStop();
	}
	
	private function getInputName($sLang)
	{
		return 'newtranslation_name_'.$sLang;
	}
	
	private function getChkName($sLang)
	{
		return 'newtranslation_'.$sLang;
	}
	
	private function saveTranslation()
	{
		try
		{
			$asAvailableLangs = $this->getoPage()->getPageGroup()->getAvailableLangs();
			
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
			
			$oPageTranslation = null;
			AnwStorage::transactionStart();
			try
			{
				foreach ($asAvailableLangs as $sLang)
				{
					if (AnwEnv::_POST($this->getChkName($sLang)))
					{
						$sTranslationName = AnwEnv::_POST($this->getInputName($sLang), "");
						//create translation
						$oPageTranslation = $this->getoPage()->createNewTranslation($sTranslationName, $sLang);
					}
				}
				
				AnwStorage::transactionCommit();
			}
			catch(AnwException $e)
			{
				AnwStorage::transactionRollback();
				throw $e;
			}

			if ($oPageTranslation)
			{
				// redirect to last created translation
				AnwUtils::redirect(AnwUtils::link($oPageTranslation));
			}
			else
			{
				// no translation was created, show form again
				$this->showForm();
			}
		}
		catch(AnwBadPageNameException $e)
		{
			$this->showForm( $this->g_("err_badpagename") );
		}
		catch(AnwBadLangException $e)
		{
			$this->showForm( $this->g_("err_badlang") );
		}
		catch(AnwPageAlreadyExistsException $e)
		{
			$this->showForm( $this->g_("err_pagealreadyexists") );
		}
		catch(AnwAclException $e)
		{
			$this->showForm( $this->g_("err_nopermission") );
		}
		catch (AnwLangExistsForPageGroupException $e)
		{
			$this->showForm( $this->g_("err_langexistsforpagegroup") );
		}
	}
	
}


?>