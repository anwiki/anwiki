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
 * Logging in.
 * @package Anwiki
 * @version $Id: action_login.php 257 2010-03-10 20:52:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_login extends AnwActionGlobal implements AnwHttpsAction, AnwPublicAction, AnwAlwaysEnabledAction
{
	function run()
	{
		$sError = false;
		
		$this->setTitle( $this->t_('title') );
		
		if (AnwEnv::_POST("submit"))
		{
			$sLogin = AnwEnv::_POST("login", "");
			$sPassword = AnwEnv::_POST("password", "");
			$bRememberMe = (AnwSessions::isResumeEnabled() && AnwEnv::_POST("remember") ? true : false);
			$sUrlRedirect = AnwEnv::_POST("redirect", "");
			
			try
			{
				//try to authenticate and open the session
				AnwCurrentSession::login($sLogin, $sPassword, $bRememberMe);
				
				$this->redirectInfo($sUrlRedirect, $this->t_("t_loggedin"), $this->t_("p_loggedin"));
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
			
			//error occurred, display again the login form
			$this->showLoginForm($sLogin, $sUrlRedirect, $bRememberMe, $sError);
		}
		else
		{
			//arriving on the form
			$this->showLoginForm("", AnwEnv::_GET("redirect",""), false);
		}
	}
	
	private function showLoginForm($sLogin, $sUrlRedirect, $bRememberMe, $sError=false)
	{
		//display login form
		$sFormAction = AnwUtils::alink("login");
		$this->out .= $this->tpl()->loginForm($sFormAction, $sLogin, $sUrlRedirect, $bRememberMe, AnwSessions::isResumeEnabled(), $sError);
		$this->headJsOnload( $this->tpl()->loginFormJs() );
	}
}

?>