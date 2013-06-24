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
 * Sessions driver: MySQL.
 * @package Anwiki
 * @version $Id: sessionsdriver_mysql.php 275 2010-09-06 21:42:31Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwISettings_mysqlsessionpreference
{
	const FIELD_PUBLIC = "public";
	const FIELD_RESUME = "resume";
}
interface AnwISettings_mysqlsessionpublic
{
	const FIELD_DURATION_IDLE = "duration_idle";
	const FIELD_DURATION_MAX = "duration_max";
}
interface AnwISettings_mysqlsessionresume
{
	const FIELD_DELAY_MAX = "delay_max";
}

class AnwSessionsDriverDefault_mysql extends AnwSessionsDriverInternal implements AnwSessionsCapability_reauth, AnwSessionsCapability_resume, AnwConfigurable, AnwInitializable
{
	const CFG_MYSQL = "mysql";
	const CFG_SESSIONS = "sessions";
	
	const ANWSESSION = "anwsession";
	const SESSION_CODE = "anwsesscode"; //only used for tracking session changes in case of multiple Anwiki instances synchronized together
	const COOKIE_SESSION_ID = "anwsessid";
	const COOKIE_SESSION_CODE = "anwsesscode";
	
	private $oDb; //database
	
	
	private static $oCfgMysql;
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$aoSettings[] = new AnwContentFieldSettings_mysqlsessionpreference(self::CFG_SESSIONS);
		$oContentField = new AnwContentFieldSettings_mysqlconnexion(self::CFG_MYSQL);
		$oContentField->setMandatory(true);
		$aoSettings[] = $oContentField;
		return $aoSettings;
	}
	
	function init(){}
	
	//------------------------------------------------
	// SESSION MANAGEMENT
	//------------------------------------------------
	
	function getCurrentSession()
	{
		//try to load cached session in PHPSESSION
		//don't only check the session object, but check cookies states too, 
		//in order to have session sync working between multiple instances of Anwiki
		if ( 
			(   //user is logged in
				AnwEnv::_SESSION(self::ANWSESSION) 
				&& AnwEnv::_SESSION(self::ANWSESSION)->isLoggedIn()
				&& AnwEnv::_COOKIE(self::COOKIE_SESSION_ID) 
				&& AnwEnv::_COOKIE(self::COOKIE_SESSION_CODE) 
				&& AnwEnv::_SESSION(self::SESSION_CODE) == AnwEnv::_COOKIE(self::COOKIE_SESSION_CODE) 
			)
			||
			(   //user is logged out
				AnwEnv::_SESSION(self::ANWSESSION) 
				&& !AnwEnv::_SESSION(self::ANWSESSION)->isLoggedIn()
				&& !AnwEnv::_COOKIE(self::COOKIE_SESSION_ID) 
				&& !AnwEnv::_COOKIE(self::COOKIE_SESSION_CODE) )
			)
		{
			//session is cached in PHPSESSION
			self::debug("Resuming session from PHPSESSION");
		}
		
		else
		{
			//no cached session in PHPSESSION (or session outdated by cookies)
			
			try
			{
				//do we have a valid session in cookies?
				$oSession = $this->getCurrentSessionFromDatabase();
				self::debug("Resuming a session from database");
			}
			catch(AnwException $e)
			{
				//no session found, start a new anonymous session
				self::debug("Starting a new anonymous session");
				$oSession = new AnwSession();
			}
			
			//cache in PHPSESSION
			AnwEnv::putSession(self::ANWSESSION, $oSession);
			
			if (!$oSession->isLoggedIn())
			{
				//unset cookies - just to make sure (not really needed in fact)
				AnwEnv::unsetCookie(self::COOKIE_SESSION_ID);
				AnwEnv::unsetCookie(self::COOKIE_SESSION_CODE);
			}
		}
		
		//resume existing session
		return AnwEnv::_SESSION(self::ANWSESSION);
	}
	
	function login($oUser, $bResume)
	{
		//object in PHPSESSION has been automatically updated.
		//all we have to do is to update session in database if we are logged in.
		$oSession = AnwCurrentSession::getSession();
		$oSession->login($oUser, $bResume); //duplicate with AnwCurrentSession, but so we are sure it's done. Btw we need this for install step with minimal currentsession mode
		$this->saveSession($oSession, true);
	}
	
	function logout()
	{
		//object in PHPSESSION has been automatically updated.
		//all we have to do is to update session in database if we are logged in.
		$oSession = AnwCurrentSession::getSession();
		$oSession->logout(); //duplicate with AnwCurrentSession, but so we are sure it's done
		
		//delete session from database
		$sSessionId = $oSession->getId();
		$this->db()->query("DELETE FROM `#PFX#session` WHERE SessionId=".$this->db()->strtosql($sSessionId));
		
		//unset session cookies
		$this->saveSession($oSession);
		
		//unset session in cache to be sure to start a new anonymous session
		AnwEnv::unsetSession(self::ANWSESSION);
	}
	
	function keepAlive()
	{
		//keepAlive is used for:
		//- updating "LastSeen" value in database so that session doesn't expire
		//- refresh the SessionCode
		//- quickly detect illegitimate or remotely-closed sessions
		$oSession = AnwCurrentSession::getSession();
		$this->saveSession($oSession);
	}
	
	function setLang($sLang)
	{
		//nothing to do...
		//if user is logged in, lang is handled by UserDriver (if internal driver)
		//else, lang is handled by the session object cached in PHPSESSION
	}
	
	function setTimezone($nTimezone)
	{
		//nothing to do...
		//if user is logged in, timezone is handled by UserDriver (if internal driver)
		//else, timezone is handled by the session object cached in PHPSESSION
	}
	
	function resetReauth()
	{
		$oSession = AnwCurrentSession::getSession();
		
		$nTimeAuth = time();
		
		$asData = array(
			"SessionTimeAuth"	=>	$this->db()->inttosql($nTimeAuth)
		);
		
		$sSessionId = $oSession->getId();
		$this->db()->do_update($asData, "session", 
						"WHERE SessionId=".$this->db()->strtosql($sSessionId));
	}
	
	private function saveSession($oSession, $bCreateSessionIfNotExists=false)
	{
		$sSessionId = $oSession->getId();
		
		if ($oSession->isLoggedIn())
		{
			//purge the old sessions from database (needed for the update/insert test)
			$this->purgeExpiredSessionsFromDatabase();
			
			//try to update session in database (if it already exists)
			$sSessionIdentifier = AnwEnv::calculateSessionIdentifier();
			$sSessionCode = self::generateSessionCode(); //a new code is generated (even if session already exists) to prevent session stealing
			$nSessionUser = $oSession->getUser()->getId();
			$sSessionResume = ($oSession->isResume() ? 1 : 0);
			$nSessionTimeSeen =  time();
			
			$asData = array(
				"SessionIdentifier"	=>	$this->db()->strtosql($sSessionIdentifier),
				"SessionCode"		=>	$this->db()->strtosql($sSessionCode),
				"SessionUser"		=>	$this->db()->inttosql($nSessionUser),
				"SessionResume"		=>	$this->db()->strtosql($sSessionResume),
				"SessionTimeSeen"	=>	$this->db()->inttosql($nSessionTimeSeen)
			);
			
			$this->db()->do_update($asData, "session", 
							"WHERE SessionId=".$this->db()->strtosql($sSessionId));
		
			//otherwise, we may need to INSERT this new session or to kill it
			if ($this->db()->affected_rows() != 1)
			{
				if ($bCreateSessionIfNotExists)
				{
					//user is logging in, it's normal that the session doesn't exist in database.
					$asData["SessionId"] 			= $this->db()->strtosql($sSessionId);
					$asData["SessionTimeStart"] 	= $this->db()->inttosql(time());
					$asData["SessionTimeAuth"] 		= $this->db()->inttosql(time());
					$this->db()->do_insert($asData, "session");
				}
				else
				{
					//here, the session is supposed to exist in database, but isn't found.
					//this can happend in the following situations:
					// - The session has expired (DurationIdle or DurationMax)
					// - An user was using a session, when someone tried to steal it. The session was killed for security reasons.
					// - An administrator has killed the session.
					// - The session has expired.
					//In both situations, the current session is no longer safe and must be closed.
					self::debug("WARNING: Session doesn't exist in database, but session creation is NOT expected. Logging out.");
					AnwCurrentSession::logout();
					return;
				}
			}
			
			//remember current session in cookies
			$nCookieExpires = ( AnwSessions::isResumeEnabled() && $oSession->isResume() ? time()+$this->cfgResumeDelayMax() : 0 );
			AnwEnv::putCookie(self::COOKIE_SESSION_ID, $sSessionId, $nCookieExpires);
			AnwEnv::putCookie(self::COOKIE_SESSION_CODE, $sSessionCode, $nCookieExpires);
			AnwEnv::putSession(self::SESSION_CODE, $sSessionCode);
		}
		else
		{
			//unset cookies
			AnwEnv::unsetCookie(self::COOKIE_SESSION_ID);
			AnwEnv::unsetCookie(self::COOKIE_SESSION_CODE);
		}
	}
	
	private function getCurrentSessionFromDatabase()
	{
		$sCookieSessionId = AnwEnv::_COOKIE(self::COOKIE_SESSION_ID);
		$sCookieSessionCode = AnwEnv::_COOKIE(self::COOKIE_SESSION_CODE);
		
		if ($sCookieSessionId && $sCookieSessionCode)
		{
			//first of all, purge the old sessions from database
			$this->purgeExpiredSessionsFromDatabase();
			
			//we have session info in cookies, check against the database
			self::debug("Session info found in cookies, checking against database...");
			
			$q = $this->db()->query("SELECT SessionCode, SessionIdentifier, " .
					"SessionUser, SessionResume, " .
					"SessionTimeStart, SessionTimeSeen, SessionTimeAuth " .
					"FROM `#PFX#session` WHERE SessionId=".$this->db()->strtosql($sCookieSessionId)." ".
					"LIMIT 1");
			$oData = $this->db()->fto($q);
			$this->db()->free($q);
			if ($oData)
			{
				self::debug("Session found in database");
				
				//check session code
				if ($sCookieSessionCode == $oData->SessionCode)
				{
					self::debug("Session code OK");
					if ($sCookieSessionCode != AnwEnv::_SESSION(self::SESSION_CODE))
					{
						//_SESSION may contain an old session code when running multiple Anwiki instances synchronized together
						//update _SESSION as session is valid!
						self::debug("Session code is outdated in the session, resynchronizing it with the cookie...");
						AnwEnv::putSession(self::SESSION_CODE, $sCookieSessionCode);
					}
					
					//check session identifier
					if (AnwEnv::calculateSessionIdentifier() == $oData->SessionIdentifier)
					{
						self::debug("Session identifier OK");
						
						//check that session user still exists
						$nSessionUserId = $oData->SessionUser;
						$oSessionUser = AnwUsers::getUser($nSessionUserId);
						if ($oSessionUser->exists())
						{
							//allright, restore the session
							$bSessionResume = ($oData->SessionResume == '1' ? true : false);
							$sSessionLang = $oSessionUser->getLang();
							$nSessionTimezone = $oSessionUser->getTimezone();
							$nSessionTimeStart = $oData->SessionTimeStart;
							$nSessionTimeSeen = $oData->SessionTimeSeen;
							$nSessionTimeAuth = $oData->SessionTimeAuth;
							$oSession = AnwSession::rebuildSession($oSessionUser, $bSessionResume, $sSessionLang, $nSessionTimezone, $sCookieSessionId, $nSessionTimeStart, $nSessionTimeSeen, $nSessionTimeAuth);
							return $oSession;
						}
						else
						{
							self::debug("Session user doesn't exist anymore");
						}						
					}
					else
					{
						self::debug("Invalid session identifier");
					}
				}
				else
				{
					self::debug("Invalid session code");
				}
				
				//here, the sessionid was found but a bad sessioncode, sessionidentifier or user was given
				//we kill the session to prevent hacking attempts
				self::debug("WARNING: sessionid was found, but wrong sessions checks was provided. Kill the session.");
				$this->db()->query("DELETE FROM `#PFX#session` WHERE SessionId=".$this->db()->strtosql($sCookieSessionId));
			}
			else
			{
				self::debug("Session NOT found in database");
			}
		}
		throw new AnwSessionNotFoundException();
	}
	
	private function purgeExpiredSessionsFromDatabase()
	{
		$nMinTimeSeenPublic = time()-$this->cfgPublicDurationIdle();
		$nMinTimeStartPublic = time()-$this->cfgPublicDurationMax();
		$nMinTimeSeenResume = time()-$this->cfgResumeDelayMax();
		$this->db()->query("DELETE FROM `#PFX#session` " .
				"WHERE (SessionResume=0 AND " .
					"(" .
						"SessionTimeSeen<".$this->db()->inttosql($nMinTimeSeenPublic)." " .
						"OR SessionTimeStart<".$this->db()->inttosql($nMinTimeStartPublic)." " .
					")" .
				") ".
				"OR (SessionResume=1 AND SessionTimeSeen<".$this->db()->inttosql($nMinTimeSeenResume).") ");
		$nCountPurgedSessions = $this->db()->affected_rows();
		self::debug($nCountPurgedSessions." purged sessions");
	}
	
	private static function generateSessionCode()
	{
		return AnwUtils::genStrongRandMd5();
	}
	
	
	//------------------------------------------------
	// INITIALIZE
	//------------------------------------------------
	function initializeComponent()
	{
		// first, make sure that session path is writable
		if (!AnwEnv::isSessionPathWritable()) {
			$sError = $this->t_("err_session_path_not_writable");
			throw new AnwComponentInitializeException($sError);
		}
		
		self::transactionStart();
		try
		{			
			$asQ = array();
			$asQ[] = "CREATE TABLE `#PFX#session` ( SessionId CHAR(32) NOT NULL, SessionUser INTEGER UNSIGNED NOT NULL, SessionResume ENUM('0','1') NOT NULL, SessionTimeStart INTEGER UNSIGNED NOT NULL, SessionTimeSeen INTEGER UNSIGNED NOT NULL, SessionTimeAuth INTEGER UNSIGNED NOT NULL, SessionIdentifier CHAR(32) NOT NULL, SessionCode CHAR(32) NOT NULL, PRIMARY KEY(SessionId), INDEX(SessionUser), INDEX(SessionTimeStart), INDEX(SessionTimeSeen) ) CHARACTER SET `utf8` COLLATE `utf8_unicode_ci`";
		
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
		return ($this->db()->table_exists("session"));
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
	
	private function cfgPublicDurationIdle()
	{
		$oSessionsSettings = $this->cfg(self::CFG_SESSIONS);
		$oPublicSettings = $oSessionsSettings[AnwISettings_mysqlsessionpreference::FIELD_PUBLIC];
		return $oPublicSettings[AnwISettings_mysqlsessionpublic::FIELD_DURATION_IDLE];
	}
	
	private function cfgPublicDurationMax()
	{
		$oSessionsSettings = $this->cfg(self::CFG_SESSIONS);
		$oPublicSettings = $oSessionsSettings[AnwISettings_mysqlsessionpreference::FIELD_PUBLIC];
		return $oPublicSettings[AnwISettings_mysqlsessionpublic::FIELD_DURATION_MAX];
	}
	
	private function cfgResumeDelayMax()
	{
		$oSessionsSettings = $this->cfg(self::CFG_SESSIONS);
		$oResumeSettings = $oSessionsSettings[AnwISettings_mysqlsessionpreference::FIELD_RESUME];
		return $oResumeSettings[AnwISettings_mysqlsessionresume::FIELD_DELAY_MAX];
	}
}



?>