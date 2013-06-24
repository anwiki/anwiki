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
 * ContentField definition for Page contents.
 * @package Anwiki
 * @version $Id: class_contentfield_page.php 336 2010-10-10 21:03:15Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwContentFieldPage extends AnwStructuredContentField
{
	private $bTranslatable;	
	
	private $bDynamicPhpAllowed; //does contentclass allow PHP for this contentfield?
	private $bDynamicParsingAllowed; //does contentclass allow dynamic parsing for this contentfield?
	
	private $sIndexName;
	private $bIndexed;
	
	
	function __construct($sName)
	{
		parent::__construct($sName);
		$this->bTranslatable = true;
		
		//PHP and parsing are denied by default
		$this->bDynamicPhpAllowed = false;
		$this->bDynamicParsingAllowed = false;
	}
	
	function t_structuredcontentfield($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		if ($this->getRelatedComponent())
		{
			$oComponentForTranslation = $this->getRelatedComponent();
		}
		else
		{
			$oComponentForTranslation = $this->getComponent();
		}
		return $oComponentForTranslation->t_contentfieldpage($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	function getComponent()
	{
		return $this->getContentClass();
	}
	
	function setTranslatable($bTranslatable)
	{
		$this->bTranslatable = $bTranslatable;
	}
	
	function indexAs($sIndexName)
	{
		$this->sIndexName = $sIndexName;
		$this->bIndexed = true;
	}
	
	function isIndexed()
	{
		return $this->bIndexed;
	}
	
	function getIndexName()
	{
		return $this->sIndexName;
	}
	
	function isTranslatable()
	{
		return $this->bTranslatable;
	}
	
	/*
	function getContentFieldForIndex($sIndexName)
	{
		if ($this instanceof AnwStructuredContentField_atomic)
		{
			// we are looking for the contentfield indexed as '$sIndexName'
			if ($this->isIndexed() && $this->getIndexName() == $sIndexName)
			{
				// we found the contentfield for this index!
				return $this;
			}
		}
		else
		{
			// composed content field are not indexable, but may contain indexed atomic subcontentfields
			$aoSubContentFields = $this->getContentFields();
			foreach ($aoContentFields as $oContentField)
			{
				$oContentFieldForIndex = $oContentField->getContentFieldForIndex($sIndexName);
				if ($oContentFieldForIndex)
				{
					return $oContentFieldForIndex;
				}
			}
		}
		
		// no (sub)contentfield found for this index
		return false;
	}*/
	
	//
	// BELOW : Stuff artificially INHERITED from AnwStructuredContentFieldsContainerPage
	// (multiple inherit is not supported by PHP5)
	//
	
	final function rebuildContentFromXml($sXmlValue)
	{
		return AnwContentPage::rebuildContentFromXml($this, $sXmlValue);
	}
	
	//
	//End of Stuff artificially INHERITED from AnwStructuredContentFieldsContainerPage
	//
	
	//only run parsing when contentfield type supports it, and when contentclass doesn't deny it
	final function runDynamicParsing()
	{
		return ($this->bDynamicParsingAllowed && $this->runDynamicParsingSupported());
	}
	//only run PHP when contentfield type supports it, and when contentclass doesn't deny it
	final function runDynamicPhp()
	{
		return ($this->bDynamicPhpAllowed && $this->runDynamicPhpSupported());
	}
	
	function setDynamicPhpAllowed($bDynamicPhpAllowed)
	{
		$this->bDynamicPhpAllowed = $bDynamicPhpAllowed;
	}
	
	function setDynamicParsingAllowed($bDynamicParsingAllowed)
	{
		$this->bDynamicParsingAllowed = $bDynamicParsingAllowed;
	}
		
	//overridable - may a contentfield of this type contain dynamic parsing syntax (conditions, loops, cache blocks...) ?
	function runDynamicParsingSupported()
	{
		return false;
	}
	//overridable - may a contentfield of this type contain PHP code ?
	function runDynamicPhpSupported()
	{
		return false;
	}
	
	//overridable
	function output($sValue)
	{
		return $sValue;
	}
	
	protected final function testContentFieldValueAtomic($mContentFieldValue)
	{
		if (!$this instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("testContentFieldValueAtomic on a non atomic field");
		}
		
		//global tests common to all contentfields
		if (!$this->runDynamicParsing() && AnwParser::contentHasDynamicParsing($mContentFieldValue))
		{
			$sTags = '&lt;'.implode('&gt; &lt;', AnwParser::getDynamicParsingTags()).'&gt;';
			$sError = AnwComponent::g_editcontent("err_contentfield_dynamic_parsing_disabled", array('tags'=>$sTags));
			throw new AnwInvalidContentFieldValueException($sError);
		}
		
		if (!$this->runDynamicPhp() && AnwUtils::contentHasPhpCode($mContentFieldValue))
		{
			$sError = AnwComponent::g_editcontent("err_contentfield_dynamic_php_disabled");
			throw new AnwInvalidContentFieldValueException($sError);
		}
		
		//tests related to contentfield type
		$this->doTestContentFieldValue($mContentFieldValue);
	}
	
	final function testContentFieldValueComposed($oSubContent)
	{
		if (!$this instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("testContentFieldValueComposed on a non composed field");
		}
		
		//tests related to contentfield type
		$this->doTestContentFieldValueComposed($oSubContent);
	}
	
	//overridable
	protected function doTestContentFieldValue($mContentFieldValue){}
	protected function doTestContentFieldValueComposed($oSubContent){}
}

/**
 * Grouping some code for contentfield based on a Datatype.
 */
abstract class AnwContentFieldPage_datatype extends AnwContentFieldPage implements AnwStructuredContentField_atomic
{
	private $oData=null;
	
	//needs to be overriden
	abstract function initDatatype();
	
	protected function getDatatype()
	{
		if ($this->oData === null)
		{
			$this->oData = $this->initDatatype();
		}
		return $this->oData;
	}
	
	function doGetDefaultAssistedValue()
	{
		return $this->getDatatype()->getDefaultAssistedValue();
	}
	
	protected function initBeforeRender(){}
	
	protected function renderEditInput($sInputValue, $sSuffix, $nInputNumber)
	{
		$this->initBeforeRender();
		$sInputName = $this->getInputName($sSuffix);
		$sInputId = $this->getInputId($sInputName, $nInputNumber);
		
		$HTML = $this->getDatatype()->renderInput($sInputName, $sInputValue, $sInputId, array());
		return $HTML;
	}
	
	protected function renderCollapsedInput($sInputValue, $sSuffix)
	{
		$this->initBeforeRender();
		$sInputName = $this->getInputName($sSuffix);		
		$HTML = $this->getDatatype()->renderCollapsedInput($sInputName, $sInputValue, array());
		return $HTML;
	}
	
	function doTestContentFieldValue($mContentFieldValue)
	{
		$this->getDatatype()->testValue($mContentFieldValue);
	}
	
	function getValuesFromPost($sSuffix)
	{
		$sInputName = $this->getInputName($sSuffix);
		$asValues = $this->getDatatype()->getValuesFromPost($sInputName);
		return $asValues;
	}
	
	function getFieldTip($asParameters=array())
	{
		return $this->getDatatype()->getTip($asParameters);
	}
		
	function getValueFromArrayItem($mCfgItem)
	{
		return $this->getDatatype()->getValueFromArrayItem($mCfgItem);
	}
	function getArrayItemFromValue($mValue)
	{
		return $this->getDatatype()->getArrayItemFromValue($mValue);
	}
	
	// only atomic content fields are indexable
	function getIndexedValue($mFieldValue)
	{
		return $mFieldValue;
	}
}

class AnwContentFieldPage_xml extends AnwContentFieldPage_datatype
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_xml();
		return $oDatatype;
	}
	
	function setCheckPhpSyntax($bValue)
	{
		$this->getDatatype()->setCheckPhpSyntax($bValue);
	}
	
	protected function renderEditInput($sInputValue, $sSuffix, $nInputNumber)
	{
		$sInputName = $this->getInputName($sSuffix);
		$sInputId = $this->getInputId($sInputName, $nInputNumber);
		
		$asInputParameters = array(
			'onkeypress' => "textareaKeyPress(event)",
			'dir' => AnwComponent::g_("local_html_dir")
		);
		
		$HTML = $this->getDatatype()->renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters);		
		$HTML .= <<<EOF

	<script type="text/javascript">document.observe("dom:loaded", function(){anwLockAutoRenew($('$sInputId')); textareaInit($('$sInputId'));});</script>
EOF;
		return $HTML;
	}
	
	function getIndexedValue($mFieldValue)
	{
		throw new AnwUnexpectedException("XML fields are not indexable");
	}
	
	function runDynamicParsingSupported()
	{
		return true;
	}
	
	function runDynamicPhpSupported()
	{
		return true;
	}
}

