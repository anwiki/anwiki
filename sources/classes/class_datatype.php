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
 * Definition of Anwiki datatypes, for rendering edition fields and validating input values.
 * @package Anwiki
 * @version $Id: class_datatype.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwDatatype
{
	private $bIsError;
	
	final function __construct()
	{
		$this->bIsError = false;
		$this->init();
	}
	
	//overridable
	function init()
	{
		
	}
	
	protected final function setError($sError)
	{
		$this->bIsError = $sError;
	}
	
	protected function inputParametersToHtml($sInputId, $asInputParameters)
	{
		$HTML = "";
		
		//append ID
		$asInputParameters['id'] = $sInputId;
		
		//render string
		foreach ($asInputParameters as $i => $v)
		{
			$HTML .= ' '.$i.'="'.AnwUtils::xQuote($v).'"';
		}
		return $HTML;
	}
	
	final function getError()
	{
		return $this->bIsError;
	}
	
	//-----------------------------------
	
	abstract function testValue($sValue);
	
	abstract function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array());
	abstract function renderCollapsedInput($sInputName, $sInputValue);
	
	//overridable
	function getTip($asParameters=array())
	{
		return AnwComponent::g_editcontent("datatype_".$this->getName()."_tip", $asParameters, false, ""); //return empty comment if no comment
	}
	
	//overridable
	function getValuesFromPost($sInputName)
	{
		$asValues = AnwEnv::_POST($sInputName, array());
		foreach ($asValues as $i => $sValue)
		{
			$asValues[$i] = $this->cleanValueFromPost($sValue);
		}
		return $asValues;
	}
	
	//overridable
	protected function cleanValueFromPost($sValue)
	{
		return $sValue;
	}
	
	protected function getName()
	{
		$sPattern = '!^AnwDatatype_(.*)$!si';
		$sClassName = get_class($this);
		if (preg_match($sPattern, $sClassName, $asMatches))
		{
			return $asMatches[1];
		}
		throw new AnwUnexpectedException("Datatype name not recognized: ".$sClassName);
	}
	
	//overridable
	function getValueFromArrayItem($mCfgItem)
	{
		return $mCfgItem;
	}
	function getArrayItemFromValue($mValue)
	{
		return $mValue;
	}
	
	/**
	 * Get the default value for assisting content edition.
	 */
	function getDefaultAssistedValue() //overridable
	{
		return "";
	}
}

class AnwDatatype_xml extends AnwDatatype
{
	private $bCheckPhpSyntax = true;
	
	function setCheckPhpSyntax($bValue)
	{
		$this->bCheckPhpSyntax = $bValue;
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xTextareaValue($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<textarea name="$sInputName" class="contentfield_xml" wrap="off"$sInputParameters>$sInputValue</textarea>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$sInputValue = AnwUtils::xText($sInputValue);
		$HTML = <<<EOF

	<pre>$sInputValue</pre>
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		if (!AnwXml::xmlIsValid($sValue))
		{
			//XML error found
			$sXmlErrors = '<ul>';
			$aoLibXmlErrors = libxml_get_errors();
			foreach ($aoLibXmlErrors as $oLibXmlError)
			{
				$sMessage = $oLibXmlError->message;
				if (!strstr($sMessage, "Premature end of data"))
				{
					//TODO dirty #CONTENTFIELDINPUTID#
					$sMessage = preg_replace('! line ([0-9]*) and doc!si', ' <a class="textareafocusline" onclick="setTextareaFocusLine($1,$(\'#CONTENTFIELDINPUTID#\'))">line $1</a>', $sMessage);
					$sXmlErrors .= '<li>'.$sMessage.'</li>';
				}
			}
			$sXmlErrors .= '</ul>';
			$sError = AnwComponent::g_editcontent("err_contentfield_xml_invalid", array("xmlerrors"=>$sXmlErrors));
			throw new AnwInvalidContentFieldValueException($sError);
		}
		else if(AnwUtils::contentHasPhpCode($sValue))
		{
			if ($this->bCheckPhpSyntax)
			{
				$sPhpError=null; //gets modified by evalMixedPhpSyntax
				if (AnwUtils::evalMixedPhpSyntax($sValue, $sPhpError))
				{
					//print "ok";
				}
				else
				{
					//better php error : hide file, and add a link to the line 
					//TODO dirty #CONTENTFIELDINPUTID#
					$sPhpError = preg_replace('!in (.*) on line <b>([0-9]*)</b>!si', '<a class="textareafocusline" onclick="setTextareaFocusLine($2,$(\'#CONTENTFIELDINPUTID#\'))">on line $2</a>', $sPhpError);
					$sError = $sPhpError;
					throw new AnwInvalidContentFieldValueException($sError);
				}
			}
		}
	}
}

class AnwDatatype_xhtml extends AnwDatatype_xml
{
	//special treatments before returning the typed value
	protected function cleanValueFromPost($sValue)
	{
		$sValue = parent::cleanValueFromPost($sValue);
		
		$sValue = self::closeMinimizedEndTags($sValue);
		
		$sValue = AnwPlugins::vHook("datatype_xhtml_cleanvaluefrompost", $sValue);
		
		return $sValue;
	}
	
