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
 * Anwiki drivers interfaces. 
 * Feel free to share your own implementations on http://www.anwiki.com.
 * @package Anwiki
 * @version $Id: _drivers.php 292 2010-09-11 09:26:26Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 

abstract class AnwDriver extends AnwComponent
{
	function __construct($sName, $bIsAddon)
	{
		$this->initComponent($sName, $bIsAddon);
	}
}

#################################################################
#                        STORAGE
#################################################################

abstract class AnwStorageDriver extends AnwDriver
{
	//------------------------------------------------
	// LAST CHANGES
	//------------------------------------------------
	
	/**
	 * Get last changes from storage system.
	 * @param int $nLimit Number of changes to retrieve
	 * @param object AnwPage $oPage If not null, get changes related to this page
	 * @return array of AnwChange objects
	 */
	abstract function getLastChanges($nLimit=false, $nStart, $asDisplayLangs, $asDisplayClasses, $amChangeTypes, $oPage=null, $oPageGroup=null);
	
	/**
	 * Save a new change to storage system.
	 * @param object AnwChange Change to save
	 */
	abstract function createChange($oChange);
	
	/**
	 * Get untranslated pages.
	 */
	abstract function getUntranslatedPages($asLangs, $asContentClasses);
	
	
	//------------------------------------------------
	// PAGES AND ARCHIVES
	//------------------------------------------------
	
	/**
	 * Is this pagename available?
	 */
	 abstract function isAvailablePageName($sPageName);
	 
	/**
	 * Save a new page to storage system.
	 * @param object AnwPage Page to create
	 * @param integer ContentClass
	 * @return integer ID for the new page
	 */
	abstract function createPage($oPage, $oChange, $fCallbackSetId);
	
	/**
	 * Restore an archive.
	 */
	abstract function restoreArchive($oPageArchived, $oChange, $fCallbackSetNonArchive);
	
	/**
	 * Save an existing page to storage system.
	 * @param object AnwPage Page to update
	 */
	abstract function updatePage($oPage, $oChange);
	
	/**
	 * Rename an existing page.
	 */
	abstract function renamePage($oPage, $oChange);
	
	/**
	 * Change lang of an existing page.
	 */
	abstract function changeLangPage($oPage, $oChange);
	
	/**
	 * Save a draft for a page.
	 */
	//abstract function savePageDraft($oDraft);
	
	/**
	 * Delete a draft.
	 */
	//abstract function deletePageDraft($oDraft);
	
	/**
	 * Get all drafts related to a page.
	 */
	//abstract function getPageDrafts($oPage);
	
