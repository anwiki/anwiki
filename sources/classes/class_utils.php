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
 * Anwiki toolbox.
 * @package Anwiki
 * @version $Id: class_utils.php 360 2011-02-20 21:58:26Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwUtils
{
	private static $asOb = array();
	
	const MAXLEN_CONTENTCLASS=40;
	const MAXLEN_INDEXVALUE=20;
	
	const FLAG_UNTRANSLATED_OPEN = "[untr]";
	const FLAG_UNTRANSLATED_CLOSE = "[/untr]";
	const XML_NODENAME_UNTRANSLATABLE = "fix"; //moved from AnwXml for performances issues
	
	const SORT_BY_NAME = "myname";
	const SORT_BY_TIME = "mytime";
	const SORTORDER_ASC = "asc";
	const SORTORDER_DESC = "desc";	
	
	const FILTER_OP_EQUALS = '=';
	const FILTER_OP_LIKE = ':';
	const FILTER_OP_LT = '_lt_';
	const FILTER_OP_GT = '_gt_';
	const FILTER_OP_LE = '_le_';
	const FILTER_OP_GE = '_ge_';
	
	const SESSION_POST = "anwpostdata";
	const INDEX_FILE = "index.php";
	
	static function checkFriendAccess($mClassNames)
	{
		if (!is_array($mClassNames))
		{
			$mClassNames = array($mClassNames);
		}
		$trace = debug_backtrace();
		if(!isset($trace[2]['class']) || !in_array($trace[2]['class'], $mClassNames))
		{	//print_r($trace);exit;
			print "friendAccess denied for ".$trace[2]['class'].", accessing ".$trace[1]['function'];
			exit;
		}
	}
	
	static function genStrongRandMd5()
	{
		return md5(uniqid(microtime().rand(), true));
	}
	
	static function genUniqueIdNumeric()
	{
		list($usec, $sec) = explode(" ", microtime());
		list($int, $dec) = explode(".", $usec);
		return $sec.$dec;
	}
	
	static function time($nTimestamp=false, $nTimezone=false)
	{
		if ($nTimestamp === false)
		{
			$nTimestamp = time();
		}
		if ($nTimezone === false)
		{
			$nTimezone = AnwCurrentSession::getTimezone();
		}
		$nTimestamp += ($nTimezone*3600) - intval(date('Z'));
		return $nTimestamp;
	}
	
	static function strtotime($sValue, $nTimezone=false, $nTimestamp=false)
	{
		return strtotime($sValue, self::time($nTimestamp, $nTimezone));
	}
	
	/**
	 * Returns the timestamp for midnight today.
	 * @param unknown_type $nTimestamp
	 * @param unknown_type $nTimezone
	 * @return unknown_type
	 */
	static function timeToday($nTimestamp=false, $nTimezone=false)
	{
		return self::strtotime("today", $nTimezone, $nTimestamp);
	}
	
	static function date($sFormat, $nTimestamp, $nTimezone=false)
	{
		$nTimestamp = self::time($nTimestamp, $nTimezone);
		return date($sFormat, $nTimestamp);
	}
	
	static function httpPostToSession()
	{
		if (is_array($_POST) && count($_POST) > 0)
		{
			self::debug("Saving POST to session...");
			AnwEnv::putSession(self::SESSION_POST, $_POST);
		}
	}
	
	static function restoreHttpPostFromSession()
	{
		if ( AnwEnv::_SESSION(self::SESSION_POST) 
			&& is_array(AnwEnv::_SESSION(self::SESSION_POST)) )
		{
			$asPost = AnwEnv::_SESSION(self::SESSION_POST); //warning, we get a reference to _SESSION here!
			
			self::debug("Restoring POST from session...");
			foreach ($asPost as $i => $v)
			{
				if (!isset($_POST[$i]))
				{
					$_POST[$i] = $v;
					self::debug("RestoreHttpPostFromSession(): restored _POST[$i]");
				}
				else
				{
					self::debug("RestoreHttpPostFromSession(): skipping _POST[$i], already set");
				}
				/*if (!isset($_REQUEST[$i]))
				{
					$_REQUEST[$i] = $v;
					self::debug("RestoreHttpPostFromSession(): restored _REQUEST[$i]");
				}
				else
				{
					self::debug("RestoreHttpPostFromSession(): skipping _REQUEST[$i], already set");
				}*/
			}
			self::debug("anwpostdata : unset");
			AnwEnv::unsetSession(self::SESSION_POST);
		}
		else
		{
			self::debug("ResrestoreHttpPostFromSession(): nothing to restore");
		}
	}
	
	static function dateToTime($sDate)
	{
		return strtotime($sDate);
	}
		
	static function isViewDebugAuthorized()
	{
		return AnwComponent::globalCfgTraceEnabled() && in_array(AnwEnv::getIp(), AnwComponent::globalCfgTraceViewIps());
	}
	
	
	
	
	static function array_remove($amArray, $mValueToDelete)
	{
		foreach ($amArray as $i => $mValue)
		{
			if ($mValue == $mValueToDelete)
			{
				unset($amArray[$i]);
			}
		}
		return $amArray;
	}
	
	
	static function redirect($destination=false, $bPermanent=false)
	{
		if (!$destination || !self::isSafeUrl($destination))
		{
			$destination = self::link(AnwComponent::globalCfgHomePage());
		}
		
		// redirection fails if we don't remove html entity &amp;
		$destination = str_replace('&amp;', '&', $destination);
		
		if (self::isViewDebugAuthorized())
		{
			print 'Redirection ';
			if ($bPermanent)
			{
				print 'PERMANENT ';
			}
			print ': <a href="'.self::xQuote($destination).'">'.self::xText($destination).'</a><br/>';
			print '<br/><br/><hr/>'.AnwDebug::getLog();
			exit();
		}
		else
		{
			if ($bPermanent)
			{
				header("HTTP/1.1 301 Moved Permanently");
			}
			header("Location: $destination");
			exit; //TODO properly close DB connexion?
		}		
	}
	
	/**
	 * @param $sLink link which may already contain params
	 * @param $sAdditionalArgs args (parameters and/or anchor) to be appened to the link
	 */
	public static function appendLinkArgs($sLink, $sAdditionalArgs) {
		// do we already have params in both additional args and link?
		if (substr($sAdditionalArgs, 0, 1) == '?' && strpos($sLink, '?') !== false)
		{
			// args must start with '&'
			$sAdditionalArgs = '&'.substr($sAdditionalArgs, 1);
		}
		return $sLink.$sAdditionalArgs;
	}
	
	/**
	 * Thanks to jpic, from php.net.
	 */
//	private static function getRelativePath( $sDestination, $sCurrentLocation ) {
//		if ( substr( $sCurrentLocation, -1 ) == '/' ) {
//            //$sDestination = substr( $sDestination, 0, -1 );
//            $sCurrentLocation .= "dummy";
//        }
//		$sCurrentLocation = dirname($sCurrentLocation);
//        
//        // clean arguments by removing trailing and prefixing slashes
//        if ( substr( $sDestination, -1 ) == '/' ) {
//            $sDestination = substr( $sDestination, 0, -1 );
//        }
//        if ( substr( $sDestination, 0, 1 ) == '/' ) {
//            $sDestination = substr( $sDestination, 1 );
//        }
//
//        if ( substr( $sCurrentLocation, 0, 1 ) == '/' ) {
//            $sCurrentLocation = substr( $sCurrentLocation, 1 );
//        }
//
//        // simple case: $sCurrentLocation is in $sDestination
//        if ( strpos( $sDestination, $sCurrentLocation ) === 0 ) {
//            $offset = strlen( $sCurrentLocation ) + 1;
//            return substr( $sDestination, $offset );
//        }
//
//        $relative  = array(  );
//        $sDestinationParts = explode( '/', $sDestination );
//        $sCurrentLocationParts = explode( '/', $sCurrentLocation );
//
//        foreach( $sCurrentLocationParts as $index => $part ) {
//            if ( isset( $sDestinationParts[$index] ) && $sDestinationParts[$index] == $part ) {
//                continue;
//            }
//
//            $relative[] = '..';
//        }
//
//        foreach( $sDestinationParts as $index => $part ) {
//            if ( isset( $sCurrentLocationParts[$index] ) && $sCurrentLocationParts[$index] == $part ) {
//                continue;
//            }
//
//            $relative[] = $part;
//        }
//
//        return implode( '/', $relative );
//    }
	
    /**
     * Access reserved to unit tests.
     */
//	static function __test_getRelativePath($sDestination, $sCurrentLocation) {
//		return self::getRelativePath($sDestination, $sCurrentLocation);
//	}
    
    private static function getInstallPath()
    {
    	$sAbsoluteInstallUrl = AnwComponent::globalCfgUrlRoot();
    	$sRelativeInstallPath = self::getPathFromUrl($sAbsoluteInstallUrl);
    	return $sRelativeInstallPath;
    }
    
    /**
     * With ending slash.
     */
	private static function getPathFromUrl($sUrlFull)
    {
    	$amUrl = parse_url($sUrlFull);
    	$sPath = @$amUrl['path'];
    	if (!$sPath)
    	{
    		$sPath = "/";
    	}
    	if (substr($sPath, -1) != '/')
    	{
    		$sPath .= '/';
    	}
    	//self::debug("getPathFromUrl(): $sUrlFull --> $sPath");
    	return $sPath;
    }
    
    public static function linkRelative($sUrl=false)
    {
    	$sInstallPath = self::getInstallPath();
    	if ($sUrl === false)
    	{
    		return $sInstallPath;
    	}
    	
    	return $sInstallPath.$sUrl;
    }
    
//	static function linkRelative($sDestinationRelativeToInstallPath)
//	{
//		$sCurrentPath = AnwEnv::getCurrentPath();
//		$sDestinationRelative = self::getInstallPath().$sDestinationRelativeToInstallPath;
//		
//		$sRelativeUrl = self::getRelativePath($sDestinationRelative, $sCurrentPath);
//		self::debug("relativeUrl: ".$sCurrentPath." ---> ".$sDestinationRelative." = ".$sRelativeUrl);
//		return $sRelativeUrl;
//	}
	
    /**
	 * Link to a page.
	 */
	static function link($mPage, $sAction="view", $asParams=array(), $bUseSecureUrl=false)
	{
		if ($mPage instanceof AnwPage)
		{
			$sPagename = $mPage->getName();
		}
		else
		{
			$sPagename = (string)$mPage;
		}
		
		$bUseAbsoluteUrl = false;
		$sLink = self::doLinkGeneric($sAction, $asParams, $bUseSecureUrl, $bUseAbsoluteUrl, $sPagename);
		return $sLink;
	}
	
	/**
	 * Absolute link to a page.
	 */
	static function linkAbsolute($mPage, $sAction="view", $asParams=array(), $bUseSecureUrl=false)
	{
		if ($mPage instanceof AnwPage)
		{
			$sPagename = $mPage->getName();
		}
		else
		{
			$sPagename = (string)$mPage;
		}
		
		$bUseAbsoluteUrl = true;
		$sLink = self::doLinkGeneric($sAction, $asParams, $bUseSecureUrl, $bUseAbsoluteUrl, $sPagename);
		return $sLink;
	}
	
	/**
	 * Link to a global action.
	 */
	static function alink($sAction, $asParams=array(), $bUseSecureUrl=false) 
	{
		$bUseAbsoluteUrl = false;
		$sLink = self::doLinkGeneric($sAction, $asParams, $bUseSecureUrl, $bUseAbsoluteUrl);
		return $sLink;
	}
	
	/**
	 * Absolute link to a global action.
	 */
	static function alinkAbsolute($sAction, $asParams=array(), $bUseSecureUrl=false) 
	{
		$bUseAbsoluteUrl = true;
		$sLink = self::doLinkGeneric($sAction, $asParams, $bUseSecureUrl, $bUseAbsoluteUrl);
		return $sLink;
	}
	
	/**
     * Appropriated link base (absolute or relative, HTTP or HTTPS).
     */
	private static function doLinkBase($bUseSecureUrl, $bUseAbsoluteUrl)
	{
		// when we are in HTTPS, we want to stay in this mode...
		if ( AnwComponent::globalCfgHttpsEnabled() && ($bUseSecureUrl || AnwAction::isActionSecure($sAction) || AnwEnv::isHttps()))
		{
			if (!AnwEnv::isHttps() || $bUseAbsoluteUrl)
			{
				// we switch to HTTPS (or force absolute url)
				$sLink = AnwComponent::globalCfgHttpsUrl();
			}
			else
			{
				// we are already in HTTPS (and we want to stay in this mode - even if action doesn't require it)
				$sLink = self::linkRelative();
			}
		}
		else
		{
			if ($bUseAbsoluteUrl)
			{
				// we force absolute url
				$sLink = AnwComponent::globalCfgUrlRoot();
			}
			else
			{
				// we are already in HTTP
				$sLink = self::linkRelative();
			}
		}
		return $sLink;
	}
	
	/**
	 * $sPagename is null when linking global actions.
	 */
	private static function doLinkGeneric($sAction, $asParams, $bUseSecureUrl, $bUseAbsoluteUrl, $sPagename=null)
	{
		$sLink = self::doLinkBase($bUseSecureUrl, $bUseAbsoluteUrl);
		
		if ($sPagename !== null)
		{
			//
			// page actions
			//
			if (AnwComponent::globalCfgFriendlyUrlEnabled())
			{
				// special case for page actions with friendlyurls enabled
				$sLink .= $sPagename;
				unset($asParams[AnwActionPage::GET_PAGENAME]);
			}
			else
			{
				if (!AnwComponent::globalCfgNoIndexyUrlEnabled())
				{
					$sLink .= self::INDEX_FILE;
				}
				$asParams[AnwActionPage::GET_PAGENAME] = $sPagename;
			}
		}
		else
		{
			//
			// global actions
			//
			if (!AnwComponent::globalCfgNoIndexyUrlEnabled())
			{
				$sLink .= self::INDEX_FILE;
			}
		}
		
		// view action is implicit...
		if ($sAction != "view")
		{
			$asParams[AnwAction::GET_ACTIONNAME] = $sAction;
		}
		
		$sLink .= self::linkParams($asParams);
		return $sLink;
	}
	
	private static function linkParams($asParams=array())
	{
		$sNextParamSeparator = '?';
		$sLink = "";
		
		foreach ($asParams as $name=>$value)
		{
			$sLink .= $sNextParamSeparator.urlencode($name)."=".urlencode($value);
			//$sNextParamSeparator = '&amp;';
			$sNextParamSeparator = '&';
		}
		return $sLink;
	}
	
	//-----------------------------------
	// output filtering
	//-----------------------------------
	
	/**
	 * Filter a string to be output as text.
	 */
	static function xText($sString)
	{
		$sString = htmlentities($sString, ENT_NOQUOTES, "UTF-8");
		return $sString;
	}
	
	/**
	 * Filter a string to be output as attribute between double quotes or simple quotes.
	 */
	static function xQuote($sString)
	{
		$sString = htmlentities($sString, ENT_QUOTES, "UTF-8");
		return $sString;
	}
	
	/**
	 * Filter a string to be output as textarea value.
	 */
	static function xTextareaValue($sString)
	{
		$sString = htmlentities($sString, ENT_NOQUOTES, "UTF-8");
		return $sString;
	}
	
	/**
	 * @deprecated use xQuote() instead.
	 */
	static function escapeFieldValue($sString)
	{
		return self::xQuote($sString);
	}
	
	/**
	 * @deprecated use xTextareaValue() instead.
	 */
	static function escapeTextareaValue($sString)
	{
		return self::xTextareaValue($sString);
	}
	
	/**
	 * @deprecated use xText() or xQuote() instead.
	 */
	static function escapeLink($sString)
	{
		// make links valid XML for output
		return self::xQuote($sString);
	}
	
	//-----------------------------------
	// simple string replacements
	//-----------------------------------
	
	static function escapeTags($sString)
	{
		return str_replace ( array ( /*'&', '"', "'",*/ '<', '>' ), array ( /*'&amp;' , '&quot;', '&apos;' ,*/ '&lt;' , '&gt;' ), $sString );
	}
	
	// useful for JS code inside attributes, such as onclick="window.location.href='..'"
	static function escapeQuoteApostrophe($sString)
	{
		return AnwUtils::xQuote(AnwUtils::escapeApostrophe($sString));
	}
	
	static function escapeQuote($sString, $bEscapeBaskslash=true)
	{
		if ($bEscapeBaskslash) $sString = self::escapeBackslash($sString); //escape backslash FIRST, before escaping any other char!
		$sString = self::escapeBreakLines($sString);
 		return str_replace ( '"', '\"', $sString );
	}
	
	static function unescapeQuote($sString, $bUnEscapeBaskslash=true)
	{
		$sString = str_replace ( '\"', '"', $sString );
		$sString = self::unescapeBreakLines($sString);
 		if ($bUnEscapeBaskslash) $sString = self::unescapeBackslash($sString); //unescape backslash AT LAST, after unescaping any other char!
		return $sString;
	}
	
	static function escapeApostrophe($sString, $bEscapeBaskslash=true)
	{
		if ($bEscapeBaskslash) $sString = self::escapeBackslash($sString); //escape backslash FIRST, before escaping any other char!
		$sString = self::escapeBreakLines($sString);
 		return str_replace ( "'", "\'", $sString );
	}
	
	static function unescapeApostrophe($sString, $bUnEscapeBaskslash=true)
	{
		$sString = str_replace ( "\'", "'", $sString );
		$sString = self::unescapeBreakLines($sString);
 		if ($bUnEscapeBaskslash) $sString = self::unescapeBackslash($sString); //unescape backslash AT LAST, after unescaping any other char!
		return $sString;
	}
	
	private static function escapeBackslash($sString)
	{
		$sString = str_replace('\\', '\\\\', $sString);
		return $sString;
	}
	
	private static function unescapeBackslash($sString)
	{
		$sString = str_replace('\\\\', '\\', $sString);
		return $sString;
	}
	
	private static function escapeBreakLines($sString)
	{
		$sString = str_replace(array("\n", "\r"), array('\n', '\r'), $sString);
		return $sString;
	}
	
	private static function unescapeBreakLines($sString)
	{
		$sString = str_replace(array('\n', '\r'), array("\n", "\r"), $sString);
		return $sString;
	}
	
	/**
	 * Returns true if we can safely redirect to this URL, or false if URL is not trusted.
	 */
	static function isSafeUrl($url) {
		// Ignore URLs using a protocol that isn't HTTP or HTTPS
		if (preg_match('/^([^\/]*):/', $url, $match) && $match[1] != 'http' && $match[1] != 'https') {
			return false;
		}
		return true;
	}
	
	static function escapePhpArg($sString)
	{
		$sString = self::escapeBackslash($sString); //escape backslash FIRST, before escaping any other char!
		
		//escape apostrophe
		$sString = self::escapeApostrophe($sString, false);
		
		//escape ``
		$sString = str_replace('`', '\\`', $sString);
		
		//surround with '...' to avoid PHP interpretation
		$sString = "'".$sString."'";
		
		return $sString;
	}
	
	static function isPhpEvalEnabled()
	{
		return ANWIKI_PHPEVAL_ENABLED && AnwComponent::globalCfgPhpEvalEnabled();
	}
	
	static function evalMixedPhpCode($sHtmlAndPhpCode, $amContextVars=array())
	{
		//check security setting
		if (self::isPhpEvalEnabled())
		{
			$sReturn = "";
			if (self::contentHasPhpCode($sHtmlAndPhpCode))
			{
				//rebuild context vars
				foreach ($amContextVars as $sVarName => $mVarValue)
				{
					$$sVarName = $mVarValue;
				}
				
				self::debug("evalPhpCode: Evaluating some PHP code...");
				self::ob_start_capture("evalPhpCode");
				try
				{
					$result = eval('?>'.$sHtmlAndPhpCode);
					if ($result===false)
					{
						$sReturn = AnwUtils::ob_end_capture("evalPhpCode");
						throw new AnwUnexpectedException("Eval failed");
					}
				}
				catch(AnwRunInterruptionException $e)
				{
					//php code exited, let's continue Anwiki execution...
				}
				$sReturn = AnwUtils::ob_end_capture("evalPhpCode");
			}
			else
			{
				self::debug("evalPhpCode: No PHP code found for evaluation...");
				$sReturn = $sHtmlAndPhpCode;
			}
		}
		else
		{
			//eval is disabled in configuration
			$sReturn = AnwComponent::g_("err_phpeval_disabled");
		}
		return $sReturn;
	}
	
	/**
	 * Evaluate PHP syntax without executing it.
	 * It may be not 100% safe but we use ACLs checks from evalMixedPhpCode before evaluating anything.
	 */
	//thanks to nicolas grekas!
	static function evalMixedPhpSyntax($sHtmlAndPhpCode, &$aPhpError) //$aPhpError gets modified
	{
	    $braces = 0;
	    $inString = 0;
	
	    // We need to know if braces are correctly balanced.
	    // This is not trivial due to variable interpolation
	    // which occurs in heredoc, backticked and double quoted strings
	    foreach (token_get_all($sHtmlAndPhpCode) as $token)
	    {
	        if (is_array($token))
	        {
	            switch ($token[0])
	            {
	            case T_CURLY_OPEN:
	            case T_DOLLAR_OPEN_CURLY_BRACES:
	            case T_START_HEREDOC: ++$inString; break;
	            case T_END_HEREDOC:   --$inString; break;
	            }
	        }
	        else if ($inString & 1)
	        {
	            switch ($token)
	            {
	            case '`':
	            case '"': --$inString; break;
	            }
	        }
	        else
	        {
	            switch ($token)
	            {
	            case '`':
	            case '"': ++$inString; break;
	
	            case '{': ++$braces; break;
	            case '}':
	                if ($inString) --$inString;
	                else
	                {
	                    --$braces;
	                    if ($braces < 0){
	                    	$aPhpError = "<b>PHP syntax error:</b> '}' was never opened";
	                    	return false;
	                    }
	                }
	
	                break;
	            }
	        }
	    }
	
	    if ($braces){
	    	$aPhpError = "<b>PHP syntax error:</b> '{' was never closed";
	    	return false; // Unbalanced braces would break the eval below
	    }
	    else
	    {
	    	self::ob_start_capture("evalSyntax");
	        ob_start(); // Catch potential parse error messages
	        $codeOriginal = $sHtmlAndPhpCode;
	        try
	        {
	        	$sHtmlAndPhpCode = self::evalMixedPhpCode('if(0){' . $sHtmlAndPhpCode . '}'); // Put $code in a dead code sandbox to prevent its execution
	        	$bReturn = true;
	        }
	        catch(AnwUnexpectedException $e)
	        {
	        	$bReturn = false;
	        }
	        $sObCaptured = self::ob_end_capture("evalSyntax");
	        if (!$bReturn)
	        {
	        	$aPhpError = $sObCaptured;
	        }
	        return $bReturn;
	    }
	}
	
	
	//-----------------------------------
	// PATHS
	//-----------------------------------
	
	static function pathImg($name)
	{
		//$name = AnwComponent::getGlobalComponentFullDir().ANWDIR_IMG.$name;
		//$sFileName = ANWDIR_OVERRIDE_STATIC.$name;
		//if (!file_exists(ANWPATH_ROOT_SHARED.$sFileName))
		//{
			//$sFileName = ANWDIR_DEFAULT_STATIC.$name;
		//}
		$sFileUrl = AnwComponent::getGlobalUrlStaticDefault().ANWDIR_IMG.$name;
		return $sFileUrl;
	}
	
	static function cssViewContent($oPage)
	{
		$sContentClassName = $oPage->getPageGroup()->getContentClassName();
		$sCss = 'viewcontent cc-'.$sContentClassName;
		return $sCss;
	}
	
	//misc
	
	static function mkdir($path, $rights = 0775)
	{
		self::debug("mkdir(".$path.")");
		//buggy?
		/*
		$folder_path = array(strstr($path, '.') ? dirname($path) : $path);
		
		while(!@is_dir(dirname(end($folder_path)))
			&& dirname(end($folder_path)) != '/'
			&& dirname(end($folder_path)) != '.'
			&& dirname(end($folder_path)) != '')
		{
			array_push($folder_path, dirname(end($folder_path)));
		}
		
		while($parent_folder_path = array_pop($folder_path))
		{
			self::debug("mkdir -> ".$parent_folder_path);
			if(!@mkdir($parent_folder_path, $rights))
			{
				return false;
			}
		}
		return true;*/
		return mkdir($path, $rights);
	}
	
	static function createDirIfNotExists($sDirName, $nMaxParentsToCreate)
	{
		if (!is_dir($sDirName))
		{
			if ($nMaxParentsToCreate > 0)
			{
				$sParentDirName = dirname($sDirName);
				self::createDirIfNotExists($sParentDirName, $nMaxParentsToCreate-1);
			}
			if (!self::mkdir($sDirName))
			{
				throw new AnwUnexpectedException("Unable to create dir ".$sDirName);
			}
		}
	}
	
	public static function makeSureDirExists($sFileName, $nMaxParentsToCreate=1)
	{
		//levels dir creation
		$sDirName = dirname($sFileName);
		self::createDirIfNotExists($sDirName, $nMaxParentsToCreate);
	}
	
	static function file_put_contents($sFileName, $sContent, $mFlags=null)
	{
		self::makeSureDirExists($sFileName);
		if (!file_exists($sFileName))
		{
			self::debug("touch(".$sFileName.")");
			if (!@touch($sFileName))
			{
				throw new AnwUnexpectedException("Unable to touch file ".$sFileName);
			}
		}
		self::debug("file_put_contents(".$sFileName.")");
		$bTest = @file_put_contents($sFileName, $sContent, $mFlags);
		if ($bTest===false) throw new AnwUnexpectedException("Unable to write ".$sFileName);
	}
	
	static function file_get_contents($sFileName, $mFlags=null)
	{
		self::debug("file_get_contents(".$sFileName.")");
		
		$mContent = @file_get_contents($sFileName, $mFlags);
		if ($mContent === false) throw new AnwUnexpectedException("Unable to read ".$sFileName);
		return $mContent;
	}
	
	const FILE_SERIALIZED_OBJECT_SECURITY = '<?php exit();?>';
	
	static function putFileSerializedObject($sFileName, $oObject, $bSecure=true)
	{
		$sStr = '';
		if ($bSecure) $sStr .= self::FILE_SERIALIZED_OBJECT_SECURITY; //add security check
		$sStr .= serialize($oObject);
		AnwUtils::file_put_contents($sFileName, $sStr, LOCK_EX);
	}
	
	static function getFileSerializedObject($sFileName, $bSecure=true)
	{
		$sStr = AnwUtils::file_get_contents($sFileName);
	 	if ($bSecure) $sStr = substr($sStr, strlen(self::FILE_SERIALIZED_OBJECT_SECURITY)); //remove security check
	 	$oObject = unserialize($sStr);
	 	return $oObject;
	}
	
	static function copy($sSourceName, $sDestName)
	{
		$sDirName = dirname($sDestName);
		if (!is_dir($sDirName))
		{
			self::mkdir($sDirName);
		}
		return copy($sSourceName, $sDestName);
	}
	
	static function rename($sSourceName, $sDestName)
	{
		$sDirName = dirname($sDestName);
		if (!is_dir($sDirName))
		{
			self::mkdir($sDirName);
		}
		return rename($sSourceName, $sDestName);
	}
	
	static function unlink($sFilename, $sSafeTestRootDirectory)
	{
		if (!$sSafeTestRootDirectory || substr($sFilename,0,strlen($sSafeTestRootDirectory))!=$sSafeTestRootDirectory)
		{
			throw new AnwUnexpectedException("Critical error! System tried to delete file which seems not to be in the expected root directory: ".$sFilename." was expected to be in ".$sSafeTestRootDirectory);
		}
		$mResult = unlink($sFilename);
		self::debug('unlink('.$sFilename.') : '.($mResult==1?'OK':'ERROR '.$mResult));
		return $mResult;
	}
	
	static function symlink($sTarget, $sSymlink, $sSafeTestRootDirectory, $bUnlinkIfExists=true)
	{
		self::makeSureDirExists($sSymlink);
		if ($bUnlinkIfExists)
		{
			@self::unlink($sSymlink, $sSafeTestRootDirectory);
			if (file_exists($sSymlink)||is_link($sSymlink))
			{
				self::debug("WARNING, symlink was not correcly deleted: ".$sSymlink);
			}
		}
		self::debug('symlink('.$sSymlink.' -> '.$sTarget.')');
		if (function_exists('symlink'))
		{
			return @symlink($sTarget, $sSymlink);
		}
		else
		{
			self::debug("ERROR! symlink is not available!");
			return false;
		}
	}
	
	/**
	 * Only copy files from one dir to another. Don't copy subdirectories.
	 */
	function copyDirFiles( $source, $target, $bUnlinkIfExists=true ) {
		if ($bUnlinkIfExists && file_exists($target))
		{
			@rmdir($target, $target);
		}
		if ( is_dir( $source ) ) {
			@mkdir( $target );
			$d = dir( $source );
			while ( FALSE !== ( $entry = $d->read() ) ) {
				if ( $entry == '.' || $entry == '..' ) {
					continue;
				}
				$Entry = $source . '/' . $entry; 
				if ( is_dir( $Entry ) ) {
					//skip
				}
				copy( $Entry, $target . '/' . $entry );
			}
	 
			$d->close();
		}else {
			copy( $source, $target );
		}
	}
	
	//deletes all files from a directory
	static function rmdirFiles($sDirName, $sSafeTestRootDirectory)
	{
		if (substr($sDirName, -1, 1) != '/')
		{
			$sDirName .= '/';
		}
		
		if (!is_dir($sDirName))
		{
			return;
		}
		
		$mDir = opendir($sDirName);
		while (($sFileName = readdir($mDir)) !== false)
		{
			if ($sFileName != '.' && $sFileName != '..')
			{
				$sFilePath = $sDirName.$sFileName;
				
				if (is_dir($sFilePath))
				{
					self::rmdirFiles($sFilePath, $sSafeTestRootDirectory);
					self::rmdir($sFilePath, $sSafeTestRootDirectory);
				}
				else
				{
					if (!self::unlink($sFilePath, $sSafeTestRootDirectory))
					{
						closedir($mDir);
						throw new AnwUnexpectedException("unable to delete file : ".$sFilePath);
					}
				}
			}
		}
		closedir($mDir);
	}
	
	static function rmdir($sFilename, $sSafeTestRootDirectory)
	{
		if (!$sSafeTestRootDirectory || substr($sFilename,0,strlen($sSafeTestRootDirectory))!=$sSafeTestRootDirectory)
		{
			throw new AnwUnexpectedException("Critical error! System tried to delete directory which seems not to be in the expected root directory: ".$sFilename." was expected to be in ".$sSafeTestRootDirectory);
		}
		return rmdir($sFilename);
	}
	
	static function move_uploaded_file($sUploadedFile, $sDestinationPath)
	{		
		$sUploadedFileName = $sUploadedFile['tmp_name'];
		$bResult = move_uploaded_file($sUploadedFileName, $sDestinationPath);
		if (!$bResult) throw new AnwUnexpectedException("move_uploaded_file failed : ".$sDestinationPath);
	}
	
	static function mail($sEmail, $sSubject, $sBody)
	{
		$sSubject = "[".AnwComponent::globalCfgWebsiteName()."] ".$sSubject;
		$sBody .= "\n\n".AnwComponent::globalCfgWebsiteName()."\n";
		$sBody .= AnwComponent::globalCfgUrlRoot();
		@mail($sEmail, $sSubject, $sBody);
		self::debug("(AnwUtils) Sent a mail to ".$sEmail." : ".$sSubject);
	}
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwUtils) ".$sMessage);
	}
	
	static function ob_end_capture($sId)
	{
		//update buffers with current ob_buffer
		$sBufferValue = ob_get_contents();
		foreach(self::$asOb as $sTheId => $sValue)
		{
			self::$asOb[$sTheId] .= $sBufferValue;
		}
		
		//close specified buffer
		$sReturnValue = "";
		if( isset(self::$asOb[$sId]))
		{
			$sReturnValue = self::$asOb[$sId];
			unset(self::$asOb[$sId]);
		}
		
		//reset ob_buffer
		ob_end_clean();
		ob_start();
		
		return $sReturnValue;
	}
	
	static function ob_start_capture($sId)
	{
		if (count(self::$asOb) > 0)
		{
			self::ob_end_capture($sId);
		}
		ob_start();
		self::$asOb[$sId] = "";
	}
	
	static function stripBreakLines($sContent, $bReplaceBySpaces=true)
	{
		$sReplacement = "";
		if ($bReplaceBySpaces) $sReplacement = " ";
		$sContent = str_replace(array("\n", "\r"), $sReplacement, $sContent);
		return $sContent;
	}
	
	
	
	/*
	private static function executeHtmlAndPhpCode_cbk2($asMatches)
	{
		$sItem = $asMatches[1];
		$sFieldName = $asMatches[2];
		
		$sReturn = '';
		$sReturn .= '<?php ';
		$sReturn .= 'try{';
		$sReturn .= 'print $oLoopPage_'.$sItem.'->getContent()->getContentFieldValue("'.$sFieldName.'");';
		$sReturn .= '}catch(AnwException $e){ print AnwComponent::g_("view_fetch_error"); }';
		$sReturn .= '?>';
		
		return $sReturn;
	}*/
	/*
	private static function executeHtmlAndPhpCode_cbk3($asMatches)
	{
		$sItem = $asMatches[1];
		
		$sReturn = '';
		$sReturn .= '<?php ';
		$sReturn .= 'print AnwUtils::link($oLoopPage_'.$sItem.');';
		$sReturn .= '?>';
		
		return $sReturn;
	}
	*/
	
	public static function renderUntr( $sContent )
	{
		$sRegexp = self::getRegexpUntr();
		$sContent = preg_replace($sRegexp, '<span class="untranslated">$1</span>', $sContent);
		return $sContent;
	}
	
	public static function stripUntr( $sContent )
	{
		$sRegexp = self::getRegexpUntr();
		$sContent = preg_replace($sRegexp, '$1', $sContent);
		return $sContent;
	}
	
	static function getRegexpUntr()
	{
		$sFlagOpen = str_replace(array('/', '[', ']'), array('\/', '\[', '\]'),AnwUtils::FLAG_UNTRANSLATED_OPEN);
		$sFlagClose = str_replace(array('/', '[', ']'), array('\/', '\[', '\]'),AnwUtils::FLAG_UNTRANSLATED_CLOSE);
		$sRegexp = '/'.$sFlagOpen.'(.*?)'.$sFlagClose.'/si';
		return $sRegexp;
	}
	
	
	/**
	 * Search a file in ANWPATH_ADDONS or ANWPATH_DEFAULT.
	 */
	public static function getFileDefault($sFile, $sDir)
	{
		$asLocations = array(ANWPATH_ADDONS, ANWPATH_DEFAULT);
		
		foreach ($asLocations as $sLocation)
		{
			$sFileName = $sLocation.$sDir.$sFile;
			if (file_exists($sFileName))
			{
				$bIsAddon = ($sLocation==ANWPATH_ADDONS);
				return array($sFileName, $bIsAddon);
			}
		}
		
		throw new AnwFileNotFoundException("Default file not found : ".$sFile." (".$sDir.")");
	}
	
	/**
	 * Search a file in ANWPATH_OVERRIDE.
	 */
	public static function getFileOverride($sFile, $sDir, $bEvenIfNotExists=false)
	{
		$sFileName = ANWPATH_OVERRIDE.$sDir.$sFile;
		if ($bEvenIfNotExists || file_exists($sFileName))
		{
			return $sFileName;
		}
		
		throw new AnwFileNotFoundException("Override file not found : ".$sFile." (".$sDir.")");
	}
	
	static function getSettingsFromFile($sFileName, $sDir)
	{
		$allSettings = array();
		
		try
		{
			//load default settings
			list($sFilePath, $null) = self::getFileDefault($sFileName, $sDir);
			$cfg = array(); //$cfg is defined in the included file
			//self::debug("getSettingsFromFile: ".$sFilePath);
			(require ($sFilePath)) or die("Unable to load configuration file : ".$sFileName); //not require_once!!!
			foreach ($cfg as $i => $value)
			{
				$allSettings[$i] = $value;
			}
		}
		catch(AnwFileNotFoundException $e){} //no default file
		
		//override if exists
		try
		{
			$sFilePath = self::getFileOverride($sFileName, $sDir);
			$cfg = array(); //$cfg is defined in the included file
			(require ($sFilePath)) or die("Unable to load configuration file : ".$sFileName); //not require_once!!!
			$allSettings = self::getSettingsFromFile_doOverride($allSettings, $cfg);
		}
		catch(AnwFileNotFoundException $e){} //no override file

		return $allSettings;
	}
	
	private static function isMultipleSettingsArray($mDefaultSettings, $mOverrideSettings)
	{
		if (!is_array($mDefaultSettings) || !is_array($mOverrideSettings))
		{
			//it's not an array, so it's not a multiple setting
			return false;
		}
		
		//we look at the first key of the array
		foreach ($mDefaultSettings as $i => $null)
		{
			if (is_int($i))
			{
				//the key is numeric, it's a multiple settings
				//print_r($mDefaultSettings);
				return true;
			}
			else
			{
				//the key is numeric, it's a single setting
				return false;
			}
		}
		
		// if we are here, array $mDefaultSettings was empty. Let's try $mOverrideSettings.
				
		//we look at the first key of the array
		foreach ($mOverrideSettings as $i => $null)
		{
			if (is_int($i))
			{
				//the key is numeric, it's a multiple settings
				//print_r($mOverrideSettings);
				return true;
			}
			else
			{
				//the key is numeric, it's a single setting
				return false;
			}
		}
		
		// if we are here, both arrays were empty. we return true so that getSettingsFromFile_doOverride() will just copy empty value...
		return true;
	}
	
	/**
	 * Only for unittest usage.
	 */
	static function ___unittest_getSettingsFromFile_doOverride($amDefaultSettings, $amOverrideSettings)
	{
		AnwUtils::checkFriendAccess("AnwSettingsTestCase");
		return self::getSettingsFromFile_doOverride($amDefaultSettings, $amOverrideSettings);
	}
	
	//this recursive function is required for handling multidimensionnal config arrays
	private static function getSettingsFromFile_doOverride($amDefaultSettings, $amOverrideSettings)
	{
		foreach ($amOverrideSettings as $i => $mOverrideSetting)
		{
			//override default config
			if (isset($amDefaultSettings[$i]) && is_array($amDefaultSettings[$i]) && is_array($mOverrideSetting) 
				&& !self::isMultipleSettingsArray($amDefaultSettings[$i], $mOverrideSetting)) //only overload atomic fields 
			{
				//recursive call to overload default config
				$amDefaultSettings[$i] = self::getSettingsFromFile_doOverride($amDefaultSettings[$i], $mOverrideSetting);
			}
			else
			{
				//taking override value instead of default value
				$amDefaultSettings[$i] = $mOverrideSetting;
			}
		}
		return $amDefaultSettings;
	}
	
	static function firstWords($sStr, $nMinChars)
	{
		$sStr = strip_tags($sStr); //avoid breaking HTML tags
		
		$asSeparators = array(' ', ',', '!', '.', ':', ';');
		$nMaxChars = min($nMinChars+15, strlen($sStr));
		
		$sReturn = substr($sStr, 0, $nMinChars);
		
		//complete current word
		$iReturn = $nMinChars-1;
		while ($iReturn<$nMaxChars && !in_array($sReturn[$iReturn], $asSeparators))
		{
			$iReturn++;
			$sReturn[$iReturn] = $sStr[$iReturn];
		}
		
		//TODO security check
		if (strstr($sReturn, '<?'))
		{
			$sReturn = "**ERROR**";
		}
		return $sReturn;
	}
	
	static function runCallbacksOnTranslatableField($oInstance, $oContent, $fOnValue=null, $fBeforeContentField=null, $fAfterContentField=null, $bFirstCall=true)
	{
		static $i=0;
		if ($bFirstCall) $i=0; //reset
		
		$aoContentFields = $oContent->getContentFieldsContainer()->getContentFields();
		
		//each ContentFields
		foreach ($aoContentFields as $sFieldName => $oContentField)
		{
			
			if ($oContentField instanceof AnwStructuredContentField_atomic)
			{
				if ($oContentField->isTranslatable())
				{
					$asCurrentValues = $oContent->getContentFieldValues($sFieldName);
					
					if ($fBeforeContentField) $oInstance->$fBeforeContentField($oContentField, $oContent);
					
					//each value of the ContentField
					foreach($asCurrentValues as $sValue)
					{
						$sInputName = self::runCallbacks_getInputName($oContentField->getName(), $i, '_');
						$oXmlValue = AnwUtils::loadXML('<doc>'.$sValue.'</doc>');
						//run callback
						if ($fOnValue) $oInstance->$fOnValue($oContentField, $oXmlValue, $sInputName);
						$i++;
					}
					
					if ($fAfterContentField) $oInstance->$fAfterContentField($oContentField, $oContent);
				}
			}
			else
			{
				$aoSubContents = $oContent->getSubContents($sFieldName);
				if (count($aoSubContents) > 0) //important
				{
					$aoSubContentsNew = array();
					foreach ($aoSubContents as $oSubContent)
					{
						self::runCallbacksOnTranslatableField($oInstance, $oSubContent, $fOnValue, $fBeforeContentField, $fAfterContentField, false);
						$aoSubContentsNew[] = $oSubContent;
					}
					$oContent->setSubContents($sFieldName, $aoSubContentsNew);
				}
			}
		}
	}
	
	private static function runCallbacks_getInputName($sFieldName, $i, $sSeparator)
	{
		return $sFieldName.$sSeparator.$i;
	}
	
	static function runCallbacksOnTranslatableValue($oInstance, $oRootNode, $sFieldInput, $fOnTextValue, $fBeforeChilds=null, $fAfterChilds=null, $fOnUntranslatableNode=null, $bFirstCall=true)
	{
		static $j=0;
		if ($bFirstCall) $j=0; //reset
		
		if (AnwXml::xmlIsTranslatableParent($oRootNode))
		{
			if ( AnwXml::xmlIsTextNode($oRootNode) )
			{
				if ( !AnwXml::xmlIsEmptyNode($oRootNode) )
				{
					if ($fOnTextValue) $oInstance->$fOnTextValue($oRootNode, $sFieldInput);
				}
			}
			else if (AnwXml::xmlIsCommentNode($oRootNode)){}
			else if( AnwXml::xmlIsPhpNode($oRootNode) )
			{
				//do nothing
			}
			else
			{
				if ($fBeforeChilds && !AnwXml::xmlIsRootNode($oRootNode)) $oInstance->$fBeforeChilds($oRootNode);
				
				$oChildNodes = $oRootNode->childNodes;
				if ($oChildNodes)
				{
					//WARNING - don't use foreach here ! bug on some servers, as childs are getting modified...
					for ($i=0; $i<$oChildNodes->length; $i++)
					{
						$oChild = $oChildNodes->item($i);
						$sFieldInputNew = self::runCallbacks_getInputName($sFieldInput, $j, '-');
						
						self::runCallbacksOnTranslatableValue($oInstance, $oChild, $sFieldInputNew, $fOnTextValue, $fBeforeChilds, $fAfterChilds, $fOnUntranslatableNode, false); //recursive
						$j++;
					}
				}
				
				if ($fAfterChilds && !AnwXml::xmlIsRootNode($oRootNode)) $oInstance->$fAfterChilds($oRootNode);
			}
		}
		else
		{
			if ($fOnUntranslatableNode) $oInstance->$fOnUntranslatableNode($oRootNode);
		}
	}
	
	static function detectPreferredLang()
	{
		$asAllowedLangs = AnwComponent::globalCfgLangs();
		
		if (AnwEnv::_SERVER("HTTP_ACCEPT_LANGUAGE"))
		{
			$asAcceptedLangs = explode(",", AnwEnv::_SERVER("HTTP_ACCEPT_LANGUAGE"));
			foreach($asAcceptedLangs as $sLangItem)
			{
				$asLangItem = explode(';', $sLangItem);
				$sAcceptedLang = strtolower($asLangItem[0]);
				
				if (in_array($sAcceptedLang, $asAllowedLangs))
				{
					return $sAcceptedLang;
				}
			}
		}
		throw new AnwUnexpectedException("No preferred lang is supported");
	}
	
	static function CONSTANT($sConstantName, $oInstance)
	{
		$cClass = get_class($oInstance);
		return constant($cClass.'::'.$sConstantName);
	}
	
	static function arrayToPhp($asArray)
	{
		/*$sReturn = 'array(';
		$bHasItems = false;
		foreach ($asArray as $sIndice => $mValue)
		{
			$sReturn .= "'".self::escapeApostrophe($sIndice)."'=>";
			if (is_array($mValue))
			{
				$sReturn .= self::arrayToPhp($mValue);
			}
			else
			{
				$sReturn .= "'".self::escapeApostrophe($mValue)."'";
			}
			$sReturn .= ',';
			$bHasItems = true;
		}
		if ($bHasItems) $sReturn = substr($sReturn,0,-1); //remove ending comma
		$sReturn .= ')';
		return $sReturn;*/
		return var_export($asArray, true);
	}
	
	static function contentHasPhpCode($sContent)
	{
		if (strstr($sContent, '<?') || strstr($sContent, '<%')
			/*|| strstr($sContent, '?>')
			|| strstr($sContent, '?php')*/
		)
		{
			return true;
		}
		return false;
	}
	
	static function contentHasJsCode($sContent)
	{
		if (strstr($sContent, '<script') 
			|| strstr($sContent, '<iframe')
			)
		{
			return true;
		}
		return false;
	}
	
	//some xml functions that are very often used
	//we put it here instead of AnwXml so that we don't need to load AnwXml class
	
	//duplicated with AnwXml, for internal use for performance issues...
	private static function xmlIsTextNode($node)
	{
		if (!is_object($node))
		{
			throw new AnwUnexpectedException("xmlIsTextNode on empty node");
		}
		if ($node->nodeName == AnwXml::XML_NODENAME_TEXT)
		{
			if ($node->childNodes) throw new AnwUnexpectedException("Bug detected, please report this bug");
			return true;
		}
		return false;
	}
	
	
	static function newDomDocument()
	{
		//UTF-8 encoding
		$oDoc = new DOMDocument("1.0", "UTF-8");
		return $oDoc;
	}
	
	private static function xmlToUTF8($sString)
	{
		//on some servers, DOM doesn't return clean UTF-8 but html entities... clean it now
		return html_entity_decode($sString, ENT_COMPAT, "UTF-8");
	}	
	
	static function xmlDumpNode($node)
	{
		if (!$node instanceof DOMNode)
		{
			self::debug('ERROR - xmlDumpNode on NULL node !');
			throw new AnwUnexpectedException("xmlDumpNode on NULL node !");
			return "";
		}
		if ( self::xmlIsTextNode($node) )
		{
			$sReturn = $node->nodeValue;
		}
		else
		{
			$owner_document = $node->ownerDocument;
			if (!$owner_document) throw new AnwUnexpectedException("xmlDumpNode on node with no document !");
			$output = self::xmlToUTF8($owner_document->saveXML($node));
			$sReturn = $output;
		}
		
		//TODO prepareXmlValueFromXml should only be applied between <anwv/> tags
		$sReturn = AnwXml::prepareXmlValueFromXml($sReturn);
		
		return $sReturn;
	}
	
	static function xmlDumpNodeChilds($node)
	{
		$output = '';
		$owner_document = $node->ownerDocument;
		
		foreach ($node->childNodes as $el)
		{
			$output .= $owner_document->saveXML($el);
		}
		$output = self::xmlToUTF8($output);
		
		//TODO prepareXmlValueFromXml should only be applied between <anwv/> tags
		$output = AnwXml::prepareXmlValueFromXml($output);
		
		return $output;
	}
	
	static function xmlDumpNodeOpen($oNode)
	{
		if (self::xmlIsTextNode($oNode)) throw new AnwUnexpectedException("xmlDumpNodeOpen on text node");
		
		/*$tmpnode = $oNode->cloneNode();
		if ($tmpnode->childNodes)
		{
			foreach ($tmpnode->childNodes as $child)
			{
				$tmpnode->removeChild($child);
			}
		}
		$output = AnwUtils::xmlDumpNode($tmpnode);
		$output = preg_replace("/ xmlns=\"(.*?)\"/", "", $output);
		$output = preg_replace("/\/>$/", ">", $output);*/
		
		$sReturn = "";
		$sReturn = '<'.$oNode->nodeName;
		foreach ($oNode->attributes as $sAttrName => $oAttr)
		{
			if ($sAttrName != 'xmlns')
			{
				$sReturn .= ' '.$sAttrName.'="'.$oNode->getAttribute($sAttrName).'"';
			}
		}
		$sReturn .= '>';
		
		/*if ($sReturn != $output)
		{
			print htmlentities($sReturn).'<br/>';
			print htmlentities($output).'<br/>----<br/>';
			throw new AnwUnexpectedException("xmlDumpNodeOpen error detected");
		}*/
		
		$sReturn = AnwXml::prepareXmlValueFromXml($sReturn);
		
		return $sReturn;
	}
	
	static function xmlDumpNodeClose($node)
	{
		if (self::xmlIsTextNode($node)) throw new AnwUnexpectedException("xmlDumpNodeOpen on text node");
		$sReturn = '</'.$node->nodeName.'>';
		
		$sReturn = AnwXml::prepareXmlValueFromXml($sReturn);
		
		return $sReturn;
	}
	
	
	
	static function loadXML($sContent)
	{
		//TODO prepareXmlValueToXml should only be applied between <anwv/> tags
		$sContent = AnwXml::prepareXmlValueToXml($sContent);
		
		//we don't want &gt; and &lt; to be converted in normal chars but keep html entities as they are
		$sContent = str_replace('&', '&amp;', $sContent);
		
		$oDoc = self::newDomDocument();
		
		libxml_use_internal_errors(true);
		$bTest = $oDoc->loadXML($sContent);
		if (!$bTest)
		{
			throw new AnwUnexpectedException("loadXML failed");
		}
		return $oDoc->documentElement;
	}
	
	static function instanceVars($amInstanceVars, $asUnwantedVars=array())
	{
		//get class attributes
		$asVars = array_keys($amInstanceVars);
		
		//strip out unwanted vars
		foreach ($asVars as $i=>$sVar)
		{
			if (in_array($sVar, $asUnwantedVars))
			{
				unset($asVars[$i]);
			}
		}
		
		self::debug("instanceVars: ".implode(' ; ', $asVars));		
		return $asVars;
	}
	
	static function standardizeCRLF($s)
	{
		// thanks to montana [at] percepticon [dot] com for his contribution on php.net
		$sCRLF = "\r\n";
		$s = preg_replace("/(?<!\\n)\\r+(?!\\n)/", $sCRLF, $s); //replace just CR with CRLF
		$s = preg_replace("/(?<!\\r)\\n+(?!\\r)/", $sCRLF, $s); //replace just LF with CRLF
		$s = preg_replace("/(?<!\\r)\\n\\r+(?!\\n)/", $sCRLF, $s); //replace misordered LFCR with CRLF 
		return $s;
	}
	
	/**
	 * Apply the same permutation (permute indices $iFrom and $iTo) on several arrays.
	 */
	static function permuteMultipleArrays($aaArrays, $iFrom, $iTo)
	{
		foreach ($aaArrays as $i => $aaArray)
		{
			$nTmp = $aaArrays[$i][$iFrom];
			$aaArrays[$i][$iFrom] = $aaArrays[$i][$iTo];
			$aaArrays[$i][$iTo] = $nTmp;
		}
		return $aaArrays;
	}
	
	//TODO duplicate code
	//temporarily replaces AnwContentClasses :: loadContentClassInterface(), avoids loading whole contentclasses framework
	static function loadContentClassInterfaceFaster($sName)
	{
		$sFile = 'contentclass_'.$sName.'-interface.php';
		$sDir = ANWDIR_CONTENTCLASSES.'contentclass_'.$sName.'/';
		try
		{
			list($sFileDefault, $bIsAddon) = AnwUtils::getFileDefault($sFile, $sDir);
			loadApp ($sFileDefault);
		}
		catch(AnwFileNotFoundException $e)
		{
			//no interface found for this class
		}
	}
	
	static function errorReportingTolerant()
	{
		error_reporting(E_ALL^(E_NOTICE | E_WARNING));
	}
	
	static function errorReportingDefault()
	{
		anwInitErrorReporting();
	}
}