class AnwContentFieldPage_xhtml extends AnwContentFieldPage_xml
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_xhtml();
		return $oDatatype;
	}
}

class AnwContentFieldPage_date extends AnwContentFieldPage_datatype
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_date();
		return $oDatatype;
	}
	
	protected function renderEditInput($sInputValue, $sSuffix, $nInputNumber)
	{
		$sInputName = $this->getInputName($sSuffix);
		$sInputId = $this->getInputId($sInputName, $nInputNumber);
		
		$asInputParameters = array(
			'dir' => AnwComponent::g_("local_html_dir")
		);
		
		$HTML = $this->getDatatype()->renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters);
		$HTML .= <<<EOF

	<script type="text/javascript">document.observe("dom:loaded", function(){anwLockAutoRenew($('$sInputId'));});</script>
EOF;
		return $HTML;
	}
	
	public function getValueTimestamp($sValue)
	{
		return $this->getDatatype()->getValueTimestamp($sValue);
	}
	
	function getIndexedValue($mFieldValue)
	{
		// index as timestamp, for easier manipulation and filters
		return $this->getValueTimestamp($mFieldValue);
	}
}

class AnwContentFieldPage_string extends AnwContentFieldPage_datatype
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_string();
		return $oDatatype;
	}
	
	protected function renderEditInput($sInputValue, $sSuffix, $nInputNumber)
	{
		$sInputName = $this->getInputName($sSuffix);
		$sInputId = $this->getInputId($sInputName, $nInputNumber);
		
		$asInputParameters = array(
			'dir' => AnwComponent::g_("local_html_dir")
		);
		
		$HTML = $this->getDatatype()->renderInput($sInputName, $sInputValue, $sInputId, $asInputParameters);
		$HTML .= <<<EOF

	<script type="text/javascript">document.observe("dom:loaded", function(){anwLockAutoRenew($('$sInputId'));});</script>
EOF;
		return $HTML;
	}
	
	function addAllowedPattern($sPattern)
	{
		$this->getDatatype()->addAllowedPattern($sPattern);
	}
	
	function addForbiddenPattern($sPattern)
	{
		$this->getDatatype()->addForbiddenPattern($sPattern);
	}
}