	/**
	 * Get a page from the storage system.
	 * @param $nPageId integer ID of the page to retrieve
	 * @return object AnwPage Page found in the storage system
	 */
	 abstract function getPage($nPageId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
	 
	/**
	 * Get a page from the storage system.
	 * @param $sPageName string Name of the page to retrieve
	 * @return object AnwPage Page found in the storage system
	 */
	 abstract function getPageByName($sPageName, $bSkipLoadingTranslationsContent, $bSkipLoadingContent, $sLoadedLang);
	
	/**
	 * Get an archived page from the storage system.
	 * @param $nPageId integer ID of the archived page to retrieve
	 * @param $nRevisionTime integer Revision time of the archived page to retrieve
	 * @return object AnwPage Page found in the storage system
	 */
	abstract function getPageArchive($nPageId, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
	
	/**
	 * Get an archived page from the storage system.
	 * @param $sPageName string Name of the archived page to retrieve
	 * @param $nRevisionTime integer Revision time of the archived page to retrieve
	 * @return object AnwPage Page found in the storage system
	 */
	abstract function getPageArchiveByName($sPageName, $nChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
	
	/**
	 * Get the latest available version of a page (active or archived).
	 */
	abstract function getLastPageRevision($nPageId);
	
	/**
	 * Get the previous archive for a page from the storage system.
	 * @param $oPage object AnwPage
	 * @param $nMaxTime integer Max time for the archive to be returned
	 * @return object AnwPage Archive
	 */
	abstract function getPageArchivePrevious($oPage, $nMaxChangeId, $bSkipLoadingTranslationsContent, $bSkipLoadingContent);
	
	abstract function getPageGroupPreviousArchives($nReferenceChangeId, $oPageGroup, $bSkipLoadingContent);
	
	/**
	 * Delete a page.
	 * @param $oPage object AnwPage Page to delete
	 */
	abstract function deletePage($oPage, $oChange);
	
	/**
	 * Get pages linked to the specified page $oPage.
	 * @param $oPage object AnwPage
	 * @return array of objects AnwPage
	 */
	abstract function getPageGroupsLinking($oPageGroupLinked);
	
	/**
	 * Fetch pages from the same class, class filters supported.
	 */
	abstract function fetchPagesByClass($asPatterns=array(), $oContentClass, $asLangs=array(), $nLimit=0, $sSortUser, $sOrder, $asFilters);
	
	/**
	 * Fetch pages from multiple contentclasses.
	 * No filter is supported, and only local sorts are supported.
	 */
	abstract function fetchPages($asPatterns=array(), $aoContentClasses, $asLangs=array(), $nLimit=0, $sLocalSort, $sOrder);
	
	//------------------------------------------------
	// PAGE GROUPS
	//------------------------------------------------
	
	/**
	 * Save a new PageGroup to storage system. Then set the group id.
	 * @param object AnwPageGroup PageGroup to create
	 * @return integer ID for the new PageGroup
	 */
	abstract function createPageGroup($oPageGroup);
	
	/**
	 * Save an existing PageGroup to storage system.
	 * @param object AnwPageGroup PageGroup to update
	 */
	abstract function updatePageGroup($oPageGroup);
	
	/**
	 * Get a PageGroup from the storage system.
	 * @param $nPageGroupId integer Id of the PageGroup to retrieve
	 * @return object AnwPageGroup PageGroup found in the storage system
	 */
	abstract function getPageGroup($nPageGroupId);
	
	/**
	 * Get all existing PageGroups.
	 * @return array of AnwPageGroup objects
	 */
	abstract function getPageGroups($bSkipLoadingContent, $asLangs, $asContentClasses);
	
	
	//------------------------------------------------
	// LOCKS
	//------------------------------------------------
	
	/**
	 * Get exclusive lock to a page.
	 */
	abstract function lockPage($nLockType, $oPage, $oSession);
	
	/**
	 * Unlock a page.
	 */
	abstract function unlockPage($oPage, $oSession);
	
	
	//------------------------------------------------
	// TRANSACTIONS
	//------------------------------------------------
	
	abstract function transactionStart();
	abstract function transactionCommit();
	abstract function transactionRollback();
	
	//------------------------------------------------
	
	//------------------------------------------------
	// INITIALIZE
	//------------------------------------------------
	
	protected function debug($sMessage)
	{
		return AnwDebug::log("(".get_class($this).")".$sMessage);
	}
	
	function getComponentName()
	{
		return 'storagedriver_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_STORAGEDRIVERS;
	}
	static function getComponentsDirsBegin()
	{
		return 'storagedriver_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_STORAGEDRIVER;
	}
	
	static function discoverEnabledComponents()
	{
		return array(self::globalCfgDriverStorage());
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'storagedriver');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}		
		
		$sFile = 'storagedriver_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwStorageDriver%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oDriver = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'storagedriver', $oDriver);
		return $oDriver;
	}
	
	
}

#################################################################
#                        SESSION
#################################################################

/**
 * Object is able to read current session.
 */
interface AnwSessionsCapability_read
{
	/**
	 * Get current session.
	 * @return object AnwSession Current session
	 */
	function getCurrentSession();
}

/**
 * Object is able to write changes to the session.
 */
interface AnwSessionsCapability_write
{
	/**
	 * Open a new session with an user.
	 * @param $oUser object AnwUserReal User for the new session
	 */
	function login($oUser, $bRemember);
	
