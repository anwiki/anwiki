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
 * Creating massive new translations for multiple PageGroups.
 * @package Anwiki
 * @version $Id: action_newtranslations.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_newtranslations extends AnwActionGlobal
{
	function getNavEntry()
	{
		return $this->createManagementGlobalNavEntry();
	}
	
	function run()
	{
		$this->setTitle( $this->t_('title') );
		
		if (AnwEnv::_POST("addlang"))
		{
			$this->saveTranslations( AnwEnv::_POST("addlang") );
		}
		else
		{
			$this->showForm(AnwEnv::_GET("addlang",""));
		}
	}
	
	function showForm($sAddLang=null, $sError=false)
	{
		$this->out = "";
		
		$sDefaultLang = self::globalCfgLangDefault();
		$asAllLangs = self::globalCfgLangs();
		
		if (!in_array($sAddLang, $asAllLangs))
		{
			$sAddLang = $sDefaultLang;
		}
		
		$this->out .= $this->tpl()->startForm($this->linkMe(), $asAllLangs, $sAddLang, $sError);
		
		$bTranslationsAvailable = false;
		
		$aoPageGroups = AnwStorage::getPageGroups(true, null, null);
		foreach ($aoPageGroups as $oPageGroup)
		{
			$aoPages = $oPageGroup->getPages();
			if (!isset($aoPages[$sAddLang]))
			{
				//find PageRef
				if (isset($aoPages[$sDefaultLang]))
				{
					$oPageRef = $aoPages[$sDefaultLang];
				}
				else
				{
					$oPageRef = array_pop($aoPages);
				}
				
				//find default translation name
				$sPageNameDefault = $oPageRef->getTranslationNameDefault($sAddLang);
				$sInputName = $this->getInputName($oPageGroup);
				$sCheckBoxName = $this->getChkName($oPageGroup);
				$sInputRef = $this->getInputRef($oPageGroup);
				
				//check permission : translate
				if (AnwCurrentSession::isActionAllowed($sPageNameDefault, 'translate', $sAddLang))
				{
					$nPageRefId = $oPageRef->getId();
					$this->out .= $this->tpl()->newTranslationRow($oPageRef->getLang(), $oPageRef->getName(), $nPageRefId, $sAddLang, $sPageNameDefault, $sInputRef, $sInputName, $sCheckBoxName);
					$bTranslationsAvailable = true;
				}
			}
		}
		if ($bTranslationsAvailable)
		{
			$this->out .= $this->tpl()->submitButton();
		}
		else
		{
			$this->out .= $this->tpl()->noTranslation();
		}
		$this->out .= $this->tpl()->endForm();
	}
	
	private function getInputName($oPageGroup)
	{
		return 'newtr_name_'.$oPageGroup->getId();
	}
	
	private function getInputRef($oPageGroup)
	{
		return 'newtr_ref_'.$oPageGroup->getId();
	}
	
	private function getChkName($oPageGroup)
	{
		return 'newtr_'.$oPageGroup->getId();
	}
	
	private function saveTranslations($sAddLang)
	{
		try
		{
			if (!Anwi18n::langExists($sAddLang))
			{
				throw new AnwBadLangException();
			}
			
			$this->out .= $this->tpl()->startProcess();
			
			$bSomethingDone = false;
			$aoPageGroups = AnwStorage::getPageGroups(false, null, null);
			
			AnwStorage::transactionStart();
			try
			{
				foreach ($aoPageGroups as $oPageGroup)
				{
					$aoPages = $oPageGroup->getPages();
					$bChecked = AnwEnv::_POST($this->getChkName($oPageGroup));
					if (!isset($aoPages[$sAddLang]) && $bChecked)
					{
						$sTranslationName = AnwEnv::_POST($this->getInputName($oPageGroup));
						
						//check permissions : translate
						if (!AnwCurrentSession::isActionAllowed($sTranslationName, 'translate', $sAddLang))
						{
							throw new AnwAclException("permission translate denied");
						}
						
						//find PageRef
						$nPageRefId = (int)AnwEnv::_POST($this->getInputRef($oPageGroup));
						$oPageRef = new AnwPageById($nPageRefId);
						if (isset($aoPages[$oPageRef->getLang()]) && $oPageRef->getId() == $aoPages[$oPageRef->getLang()]->getId())
						{
							//create translation
							$oPageTranslation = $oPageRef->createNewTranslation($sTranslationName, $sAddLang);
							$this->out .= $this->tpl()->newTranslationCreated($sAddLang, $oPageTranslation->link());
							$bSomethingDone = true;
						}
					}
				}
				
				AnwStorage::transactionCommit();
			}
			catch(AnwException $e)
			{
				AnwStorage::transactionRollback();
				throw $e;
			}
			
			$sUrlContinue = $this->linkMe(array("addlang"=>$sAddLang));
			
			if (!$bSomethingDone)
			{
				AnwUtils::redirect($sUrlContinue);
			}
			
			$this->out .= $this->tpl()->endProcess($sUrlContinue);
		}
		catch(AnwBadPageNameException $e)
		{
			$this->showForm( $sAddLang, $this->g_("err_badpagename") );
		}
		catch(AnwBadLangException $e)
		{
			$this->showForm( $sAddLang, $this->g_("err_badlang") );
		}
		catch(AnwPageAlreadyExistsException $e)
		{
			$this->showForm( $sAddLang, $this->g_("err_pagealreadyexists") );
		}
		catch(AnwAclException $e)
		{
			$this->showForm( $sAddLang, $this->g_("err_nopermission") );
		}
		catch (AnwLangExistsForPageGroupException $e)
		{
			$this->showForm( $sAddLang, $this->g_("err_langexistsforpagegroup") );
		}
	}
	
}


?>