	/**
	 * Close unclosed tags compatible with "minimized" style.
	 * Example: <img src="foo"> becomes <img src="foo" />
	 * Example: <img src="foo">dummy</img> becomes <img src="foo" />
	 */
	private static function closeMinimizedEndTags($sValue) {
		// caution: <img src="foo"><attr name="alt">logo</attr></img> must remain unchanged!
		$sValue = preg_replace('/<((link|meta|br|col|base|img|param|area|hr|input)\b[^<>\/]*?)\s*>([^<>]*)(?!\s*<\/\2>|\s*<attr\s)/', '<$1 />$3', $sValue);
		return $sValue;
	}
	
	/**
	 * Access reserved for unit tests.
	 */
	public static function __test_closeMinimizedEndTags($sValue) {
		return self::closeMinimizedEndTags($sValue);
	}
}

class AnwDatatype_date extends AnwDatatype
{
	const DATE_FORMAT = "Y-m-d H:i:s";
	
	function getDefaultAssistedValue()
	{
		return self::formatTime(time());
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<input type="text" name="$sInputName" class="contentfield_date intext" value="$sInputValue"$sInputParameters/>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$sInputValue = AnwUtils::xText($sInputValue);
		$HTML = <<<EOF

	$sInputValue
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		//if (!preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$!', $sValue))
		if (!self::getValueTimestamp($sValue))
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_date_format", array("dateformat"=>'<i>'.date(self::DATE_FORMAT).'</i>'));
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
	
	function getTip($asParameters=array())
	{
		return parent::getTip(array("dateformat"=>date(self::DATE_FORMAT)));
	}
	
	static function formatTime($nTime)
	{
		return date(self::DATE_FORMAT, $nTime);
	}
	
	static function getValueTimestamp($sValue)
	{
		return AnwUtils::strtotime($sValue);
	}
}

class AnwDatatype_string extends AnwDatatype
{
	private static $FORBIDDEN_CHARS = array('&lt;'=>'<', '&gt;'=>'>');
	private $asAllowedPatterns = array();
	private $asForbiddenPatterns = array();
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<input type="text" name="$sInputName" class="contentfield_string" value="$sInputValue"$sInputParameters/>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$sInputValue = AnwUtils::xText($sInputValue);
		$HTML = <<<EOF

	$sInputValue
EOF;
		return $HTML;
	}
	
	function addAllowedPattern($sPattern)
	{
		$this->asAllowedPatterns[] = $sPattern;
	}
	
	function addForbiddenPattern($sPattern)
	{
		$this->asForbiddenPatterns[] = $sPattern;
	}
	
	function testValue($sValue)
	{
		//test forbidden chars
		if (str_replace(self::$FORBIDDEN_CHARS, '', $sValue) != $sValue)
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_string_tags");
			throw new AnwInvalidContentFieldValueException($sError);
		}
		
