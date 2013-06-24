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
 * Install step : grant admin permissions.
 * @package Anwiki
 * @version $Id: stepinstall_grant_admin.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwStepInstallDefault_grant_admin extends AnwStepInstall
{
	function run()
	{
		if (AnwAcls::isDriverReadWrite())
		{
			if (AnwEnv::_POST("submit_create") && AnwUsers::isDriverInternal())
			{
				$this->createAndGrant(AnwEnv::_POST("login"), AnwEnv::_POST("displayname"), AnwEnv::_POST("email"), AnwEnv::_POST("password"));
			}
			else if (AnwEnv::_POST("submit_existing"))
			{
				$this->chooseAndGrant(AnwEnv::_POST("login"), AnwEnv::_POST("password"));
			}
			else if (AnwEnv::_GET("skipgrant"))
			{
				$this->skipGrant();
			}
			else
			{
				$this->showChooseGrant();
			}
		}
		else
		{
			$this->showGrantNotSupported();
		}
	}
	
	protected function showGrantNotSupported()
	{
		$this->out .= $this->tpl()->chooseGrantNotSupported($this->linkSkipGrant(), $this->linkStep(AnwStepInstall::STEP_CFG_GLOBAL));
	}
	
	protected function linkSkipGrant()
	{
		return $this->linkMe()."&skipgrant=1";
	}
	
	protected function skipGrant()
	{
		//update step status
		$this->getActionInstall()->updateStepStatusNext();		
		AnwUtils::redirect($this->linkStepNext());
	}
	
	protected function showChooseGrant($sLogin="", $sDisplayName="", $sEmail="", $sExistingLogin="", $sErrorCreate="", $sErrorExisting="")
	{
		$this->out .= $this->tpl()->chooseGrant($this->linkMe(), AnwUsers::isDriverInternal(), $sLogin, $sDisplayName, $sEmail, $sExistingLogin, $sErrorCreate, $sErrorExisting);
	}
	
	protected function createAndGrant($sLogin, $sDisplayName, $sEmail, $sPassword)
	{
		try
		{
			//try to register
			$sLang = AnwCurrentSession::getLang();
			$nTimezone = AnwCurrentSession::getTimezone();
			$oUser = AnwUsers::createUser($sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone, $sPassword);
			$this->grantUserAdmin($oUser);
			return;
		}
		catch(AnwLoginAlreadyTakenException $e)
		{
			$sError = $this->g_("err_loginalreadytaken");
		}
		catch(AnwBadLoginException $e)
		{
			$sError = $this->g_("err_badlogin");
		}
		catch(AnwDisplayNameAlreadyTakenException $e)
		{
			$sError = $this->g_("err_displaynamealreadytaken");
		}
		catch(AnwBadDisplayNameException $e)
		{
			$sError = $this->g_("err_baddisplayname");
		}
		catch(AnwEmailAlreadyTakenException $e)
		{
			$sError = $this->g_("err_emailalreadytaken");
		}
		catch(AnwBadEmailException $e)
		{
			$sError = $this->g_("err_bademail");
		}
		catch(AnwBadPasswordException $e)
		{
			$sError = $this->g_("err_badpassword");
		}
		catch(AnwBadCaptchaException $e)
		{
			$sError = $this->g_("err_badcaptcha");
		}
		$this->showChooseGrant($sLogin, $sDisplayName, $sEmail, "", $sError);
	}
	
	protected function chooseAndGrant($sLogin, $sPassword)
	{
		try
		{
			//try to authenticate
			$oUser = AnwUsers::authenticate($sLogin, $sPassword);	
			$this->grantUserAdmin($oUser);
			return;
		}
		catch(AnwAuthException $e)
		{
			$sError = $this->g_("err_auth");
		}
		catch(AnwBadLoginException $e)
		{
			$sError = $this->g_("err_badlogin");
		}
		catch(AnwBadPasswordException $e)
		{
			$sError = $this->g_("err_badpassword");
		}
		$this->showChooseGrant("", "", "", $sLogin, "", $sError);
	}
	
	protected function grantUserAdmin($oUser)
	{
		//grant admin privileges
		AnwAcls::grantUserAdminOnInstall($oUser);		
		
		//open the session
		AnwSessions::login($oUser, false); //squeezing AnwCurrentSession...
		
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
		
		AnwUtils::redirect($this->linkStepNext());
	}
}

?>