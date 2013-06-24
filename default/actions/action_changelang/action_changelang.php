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
 * Changing the lang of a Page.
 * @package Anwiki
 * @version $Id: action_changelang.php 305 2010-09-12 15:21:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_changelang extends AnwActionPage
{
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function preinit()
	{
		$this->getoPage()->setSkipLoadingContent(true);
		$this->checkPageExists();
	}
	
	function run()
	{
		$this->lockPageForEdition(AnwLock::TYPE_PAGEGROUP);
		
		$this->setTitle( $this->t_('title',array("pagename"=>$this->getPageName())) );
		
		//decide what to do
		if(AnwEnv::_POST("abort"))
		{
			$this->abortChangeLang();
		}
		else if (AnwEnv::_POST("newlang") && AnwEnv::_POST("newlang") != $this->getoPage()->getLang())
		{
			$this->doChangeLang(AnwEnv::_POST("newlang", ""), AnwEnv::_POST("comment", ""));
		}
		else
		{
			$this->changeLangForm(	AnwEnv::_POST("newlang", $this->getoPage()->getLang()), 
									AnwEnv::_POST("comment", ""));
		}		
	}
	
	private function changeLangForm($sLang, $sComment, $error=false)
	{	
		//lock
		$nLockType = AnwLock::TYPE_PAGEGROUP;
		
		//langs
		$asAvailableLangs = $this->getoPage()->getPageGroup()->getAvailableLangs();
		$asAvailableLangs[] = $this->getoPage()->getLang();
		//check permission : create
		foreach ($asAvailableLangs as $i => $sAvailableLang)
		{
			if (!AnwCurrentSession::isActionAllowed($this->getoPage()->getName(), 'create', $sAvailableLang))
			{
				unset($asAvailableLangs[$i]);
			}
		}
		
		$selectlang = $this->tpl()->selectLang($asAvailableLangs, $this->getoPage()->getLang());
		$selectlang = '<select name="newlang" class="languages inselect">'.$selectlang.'</select>';
		
		$this->out .= $this->tpl()->changeLangForm($sLang, $sComment, $selectlang,
											$this->getoPage()->getName(), $this->linkMe(),
											$error);
		$aaObservers = array();
		$aaObservers[] = array('INPUT' => 'comment', 'EVENT' => 'keypress');
		$this->lockObserver($nLockType, "changelang_form", $aaObservers);
	}
	
	private function doChangeLang($sLang, $sComment)
	{	
		try
		{
			if (!AnwCurrentSession::isActionAllowed($this->getoPage()->getName(), 'create', $sLang))
			{
				throw new AnwAclException("permission create denied");
			}
			
			//change page lang
			$this->getoPage()->changeLang($sLang, $sComment);
			
			//unlock
			$this->unlockPageForEdition();
			
			//redirect
			AnwUtils::redirect(AnwUtils::link($this->getoPage()));
		}
		catch(AnwBadLangException $e)
		{
			$sError = $this->g_("err_badlang");
			$this->changeLangForm( $sLang, $sComment, $sError );
		}
		catch (AnwBadCommentException $e)
		{
			$sError = $this->g_("err_badcomment");
			$this->changeLangForm( $sLang, $sComment, $sError );
		}
		catch (AnwLangExistsForPageGroupException $e)
		{
			$sError = $this->g_("err_langexistsforpagegroup");
			$this->changeLangForm( $sLang, $sComment, $sError );
		}
	}
	
	private function abortChangeLang()
	{
		//unlock
		$this->unlockPageForEdition();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getoPage()));
	}
}

?>