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
 * Anwiki exceptions and errors management.
 * @package Anwiki
 * @version $Id: class_exception.php 256 2010-03-10 20:50:10Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

/**
 * AnwCriticalException is used for critical errors requiring the process to stop immediately.
 * This exception should never be catched.
 */
function AnwDieCriticalError($sErrorInfo="")
{
	print "<h1>Critical error</h1><i>[".$sErrorInfo."]</i><br/>Please contact the website administrator.<br/>Sorry for inconvenience.";
	exit;
}

abstract class AnwException extends Exception{}

abstract class AnwStorageException extends AnwException{}
abstract class AnwUsersException extends AnwException{}
abstract class AnwSessionsException extends AnwException{}

//--

class AnwDbConnectException extends AnwException{
}

class AnwEditUntranslatedException extends AnwException{}
class AnwUnexpectedException extends AnwException{
	function __construct($sMsg="")
	{
		parent::__construct($sMsg);
		if (class_exists('AnwDebug') && AnwDebug::isEmergencyDebugEnabled())
		{
			print "AnwUnexpectedException:".$sMsg."<br/>";
		}
		AnwDebug::log("**** new UnexpectedException: ".$sMsg." ****");
	}
}


class AnwAuthException extends AnwException{}
class AnwBadCallException extends AnwException{}
class AnwBadPageNameException extends AnwException{}
class AnwBadLangException extends AnwException{}
class AnwBadTimezoneException extends AnwException{}
class AnwBadCommentException extends AnwException{}
class AnwBadLoginException extends AnwException{}
class AnwBadDisplayNameException extends AnwException{}
class AnwBadEmailException extends AnwException{}
class AnwBadPasswordException extends AnwException{}
class AnwPageAlreadyExistsException extends AnwException{}
class AnwNotLoggedInFoundException extends AnwSessionsException{}
class AnwCacheNotFoundException extends AnwException{}
class AnwLangExistsForPageGroupException extends AnwException{}
class AnwBadCaptchaException extends AnwException{}
class AnwParserException extends AnwException{}

class AnwLoginAlreadyTakenException extends AnwBadLoginException{}
class AnwDisplayNameAlreadyTakenException extends AnwBadDisplayNameException{}
class AnwEmailAlreadyTakenException extends AnwBadEmailException{}

class AnwAclException extends AnwException{}
class AnwAclPhpEditionException extends AnwAclException{}
class AnwAclJsEditionException extends AnwAclException{}
class AnwAclMinTranslatedPercentException extends AnwAclException{
	private $nTranslatedPercent;
	function __construct($nTranslatedPercent, $sMessage="")
	{
		parent::__construct($sMessage);
		$this->nTranslatedPercent = $nTranslatedPercent;
	}
	function getTranslatedPercent()
	{
		return $this->nTranslatedPercent;
	}
}
class AnwLockException extends AnwException
{
	private $aoLocks;
	function __construct($aoLocks=array())
	{
		$this->aoLocks = $aoLocks;
	}
	
	function getLocks()
	{
		return $this->aoLocks;
	}
}

class AnwFileNotFoundException extends AnwException{}
class AnwStructuredContentEditionFormException extends AnwException{}

class AnwContentFieldValueNotFoundException extends AnwException{}
class AnwInvalidContentException extends AnwException{}

class AnwInvalidContentFieldValueException extends AnwInvalidContentException
{
	function __construct($sMessage)
	{
		parent::__construct($sMessage);
	}
}
class AnwInvalidContentFieldMultiplicityException extends AnwInvalidContentException
{
	function __construct($sMessage)
	{
		parent::__construct($sMessage);
	}
}

class AnwDependancyException extends AnwInvalidContentFieldValueException
{
	function __construct($sMessage)
	{
		parent::__construct($sMessage);
	}
}

class AnwPageGroupNotFoundException extends AnwStorageException{}
class AnwPageNotFoundException extends AnwStorageException{}
class AnwArchiveNotFoundException extends AnwStorageException{}
class AnwUserNotFoundException extends AnwUsersException{}
class AnwUserAnonymousException extends AnwUsersException{}
class AnwSessionNotFoundException extends AnwSessionsException{}

class AnwPluginInterruptionException extends AnwException{}
class AnwRunInterruptionException extends AnwException{}
class AnwComponentInitializeException extends AnwException{}

//handling standard PHP errors
/*
class PhpRuntimeException extends AnwException
{
    protected $_context = array();
    
    function __construct($level, $string, $file, $line, $context)
    {
        parent::__construct($string);
        $this->file = $file; 
        $this->line = $line;
        $this->level = $level;
        $this->context = $context;
    }
}

set_error_handler('anwErrorsHandler');

function anwErrorsHandler($level, $string, $file, $line, $context)
{
	if (in_array($level, array(E_NOTICE, E_WARNING))) return false;
    throw new PhpRuntimeException($level, $string, $file, $line, $context);
}
*/
?>