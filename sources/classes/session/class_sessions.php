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
 * Anwiki sessions manager.
 * @package Anwiki
 * @version $Id: class_sessions.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwSessions
{
	private static $oDriver;	
	
	
	//------------------------------------------------
	// SESSIONS MANAGEMENT
	//------------------------------------------------
	
	static function getCurrentSession()
	{
		self::debug("Getting current session...");
		$oSession = self::getDriver()->getCurrentSession();
		if ($oSession->isLoggedIn())
		{
			self::debug("Current session : user=".$oSession->getUser()->getId().", lang=".$oSession->getLang().", timezone=".$oSession->getTimezone());
		}
		else
		{
			self::debug("Current session : anonymous user, lang=".$oSession->getLang().", timezone=".$oSession->getTimezone());
		}
		self::debug("SessionId=".$oSession->getId());
		self::debug("SessionTimeStart=".date("Y-m-d H:i:s",$oSession->getTimeStart()));
		self::debug("SessionTimeSeen=".date("Y-m-d H:i:s",$oSession->getTimeSeen()));
		self::debug("SessionTimeAuth=".date("Y-m-d H:i:s",$oSession->getTimeAuth()));
		return $oSession;
	}
	
	static function keepAlive()
	{
		self::debug("Keepalive");
		return self::getDriver()->keepAlive();
	}
	
	static function setLang($sLang)
	{
		self::debug("Setting session lang : ".$sLang);
		return self::getDriver()->setLang($sLang);
	}
	
	static function setTimezone($nTimezone)
	{
		self::debug("Setting session timezone : ".$nTimezone);
		return self::getDriver()->setTimezone($nTimezone);
	}
	
	static function resetReauth()
	{
		self::debug("Reseting session reauthentication time");
		return self::getDriver()->resetReauth();
	}
	
	//------------------------------------------------
	// INTERNAL SESSION DRIVER
	//------------------------------------------------
	
	static function login($oUser, $bResume)
	{
		self::checkDriverInternal();
		self::debug("Opening session with user : ".$oUser->getId().($bResume?' [Remember me]':' [public]'));
		return self::getDriver()->login($oUser, $bResume);
	}
	
	static function logout()
	{
		self::checkDriverInternal();
		self::debug("Closing current session");
		return self::getDriver()->logout();
	}
	
	
	//------------------------------------------------
	// EXTERNAL SESSION DRIVER
	//------------------------------------------------
	
	function getLoginLink()
	{
		self::checkDriverExternal();
		return self::getDriver()->getLoginLink();
	}
	
	function getLogoutLink()
	{
		self::checkDriverExternal();
		return self::getDriver()->getLogoutLink();
	}
	
	
	//------------------------------------------------
	// DRIVERS MANAGEMENT
	//------------------------------------------------
	
	/**
	 * @throws AnwUnexpectedException
	 */
	static function loadDriver()
	{
		AnwDebug::startbench("Sessions driver init");
		
		self::$oDriver = AnwSessionsDriver::loadComponent(AnwComponent::globalCfgDriverSessions());
		self::$oDriver->init();
		
		if (self::isDriverInternal())
		{
			self::debug("Sessions Driver loaded : internal");
		}
		else if (self::isDriverExternal())
		{
			self::debug("Sessions Driver loaded : external");
		}
		else throw new AnwUnexpectedException("Unknown sessionsdriver type");
		AnwDebug::stopbench("Sessions driver init");
	}
	
	static function isDriverInternal()
	{
		return (self::getDriver() instanceof AnwSessionsDriverInternal);
	}
	
	static function isDriverExternal()
	{
		return (self::getDriver() instanceof AnwSessionsDriverExternal);
	}
	
	static function isReauthSupported()
	{
		return (self::getDriver() instanceof AnwSessionsCapability_reauth);
	}
	
	static function isResumeEnabled()
	{
		return (AnwComponent::globalCfgSessionResumeEnabled() 
				&& self::getDriver() instanceof AnwSessionsCapability_resume);
	}
	
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function getDriver()
	{
		if (!self::$oDriver)
		{
			self::loadDriver();
			if (!self::$oDriver) throw new AnwUnexpectedException("No sessions driver loaded");
		}
		return self::$oDriver;
	}
	
	private static function checkDriverInternal()
	{
		if (!self::isDriverInternal())
		{
			throw new AnwUnexpectedException("No INTERNAL sessions driver loaded");
		}
	}
	
	private static function checkDriverExternal()
	{
		if (!self::isDriverExternal())
		{
			throw new AnwUnexpectedException("No EXTERNAL sessions driver loaded");
		}
	}
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwSessions)".$sMessage);
	}
}

?>