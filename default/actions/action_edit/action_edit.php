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
 * Editing a Page.
 * @package Anwiki
 * @version $Id: action_edit.php 305 2010-09-12 15:21:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_edit extends AnwActionPage implements AnwHttpsAction
{
	private $oEditionForm;
	const CSS_FILENAME = 'action_edit.css';
	
	
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
		$this->lockPageForEdition(AnwLock::TYPE_PAGEGROUP);
		
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
				
		try
		{
			$this->getoPage()->checkPageGroupSynchronized();
			
			$this->setTitle( $this->t_('title', array('pagename'=>$this->getPageName())) );
			
			//decide what to do
			try
			{
				if (AnwEnv::_POST("publish"))
				{
					$this->saveEdition(AnwEnv::_POST("comment", ""), AnwEnv::_POST("draft"));
				}
				else if (AnwEnv::_POST("abort"))
				{
					$this->abortEdition();
				}/*
				else if(AnwEnv::_POST("updatedraft"))
				{
					$this->saveDraft(AnwEnv::_POST("comment", ""), AnwEnv::_POST("draft"), true);
				}
				else if(AnwEnv::_POST("savenewdraft"))
				{
					$this->saveDraft(AnwEnv::_POST("comment", ""), AnwEnv::_POST("draft"), false);
				}
				else if(AnwEnv::_POST("discarddraft"))
				{
					$this->deleteDraft(AnwEnv::_POST("draft"));
				}*/
				else if(AnwEnv::_GET("js")=="addmultiplecontentfield")
				{
					$sFieldName = AnwEnv::_GET("fieldname");
					$sSuffix = AnwEnv::_GET("suffix");
					$this->JS_AddMultipleContentField($sFieldName, $sSuffix);
				}
				else
				{
					if(AnwEnv::_POST("preview"))
					{
						$this->previewForm();
					}
					else
					{
						$this->editForm(AnwEnv::_POST("comment"), AnwEnv::_GET("draft"));
					}
				}
			}
			catch (AnwInvalidContentException $e)
			{
				$this->editForm( AnwEnv::_POST("comment"), AnwEnv::_POST("draft"), $this->g_("err_contentinvalid") );
			}
		}
		//we catch any exception to unlock the page
		catch(AnwException $e) //ie: acl php denied
		{
			//unlock
			$this->unlockPageForEdition();
			
			//throw again the exception
			throw $e;
		}
	}
	
	protected function editForm($sComment = "", $nDraftTime=null, $error=false)
	{	
		//TODO Drafts
		/*
		 
		if (!$bPosted) //no content posted - load it from DB
		{
			if ($nDraftTime)
			{
				//start edition from draft content
				$oDraft = $this->getoPage()->getDraft($nDraftTime);
				$sContent = $oDraft->getContent();
				$sComment = $oDraft->getComment();
			}
			else
			{
				//start edition from last page content
				$sContent = $this->getoPage()->getContent();
			}
		}*/
		
		$aaObservers = array();
		
		$oContentClass = $this->getoPage()->getPageGroup()->getContentClass();
		
		//$sHtmlDrafts = $this->getDraftInfo($nDraftTime); //drafts info
		$sHtmlDrafts = "";
		
		$sRenderForm = $this->getEditionForm()->getRender();
		
		//output edit form
		$sLang = $this->getoPage()->getLang();
		$sContentHtmlDir = $this->g_("local_html_dir");
		$bShowCaptcha = $this->needsCaptcha();
		$this->out .= $this->tpl()->editForm(	AnwUtils::link($this->getoPage(),"edit"), 
											$sContentHtmlDir,
											$this->getPageName(), 
											$sRenderForm, 
											$sLang, 
											$oContentClass->getLabel(),
											$sHtmlDrafts, 
											$nDraftTime,
											$sComment, 
											$bShowCaptcha,
											$error);
		
		
		//lock info
		$nLockType = AnwLock::TYPE_PAGEGROUP;
		$sLockPage = $this->getoPage()->getId();
		$aaObservers[] = array('INPUT' => 'comment', 'EVENT' => 'keypress');
		$this->lockObserver($nLockType, "edit_form", $aaObservers);
		
	}
	
	/*
	//TODO
	protected function getDraftInfo($nDraftTime)
	{
		$aoDrafts = $this->getoPage()->getDrafts();
		if (count($aoDrafts) > 0)
		{
			$sHtmlDrafts = $this->tpl()->draftsOpen();
			foreach ($aoDrafts as $oDraft)
			{
				$sDraftTime = Anwi18n::dateTime($oDraft->getTime());
				$sDraftComment = $oDraft->getComment();
				$sDraftUser = $oDraft->getUser()->getDisplayName();
				
				if ($oDraft->getTime() == $nDraftTime)
				{
					$sHtmlDrafts .= $this->tpl()->draftsLineCurrent($sDraftTime, $sDraftComment, $sDraftUser);
				}
				else
				{
					$sDraftLink = AnwUtils::link($this->getoPage(), "edit", array("draft"=>$oDraft->getTime()));
					$sHtmlDrafts .= $this->tpl()->draftsLine($sDraftTime, $sDraftComment, $sDraftUser, $sDraftLink);
				}
			}
			$sHtmlDrafts .= $this->tpl()->draftsClose();
		}
		else
		{
			$sHtmlDrafts = $this->tpl()->draftsNone();
		}
		return $sHtmlDrafts;
	}*/
	
	function needsCaptcha()
	{
		return (AnwCurrentSession::isLoggedIn() ? false : true);
	}
	
	protected function previewForm()
	{
		try {
			//check captcha
			if ($this->needsCaptcha()) $this->checkCaptcha();
			
			//update content from post
			$oEditContent = $this->getEditionForm()->updateContentFromEdition();
			
			//show again edit form on the top
			$this->editForm(AnwEnv::_POST("comment"), AnwEnv::_GET("draft"));
			
			//render preview
			try{
				$oHtml = $oEditContent->toHtml($this->getoPage());
				$oHtml->disableCaching();
				
				$sPreview = $oHtml->runHead().$oHtml->runBody();
				$this->out .= $this->tpl()->preview($sPreview, AnwUtils::cssViewContent($this->getoPage()));
				$this->headJsOnload( $this->tpl()->preview_jsOnload() );
			}
			catch(AnwException $e)
			{
				$this->out .= 'error';
			}
		}
		catch (AnwStructuredContentEditionFormException $e)
		{
			$sError = $e->getMessage();
			$this->editForm( AnwEnv::_POST("comment"), AnwEnv::_POST("draft"), $sError );
		}
		catch(AnwBadCaptchaException $e)
		{
			$sError = $this->g_("err_badcaptcha");
			$this->editForm( AnwEnv::_POST("comment"), AnwEnv::_POST("draft"), $sError );
		}
	}
	
	protected function saveEdition($sComment, $nDraftTime)
	{	
		try
		{
			//check captcha
			if ($this->needsCaptcha()) $this->checkCaptcha();
			
			//update content from post
			$oEditContent = $this->getEditionForm()->updateContentFromEdition();
			
			//save changes
			$this->getoPage()->saveEditAndDeploy($oEditContent, AnwChange::TYPE_PAGE_EDITION, $sComment);
			
			//delete old draft
			/*if ($nDraftTime)
			{
				$oDraft = $this->getoPage()->getDraft($nDraftTime);
				$oDraft->delete();
			}*/
			
			//unlock
			$this->unlockPageForEdition();
			
			//redirect
			AnwUtils::redirect(AnwUtils::link($this->getoPage()));
		}
		catch (AnwStructuredContentEditionFormException $e)
		{
			$sError = $e->getMessage();
			$this->editForm( $sComment, AnwEnv::_POST("draft"), $sError );
		}
		catch(AnwBadCaptchaException $e)
		{
			$sError = $this->g_("err_badcaptcha");
			$this->editForm( $sComment, AnwEnv::_POST("draft"), $sError );
		}
		catch (AnwBadCommentException $e)
		{
			$sError = $this->g_("err_badcomment");
			$this->editForm( $sComment, AnwEnv::_POST("draft"), $sError );
		}
		catch(AnwUnexpectedException $e)
		{
			$sError = $this->g_("err_ex_unexpected_p");
			$nErrorNumber = AnwDebug::reportError($e);
			if ( $nErrorNumber )
			{
				$sError .= '<br/>'.$this->g_("err_ex_report",array("errornumber"=>$nErrorNumber));
			}
			$this->editForm( $sComment, AnwEnv::_POST("draft"), $sError );
		}
	}
	
	
	protected function abortEdition()
	{
		//unlock
		$this->unlockPageForEdition();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getoPage()));
	}
	
	/*
	protected function saveDraft($sContent, $sComment, $nOldDraftTime=null, $deleteCurrent=false)
	{	
		try
		{
			//insert new draft
			$oDraftPage = $this->getoPage();
			$nDraftTime = time();
			$oDraftUser =  AnwCurrentSession::getUser();
			
			$oDraft = new AnwPageDraft($oDraftPage, $nDraftTime, $oDraftUser, $sContent, $sComment);
			$oDraft->save();
			
			//delete old draft
			if ($deleteCurrent && $nOldDraftTime)
			{
				$oDraft = $this->getoPage()->getDraft($nOldDraftTime);
				$oDraft->delete();
			}
			
			//redirect
			AnwUtils::redirect(AnwUtils::link($this->getoPage(), "edit", array("draft" => $nDraftTime)));
		}
		catch (AnwInvalidContentException $e)
		{
			$this->editForm( $sComment, AnwEnv::_POST("draft"), Anwi18n::t("err_edit_invalid") );
		}
		catch (AnwBadCommentException $e)
		{
			$this->editForm( $sComment, AnwEnv::_POST("draft"), Anwi18n::t("err_badcomment") );
		}
	}
	
	protected function deleteDraft($nDraftTime)
	{
		//delete current draft
		$oDraft = $this->getoPage()->getDraft($nDraftTime);
		$oDraft->delete();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getoPage(), "edit"));
	}*/
	
	
	protected function getEditionForm()
	{
		if (!$this->oEditionForm)
		{
			$sTarget = AnwUtils::link($this->getoPage(), $this->getName());			
			$this->oEditionForm = new AnwStructuredContentEditionFormPage($this->getoPage()->getContent(), $sTarget);
		}
		return $this->oEditionForm;
	}
	
	
	//-----------------
	
	protected function JS_AddMultipleContentField($sFieldName, $sSuffix)
	{
		//TODO search by prefix, not by fieldname
		$this->out = $this->getoPage()->getContent()->renderAdditionalEditInput($sFieldName, $sSuffix);
		$this->printOutputRaw();
	}
}

?>