	/**
	 * Close current session.
	 */
	function logout();
}

/**
 * All SessionsDrivers must be able to retrieve current session, and write settings such as lang and timezone.
 */
abstract class AnwSessionsDriver extends AnwDriver implements AnwSessionsCapability_read
{
	//------------------------------------------------
	// SESSION MANAGEMENT
	//------------------------------------------------
	
	/**
	 * Initialize current session.
	 */
	abstract function init();
	

	/**
	 * Change session lang.
	 * @param $sLang string Session lang
	 */
	abstract function setLang($sLang);
	
	/**
	 * Change session timezone.
	 * @param $nTimezone Session timezone
	 */
	abstract function setTimezone($nTimezone);
	
	/**
	 * Keep the session alive (called every X minutes).
	 */	
	function keepAlive(){}
	
	
	
	protected function debug($sMessage)
	{
		return AnwDebug::log("(".get_class($this).")".$sMessage);
	}	
	
	function getComponentName()
	{
		return 'sessionsdriver_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_SESSIONSDRIVERS;
	}
	static function getComponentsDirsBegin()
	{
		return 'sessionsdriver_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_SESSIONSDRIVER;
	}
	
	static function discoverEnabledComponents()
	{
		return array(self::globalCfgDriverSessions());
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'sessionsdriver');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}		
		
		$sFile = 'sessionsdriver_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwSessionsDriver%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oDriver = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'sessionsdriver', $oDriver);
		return $oDriver;
	}
	
	
}

/**
 * For sessions supporting Reauth mechanism.
 */
interface AnwSessionsCapability_reauth
{
	/**
	 * Password has just been checked for a critical action, don't ask it again before X minutes.
	 */
	function resetReauth();
}

/**
 * For sessions supporting Resume / "remember me" mechanism.
 */
interface AnwSessionsCapability_resume
{
	
}

/**
 * Internal SessionsDrivers can directly login/logout.
 */
abstract class AnwSessionsDriverInternal extends AnwSessionsDriver implements AnwSessionsCapability_write
{
	//implements
}

/**
 * External SessionsDrivers can't directly login/logout.
 * It provides links to remotely login/logout.
 */
abstract class AnwSessionsDriverExternal extends AnwSessionsDriver
{
	//------------------------------------------------
	// EXTERNAL SESSION SYSTEM INTEGRATION
	//------------------------------------------------
	
	abstract function getLoginLink();
	abstract function getLogoutLink();
}




#################################################################
#                        USERS
#################################################################

/**
 * Object is able to check user infos.
 */
interface AnwUsersCapability_check
{
	function isValidLogin($sLogin);	
	function isValidDisplayName($sDisplayName);	
	function isValidEmail($sEmail);	
	function isValidPassword($sPassword);
}

/**
 * Object is able to read user infos.
 */
interface AnwUsersCapability_read extends AnwUsersCapability_check
{
	/**
	 * Get an user from the users system by it's ID.
	 * @param $nUserId integer ID of the user to retrieve
	 * @return object AnwUserReal User found in the users system
	 */
	function getUser($nUserId);
	
	/**
	 * Get an user from the users system by it's login.
	 * @param $sUserLogin string Login of the user to retrieve
	 * @return object AnwUserReal User found in the users system
	 */
	function getUserByLogin($sUserLogin);
	
	/**
	 * Authenticate user.
	 * @param $sLogin string Login of the user
	 * @param $sPassword string Password of the user
	 * @return User object
	 * @throws AnwAuthException if authentication error
	 */
	function authenticate($sLogin, $sPassword);
}

/**
 * Object is able to write user infos.
 */
interface AnwUsersCapability_write extends AnwUsersCapability_check
{
	/**
	 * Save a new user to users system.
	 * @param $sLogin string Login for this user
	 * @param $sEmail string Email for this user
	 * @param $sLang string Lang for this user
	 * @param $sPassword string Password for this user
	 * @return integer ID for the new user
	 */
	function createUser($sLogin, $sLogin, $sEmail, $sLang, $nTimezone, $sPassword);
	
	/**
	 * Update an existing user to users system.
	 * @param $oUser object AnwUserReal User to update
	 */
	//function updateUser($oUser);
	
