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
 * Anwiki ACLs manager.
 * @package Anwiki
 * @version $Id: class_acls.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwAcls
{
	private static $oDriver;
	
	
	//------------------------------------------------
	// ACL MANAGEMENT
	//------------------------------------------------
	
	static function isActionAllowed($oUser, $sPageName, $sAction, $sLang)
	{
		if (AnwAction::isAdminAction($sAction))
		{
			$bReturn = self::isAdminAllowed($oUser);
		}
		else if (AnwAction::isPublicAction($sAction))
		{
			$bReturn = true;
		}
		else
		{
			if (AnwAction::isMagicAclAction($sAction))
			{
				return false; //we should never go here, this test is just in case of...
			}
			$bReturn = (self::getDriver()->isActionAllowed($oUser, $sPageName, $sAction, $sLang) || self::isAdminAllowed($oUser)); //admins have full rights everywhere
		}
		return $bReturn;
	}
	
	static function isActionGlobalAllowed($oUser, $sAction)
	{
		if (AnwAction::isAdminAction($sAction))
		{
			$bReturn = self::isAdminAllowed($oUser);
		}
		else if (AnwAction::isPublicAction($sAction))
		{
			$bReturn = true;
		}
		else
		{
			if (AnwAction::isMagicAclAction($sAction))
			{
				return false; //we should never go here, this test is just in case of...
			}
			$bReturn = (self::getDriver()->isActionGlobalAllowed($oUser, $sAction) || self::isAdminAllowed($oUser)); //admins have full rights everywhere
		}
		return $bReturn;
	}
	
	static function isPhpEditionAllowed($oUser)
	{
		//hardcoded security
		if (!$oUser instanceof AnwUserReal)
		{
			return false;
		}
		
		$bReturn = self::getDriver()->isPhpEditionAllowed($oUser);
		if ($bReturn) self::debug("Checking ACL/PHP : allowed (".$oUser->getLogin().")");
		else self::debug("Checking ACL/PHP : refused (".$oUser->getLogin().")");
		return $bReturn;
	}
	
	static function isJsEditionAllowed($oUser)
	{
		//hardcoded security
		if (!$oUser instanceof AnwUserReal)
		{
			return false;
		}
		
		$bReturn = self::getDriver()->isJsEditionAllowed($oUser);
		if ($bReturn) self::debug("Checking ACL/JS : allowed (".$oUser->getLogin().")");
		else self::debug("Checking ACL/JS : refused (".$oUser->getLogin().")");
		return $bReturn;
	}
	
	static function isAdminAllowed($oUser)
	{
		//special case allowing any user for fresh install
		if (!file_exists(ANWIKI_INSTALL_LOCK) && ANWIKI_MODE_MINIMAL)
		{
			return true;
		}
		
		//hardcoded security
		if (!$oUser instanceof AnwUserReal)
		{
			return false;
		}
		
		$bReturn = self::getDriver()->isAdminAllowed($oUser);
		if ($bReturn) self::debug("Checking isAdminAllowed : allowed (".$oUser->getLogin().")");
		else self::debug("Checking isAdminAllowed : refused (".$oUser->getLogin().")");
		return $bReturn;
	}
	
	static function grantUserAdminOnInstall($oUser)
	{
		//hardcoded security
		if (!ANWIKI_IN_INSTALL)
		{
			throw new AnwUnexpectedException("grantUserAdminOnInstall can only be used in install");
		}
		
		self::getDriver()->grantUserAdminOnInstall($oUser);
		self::debug("Granting user admin permissions : ".$oUser->getLogin());
	}
	
	//------------------------------------------------
	// DRIVERS MANAGEMENT
	//------------------------------------------------
	
	
	/**
	 * @throws AnwUnexpectedException
	 */
	static function loadDriver()
	{
		AnwDebug::startbench("Acls driver init");
		self::$oDriver = AnwAclsDriver::loadComponent(AnwComponent::globalCfgDriverAcls());
		AnwDebug::stopbench("Acls driver init");
	}
	
	static function isDriverReadOnly()
	{
		return (self::getDriver() instanceof AnwAclsDriverReadOnly);
	}
	
	static function isDriverReadWrite()
	{
		return (self::getDriver() instanceof AnwAclsDriverReadWrite);
	}
	
	/**
	 * @throws AnwUnexpectedException
	 */
	private static function getDriver()
	{
		if (!self::$oDriver)
		{
			self::loadDriver();
			if (!self::$oDriver) throw new AnwUnexpectedException("No ACL driver loaded");
		}
		return self::$oDriver;
	}
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwAcls)".$sMessage);
	}
}

?>