function array_insert( &$array, $index, $arrayvalues ) {
	$cnt = count($array);
	$cntvalues = count($arrayvalues);
	$imax = $cntvalues + $index;
	$newarray = array();
	
	//shift existing values to free space
	foreach( $array as $i => $v)
	{
		if ($i == $index)
		{
			//insert new values
			foreach( $arrayvalues as $ii => $vv)
			{
				$newarray[] = $arrayvalues[$ii];
			}
		}
		$newarray[] = $array[$i];
		unset($array[$i]);
	}
	
	/*
	
	//insert remaining values
	foreach( $array as $i => $v)
	{
		$newarray[] = $array[$i];
	}
	*/
	$array = $newarray;
	
	/*
	//shift existing values to free space
	for( $i = $index; $i<$imax; $i++ ) {
		$array[ $i + $cntvalues ] = $array[ $i ];
	}
	
	//insert new values
	for( $j = 0; $j<$cntvalues; $j++ ) {
		$array[ $index + $j ] = $arrayvalues[ $j ];
	}*/
}

// unset $array[$index], shifting others values
/*function array_unset_shift($array,$index)
{
	$res=array();
	$i=0;
	foreach ($array as $item) {
		if ($i!=$index) $res[]=$item;
		$i++;
	}
	return $res;
}*/

?>