		//test forbidden patterns
		if (count($this->asForbiddenPatterns)>0)
		{
			foreach ($this->asForbiddenPatterns as $sForbiddenPattern)
			{
				if (preg_match($sForbiddenPattern, $sValue))
				{
					$sForbiddenPattern = '<span style="font-size:0.7em">'.implode(',', $this->asForbiddenPatterns).'</span>';
					$sError = AnwComponent::g_editcontent("err_contentfield_string_forbidden_pattern", array('forbiddenpattern'=>$sForbiddenPattern));
					throw new AnwInvalidContentFieldValueException($sError);
				}
			}
		}
		
		//test allowed patterns
		if (count($this->asAllowedPatterns)>0)
		{
			$bPatternMatch = false;
			foreach ($this->asAllowedPatterns as $sAllowedPattern)
			{
				if (preg_match($sAllowedPattern, $sValue))
				{
					$bPatternMatch = true;
					break;
				}
			}
			if (!$bPatternMatch)
			{
				$sAllowedPatterns = '<span style="font-size:0.7em">'.implode(',', $this->asAllowedPatterns).'</span>';
				if (count($this->asAllowedPatterns)==1)
				{
					$sError = AnwComponent::g_editcontent("err_contentfield_string_nomatch_pattern", array('allowedpattern'=>$sAllowedPatterns));
				}
				else
				{
					$sError = AnwComponent::g_editcontent("err_contentfield_string_nomatch_patterns", array('allowedpatterns'=>$sAllowedPatterns));
				}
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
	
	function getTip($asParameters=array())
	{
		return parent::getTip(array("forbiddenchars"=>htmlentities(implode(' ', self::$FORBIDDEN_CHARS))));
	}
}

class AnwDatatype_password extends AnwDatatype_string
{
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<input type="password" name="$sInputName" class="contentfield_password" value="$sInputValue"$sInputParameters/>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$HTML = <<<EOF

	*****
EOF;
		return $HTML;
	}
}

class AnwDatatype_ip_address extends AnwDatatype_string
{
	function init()
	{
		parent::init();
		$this->addAllowedPattern('!^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$!');
	}
	
	function getDefaultAssistedValue()
	{
		return "XX.XX.XX.XX";
	}
}

class AnwDatatype_email_address extends AnwDatatype_string
{
	function init()
	{
		parent::init();
		$this->addAllowedPattern('!^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$!i');
	}
	
	function getDefaultAssistedValue()
	{
		return "user @ domain .com";
	}
}

class AnwDatatype_url extends AnwDatatype_string
{
	private $amAllowedUrlTypes = array(self::TYPE_ALL);
	
	const TYPE_ALL="all";
	const TYPE_ABSOLUTE="absolute";
	const TYPE_RELATIVE="relative";
	const TYPE_FULL="full";
	
	function getDefaultAssistedValue()
	{
		if (in_array(self::TYPE_ALL, $this->amAllowedUrlTypes) || in_array(self::TYPE_FULL, $this->amAllowedUrlTypes))
		{
			return "http://";
		}
		else if (in_array(self::TYPE_ABSOLUTE, $this->amAllowedUrlTypes))
		{
			return "/";
		}
		else
		{
			return "";
		}
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$HTML = parent::renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters);
		
		$sTranslation = AnwComponent::g_editcontent("contentfield_url_buttonopen");
		$HTML .= <<<EOF

<a onclick="window.open($('{$sInputId}').value)" href="#">{$sTranslation}</a>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$HTML = <<<EOF

	<a href="$sInputValue" target="_blank">$sInputValue</a>
EOF;
		return $HTML;
	}
	
	function setAllowedUrlTypes($amAllowedUrlTypes)
	{
		$this->amAllowedUrlTypes = $amAllowedUrlTypes;
	}
	
