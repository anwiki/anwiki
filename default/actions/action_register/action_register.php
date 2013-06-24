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
 * Creating a new User account.
 * @package Anwiki
 * @version $Id: action_register.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_register extends AnwActionGlobal implements AnwHttpsAction, AnwPublicAction
{
	function run()
	{
		if (!self::globalCfgUsersRegisterEnabled()) AnwUtils::redirect();
		
		$this->setTitle( $this->t_('title') );
		
		$sError = false;
		$sLogin = "";
		$sDisplayName = "";
		$sEmail = "";
		
		if (AnwEnv::_POST("submit"))
		{
			$sLogin = AnwEnv::_POST("login", "");
			$sDisplayName = AnwEnv::_POST("displayname", "");
			$sEmail = AnwEnv::_POST("email", "");
			$sPassword = AnwEnv::_POST("password", "");
						
			//try to register
			try
			{
				$this->checkCaptcha();
				$sLang = AnwCurrentSession::getLang();
				$nTimezone = AnwCurrentSession::getTimezone();
				$oUser = AnwUsers::createUser($sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone, $sPassword);
				
				AnwCurrentSession::login($sLogin, $sPassword, false); //open a public time-limited session
				$this->redirectInfo(false, $this->t_("t_created"), $this->t_("p_created"));
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
		}
		//display register form
		$this->out .= $this->tpl()->registerForm(AnwUtils::alink("register"), $sLogin, $sDisplayName, $sEmail, $sError);	
	}
}

?>