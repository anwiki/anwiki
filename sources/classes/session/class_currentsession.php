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
 * CurrentSession gives a direct access to the current user's session.
 * @package Anwiki
 * @version $Id: class_currentsession.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwCurrentSession
{
	private static $oSession;
	const SESSION_CAPTCHA = "anw_captcha";
	const SESSION_LASTKEEPALIVE = "anw_lastkeepalive";
		
	
	//------------------------------------------------
	// SETTERS
	//------------------------------------------------
	
	static function login($sLogin, $sPassword, $bResume)
	{
		//authenticate
		$oUser = AnwUsers::authenticate($sLogin, $sPassword);
		
		//user is authenticated, open the session
		self::getSession()->login($oUser, $bResume);
		AnwSessions::login($oUser, $bResume);
		AnwPlugins::hook("user_loggedin", $oUser, $sPassword, $bResume);
	}
	
	static function logout()
	{
		$oUserTmp = self::getSession()->getUser();
		self::getSession()->logout();
		AnwSessions::logout();		
		AnwPlugins::hook("user_loggedout", $oUserTmp);
	}
	
	static function setLang($sLang)
	{
		self::getSession()->setLang($sLang);
		AnwSessions::setLang($sLang);
	}
	
	static function setTimezone($nTimezone)
	{
		self::getSession()->setTimezone($nTimezone);
		AnwSessions::setTimezone($nTimezone);
	}
	

	//------------------------------------------------
	// ACCESSORS
	//------------------------------------------------
	
	static function getUser()
	{
		return self::getSession()->getUser();
	}
	
	static function getLang()
	{
		return self::getSession()->getLang();
	}
	
	static function getId()
	{
		return self::getSession()->getId();
	}
	
	static function getTimezone()
	{
		return self::getSession()->getTimezone();
	}
	
	static function isLoggedIn()
	{
		return self::getSession()->isLoggedIn();
	}
	
	static function needsReauth()
	{
		return self::getSession()->needsReauth();
	}
	
	static function resetReauth()
	{
		self::getSession()->resetReauth();
		AnwSessions::resetReauth();
	}
	
	static function getSession()
	{
		if (!self::$oSession)
		{
			self::loadCurrentSession();
			if (!self::$oSession) throw new AnwUnexpectedException("No current session loaded");
		}
		return self::$oSession;
	}
	
	static function getIp()
	{
		return AnwEnv::getIp();
	}
	
	static function isActionAllowed($sPageName, $sAction, $sLang)
	{
		return AnwAcls::isActionAllowed(self::getUser(), $sPageName, $sAction, $sLang);
	}
	
	static function isActionGlobalAllowed($sAction)
	{
		return AnwAcls::isActionGlobalAllowed(self::getUser(), $sAction);
	}
	
	//------------------------------------------------
	// MANAGEMENT
	//------------------------------------------------
	
	private static function loadCurrentSession()
	{
		AnwDebug::startbench("Current session load");
		try
		{
			self::$oSession = AnwSessions::getCurrentSession();
			
			//keepalive
			$nElapsedTimeSinceKeepalive = time() - self::getLastKeepAlive();
			$nKeepAliveInterval = AnwComponent::globalCfgKeepaliveDelay();
			AnwDebug::log('(AnwSessions) Time elapsed since last keepalive: '.$nElapsedTimeSinceKeepalive.'/'.$nKeepAliveInterval.'s');
			if ($nElapsedTimeSinceKeepalive > $nKeepAliveInterval)
			{
				AnwDebug::log('(AnwSessions) Running keepalive...');
				$nTime = time();
				self::resetLastKeepAlive();
				
				//keepalive session
				AnwSessions::keepAlive();
				
				//run hooks
				$oSessionUser = self::$oSession->getUser();
				AnwPlugins::hook("session_keepalive_any", $oSessionUser);
				
				if (self::$oSession->isLoggedIn())
				{
					AnwPlugins::hook("session_keepalive_loggedin", $oSessionUser);
				}
				else
				{
					AnwPlugins::hook("session_keepalive_loggedout", $oSessionUser);
				}
			}			
		}
		catch(AnwUserNotFoundException $e)
		{
			//current user doesn't exist anymore
			self::$oSession = new AnwSession();
			self::logout();
		}
		AnwDebug::stopbench("Current session load");
	}
	
	private static function getLastKeepAlive()
	{
		if (!AnwEnv::_SESSION(self::SESSION_LASTKEEPALIVE))
		{
			AnwEnv::putSession(self::SESSION_LASTKEEPALIVE, 0);
		}
		return AnwEnv::_SESSION(self::SESSION_LASTKEEPALIVE);
	}
	
	private static function resetLastKeepAlive()
	{
		AnwEnv::putSession(self::SESSION_LASTKEEPALIVE, time());
	}
	
	static function setCaptcha($nNumber)
	{
		$nNumber = "$nNumber";
		AnwEnv::putSession(self::SESSION_CAPTCHA, md5($nNumber));
	}
	
	static function testCaptcha()
	{
		//retrieve typed number
		$nTestedNumber = AnwEnv::_POST("captcha");
		if (!$nTestedNumber)
		{
			$nTestedNumber = AnwEnv::_GET("captcha", 0);
		}
		$nTestedNumber = "$nTestedNumber";
		
		//compare
		$bTest = (AnwEnv::_SESSION(self::SESSION_CAPTCHA) && AnwEnv::_SESSION(self::SESSION_CAPTCHA) == md5($nTestedNumber));
		AnwEnv::unsetSession(self::SESSION_CAPTCHA);
		return $bTest;
	}
}

?>