	function testValue($sValue)
	{
		parent::testValue($sValue);
		
		//test allowed types
		if (!in_array(self::TYPE_ALL, $this->amAllowedUrlTypes))
		{
			$sTypeLabelError = false;
			if (strstr($sValue, '://'))
			{
				//it's a full url
				$sType = self::TYPE_FULL;
				if (!in_array($sType, $this->amAllowedUrlTypes))
				{
					$sTypeLabelError = self::getTypeLabel($sType);
				}
			}
			else if (substr($sValue, 0, 1)=='/')
			{
				//it's an absolute url
				$sType = self::TYPE_ABSOLUTE;
				if (!in_array($sType, $this->amAllowedUrlTypes))
				{
					$sTypeLabelError = self::getTypeLabel($sType);
				}
			}
			else
			{
				//it's a relative url
				$sType = self::TYPE_RELATIVE;
				if (!in_array($sType, $this->amAllowedUrlTypes))
				{
					$sTypeLabelError = self::getTypeLabel($sType);
				}
			}
			
			if ($sTypeLabelError)
			{
				$sTypeLabel = self::getTypeLabel($sType);
				$sError = AnwComponent::g_editcontent("err_contentfield_url_typeforbidden", array('typeforbidden'=>$sTypeLabel, 'typesallowed'=>$this->getTip()));
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
	
	function getTip($asParameters=array())
	{
		if (!in_array(self::TYPE_ALL, $this->amAllowedUrlTypes))
		{
			if (count($this->amAllowedUrlTypes)==1)
			{
				$sAllowedType = self::getTypeLabel($this->amAllowedUrlTypes[0]);
				return AnwComponent::g_editcontent("datatype_url_tip_typeallowed", array("allowedtype"=>$sAllowedType));
			}
			else
			{
				$amTypeLabels = array();
				foreach ($this->amAllowedUrlTypes as $sType)
				{
					$amTypeLabels[] = self::getTypeLabel($sType);
				}
				$sAllowedTypes = implode(",", $amTypeLabels);
				return AnwComponent::g_editcontent("datatype_url_tip_listallowed", array("allowedtypes"=>$sAllowedTypes));
			}
		}
		return AnwComponent::g_editcontent("datatype_url_tip_any");
	}
	
	private static function getTypeLabel($sType)
	{
		return AnwComponent::g_editcontent("datatype_url_typelabel_".$sType);
	}
}

class AnwDatatype_system_directory extends AnwDatatype_string
{
	private $asTestFiles = array();
	
	function addTestFile($sTestFile)
	{
		$this->asTestFiles[] = $sTestFile;
	}
	
	protected function cleanValueFromPost($sValue)
	{
		//automatically append a slash if not present
		if ($sValue != "" && substr($sValue, -1, 1)!='/')
		{
			$sValue .= '/';
		}
		return $sValue;
	}
	
	function testValue($sValue)
	{
		parent::testValue($sValue);
		
		if (!is_dir($sValue))
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_system_directory_notfound");
			throw new AnwInvalidContentFieldValueException($sError);
		}
		
		foreach ($this->asTestFiles as $sTestFile)
		{
			$sFileToTest = $sValue.$sTestFile;
			if (!file_exists($sFileToTest))
			{
				$sError = AnwComponent::g_editcontent("err_contentfield_system_directory_testfilenotfound", array('file'=>$sFileToTest));
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
}

class AnwDatatype_userlogin extends AnwDatatype_string
{
	function testValue($sValue)
	{
		//check that user exists
		try
		{
			$oUser = AnwUsers::getUserByLogin($sValue);
			unset($oUser);
		}
		catch(AnwException $e)
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_user_notfound");
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
}

class AnwDatatype_boolean extends AnwDatatype
{
	private $sLabel = "";
	
	const VALUE_FALSE = 'false';
	const VALUE_TRUE = 'true';
	
	function getDefaultAssistedValue()
	{
		return self::formatBoolean(false);
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$bInputValue = ($sInputValue == self::VALUE_TRUE ? true : false);
		$sChecked = $bInputValue ? ' checked="checked"' : '';
		
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$sValueTrue = self::VALUE_TRUE;
		
		$HTML = <<<EOF

	<input type="checkbox" name="$sInputName" class="contentfield_boolean" value="$sValueTrue"{$sChecked}{$sInputParameters}/> <label for="$sInputId">{$this->sLabel}</label>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$bInputValue = ($sInputValue == self::VALUE_TRUE ? true : false);
		$sTranslationId = ($bInputValue ? "datatype_boolean_collapsed_true" : "datatype_boolean_collapsed_false");
		$sTranslation = AnwComponent::g_editcontent($sTranslationId);
		$HTML = <<<EOF

	{$this->sLabel}: <b>$sTranslation</b>
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		if (!in_array($sValue, array(self::VALUE_TRUE, self::VALUE_FALSE)))
		{
			$sError = AnwComponent::g_("err_unkn");
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
	
	//special treatments before returning the typed value
	protected function cleanValueFromPost($sValue)
	{
		$sValue = ($sValue == self::VALUE_TRUE ? self::VALUE_TRUE : self::VALUE_FALSE);
		return $sValue;
	}
	
	function getValuesFromPost($sInputName)
	{
		$asValues = parent::getValuesFromPost($sInputName);
		//when checkbox is unchecked, we never get the value from post...
		if (count($asValues)==0)
		{
			$asValues = array(self::VALUE_FALSE);
		}
		return $asValues;
	}
	
	function setLabel($sLabel)
	{
		$this->sLabel = $sLabel;
	}
	
	static function formatBoolean($bBoolean)
	{
		$mValue = ($bBoolean ? self::VALUE_TRUE : self::VALUE_FALSE);
		return $mValue;
	}
	
	function getValueFromArrayItem($mCfgItem)
	{
		return $this->formatBoolean($mCfgItem);
	}
	function getArrayItemFromValue($mValue)
	{
		return ($mValue == self::VALUE_TRUE);
	}
}

abstract class AnwDatatype_number extends AnwDatatype
{
	protected $sLabel = "";	
	protected $nValueMin;
	protected $nValueMax;
	const UNDEFINED="undefined";
	
	function init()
	{
		$this->nValueMin = self::UNDEFINED;
		$this->nValueMax = self::UNDEFINED;
	}
	
	function getDefaultAssistedValue()
	{
		if ($this->nValueMin!=self::UNDEFINED)
		{
			return $this->nValueMin;
		}
		return 0;
	}
	
	function setLabel($sLabel)
	{
		$this->sLabel = $sLabel;
	}
	
	//override : number is never translatable
	function isTranslatable()
	{
		return false;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$sInputValue = AnwUtils::xText($sInputValue);
		$HTML = <<<EOF

	$sInputValue $this->sLabel
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		if ($this->nValueMin != self::UNDEFINED && $sValue < $this->nValueMin )
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_number_min",array("minval"=>$this->nValueMin));
			throw new AnwInvalidContentFieldValueException($sError);
		}
		
		if ($this->nValueMax != self::UNDEFINED && $sValue > $this->nValueMax )
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_number_max",array("maxval"=>$this->nValueMax));
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
	
	function getTipNumber($sNumberType)
	{
		if ($this->nValueMin != self::UNDEFINED && $this->nValueMax != self::UNDEFINED)
		{
			return AnwComponent::g_editcontent("datatype_number_tip_between", array("numbertype"=>$sNumberType, "minval"=>$this->nValueMin, "maxval"=>$this->nValueMax));
		}
		if ($this->nValueMin != self::UNDEFINED)
		{
			return AnwComponent::g_editcontent("datatype_number_tip_greater", array("numbertype"=>$sNumberType, "minval"=>$this->nValueMin));
		}
		if ($this->nValueMax != self::UNDEFINED)
		{
			return AnwComponent::g_editcontent("datatype_number_tip_lower", array("numbertype"=>$sNumberType, "maxval"=>$this->nValueMax));
		}
		return AnwComponent::g_editcontent("datatype_number_tip_any", array("numbertype"=>$sNumberType));
	}
	
	function setValueMin($nValue)
	{
		$this->nValueMin = $nValue;
	}
	
	function setValueMax($nValue)
	{
		$this->nValueMax = $nValue;
	}
}

class AnwDatatype_integer extends AnwDatatype_number
{
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<input type="text" name="$sInputName" class="contentfield_integer" value="$sInputValue"$sInputParameters/> $this->sLabel
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		if (!is_numeric($sValue) || intval(0+$sValue) != $sValue)
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_integer_numeric");
			throw new AnwInvalidContentFieldValueException($sError);
		}
		parent::testValue($sValue); //test min-max
	}
	
	function getTip($asParameters=array())
	{
		return parent::getTipNumber(AnwComponent::g_editcontent('datatype_number_integer'));
	}
}

class AnwDatatype_delay extends AnwDatatype_integer
{
	//we use 4 integer inputs to enter days, hours, minutes and seconds
	
	private static function getDaysHoursMinutesSeconds($sInputValue)
	{
		//convert seconds to days+hours+minutes+seconds
		$nRemainingSeconds = (int)$sInputValue;
		
		$nValueDays = floor($nRemainingSeconds/86400);
		$nRemainingSeconds -= $nValueDays*86400;
		
		$nValueHours = floor( $nRemainingSeconds/3600 );
		$nRemainingSeconds -= $nValueHours*3600;
		
		$nValueMinutes = floor( $nRemainingSeconds/60 );
		$nRemainingSeconds -= $nValueMinutes*60;
		
		$nValueSeconds = $nRemainingSeconds;
		
		return array($nValueDays, $nValueHours, $nValueMinutes, $nValueSeconds);
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		list($nValueDays, $nValueHours, $nValueMinutes, $nValueSeconds) = self::getDaysHoursMinutesSeconds($sInputValue);
		
		$sInputValueDays = $nValueDays;
		$sInputValueHours = $nValueHours;
		$sInputValueMinutes = $nValueMinutes;
		$sInputValueSeconds = $nValueSeconds;
		
		$sHtmlInputDays = parent::renderInput($sInputName."_days", $sInputValueDays, $sInputId."_days", $asInputParameters);
		$sHtmlInputHours = parent::renderInput($sInputName."_hours", $sInputValueHours, $sInputId."_hours", $asInputParameters);
		$sHtmlInputMinutes = parent::renderInput($sInputName."_minutes", $sInputValueMinutes, $sInputId."_minutes", $asInputParameters);
		$sHtmlInputSeconds = parent::renderInput($sInputName."_seconds", $sInputValueSeconds, $sInputId."_seconds", $asInputParameters);
		
		$sLabelDays = AnwComponent::g_editcontent("datatype_delay_days");
		$sLabelHours = AnwComponent::g_editcontent("datatype_delay_hours");
		$sLabelMinutes = AnwComponent::g_editcontent("datatype_delay_minutes");
		$sLabelSeconds = AnwComponent::g_editcontent("datatype_delay_seconds");
		
		$HTML = <<<EOF

	<div class="contentfield_delay">
		{$sHtmlInputDays}{$sLabelDays} {$sHtmlInputHours}{$sLabelHours} 
		{$sHtmlInputMinutes}{$sLabelMinutes} {$sHtmlInputSeconds}{$sLabelSeconds}
	</div>
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		list($nValueDays, $nValueHours, $nValueMinutes, $nValueSeconds) = self::getDaysHoursMinutesSeconds($sInputValue);
		
		$sLabelDays = AnwComponent::g_editcontent("datatype_delay_days");
		$sLabelHours = AnwComponent::g_editcontent("datatype_delay_hours");
		$sLabelMinutes = AnwComponent::g_editcontent("datatype_delay_minutes");
		$sLabelSeconds = AnwComponent::g_editcontent("datatype_delay_seconds");
		
		$HTML = <<<EOF

	{$nValueDays} {$sLabelDays}, {$nValueHours} {$sLabelHours}, 
		{$nValueMinutes} {$sLabelMinutes}, {$nValueSeconds} {$sLabelSeconds}
EOF;
		return $HTML;
	}
	
	function getTip($asParameters=array())
	{
		return AnwDatatype::getTip($asParameters); //squeeze getTip() from parent
	}
	
	function getValuesFromPost($sInputName)
	{
		//convert hours+minutes+seconds to seconds
		
		$asValuesDays = parent::getValuesFromPost($sInputName."_days");
		$asValuesHours = parent::getValuesFromPost($sInputName."_hours");
		$asValuesMinutes = parent::getValuesFromPost($sInputName."_minutes");
		$asValuesSeconds = parent::getValuesFromPost($sInputName."_seconds");
				
		$asValues = array();
		foreach ($asValuesDays as $i => $sDays)
		{
			$sDays = (int)$sDays;
			$sHours = (int)$asValuesHours[$i];
			$sMinutes = (int)$asValuesMinutes[$i];
			$sSeconds = (int)$asValuesSeconds[$i];
			
			$asValues[] = $sDays*86400 + $sHours*3600 + $sMinutes*60 + $sSeconds;
		}		
		return $asValues;
	}
}

class AnwDatatype_float extends AnwDatatype_number
{
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<input type="text" name="$sInputName" class="contentfield_float" value="$sInputValue"$sInputParameters/> $this->sLabel
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		$nValue = (float)$sValue;
		if ($nValue != $sValue)
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_float_numeric");
			throw new AnwInvalidContentFieldValueException($sError);
		}
		parent::testValue($sValue); //test min-max
	}
	
	function getTip($asParameters=array())
	{
		return parent::getTipNumber(AnwComponent::g_editcontent('datatype_number_float'));
	}
}

abstract class AnwDatatype_enum extends AnwDatatype
{
	private $asEnumValues = array();
	
	function setEnumValues($asEnumValues)
	{
		$this->asEnumValues = $asEnumValues;
	}
	
	function setEnumValuesFromList($asValues)
	{
		$this->asEnumValues = array();
		foreach ($asValues as $sValue)
		{
			$this->asEnumValues[$sValue] = $sValue;
		}
	}
	
	protected function getEnumValues()
	{
		return $this->asEnumValues;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$mEnumLegend = isset($this->asEnumValues[$sInputValue]) ? $this->asEnumValues[$sInputValue] : "";
		$HTML = <<<EOF

	$mEnumLegend
EOF;
		return $HTML;
	}
	
	function testValue($sValue)
	{
		if (!isset($this->asEnumValues[$sValue]))
		{
			$sEnumValues = implode(',', $this->asEnumValues);
			$sError = AnwComponent::g_editcontent("err_contentfield_enum_values");
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
}

class AnwDatatype_radio extends AnwDatatype_enum
{
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$nEnumId = 0;
		$HTML = "";
		foreach ($this->getEnumValues() as $mEnumValue => $sEnumLegend)
		{
			$mEnumValue = AnwUtils::xQuote($mEnumValue);
			$sChecked = ( $mEnumValue == $sInputValue ? ' checked="checked"' : '');
			$sEnumId = $sInputId.$nEnumId;
			
			$HTML .= <<<EOF

		<input type="radio" name="$sInputName" id="$sEnumId" value="$mEnumValue"$sChecked/><label for="$sEnumId">$sEnumLegend</label>
EOF;
			$nEnumId++;
		}

		return $HTML;
	}
}

class AnwDatatype_select extends AnwDatatype_enum
{
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputValue = AnwUtils::xQuote($sInputValue);
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		
		$HTML = <<<EOF

	<select name="$sInputName" class="contentfield_enum inselect"$sInputParameters>
EOF;

		foreach ($this->getEnumValues() as $mEnumValue => $sEnumLegend)
		{
			$mEnumValue = AnwUtils::xQuote($mEnumValue);
			$sSelected = ( $mEnumValue == $sInputValue ? ' selected="selected"' : '');
			$HTML .= <<<EOF

		<option value="$mEnumValue"$sSelected>$sEnumLegend</option>
EOF;
		}

		$HTML .= <<<EOF
	</select>
EOF;

		return $HTML;
	}
}

class AnwDatatype_checkboxGroup extends AnwDatatype_enum
{
	function renderInput($sInputName, $asInputValues, $sInputId, $asInputParameters=array())
	{
		$sCssClass = $sInputName;
		$sInputName .= '[]';
		$sInputName = AnwUtils::xQuote($sInputName);
		
		$sInputParameters = $this->inputParametersToHtml($sInputId, $asInputParameters);
		$sChkAll = AnwComponent::g_('in_chkall');
		$sChkNone = AnwComponent::g_('in_chknone');
		
		$HTML = <<<EOF

	<fieldset class="contentfield_checkboxgroup">
		<legend>
			<a href="#" onclick="AnwUtils.chkall('$sCssClass',$(this).up().up()); return false;">{$sChkAll}</a> 
			<a href="#" onclick="AnwUtils.chknone('$sCssClass',$(this).up().up()); return false;">{$sChkNone}</a>
		</legend>
EOF;

		$asEnumValues = $this->getEnumValues();
		$nEnumId = 0;
		foreach ($asEnumValues as $mEnumValue => $sEnumLegend)
		{
			$mEnumValue = AnwUtils::xQuote($mEnumValue);
			$sSelected = ( in_array($mEnumValue, $asInputValues) ? ' checked="checked"' : '');
			$sEnumId = $sInputId.$nEnumId;
			$HTML .= <<<EOF

		<div class="checkboxgroup_item">
		<input type="checkbox" id="$sEnumId" name="$sInputName" class="$sCssClass" value="$mEnumValue"$sSelected/><label for="$sEnumId">$sEnumLegend</label>
		</div>
EOF;
			$nEnumId++;
		}

		$HTML .= <<<EOF
	</fieldset>
EOF;

		return $HTML;
	}
	/*
	function testValue($asInputValues)
	{
		foreach ($asInputValues as $sInputValue)
		{
			parent::testValue($sInputValue);
		}
	}*/
}

/**
 * GPS Coordinates stored on the following format: lat;lng.
 */
class AnwDatatype_geoposition extends AnwDatatype_string
{
	function init()
	{
		parent::init();
		$this->addAllowedPattern('!^[0-9.\-]+;[0-9.\-]+$!i');
	}
	
	static function getValueFromLatLng($nLatitude, $nLongitude)
	{
		return $nLatitude.";".$nLongitude;
	}
	
	static function getLatLngFromValue($sValue)
	{
		list($sLatitude, $sLongitude) = explode(";", $sValue);
		return array($sLatitude, $sLongitude);
	}
	
	function getDefaultAssistedValue()
	{
		return self::getValueFromLatLng(0, 0);
	}
	
	function renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters=array())
	{
		$sInputText = parent::renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters);
		
		list($sLatitude, $sLongitude) = self::getLatLngFromValue($sInputValue);
		$sRenderMap = $this->renderMapInput($sInputId, $sLatitude, $sLongitude);
		
		$HTML = <<<EOF

<div class="contentfield_geoposition">
	$sInputText
	$sRenderMap
</div>
EOF;
		return $HTML;
	}
	
	protected function renderMapInput($sInputId, $sLatitude, $sLongitude)
	{
		$sMapDivId = $sInputId."_map";
		$sMapJsVar = "map".md5($sInputId);
		$nZoom = 15;
		$oMap = new AnwGoogleMap($sMapJsVar, $sLatitude, $sLongitude, $sMapDivId, $nZoom);
		
		// bind map center to input field
		$oMap->setListenOnCenterChange($sInputId);
		
		// add a finder for resolving addresses...
		$sInputIdFinderAddress = $sInputId."_mapfinderaddress";
		$sInputIdFinderSubmit = $sInputId."_mapfindersubmit";
		$oMap->setGeoFinder($sInputIdFinderAddress, $sInputIdFinderSubmit);
		
		// render
		$sMapRender = $oMap->render();
		$HTML = <<<EOF

<div class="map" id="$sMapDivId"></div>
<div class="geofinder">
	<input id="$sInputIdFinderAddress" type="text">
	<input id="$sInputIdFinderSubmit" type="button" value="Geocode">
</div>
$sMapRender
EOF;
		return $HTML;
	}
	
	function renderCollapsedInput($sInputName, $sInputValue)
	{
		$HTML = parent::renderCollapsedInput($sInputName, $sInputValue);
		return $HTML;
	}
	
	function testValue($sValue)
	{
		parent::testValue($sValue);
	}
}

?>