class AnwContentFieldPage_ip_address extends AnwContentFieldPage_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_ip_address();
		return $oDatatype;
	}
}

class AnwContentFieldPage_email_address extends AnwContentFieldPage_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_email_address();
		return $oDatatype;
	}
}

class AnwContentFieldPage_url extends AnwContentFieldPage_string
{
	private $amAllowedUrlTypes;
	
	function __construct($sName, $amAllowedUrlTypes=false)
	{
		$this->amAllowedUrlTypes = $amAllowedUrlTypes;
		parent::__construct($sName);
	}
	
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_url();
		if ($this->amAllowedUrlTypes)
		{
			$oDatatype->setAllowedUrlTypes($this->amAllowedUrlTypes);
		}
		return $oDatatype;
	}
}

class AnwContentFieldPage_system_directory extends AnwContentFieldPage_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_system_directory();
		return $oDatatype;
	}
	
	function addTestFile($sTestFile)
	{
		$this->getDatatype()->addTestFile($sTestFile);
	}
}

class AnwContentFieldPage_userlogin extends AnwContentFieldPage_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_userlogin();
		return $oDatatype;
	}
}

class AnwContentFieldPage_boolean extends AnwContentFieldPage_datatype
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_boolean();
		return $oDatatype;
	}
	
	protected function initBeforeRender()
	{
		//we can't do this in init() because of dead loop: 
		//Component::cfg()->Component::getConfigurableContent()->contentField::init()->getFieldTranslation()->AnwSessions::getCurrentSession()->component::cfg()
		$this->getDatatype()->setLabel( $this->getFieldTranslation("_checkbox") );
	}
}

