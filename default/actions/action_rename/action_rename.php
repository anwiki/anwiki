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
 * Renaming a Page.
 * @package Anwiki
 * @version $Id: action_rename.php 305 2010-09-12 15:21:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_rename extends AnwActionPage
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
		
		$this->setTitle( $this->t_('title', array('pagename'=>$this->getPageName())) );
		
		//decide what to do
		if(AnwEnv::_POST("abort"))
		{
			$this->abortRename();
		}
		else if (AnwEnv::_POST("newname") && AnwEnv::_POST("newname") != $this->getoPage()->getName())
		{
			$this->doRename(AnwEnv::_POST("newname", ""), AnwEnv::_POST("comment", ""), (bool)AnwEnv::_POST("updatelinks", true));
		}
		else
		{
			$this->renameForm(	AnwEnv::_POST("newname", $this->getoPage()->getName()), 
								AnwEnv::_POST("comment", ""));
		}		
	}
	
	private function renameForm($sNewName, $sComment, $error=false)
	{	
		//pages referring to this page
		$aoPageGroups = $this->getoPage()->getPageGroup()->getPageGroupsLinking();
		$this->out .= $this->tpl()->renameForm($sNewName, $this->getoPage()->getLang(), $sComment,
											$this->getoPage()->getName(), $this->linkMe(), $aoPageGroups,
											$error);
		
		//lock
		$aaObservers = array();
		$aaObservers[] = array('INPUT' => 'newname', 'EVENT' => 'keypress');
		$aaObservers[] = array('INPUT' => 'comment', 'EVENT' => 'keypress');
		$this->lockObserver(AnwLock::TYPE_PAGEGROUP, "rename_form", $aaObservers);
	}
	
	private function doRename($sNewName, $sComment, $bUpdateLinks)
	{	
		$nTime = time();
		
		try
		{
			if (!AnwCurrentSession::isActionAllowed($sNewName, 'create', $this->getoPage()->getLang()))
			{
				throw new AnwAclException("permission create denied");
			}
			
			$oPageTest = new AnwPageByName($sNewName);
			$oPageTest->setSkipLoadingContent(true);
			if ($oPageTest->exists())
			{
				throw new AnwPageAlreadyExistsException();
			}
			
			$sOldName = $this->getoPage()->getName();
			
			//rename page
			$this->getoPage()->rename($sNewName, $bUpdateLinks);
			
			//unlock
			$this->unlockPageForEdition();
			
			//redirect
			AnwUtils::redirect(AnwUtils::link($sNewName));
		}
		catch (AnwBadPageNameException $e)
		{
			$sError = $this->g_("err_badpagename");
			$this->renameForm( $sNewName, $sComment, $sError );
		}
		catch (AnwBadCommentException $e)
		{
			$sError = $this->g_("err_badcomment");
			$this->renameForm( $sNewName, $sComment, $sError );
		}
		catch (AnwPageAlreadyExistsException $e)
		{
			$sError = $this->g_("err_pagealreadyexists");
			$this->renameForm( $sNewName, $sComment, $sError );
		}
		catch(AnwAclException $e)
		{
			$sError = $this->g_("err_nopermission");
			$this->renameForm( $sNewName, $sComment, $sError );
		}
	}
	
	private function abortRename()
	{
		//unlock
		$this->unlockPageForEdition();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getoPage()));
	}
}

?>