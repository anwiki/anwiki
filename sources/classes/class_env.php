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
 * Anwiki interactions with environment.
 * @package Anwiki
 * @version $Id: class_env.php 372 2011-09-15 20:58:37Z "anwiki" $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwEnv
{
	//PHP session checking
	const PHPSESSION_IDENTIFIER = "anwphpsessidentifier";
	const PHPSESSION_CODE = "anwphpsesscode";
	const COOKIE_PHPSESSION_CODE = "anwphpsesscode";
	
	private static $asCookiesPrefixes = array();
	private static $asSessionPrefixes = array();
	
	/**
	 * Do some security checks to protect from various threats.
	 */
	static function init()
	{
		//use cookies instead of ?PHPSESSID vars
		ini_set('session.use_trans_sid', 0);
		unset($_GET[session_name()]);
		unset($_POST[session_name()]);
		unset($_REQUEST[session_name()]);
		
		//turn off magic quotes
		@set_magic_quotes_runtime(0);
		
		//check PHP session
		self::initPHPSession();
	}
	
	//
	// PHP session management
	//
	
	/**
	 * Initialize PHP session and do security checks to prevent session stealing.
	 */
	private static function initPHPSession()
	{
		//start PHP session
		session_write_close();
		session_start();
		
		self::debug("initPHPSession: PHPSESSID=".session_id());
		//1. check client's identifier code
		//we make sure that who created the session is the one who resumes it.
		$sSessionIdentifier = self::calculateSessionIdentifier();
		if ( AnwEnv::_SESSION(self::PHPSESSION_IDENTIFIER) 
			&& $sSessionIdentifier == AnwEnv::_SESSION(self::PHPSESSION_IDENTIFIER) )
		{
			//2. check session code
			//just to make it harder, even if someone who steals PHPSESSID would probably steal the session code too.
			if ( AnwEnv::_SESSION(self::PHPSESSION_CODE) 
				&& AnwEnv::_COOKIE(self::COOKIE_PHPSESSION_CODE) 
				&& AnwEnv::_SESSION(self::PHPSESSION_CODE) == AnwEnv::_COOKIE(self::COOKIE_PHPSESSION_CODE) )
			{
				//allright, session seems safe to work with
				self::debug("initPHPSession: OK, resuming PHP session (".session_id().")");
				return;
			}
			else
			{
				self::debug("initPHPSession: WARNING: no valid PHP session: bad session code");
			}
		}
		else
		{
			self::debug("initPHPSession: no valid PHP session: bad or missing session identifier");
		}
		

		//no valid session found
		self::debug("initPHPSession: no valid PHP session found, starting a new session (".session_id().")");
		
		//clear session data
		if (is_array($_SESSION))
		{
			foreach ($_SESSION as $i => $v)
			{
				AnwEnv::unsetSession($i);
			}
		}
		
		//start a new session and delete old phpsession file
		session_regenerate_id(true);			
		
		//set the session identifier, for next hit
		AnwEnv::putSession(self::PHPSESSION_IDENTIFIER, $sSessionIdentifier);
		
		//generate a session code
		$sSessionCode = self::generateSessionCode();
		AnwEnv::putCookie(self::COOKIE_PHPSESSION_CODE, $sSessionCode);
		AnwEnv::putSession(self::PHPSESSION_CODE, $sSessionCode);
	}
	
	/**
	 * Generates a code supposed to be unic and fixed for each client.
	 */
	static function calculateSessionIdentifier()
	{
		static $sSessionIdentifier; //caching
		
		if (!$sSessionIdentifier)
		{		
			$sClientSign = "client-sign";
			if (AnwComponent::globalCfgSessionCheckClient())
			{
				$sClientSign .= AnwEnv::_SERVER('HTTP_ACCEPT_LANGUAGE','empty').'-'
								.AnwEnv::_SERVER('HTTP_USER_AGENT','empty').'-';
			}
			
			if (AnwComponent::globalCfgSessionCheckIp())
			{
				$asIpBytes = explode('.', self::getIp());
				$sClientSign .= $asIpBytes[0].'-'.$asIpBytes[1].'-'.$asIpBytes[2];
			}
			
			//Don't use SERVER_SIGNATURE as it may change passing from HTTP to HTTPS
			$sServerSalt = "server-salt";
			if (AnwComponent::globalCfgSessionCheckServer())
			{
				$sServerSalt .= AnwEnv::_SERVER('SERVER_ADMIN','empty').'-'
								.AnwEnv::_SERVER('SERVER_SOFTWARE','empty');
			}
			
			$sSessionIdentifier = md5( $sClientSign.'at'.$sServerSalt );
		}
		
		return $sSessionIdentifier;
	}
	
	/**
	 * Generate some random session code.
	 */
	private static function generateSessionCode()
	{
		//generate a session code hard to predict
		$sSessionCode = AnwUtils::genStrongRandMd5();
		return $sSessionCode;
	}
	
	
	
	
	//
	// SESSION
	//
	
	/**
	 * Checks if the session path is writable.
	 * @return true if the session path is writable, false otherwise.
	 */
	static function isSessionPathWritable()
	{
		$sPhpSessionPath = ini_get('session.save_path');
		
		// do we have a session path?
		if (!$sPhpSessionPath)
		{
			self::debug("isSessionPathWritable(): session path is not configured!");
			return false;
		}
		
		// FS#141 do we have open_basedir restriction? If so, we don't check the writability of the dir,
		// as in some setups, sessions path may be outside open_basedir but still work correctly
		// while is_writable returns false with a "open basedir restriction in effect" warning
		if (!ini_get('open_basedir'))
		{
			// is it writable?
			if (!is_writable($sPhpSessionPath))
			{
				self::debug("isSessionPathWritable(): session path is not writable! (".$sPhpSessionPath.")");
				return false;
			}
		}
		else
		{
			self::debug("isSessionPathWritable(): open_basedir is set, we won't check for writability! (".$sPhpSessionPath.")");
		}
		return true;
	}
	
	static function setSessionPrefix($sVarName, $sPrefix)
	{
		self::$asSessionPrefixes[$sVarName] = $sPrefix;
	}
	
	private static function getSessionPrefix($sVarName)
	{
		$sPrefix = "";
		if (isset(self::$asSessionPrefixes[$sVarName]))
		{
			$sPrefix = self::$asSessionPrefixes[$sVarName];
		}
		else
		{
			$sPrefix = AnwComponent::globalCfgPrefixSession();
		}
		return $sPrefix;
	}
	
	static function _SESSION($sVarName, $sDefaultValue=false)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sVarName = self::getSessionPrefix($sVarName).$sVarName;
				
		if (isset($_SESSION[$sVarName]))
		{
			$sValue = $_SESSION[$sVarName];
			if (is_object($sValue)) {
				self::debug("_SESSION(".$sVarName.") : object(".get_class($sValue).")");
			}
			else {
				self::debug("_SESSION(".$sVarName.") : ".$sValue);
			}
			return $sValue;
		}
		else
		{
			self::debug("_SESSION(".$sVarName.") : NOT SET");
			return $sDefaultValue;
		}
	}
	
	static function putSession($sVarName, $sValue)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sVarName = self::getSessionPrefix($sVarName).$sVarName;
				
		$_SESSION[$sVarName] = $sValue;
		if (is_object($sValue)) {
			self::debug("putSession(".$sVarName.") : object(".get_class($sValue).")");
		}
		else {
			self::debug("putSession(".$sVarName.") : ".$sValue);
		}
	}
	
	static function unsetSession($sVarName)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sVarName = self::getSessionPrefix($sVarName).$sVarName;
				
		if (isset($_SESSION[$sVarName]))
		{
			unset($_SESSION[$sVarName]);
			self::debug("unsetSession(".$sVarName.")");
		}
	}
	
	
	
	
	//
	// COOKIES
	//
	
	static function setCookiesPrefix($sVarName, $sPrefix)
	{
		self::$asCookiesPrefixes[$sVarName] = $sPrefix;
	}
	
	private static function getCookiesPrefix($sVarName)
	{
		$sPrefix = "";
		if (isset(self::$asCookiesPrefixes[$sVarName]))
		{
			$sPrefix = self::$asCookiesPrefixes[$sVarName];
		}
		else
		{
			$sPrefix = AnwComponent::globalCfgPrefixCookies();
		}
		return $sPrefix;
	}
	
	static function putCookie($sName, $mValue, $nExpires=0, $sPath=null, $sDomain=null)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sName = self::getCookiesPrefix($sName).$sName;
				
		if ($nExpires == -1)
		{
			//-1 for unlimited <> 2 years
			$nExpires = time()+86400*365*2;
		}
		if (!$sPath)
		{
			$sPath = AnwComponent::globalCfgCookiesPath();
		}
		if (!$sDomain)
		{
			$sDomain = AnwComponent::globalCfgCookiesDomain();
		}
		setcookie($sName, $mValue, $nExpires, $sPath, $sDomain);
		$_COOKIE[$sName] = $mValue;
		self::debug("putCookie(".$sName.") : ".$mValue." [".$sPath." ; ".$nExpires." ; ".$sDomain."]");
	}
	
	static function unsetCookie($sName, $sPath=null, $sDomain=null)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sName = self::getCookiesPrefix($sName).$sName;
		
		$nExpires = time()-86400; //unset
		if (!$sPath)
		{
			$sPath = AnwComponent::globalCfgCookiesPath();
		}
		if (!$sDomain)
		{
			$sDomain = AnwComponent::globalCfgCookiesDomain();
		}
		setcookie($sName, "", $nExpires, $sPath, $sDomain);
		if (isset($_COOKIE[$sName]))
		{
			unset($_COOKIE[$sName]);
		}
		self::debug("unsetCookie(".$sName.") [".$sPath." ; ".$sDomain."]");
	}
	
	static function _COOKIE($sName, $mDefaultValue=false)
	{
		//add a prefix to avoid conflicts between multiple anwiki instances running on same host
		$sName = self::getCookiesPrefix($sName).$sName;
				
		if (isset($_COOKIE[$sName]))
		{
			self::debug("_COOKIE(".$sName.") : ".$_COOKIE[$sName]);
			return $_COOKIE[$sName];
		}
		self::debug("_COOKIE(".$sName.") returning default value : ".$mDefaultValue);
		return $mDefaultValue;
	}
	
	
	
	//
	// GET
	//
	
	static function _GET($sVarName, $sDefaultValue=false)
	{
		if (isset($_GET[$sVarName]))
		{
			return self::cleanGet($_GET[$sVarName]);
		}
		else
		{
			return $sDefaultValue;
		}
	}
	
	private static function cleanGet($sValue)
	{
		$sReturn = trim(stripslashes(urldecode($sValue)));
		$sReturn = AnwUtils::standardizeCRLF($sReturn);
		return $sReturn;
	}
	
	
	
	//
	// POST
	//
	
	static function _POST($sVarName, $sDefaultValue=false, $bSkipTrim=false)
	{
		if (isset($_POST[$sVarName]))
		{
			$sReturn = $_POST[$sVarName];
			if (is_array($sReturn))
			{
				foreach ($sReturn as $i => $sValue)
				{
					$sReturn[$i] = self::cleanPost($sReturn[$i], $bSkipTrim);
				}
			}
			else //string
			{
				$sReturn = self::cleanPost($sReturn, $bSkipTrim);
			}
		}
		else
		{
			$sReturn = $sDefaultValue;
		}
		return $sReturn;
	}
	
	private static function cleanPost($sReturn, $bSkipTrim=false)
	{
		if ( get_magic_quotes_gpc() )
		{
			$sReturn = stripslashes($sReturn);
		}
		if (!$bSkipTrim)
		{
			$sReturn = trim($sReturn);
		}
		$sReturn = AnwUtils::standardizeCRLF($sReturn);
		return $sReturn;
	}
	
	/**
	 * Detect flood coming from _GET / _POST.
	 */
	/*private function cleanInputArray($amInput, $nDepth=1)
	{
		if ($nDepth > 9)
		{
			throw new AnwUnexpectedException("Bad input detected");
		}
		
		$amCleanInput = array();
		foreach ($amInput as $i => $v)
		{
			if (is_array($v))
			{
				$amCleanInput[$i] = self::cleanInputArray($v, $nDepth+1);
			}
			else
			{
				$i = self::cleanInputIndice($i);
				$v = self::cleanInputValue($v);
				$amCleanInput[$i] = $v;
			}
		}
	}
	
	private function cleanInputIndice($sIndice)
	{
		$sIndice = htmlspecialchars(urldecode($sIndice));
		return $sIndice;
	}
	
	private function cleanInputValue($sValue)
	{
		//some filters will be added to detect attacks
		return $sValue;
	}
	*/
	
	
	//
	// FILES
	//
	
	static function _FILES($sVarName)
	{
		if (isset($_FILES[$sVarName]) && isset($_FILES[$sVarName]['tmp_name']) && is_uploaded_file($_FILES[$sVarName]['tmp_name']))
		{
			$sReturn = $_FILES[$sVarName];
		}
		else
		{
			$sReturn = false;
		}
		return $sReturn;
	}
	
	
	
	//
	// CLIENT SIDE
	//
	
	static function getIp(/*$bLookForProxy=false*/)
	{
		// hardcoded IP for batch mode
		if (ANWIKI_MODE_BATCH)
		{
			return "127.0.0.1";
		}
		
		//discover client IPs
		
		$asFoundIps = array();
		
		//don't look at proxy fields as it can easily be spoofed
		/*
		if ($bLookForProxy)
		{
			$asFoundIps = array_reverse( explode(',', AnwEnv::_SERVER('HTTP_X_FORWARDED_FOR')) );
			$asFoundIps[] = AnwEnv::_SERVER('HTTP_CLIENT_IP');
			$asFoundIps[] = AnwEnv::_SERVER('HTTP_PROXY_USER');
		}
		*/
		
		$asFoundIps[] = AnwEnv::_SERVER('REMOTE_ADDR');
		
		//pick up the first valid IP
		$sValidIp = null;
		foreach($asFoundIps as $sFoundIp)
		{
			$sFoundIp = trim($sFoundIp);
			if (self::isValidIp($sFoundIp))
			{
				$sValidIp = $sFoundIp;
			}
		}
		
		if (!$sValidIp)
		{
			AnwDieCriticalError("Unable to determine your IP address");
		}
		
		return $sValidIp;
	}
	
	private static function isValidIp($sIp)
	{
		return self::validateIPv4($sIp) || self::validateIPv6($sIp);
	}
	
	private static function validateIPv4($IP)
	{
	    return $IP == long2ip(ip2long($IP));
	}
	
	/**
	 * Thanks to crisp (crisp.tweakblogs.net)
	 */
	private static function validateIPv6($IP)
	{
	    if (strlen($IP) < 3)
	        return $IP == '::';
	
	    if (strpos($IP, '.'))
	    {
	        $lastcolon = strrpos($IP, ':');
	        if (!($lastcolon && self::validateIPv4(substr($IP, $lastcolon + 1))))
	            return false;
	
	        $IP = substr($IP, 0, $lastcolon) . ':0:0';
	    }
	
	    if (strpos($IP, '::') === false)
	    {
	        return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $IP);
	    }
	
	    $colonCount = substr_count($IP, ':');
	    if ($colonCount < 8)
	    {
	        return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $IP);
	    }
	
	    // special case with ending or starting double colon
	    if ($colonCount == 8)
	    {
	        return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $IP);
	    }
	
	    return false;
	} 
	
	//
	// SERVER SIDE
	//
	
	static function _SERVER($sVarName, $sDefaultValue=false)
	{
		if (isset($_SERVER[$sVarName]))
		{
			self::debug("_SERVER(".$sVarName.") : ".$_SERVER[$sVarName]);
			return $_SERVER[$sVarName];
		}
		else
		{
			self::debug("_SERVER(".$sVarName.") : returning default value");
			return $sDefaultValue;
		}
	}
	
	static function isHttps()
	{
		return self::_SERVER("HTTPS") ? true : false;
	}
	
	static function getProcessUser()
	{
		$sProcessUser = posix_getpwuid(posix_geteuid());
		return $sProcessUser['name'];
	}
	
	static function getProcessGroup()
	{
		$sProcessUser = posix_getpwuid(posix_geteuid());
		$sProcessGroup = posix_getgrgid($sProcessUser['gid']);
		return $sProcessGroup['name'];
	}
	
	static function getCurrentPath()
	{
		$sCurrentPath = self::_SERVER("REQUEST_URI");
		return $sCurrentPath;
	}
	
	//
	// DEBUG
	//
	
	static function writeDebug()
	{
		$asServerInfos = array();
		$asServerInfos[] = 'HTTP_REFERER';
		$asServerInfos[] = 'REMOTE_ADDR';
		$asServerInfos[] = 'REMOTE_HOST';
		$asServerInfos[] = 'SCRIPT_URI';
		$asServerInfos[] = 'REQUEST_URI';
		$asServerInfos[] = 'REQUEST_METHOD';
		$asServerInfos[] = 'HTTP_USER_AGENT';
		//$asServerInfos[] = 'HTTP_ACCEPT_LANGUAGE';
		//$asServerInfos[] = 'HTTP_ACCEPT_CHARSET';
		foreach ($asServerInfos as $sServerInfo){
			self::debug("(env) ".$sServerInfo." : ".@$_SERVER[$sServerInfo]);
		}
		
		if (ANWIKI_DEVEL)
		{
			// test to avoid warning if no session started
			if (isset($_SESSION)) {
				foreach (@$_SESSION as $sName => $sValue){
					if (is_object($sValue)) {
						//self::debug("(env) _SESSION[".$sName."] : object(".get_class($sValue).")");
					}
					else {
						self::debug("(env) _SESSION[".$sName."] : ".$sValue);
					}
				}
			}
			foreach (@$_COOKIE as $sName => $sValue){
				self::debug("(env) _COOKIE[".$sName."] : ".$sValue);
			}
			foreach (@$_GET as $sName => $sValue){
				self::debug("(env) _GET[".$sName."] : ".$sValue);
			}
			foreach (@$_POST as $sName => $sValue){
				if ($sName == "reauth" || $sName == "login" || $sName == "password")
				{
					// hide confidential data
					$sValue = "***";
				}
				self::debug("(env) _POST[".$sName."] : ".(is_array($sValue)?implode(';',$sValue):$sValue));
			}
		}
	}
	
	static function hasSymlink()
	{
		return function_exists('symlink');
	}
	
	//------------
	
	private static function debug($sInfo)
	{
		AnwDebug::log('(AnwEnv)'.$sInfo);
	}
}

?>