abstract class AnwContentFieldPage_number extends AnwContentFieldPage_datatype
{
	//override : number is never translatable
	function isTranslatable()
	{
		return false;
	}
	
	function setValueMin($nValue)
	{
		$this->getDatatype()->setValueMin($nValue);
	}
	
	function setValueMax($nValue)
	{
		$this->getDatatype()->setValueMax($nValue);
	}
	
	protected function initBeforeRender()
	{
		//we can't do this in init() because of dead loop: 
		//Component::cfg()->Component::getConfigurableContent()->contentField::init()->getFieldTranslation()->AnwSessions::getCurrentSession()->component::cfg()
		$this->getDatatype()->setLabel( $this->getFieldTranslation("_number",array(),"") );
	}
}

class AnwContentFieldPage_integer extends AnwContentFieldPage_number
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_integer();
		return $oDatatype;
	}
}

class AnwContentFieldPage_delay extends AnwContentFieldPage_integer
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_delay();
		return $oDatatype;
	}
}

class AnwContentFieldPage_float extends AnwContentFieldPage_number
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_float();
		return $oDatatype;
	}
}

abstract class AnwContentFieldPage_enum extends AnwContentFieldPage_datatype
{
	//override : enum is never translatable
	function isTranslatable()
	{
		return false;
	}
	
	function setEnumValues($asEnumValues)
	{
		$this->getDatatype()->setEnumValues($asEnumValues);
	}
	
	function setEnumValuesFromList($asEnumValues)
	{
		$this->getDatatype()->setEnumValuesFromList($asEnumValues);
	}
	
	//when field is multiple, remove redondant values
	function getValuesFromPost($sSuffix)
	{
		$asFieldValues = parent::getValuesFromPost($sSuffix);
		return array_unique($asFieldValues);
	}
}

class AnwContentFieldPage_radio extends AnwContentFieldPage_enum
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_radio();
		return $oDatatype;
	}
}

class AnwContentFieldPage_select extends AnwContentFieldPage_enum
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_select();
		return $oDatatype;
	}
}

class AnwContentFieldPage_checkboxGroup extends AnwContentFieldPage_enum implements AnwStructuredContentField_renderedAsMultiple
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_checkboxGroup();
		return $oDatatype;
	}
}

class AnwContentFieldPage_pageGroup extends AnwContentFieldPage_checkboxGroup
{
	private $oFetchingContentClass;
	private $asFetchingPatterns = array();
	private $asFetchingLangs = array();
	private $sFetchingSort = AnwUtils::SORT_BY_NAME;
	private $sFetchingSortOrder=AnwUtils::SORTORDER_ASC;
	private $asFetchingFilters = array();
	
	function __construct($sName, $oContentClass)
	{
		$this->oFetchingContentClass = $oContentClass;
		parent::__construct($sName);
	}
	
	function initDatatype()
	{
		$oDatatype = parent::initDatatype();
		
		//set enum values
		$nFetchingLimit = 0;		
		$aoPages = AnwStorage::fetchPagesByClass($this->asFetchingPatterns, $this->oFetchingContentClass, 
					$this->asFetchingLangs, $nFetchingLimit, $this->sFetchingSort, $this->sFetchingSortOrder, $this->asFetchingFilters);
		
		$asEnumValues = array();
		foreach ($aoPages as $oPage)
		{
			//use pageId as field value, so that pages can be renamed without loosing enum values
			$asEnumValues[$oPage->getPageGroup()->getId()] = $oPage->getName();
		}
		$oDatatype->setEnumValues($asEnumValues);
		return $oDatatype;
	}
	