	/**
	 * Change user's lang.
	 */
	function changeUserLang($oUser, $sLang);
	
	/**
	 * Change user's timezone.
	 */
	function changeUserTimezone($oUser, $nTimezone);
	
	/**
	 * Change user's displayname.
	 */
	function changeUserDisplayName($oUser, $sDisplayName);
	
	/**
	 * Change user's email.
	 */
	function changeUserEmail($oUser, $sEmail);
	
	/**
	 * Change user's password.
	 * @param $oUser object AnwUserReal User to update
	 * @param $sPassword string New password
	 */
	function changeUserPassword($oUser, $sPassword);
	
	
	/**
	 * Does the driver support non unique emails ?
	 * @return boolean
	 */
	function supportsNonUniqueEmail();
	
	/**
	 * Does the driver support non unique displaynames ?
	 * @return boolean
	 */
	function supportsNonUniqueDisplayName();
	
	function isAvailableLogin($sLogin);	
	function isAvailableDisplayName($sDisplayName);
	function isAvailableEmail($sEmail);
}


/**
 * Any UserDriver must be able to check and read user infos.
 */
abstract class AnwUsersDriver extends AnwDriver implements AnwUsersCapability_read
{
	//------------------------------------------------
	// USERS MANAGEMENT
	//------------------------------------------------
		
	
	protected function debug($sMessage)
	{
		return AnwDebug::log("(".get_class($this).")".$sMessage);
	}		
	
	function getComponentName()
	{
		return 'usersdriver_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_USERSDRIVERS;
	}
	static function getComponentsDirsBegin()
	{
		return 'usersdriver_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_USERSDRIVER;
	}
	
	static function discoverEnabledComponents()
	{
		return array(self::globalCfgDriverUsers());
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'usersdriver');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}		
		
		$sFile = 'usersdriver_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwUsersDriver%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oDriver = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'usersdriver', $oDriver);
		return $oDriver;
	}
	
	
}

/**
 * An internal UserDriver can directly write user infos.
 */
abstract class AnwUsersDriverInternal extends AnwUsersDriver implements AnwUsersCapability_write
{
	//implements
}

/**
 * An external UserDriver can't directly write user infos.
 * It can only provide links to manage user accounts from an external location.
 */
abstract class AnwUsersDriverExternal extends AnwUsersDriver
{
	abstract function getRegisterLink();
	abstract function getEditLink();
	abstract function getLinkProfile($oUser);
}



#################################################################
#                        ACLS
#################################################################

/**
 * Object is able to read permissions.
 */
interface AnwAclsCapability_read
{
	function isActionAllowed($oUser, $sPageName, $sAction, $sLang);
	function isActionGlobalAllowed($oUser, $sAction);
	function isPhpEditionAllowed($oUser);
	function isJsEditionAllowed($oUser);
	function isAdminAllowed($oUser);
}

/**
 * Object is able to modify permissions.
 */
interface AnwAclsCapability_write
{
	function grantUserAdminOnInstall($oUser);
}

abstract class AnwAclsDriver extends AnwDriver implements AnwAclsCapability_read
{
	protected function debug($sMessage)
	{
		return AnwDebug::log("(".get_class($this).")".$sMessage);
	}
	
	function getComponentName()
	{
		return 'aclsdriver_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_ACLSDRIVERS;
	}
	static function getComponentsDirsBegin()
	{
		return 'aclsdriver_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_ACLSDRIVER;
	}
	
	static function discoverEnabledComponents()
	{
		return array(self::globalCfgDriverAcls());
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'aclsdriver');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}		
		
		$sFile = 'aclsdriver_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwAclsDriver%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oDriver = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'aclsdriver', $oDriver);
		return $oDriver;
	}
	
	
}

/**
 * A ReadWrite AclsDriver can modify permissions.
 */
abstract class AnwAclsDriverReadWrite extends AnwAclsDriver implements AnwAclsCapability_write
{
	//implements
}

/**
 * A ReadOnly AclsDriver can't modify permissions.
 */
abstract class AnwAclsDriverReadOnly extends AnwAclsDriver
{
}


?>