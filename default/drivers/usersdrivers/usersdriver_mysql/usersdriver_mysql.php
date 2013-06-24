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
 * Users driver: MySQL.
 * @package Anwiki
 * @version $Id: usersdriver_mysql.php 219 2009-10-22 21:19:27Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwUsersDriverDefault_mysql extends AnwUsersDriverInternal implements AnwConfigurable, AnwInitializable
{
	const CFG_MYSQL = "mysql";
	
	const MINLEN_LOGIN=2;
	const MAXLEN_LOGIN=20;
	const MINLEN_DISPLAYNAME=2;
	const MAXLEN_DISPLAYNAME=20;
	const MINLEN_PASSWORD=5;
	const MAXLEN_PASSWORD=20;
	const MAXLEN_EMAIL=150;
	const MAXLEN_SALT=9;
	
	private $oDb;
	
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$oContentField = new AnwContentFieldSettings_mysqlconnexion(self::CFG_MYSQL);
		$oContentField->setMandatory(true);
		$aoSettings[] = $oContentField;
		return $aoSettings;
	}
	
	//------------------------------------------------
	// USERS MANAGEMENT
	//------------------------------------------------
	
	function createUser($sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone, $sPassword)
	{
		//calculate hash
		$sUserSalt = self::generateUserSalt();
		$sPasswordHash = self::encryptPassword($sPassword, $sUserSalt);
		
		//create user
		$asSqlInsert = array(
			"UserLogin" => $this->db()->strtosql($sLogin),
			"UserDisplayName" => $this->db()->strtosql($sDisplayName),
			"UserEmail" => $this->db()->strtosql($sEmail),
			"UserLang" => $this->db()->strtosql($sLang),
			"UserTimezone" => $this->db()->inttosql($nTimezone),
			"UserPassword" => $this->db()->strtosql($sPasswordHash),
			"UserSalt" => $this->db()->strtosql($sUserSalt)
		);
		$a = $this->db()->do_insert($asSqlInsert, "user");
		$nUserId = $this->db()->insert_id();
		
		return $nUserId;
	}
	
	
	function changeUserLang($oUser, $sLang)
	{
		$nUserId = $oUser->getId();
		
		$asSqlUpdate = array(
			"UserLang" => $this->db()->strtosql($sLang)
		);
		$this->db()->do_update($asSqlUpdate, "user", "WHERE UserId = ".$this->db()->inttosql($nUserId));
	}	
	
	function changeUserTimezone($oUser, $nTimezone)
	{
		$nUserId = $oUser->getId();
		
		$asSqlUpdate = array(
			"UserTimezone" => $this->db()->inttosql($nTimezone)
		);
		$this->db()->do_update($asSqlUpdate, "user", "WHERE UserId = ".$this->db()->inttosql($nUserId));
	}
	
	function changeUserDisplayName($oUser, $sDisplayName)
	{
		$nUserId = $oUser->getId();
		
		$asSqlUpdate = array(
			"UserDisplayName" => $this->db()->strtosql($sDisplayName)
		);
		$this->db()->do_update($asSqlUpdate, "user", "WHERE UserId = ".$this->db()->inttosql($nUserId));
	}
	
	function changeUserEmail($oUser, $sEmail)
	{
		$nUserId = $oUser->getId();
		
		$asSqlUpdate = array(
			"UserEmail" => $this->db()->strtosql($sEmail)
		);
		$this->db()->do_update($asSqlUpdate, "user", "WHERE UserId = ".$this->db()->inttosql($nUserId));
	}
	
	function changeUserPassword($oUser, $sPassword)
	{
		$nUserId = $oUser->getId();
		
		//generate a new salt
		$sUserSalt = self::generateUserSalt();
		$sPasswordHash = self::encryptPassword($sPassword, $sUserSalt);
		$asSqlUpdate = array(
			"UserPassword" => $this->db()->strtosql($sPasswordHash),
			"UserSalt" => $this->db()->strtosql($sUserSalt)
		);
		$this->db()->do_update($asSqlUpdate, "user", "WHERE UserId = ".$this->db()->inttosql($nUserId));
	}
	
	function getUser($nUserId)
	{
		$sQuery = "SELECT UserId, UserLogin, UserDisplayName, UserEmail, UserLang, UserTimezone " .
				"FROM `#PFX#user` " .
				"WHERE UserId = ".$this->db()->inttosql($nUserId)." " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwUserNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		$oUser = self::getUserFromData($oData);
		return $oUser; 
	}
	
	function getUserByLogin($sUserLogin)
	{
		$sQuery = "SELECT UserId, UserLogin, UserDisplayName, UserEmail, UserLang, UserTimezone " .
				"FROM `#PFX#user` " .
				"WHERE UserLogin = ".$this->db()->strtosql($sUserLogin)." " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwUserNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$this->db()->free($q);
		
		$oUser = self::getUserFromData($oData);
		return $oUser; 
	}
	
	/*function getUserByLogin($sUserLogin)
	{
		$sQuery = "SELECT UserId, UserLogin, UserDisplayName, UserEmail, UserLang, UserTimezone " .
				"FROM `#PFX#user` " .
				"WHERE UserLogin = ".$this->db()->strtosql($sUserLogin);
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) != 1)
		{
			throw new AnwUserNotFoundException();
		}
		$oData = $this->db()->fto($q);
		$oUser = self::getUserFromData($oData);
		return $oUser; 
	}*/
	
	function authenticate($sLogin, $sPassword)
	{
		$sQuery = "SELECT UserId, UserLogin, UserDisplayName, UserEmail, UserLang, UserTimezone, UserPassword, UserSalt " .
				"FROM `#PFX#user` " .
				"WHERE UserLogin = ".$this->db()->strtosql($sLogin);
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) == 1)
		{
			$oData = $this->db()->fto($q);
			$this->db()->free($q);
			
			$nUserId = $oData->UserId;
			$sUserDisplayName = $oData->UserDisplayName;
			$sUserLogin = $sLogin;
			$sUserEmail = $oData->UserEmail;
			$sUserLang = $oData->UserLang;
			$nUserTimezone = $oData->UserTimezone;
			$sUserPassword = $oData->UserPassword;
			$sUserSalt = $oData->UserSalt;
			
			//check password
			if ($sUserPassword == self::encryptPassword($sPassword, $sUserSalt))
			{
				$oUser = AnwUserReal::rebuildUser($nUserId, $sUserLogin, $sUserDisplayName, $sUserEmail, $sUserLang, $nUserTimezone);
				return $oUser;
			}
		}
		else
		{
			$this->db()->free($q);
		}
		throw new AnwAuthException();
	}
	
	function isValidLogin($sLogin)
	{
		$nLen = strlen($sLogin);
		return ( $nLen >= self::MINLEN_LOGIN && $nLen <= self::MAXLEN_LOGIN );
	}
	
	function isValidDisplayName($sDisplayName)
	{
		$nLen = strlen($sDisplayName);
		return ( $nLen >= self::MINLEN_DISPLAYNAME && $nLen <= self::MAXLEN_DISPLAYNAME );
	}
	
	function isValidEmail($sEmail)
	{
		$sRegexp="/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
		return ( strlen($sEmail) <= self::MAXLEN_EMAIL && preg_match($sRegexp, $sEmail) );
	}
	
	function isValidPassword($sPassword)
	{
		$nLen = strlen($sPassword);
		return ( $nLen >= self::MINLEN_PASSWORD && $nLen <= self::MAXLEN_PASSWORD );
	}
	
	function isAvailableLogin($sLogin)
	{
		$sQuery = "SELECT UserId " .
				"FROM `#PFX#user` " .
				"WHERE LOWER(UserLogin) = LOWER(".$this->db()->strtosql($sLogin).") " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) == 0)
		{
			$bReturn = true;
		}
		else
		{
			$bReturn = false;
		}
		$this->db()->free($q);
		return $bReturn;
	}
	
	function isAvailableDisplayName($sDisplayName)
	{
		$sQuery = "SELECT UserId " .
				"FROM `#PFX#user` " .
				"WHERE LOWER(UserDisplayName) = LOWER(".$this->db()->strtosql($sDisplayName).") " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) == 0)
		{
			$bReturn = true;
		}
		else
		{
			$bReturn = false;
		}
		$this->db()->free($q);
		return $bReturn;
	}
	
	function isAvailableEmail($sEmail)
	{
		$sQuery = "SELECT UserId " .
				"FROM `#PFX#user` " .
				"WHERE LOWER(UserEmail) = LOWER(".$this->db()->strtosql($sEmail).") " .
				"LIMIT 1";
		
		$q = $this->db()->query($sQuery);
		if ($this->db()->num_rows($q) == 0)
		{
			$bReturn = true;
		}
		else
		{
			$bReturn = false;
		}
		$this->db()->free($q);
		return $bReturn;
	}
	
	//----------------------------------------
	
	private static function getUserFromData($oDataUser)
	{
		$nUserId = $oDataUser->UserId;
		$sUserLogin = $oDataUser->UserLogin;
		$sUserDisplayName = $oDataUser->UserDisplayName;
		$sUserEmail = $oDataUser->UserEmail;
		$sUserLang = $oDataUser->UserLang;
		$nUserTimezone = $oDataUser->UserTimezone;
		
		$oUser = AnwUserReal::rebuildUser($nUserId, $sUserLogin, $sUserDisplayName, $sUserEmail, $sUserLang, $nUserTimezone);
		return $oUser;
	}
	
	private static function encryptPassword($sPassword, $sUserSalt)
	{
		if (!$sUserSalt) throw new AnwUnexpectedException("no salt given");
		$sHash = hash('sha256', $sPassword.$sUserSalt); //return lowercase hexits (64 chars)
		return $sHash;
	}
	
	private static function generateUserSalt()
	{
		$nRandom = AnwUtils::genStrongRandMd5();
		$sUserSalt = substr($nRandom, 0, self::MAXLEN_SALT);
		return $sUserSalt;
	}
	
	function supportsNonUniqueEmail()
	{
		return true;
	}
	
	function supportsNonUniqueDisplayName()
	{
		return true;
	}
	
	
	//------------------------------------------------
	// INITIALIZE
	//------------------------------------------------
	function initializeComponent()
	{
		self::transactionStart();
		try
		{			
			$asQ = array();
			$asQ[] = "CREATE TABLE `#PFX#user`   ( UserId INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, UserLogin VARCHAR(".self::MAXLEN_LOGIN.") NOT NULL, UserDisplayName VARCHAR(".self::MAXLEN_DISPLAYNAME.") NOT NULL, UserEmail VARCHAR(".self::MAXLEN_EMAIL.") NOT NULL, UserLang VARCHAR(".Anwi18n::MAXLEN_LANG.") NOT NULL, UserTimezone TINYINT(2) SIGNED NOT NULL, UserPassword CHAR(64) NOT NULL, UserSalt CHAR(".self::MAXLEN_SALT.") NOT NULL, PRIMARY KEY(UserId), UNIQUE(UserLogin), INDEX(UserDisplayName), INDEX(UserEmail) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci`";
			
			$sInitializationLog = "";
			
			//execute queries
			foreach ($asQ as $sQ)
			{
				$sInitializationLog .= $sQ."<br/>";
				$this->db()->query($sQ);
			}
			
			self::transactionCommit();
			
			return $sInitializationLog;
		}
		catch(AnwException $e)
		{
			self::transactionRollback();
			throw $e;
		}
	}
	
	function getSettingsForInitialization()
	{
		$oMysqlSettings = $this->cfg(self::CFG_MYSQL);
		return AnwComponent::g_editcontent("contentfield_mysqlconnexion_collapsed", array(
							'user' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_USER],
							'host' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_HOST],
							'database' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_DATABASE],
							'prefix' => $oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PREFIX]
						));
	}
	
	function isComponentInitialized()
	{
		return ($this->db()->table_exists("user"));
	}
	
	
	//------------------------------------------------
	// TRANSACTIONS
	//------------------------------------------------
	
	function transactionStart()
	{
		$this->db()->transactionStart();
	}
	function transactionCommit()
	{
		$this->db()->transactionCommit();
	}
	function transactionRollback()
	{
		$this->db()->transactionRollback();
	}
	
	//------------------------------------------------
	
	private function db()
	{
		if (!$this->oDb)
		{
			$oMysqlSettings = $this->cfg(self::CFG_MYSQL);
			$this->oDb = AnwMysql::getInstance(
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_USER], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PASSWORD], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_HOST], 
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_DATABASE],
				$oMysqlSettings[AnwISettings_mysqlconnexion::FIELD_PREFIX]
			);
		}
		return $this->oDb;
	}
}

?>