	final function setEnumValues($asEnumValues)
	{
		throw new AnwUnexpectedException("setEnumValues is not applicable to AnwContentFieldPage_contentEnum");
	}
	
	function setContentFetchingPatterns($asPatterns)
	{
		$this->asFetchingPatterns = $asPatterns;
	}
	
	function setContentFetchingLangs($asLangs)
	{
		$this->asFetchingLangs = $asLangs;
	}
	
	function setContentFetchingSort($sSort)
	{
		$this->sFetchingSort = $sSort;
	}
	
	function setContentFetchingOrder($sSortOrder)
	{
		$this->sFetchingSortOrder = $sSortOrder;
	}
	
	function setContentFetchingFilters($asFilters)
	{
		$this->asFetchingFilters = $asFilters;
	}
}

class AnwContentFieldPage_geoposition extends AnwContentFieldPage_datatype
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_geoposition();
		return $oDatatype;
	}
	
	//override : geoposition is never translatable
	function isTranslatable()
	{
		return false;
	}
}

//----------------------------------------------

abstract class AnwContentFieldPage_container extends AnwContentFieldPage implements AnwStructuredContentField_composed
{
	final function newContent()
	{
		return AnwContentPage::newContent($this);
	}
	
	final function doGetDefaultAssistedValue()
	{
		throw new AnwUnexpectedException("getDefaultAssistedValue called on composed field");
	}
	
	final protected function renderEditInput($asInputSubcontentAndRendered, $sSuffix, $nInputNumber)
	{
		$oSubContent = $asInputSubcontentAndRendered[AnwStructuredContent::IDX_SUBCONTENT];
		$sRendered = $asInputSubcontentAndRendered[AnwStructuredContent::IDX_RENDERED];		
		return $this->renderEditInputComposed($oSubContent, $sRendered, $sSuffix);
	}
	
	//overridable
	protected function renderEditInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		$HTML = <<<EOF

<div class="contentfield_container">
	$sRendered
</div> <!-- end contentfield_container -->
EOF;
		return $HTML;
	}
	
	
	
	
	
	final protected function renderCollapsedInput($asInputSubcontentAndRendered, $sSuffix)
	{
		$oSubContent = $asInputSubcontentAndRendered[AnwStructuredContent::IDX_SUBCONTENT];
		$sRendered = $asInputSubcontentAndRendered[AnwStructuredContent::IDX_RENDERED];	
		return $this->renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix);
	}
	protected function renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix){
		return $sRendered;
	}	
	
	//used by action_edit
	function updateSuffix($sSuffix, $nInputNumber=-1)
	{
		$sSuffix .= '-'.$this->getName();
		if ($this->isMultiple())
		{
			if ($nInputNumber == -1) throw new AnwUnexpectedException("updateSuffix no inputNumber but multiple");
			$sSuffix .= $nInputNumber;
		}
		return $sSuffix;
	}
	
	function getValuesFromPost()
	{
		throw new AnwUnexpectedException("should never go here");
	}
	
	function getValueFromArrayItem($mCfgItem)
	{
		throw new AnwUnexpectedException("getValueFromArrayItem on container");
	}
	function getArrayItemFromValue($mValue)
	{
		throw new AnwUnexpectedException("getArrayItemFromValue on container");
	}
}

