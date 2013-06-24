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
 * Anwiki user definition.
 * @package Anwiki
 * @version $Id: class_user.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwUser{
	function isPhpEditionAllowed()
	{
		return AnwAcls::isPhpEditionAllowed($this);
	}
	
	function isJsEditionAllowed()
	{
		return AnwAcls::isJsEditionAllowed($this);
	}
	
	function isAdminAllowed()
	{
		return AnwAcls::isAdminAllowed($this);
	}
	
	function checkPhpEditionAllowed()
	{
		if (!$this->isPhpEditionAllowed())
		{
			throw new AnwAclPhpEditionException();
		}
	}
	
	function checkJsEditionAllowed()
	{
		if (!$this->isJsEditionAllowed())
		{
			throw new AnwAclJsEditionException();
		}
	}
}

//------------------------------------------------------------------------
//------------------------------------------------------------------------

class AnwUserAnonymous extends AnwUser
{
	function __construct()
	{
	}
	
	function getDisplayName()
	{
		return AnwComponent::g_("user_displayname_anonymous");
	}
	
	function getLogin()
	{
		return 'anonymous';
	}
	
	//hardcoded security for denying anonymous users to edit malicious code
	function isPhpEditionAllowed()
	{
		return false;
	}
	
	//hardcoded security for denying anonymous users to edit malicious code
	function isJsEditionAllowed()
	{
		return false;
	}
}

//------------------------------------------------------------------------
//------------------------------------------------------------------------

abstract class AnwUserReal extends AnwUser
{
	protected $nId;
	protected $sLogin;
	protected $sDisplayName;
	protected $sEmail;
	protected $sLang;
	protected $nTimezone;
	
	protected $bExists; //does the user exist
	protected $bInfoLoaded = false; //did we load existing user's info
	
	//------------------------------------------------
	// SETTERS
	//------------------------------------------------
	
	
	/**
	 * @throws AnwBadLangException
	 */
	function changeLang($sLang)
	{
		if ($this->exists())
		{
			AnwUsers::changeUserLang($this, $sLang);
			$this->sLang = $sLang;
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}
	
	function changeTimezone($nTimezone)
	{
		if ($this->exists())
		{
			AnwUsers::changeUserTimezone($this, $nTimezone);
			$this->nTimezone = $nTimezone;
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}
	
	function changeDisplayName($sDisplayName)
	{
		if ($this->exists())
		{
			AnwUsers::changeUserDisplayName($this, $sDisplayName);
			$this->sDisplayName = $sDisplayName;
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}
	
	function changeEmail($sEmail)
	{
		if ($this->exists())
		{
			AnwUsers::changeUserEmail($this, $sEmail);
			$this->sEmail = $sEmail;
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}
	
	function changePassword($sPassword)
	{
		if ($this->exists())
		{
			AnwUsers::changeUserPassword($this, $sPassword);
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}
	
	function authenticate($sPassword)
	{
		AnwUsers::authenticate($this->getLogin(), $sPassword);
	}
	
	//------------------------------------------------
	// ACCESSORS
	//------------------------------------------------
	
	function getId()
	{
		if (!$this->nId && !$this->exists()) throw new AnwUserNotFoundException();
		return $this->nId;
	}
	
	/**
	 * @throws AnwUserNotFoundException
	 */
	function getLogin()
	{
		if (!$this->sLogin && !$this->exists()) throw new AnwUserNotFoundException();
		return $this->sLogin;
	}
	
	function getDisplayName()
	{
		if (!$this->sDisplayName && !$this->exists()) return AnwComponent::g_("user_displayname_anonymous");
		return $this->sDisplayName;
	}
	
	/**
	 * @throws AnwUserNotFoundException
	 */
	function getEmail()
	{
		if (!$this->sEmail) $this->loadInfo();
		if (!$this->sEmail) throw new AnwUserNotFoundException();
		return $this->sEmail;
	}
	
	/**
	 * @throws AnwUserNotFoundException
	 */
	function getLang()
	{
		if (!$this->sLang) $this->loadInfo();
		if (!$this->sLang) throw new AnwUserNotFoundException();
		return $this->sLang;
	}
	
	function getTimezone()
	{
		if (!is_numeric($this->nTimezone)) $this->loadInfo();
		if (!is_numeric($this->nTimezone)) throw new AnwUserNotFoundException();
		return $this->nTimezone;
	}
	
	function exists()
	{
		$this->loadInfo();
		return $this->bExists;
	}
	
	
	//------------------------------------------------
	// USERS SYSTEM
	//------------------------------------------------
	
	static function rebuildUser($nId, $sLogin, $sDisplayName, $sEmail, $sLang, $nTimezone)
	{
		$oUser = new AnwUserById($nId);

		if (!AnwUsers::isValidLogin($sLogin))
		{
			throw new AnwBadLoginException();
		}
		$oUser->sLogin = $sLogin;
		
		if (!AnwUsers::isValidDisplayName($sDisplayName))
		{
			throw new AnwBadDisplayNameException();
		}
		$oUser->sDisplayName = $sDisplayName;
		
		if (!AnwUsers::isValidEmail($sEmail))
		{
			throw new AnwBadEmailException();
		}
		$oUser->sEmail = $sEmail;
		
		if (!Anwi18n::isValidLang($sLang))
		{
			$sLang = AnwComponent::globalCfgLangDefault();
		}
		$oUser->sLang = $sLang;
		
		if (!AnwUsers::isValidTimezone($nTimezone))
		{
			$nTimezone = AnwComponent::globalCfgTimezoneDefault();
		}
		$oUser->nTimezone = $nTimezone;
		
		$oUser->bExists = true;
		$oUser->bInfoLoaded = true;
		return $oUser;
	}
	
	protected function loadInfoFromUser($oUser)
	{
		//user exists, update it's attributes
		//do some tests to not override possibly updated attributes...
		$this->bExists = true;
		
		$this->sLogin = $oUser->getLogin();
		
		if(!$this->sDisplayName)
		{
			$this->sDisplayName = $oUser->getDisplayName();
		}
		
		if(!$this->sEmail)
		{
			$this->sEmail = $oUser->getEmail();
		}
		
		if (!$this->sLang)
		{
			$this->sLang = $oUser->getLang();
		}
		
		if (!is_numeric($this->nTimezone))
		{
			$this->nTimezone = $oUser->getTimezone();
		}
	}
	
	/*function save()
	{
		if ($this->exists())
		{
			AnwUsers::updateUser($this);
		}
		else
		{
			throw new AnwUserNotFoundException();
		}
	}*/
	
	protected function debug($sMessage)
	{
		return AnwDebug::log("(AnwUser)".$sMessage);
	}
}

//------------------------------------------------------------------------
//------------------------------------------------------------------------

class AnwUserById extends AnwUserReal
{
	function __construct($nId, $sLogin=null)
	{
		$this->nId = $nId;
		$this->sLogin = $sLogin;
	}
	
	function getLogin()
	{
		if (!$this->sLogin) $this->loadInfo();
		return parent::getLogin();
	}
	
	function loadInfo()
	{
		if ($this->bInfoLoaded) return;
		
		try
		{
			$this->debug("Loading user info...");
			$oUser = AnwUsers::getUser( $this->nId );
			$this->sLogin = $oUser->getLogin();
			parent::loadInfoFromUser($oUser);
		}
		catch(AnwUserNotFoundException $e)
		{
			$this->bExists = false;
		}
		$this->bInfoLoaded = true;
	}
}

?>