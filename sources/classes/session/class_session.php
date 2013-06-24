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
 * Anwiki session definition.
 * @package Anwiki
 * @version $Id: class_session.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
define("ANWSESSION","anwsession");

class AnwSession
{
	private $oUser;
	private $bResume;
	private $sId;
	private $bLoggedIn = false;

	//these infos (lang,timezone) already exists on AnwUser.
	//We need it on session level for anonymous sessions
	private $sLang;
	private $nTimezone; //signed
	
	private $nTimeStart;
	private $nTimeSeen;
	private $nTimeAuth;
	
	function __construct($sSessionLang=null, $nTimezone = null, $sSessionId = null)
	{
		//create an anonymous session
		$this->oUser = new AnwUserAnonymous();
		$this->bResume = false;
		
		//session lang
		if (!$sSessionLang)
		{
			try
			{
				$sSessionLang = AnwUtils::detectPreferredLang();
			}
			catch(AnwException $e)
			{
				AnwDebug::log("WARNING: Unable to detect preferred lang");
				$sSessionLang = AnwComponent::globalCfgLangDefault();
			}			
		}
		$this->sLang = $sSessionLang;
		
		//session timezone
		if ($nTimezone === false) //allow 0 offset
		{
			$nTimezone = AnwComponent::globalCfgTimezoneDefault();
		}
		$this->nTimezone = $nTimezone;
		
		//session id
		if (!$sSessionId)
		{
			$sSessionId = self::getNewSessionId();
		}
		$this->sId = $sSessionId;
		
		$this->nTimeStart = time();
		$this->nTimeSeen = time();
		$this->nTimeAuth = 0;
	}
	
	function getUser()
	{
		return $this->oUser;
	}
	
	function isResume()
	{
		return $this->bResume;
	}
	
	function getLang()
	{
		return $this->sLang;
	}
	
	function getId()
	{
		return $this->sId;
	}
	
	function getTimezone()
	{
		return $this->nTimezone;
	}
	
	function getTimeStart()
	{
		return $this->nTimeStart;
	}
	
	function getTimeSeen()
	{
		return $this->nTimeSeen;
	}	
	
	function getTimeAuth()
	{
		return $this->nTimeAuth;
	}
	
	function needsReauth()
	{
		if (!$this->isLoggedIn() || !AnwComponent::globalCfgReauthEnabled() || !AnwSessions::isReauthSupported())
		{
			self::debug("needsReauth: skipping");
			return false;
		}
		
		$nTimeElapsed = time() - $this->nTimeAuth;
		self::debug("needsReauth: ".$nTimeElapsed."/".AnwComponent::globalCfgReauthDelay()."s");
		if ($nTimeElapsed > AnwComponent::globalCfgReauthDelay())
		{
			return true;
		}
		
		return false;
	}
	
	function resetReauth()
	{
		if (!$this->isLoggedIn())
		{
			throw new AnwUnexpectedException("resetReauth() on anonymous session");
		}
		$this->nTimeAuth = time();
	}
	
	function isLoggedIn()
	{
		return $this->bLoggedIn;
	}
	
	static function getNewSessionId()
	{
		//generate a session ID hard to predict
		return AnwUtils::genStrongRandMd5();
	}
	
	function setLang($sLang)
	{
		if (!Anwi18n::isValidLang($sLang))
		{
			throw new AnwBadLangException();
		}
		if ($sLang != $this->sLang)
		{
			$this->sLang = $sLang;
			if (AnwUsers::isDriverInternal() && $this->isLoggedIn())
			{
				$this->oUser->changeLang($sLang);
			}
		}
	}
	
	function setTimezone($nTimezone)
	{
		if (!AnwUsers::isValidTimezone($nTimezone))
		{
			throw new AnwBadTimezoneException();
		}
		if ($nTimezone != $this->nTimezone)
		{
			$this->nTimezone = $nTimezone;
			if (AnwUsers::isDriverInternal() && $this->isLoggedIn())
			{
				$this->oUser->changeTimezone($nTimezone);
			}
		}
	}
	
	function login($oUser, $bResume)
	{
		$this->oUser = $oUser;
		$this->bResume = $bResume;
		$this->sLang = $oUser->getLang();
		$this->nTimezone = $oUser->getTimezone();
		$this->nTimeAuth = time();
		$this->bLoggedIn = true;
	}
	
	function logout()
	{
		$this->oUser = new AnwUserAnonymous();
		$this->bResume = false;
		$this->nTimeAuth = 0;
		$this->bLoggedIn = false;
	}
	
	static function rebuildSession($oUser, $bResume, $sLang, $nTimezone, $sId, $nTimeStart, $nTimeSeen, $nTimeAuth=0)
	{
		$oSession = new AnwSession();
		$oSession->oUser = $oUser;
		$oSession->bResume = $bResume;
		
		if (!Anwi18n::isValidLang($sLang))
		{
			$sLang = AnwComponent::globalCfgLangDefault();
		}
		$oSession->sLang = $sLang;
		
		if ($oUser->exists())
		{
			$oSession->bLoggedIn = true;
		}
		else
		{
			$oSession->bLoggedIn = false;
		}
		
		$oSession->sId = $sId;
		
		if (!AnwUsers::isValidTimezone($nTimezone))
		{
			$nTimezone = AnwComponent::globalCfgTimezoneDefault();
		}
		$oSession->nTimezone = $nTimezone;
		
		$oSession->nTimeStart = $nTimeStart;
		$oSession->nTimeSeen = $nTimeSeen;
		$oSession->nTimeAuth = $nTimeAuth;
				
		return $oSession;
	}
	
	private static function debug($sMsg)
	{
		AnwDebug::log("(AnwSessions)".$sMsg);
	}
}

?>