class AnwContentFieldPage_link extends AnwContentFieldPage_container implements AnwIPage_link
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldPage_string(self::FIELD_TITLE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldPage_string(self::FIELD_URL);
		$oContentField->setTranslatable(false);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldPage_radio(self::FIELD_TARGET);
		$oContentField->setTranslatable(false);
		$asEnumValues = array();
		$asEnumValues[self::TARGET_SELF] = self::getTargetLabel(self::TARGET_SELF);
		$asEnumValues[self::TARGET_BLANK] = self::getTargetLabel(self::TARGET_BLANK);
		$oContentField->setEnumValues($asEnumValues);
		$oContentField->setDefaultValue(self::TARGET_SELF);
		$this->addContentField($oContentField);
	}
	
	function setUrlTranslatable($bTranslatable)
	{
		$this->getContentField(self::FIELD_URL)->setTranslatable($bTranslatable);
	}
	
	function pubcall($sArg, $oContent)
	{
		switch($sArg)
		{
			//TODO: executeHtmlAndPhpCode
			case "title":
				return $oContent->getContentFieldValue(self::FIELD_TITLE);
				break;
			
			case "url":
				return $oContent->getSubContents(self::FIELD_URL);
				break;
			
			case "target":
				return $oContent->getSubContents(self::FIELD_TARGET);
				break;
		}
	}
	
	static function renderLink($oSubContent, $sCssClass="", $sCssStyle="")
	{
		$sTitle = $oSubContent->getContentFieldValue(self::FIELD_TITLE);
		$sUrl = $oSubContent->getContentFieldValue(self::FIELD_URL);
		$sTarget = $oSubContent->getContentFieldValue(self::FIELD_TARGET);
		$sTargetHtml = ( $sTarget==self::TARGET_SELF?'':' target="'.$sTarget.'"');
		
		$sCssClassHtml = ( $sCssClass?' class="'.$sCssClass.'"':'' );
		$sCssStyleHtml = ( $sCssStyle?' class="'.$sCssStyle.'"':'' );
		
		$HTML = <<<EOF

<a href="{$sUrl}"{$sTargetHtml}{$sCssStyleHtml}>{$sTitle}</a>
EOF;
		return $HTML;
	}
	
	protected static function getTargetLabel($sTarget)
	{
		return AnwComponent::g_editcontent("contentfield_link_target".$sTarget);
	}
	
	function renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		$sTitle = $oSubContent->getContentFieldValue(self::FIELD_TITLE);
		$sUrl = $oSubContent->getContentFieldValue(self::FIELD_URL);
		$sTarget = $oSubContent->getContentFieldValue(self::FIELD_TARGET);
		
		$asParameters = array('link'=>self::renderLink($oSubContent), 'url'=>'<a href="'.$sUrl.'" target="_blank">'.$sUrl.'</a>', 'target'=>self::getTargetLabel($sTarget));
		return $this->getFieldTranslation("_collapsed", $asParameters);
	}
}


class AnwContentFieldPage_daterange extends AnwContentFieldPage_container implements AnwIPage_daterange
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldPage_date(self::FIELD_BEGIN);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldPage_date(self::FIELD_END);
		$this->addContentField($oContentField);
	}
	
	function pubcall($sArg, $oContent)
	{
		switch($sArg)
		{
			//TODO: executeHtmlAndPhpCode
			case self::PUB_BEGIN:
				return AnwDatatype_date::getValueTimestamp($oContent->getContentFieldValue(self::FIELD_BEGIN));
				break;
			
			case self::PUB_END:
				return AnwDatatype_date::getValueTimestamp($oContent->getContentFieldValue(self::FIELD_END));
				break;
		}
	}
	
	function renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		$oContentFieldBegin = $this->getContentField(self::FIELD_BEGIN);
		$sBegin = Anwi18n::date($oContentFieldBegin->getValueTimestamp($oSubContent->getContentFieldValue(self::FIELD_BEGIN))); //TODO lang
		
		$oContentFieldEnd = $this->getContentField(self::FIELD_END);
		$sEnd = Anwi18n::date($oContentFieldEnd->getValueTimestamp($oSubContent->getContentFieldValue(self::FIELD_END))); //TODO lang
		
		$asParameters = array('begin'=>$sBegin, 'end'=>$sEnd);
		return $this->getFieldTranslation("_collapsed", $asParameters);
	}
}

?>