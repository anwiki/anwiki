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
 * Anwiki users manager.
 * @package Anwiki
 * @version $Id: class_users.php 265 2010-06-20 09:09:10Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwUsers
{
	private static $oDriver;
	private static $asCachedUsers = array();
	
	
	//------------------------------------------------
	// USERS MANAGEMENT
	//------------------------------------------------
	
	/**
	 * @throws AnwUserNotFoundException
	 */
	static function getUser($nUserId)
	{
		if(!isset(self::$asCachedUsers[$nUserId]))
		{		
			self::debug("Getting an user : ".$nUserId);
			AnwDebug::startBench("getUser");
			try
			{
				self::$asCachedUsers[$nUserId] = self::getDriver()->getUser($nUserId);
			}
			catch(AnwUserNotFoundException $e)
			{
				self::$asCachedUsers[$nUserId] = -1;
				throw new AnwUserNotFoundException();
			}
			AnwDebug::stopBench("getUser");
		}
		else
		{
			if (!is_int(self::$asCachedUsers[$nUserId]))
			{
				self::debug("Getting an user : ".$nUserId." - Already in cache");
			}
			else
			{
				self::debug("Getting an user : ".$nUserId." - Already in cache / AnwUserNotFoundException");
				throw new AnwUserNotFoundException();
			}
		}
		return self::$asCachedUsers[$nUserId];
	}
	
	static function getUserByLogin($sUserLogin)
	{
		//no cache needed here, function not called often
		self::debug("Getting an user by login : ".$sUserLogin);
		if (!self::isValidLogin($sUserLogin))
		{
			throw new AnwBadLoginException();
		}
		return self::getDriver()->getUserByLogin($sUserLogin);
	}
	
	/**
	 * Only used with internal sessiondriver
	 */
	static function authenticate($sLogin, $sPassword)
	{
		if (!self::isValidLogin($sLogin))
		{
			throw new AnwBadLoginException();
		}
		if (!self::isValidPassword($sPassword))
		{
			throw new AnwBadPasswordException();
		}
		$oUser = self::getDriver()->authenticate($sLogin, $sPassword); //throws exception if authentication fails
		return $oUser;
	}
	
	/**
	 * These check functions must not be called directly, but from AnwUtils. 
	 */
	static function isValidLogin($sLogin)
	{
		//check allowed chars
		if (strip_tags($sLogin) != $sLogin)
		{
			return false;
		}
		
		//driver check
		if (!self::getDriver()->isValidLogin($sLogin))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_valid_login', $sLogin);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	static function isValidDisplayName($sDisplayName)
	{
		//check allowed chars
		if (strip_tags($sDisplayName) != $sDisplayName)
		{
			return false;
		}
		
		//driver check
		if (!self::getDriver()->isValidDisplayName($sDisplayName))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_valid_displayname', $sDisplayName);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	static function isValidEmail($sEmail)
	{
		//check allowed chars
		if (strip_tags($sEmail) != $sEmail)
		{
			return false;
		}
		
		//driver check
		if (!self::getDriver()->isValidEmail($sEmail))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_valid_email', $sEmail);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	static function isValidPassword($sPassword)
	{
		//check allowed chars
		if (strip_tags($sPassword) != $sPassword)
		{
			return false;
		}
		
		//driver check
		return self::getDriver()->isValidPassword($sPassword);
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_valid_password', $sPassword);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}
		return true;
	}
	
	static function isValidTimezone($nTimezone)
	{
		return in_array($nTimezone, self::getTimezones());
	}
	
	static function getTimezones()
	{
		return array(-12,-11,-10,-9,-8,-7,-6,-5,-4,-3,-2,-1,0,1,2,3,4,5,6,7,8,9,10,11,12);
	}
	
	static function isAvailableLogin($sLogin)
	{
		//driver check
		if (!self::getDriver()->isAvailableLogin($sLogin))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_available_login', $sLogin);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	static function isAvailableDisplayName($sDisplayName)
	{
		//skip if non unique emails are allowed
		if (AnwUsers::isNonUniqueDisplayNameAllowed())
		{
			return true;
		}
		
		//driver check		
		if (!self::getDriver()->isAvailableDisplayName($sDisplayName))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_available_displayname', $sDisplayName);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	static function isAvailableEmail($sEmail)
	{
		$sEmail = strtolower($sEmail);
		
		//skip if non unique emails are allowed
		if (AnwUsers::isNonUniqueEmailAllowed())
		{
			return true;
		}
		
		//driver check
		if (!self::getDriver()->isAvailableEmail($sEmail))
		{
			return false;
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_available_email', $sEmail);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	
	//------------------------------------------------
	// INTERNAL USERS DRIVER
	//------------------------------------------------
	
	static function createUser($sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone, $sPassword)
	{
		self::debug("Creating user : ".$sLogin);
		
		$sEmail = strtolower($sEmail);
		
		if (!self::isValidLogin($sLogin))
		{
			throw new AnwBadLoginException();
		}
		if (!self::isValidDisplayName($sDisplayName))
		{
			throw new AnwBadDisplayNameException();
		}
		if (!self::isValidEmail($sEmail))
		{
			throw new AnwBadEmailException();
		}
		if (!Anwi18n::isValidLang($sLang))
		{
			throw new AnwBadLangException();
		}
		if (!self::isValidTimezone($nTimezone))
		{
			throw new AnwBadTimezoneException();
		}
		if (!self::isValidPassword($sPassword))
		{
			throw new AnwBadPasswordException();
		}
		if (!self::isAvailableLogin($sLogin))
		{
			throw new AnwLoginAlreadyTakenException();
		}
		if (!self::isAvailableDisplayName($sDisplayName))
		{
			throw new AnwDisplayNameAlreadyTakenException();
		}
		if (!self::isAvailableEmail($sEmail))
		{
			throw new AnwEmailAlreadyTakenException();
		}
		
		$nId = self::getDriver()->createUser($sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone, $sPassword);
		self::debug("New user id : ".$nId);
		
		$oUser = AnwUserReal::rebuildUser($nId, $sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone);
		
		AnwPlugins::hook("user_created", $oUser, $sPassword);
		
		return $oUser;
	}
	
	static function changeUserLang($oUser, $sLang)
	{
		if (!Anwi18n::isValidLang($sLang))
		{
			throw new AnwBadLangException();
		}
		self::debug("Updating user lang : ".$oUser->getId());
		self::getDriver()->changeUserLang($oUser, $sLang);
		AnwPlugins::hook("user_changed_lang", $oUser, $sLang);
	}
	
	static function changeUserTimezone($oUser, $nTimezone)
	{
		if (!self::isValidTimezone($nTimezone))
		{
			throw new AnwBadTimezoneException();
		}
		self::debug("Updating user timezone : ".$oUser->getId());
		self::getDriver()->changeUserTimezone($oUser, $nTimezone);
		AnwPlugins::hook("user_changed_timezone", $oUser, $nTimezone);
	}
	
	static function changeUserDisplayName($oUser, $sDisplayName)
	{
		if (!self::isValidDisplayName($sDisplayName))
		{
			throw new AnwBadDisplayNameException();
		}
		if (!self::isAvailableDisplayName($sDisplayName))
		{
			throw new AnwDisplayNameAlreadyTakenException();
		}
		self::debug("Updating user displayname : ".$oUser->getId());
		self::getDriver()->changeUserDisplayName($oUser, $sDisplayName);
		AnwPlugins::hook("user_changed_displayname", $oUser, $sDisplayName);
	}
	
	static function changeUserEmail($oUser, $sEmail)
	{
		$sEmail = strtolower($sEmail);
		
		if (!self::isValidEmail($sEmail))
		{
			throw new AnwBadEmailException();
		}
		if (!self::isAvailableEmail($sEmail))
		{
			throw new AnwEmailAlreadyTakenException();
		}
		self::debug("Updating user email : ".$oUser->getId());
		self::getDriver()->changeUserEmail($oUser, $sEmail);
		AnwPlugins::hook("user_changed_email", $oUser, $sEmail);
	}
	
	static function changeUserPassword($oUser, $sPassword)
	{
		if (!self::isValidPassword($sPassword))
		{
			throw new AnwBadPasswordException();
		}
		self::debug("Updating user password : ".$oUser->getId());
		self::getDriver()->changeUserPassword($oUser, $sPassword);
		AnwPlugins::hook("user_changed_password", $oUser, $sPassword);
	}
	
	
	//------------------------------------------------
	// EXTERNAL USERS DRIVER
	//------------------------------------------------
	
	static function getRegisterLink()
	{
		self::checkDriverExternal();
		return self::getDriver()->getRegisterLink();
	}
	
	static function getEditLink()
	{
		self::checkDriverExternal();
		return self::getDriver()->getEditLink();
	}
	
	static function getLinkProfile($oUser)
	{
		self::checkDriverExternal();
		return self::getDriver()->getLinkProfile($oUser);
	}
	
	
	
	//------------------------------------------------
	// TRANSACTIONS
	//------------------------------------------------
	
	static function transactionStart()
	{
		self::getDriver()->transactionStart();
	}
	static function transactionCommit()
	{
		self::getDriver()->transactionCommit();
	}
	static function transactionRollback()
	{
		self::getDriver()->transactionRollback();
	}
	
	
	//------------------------------------------------
	// DRIVERS MANAGEMENT
	//------------------------------------------------
	
	
	/**
	 * @throws AnwUnexpectedException
	 */
	static function loadDriver()
	{
		AnwDebug::startbench("Users driver init");
		
		self::$oDriver = AnwUsersDriver::loadComponent(AnwComponent::globalCfgDriverUsers());
		
		if (self::isDriverInternal())
		{
			self::debug("Users Driver loaded : internal");
		}
		else if (self::isDriverExternal())
		{
			self::debug("Users Driver loaded : external");
		}
		else throw new AnwUnexpectedException("Unknown usersdriver type");
		AnwDebug::stopbench("Users driver init");
	}
	
	
	static function isDriverInternal()
	{
		return (self::getDriver() instanceof AnwUsersDriverInternal);
	}
	
	static function isDriverExternal()
	{
		return (self::getDriver() instanceof AnwUsersDriverExternal);
	}
	
	static function isNonUniqueEmailAllowed()
	{
		if (!AnwComponent::globalCfgUsersUniqueEmail() && self::getDriver()->supportsNonUniqueEmail())
		{
			try
			{
				AnwPlugins::hook("users_nonunique_email_allowed");
				return true;
			}
			catch(AnwPluginInterruptionException $e){}
		}
		return false;
	}
	
	static function isNonUniqueDisplayNameAllowed()
	{
		if (!AnwComponent::globalCfgUsersUniqueDisplayname() && self::getDriver()->supportsNonUniqueDisplayName())
		{
			try
			{
				AnwPlugins::hook("users_nonunique_displayname_allowed");
				return true;
			}
			catch(AnwPluginInterruptionException $e){}
		}
		return false;
	}
	
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function getDriver()
	{
		if (!self::$oDriver)
		{
			self::loadDriver();
			if (!self::$oDriver) throw new AnwUnexpectedException("No users driver loaded");
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
		return AnwDebug::log("(AnwUsers)".$sMessage);
	}
}
?>