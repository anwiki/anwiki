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
 * ContentField definition, and implementation of various common ContentFields.
 * @package Anwiki
 * @version $Id: class_contentfield.php 337 2010-10-10 21:05:55Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

/* In fact, it's AnwContentField*_container which should inherit from AnwStructuredContentFieldsContainer.
 * This is not possible untill multiple inherit is supported by PHP.
 */
abstract class AnwStructuredContentField extends AnwStructuredContentFieldsContainer
{
	private $sName;
	private $oMultiplicity;
	private $bCollapsed;
	private $oContainer; //parent (contentfield or contentclass)
	private $amCachedFieldTranslations = array();
	private $amDefaultValues = null;
	private $sDefaultAssistedValueOverride = null;
	private $oRelatedComponent; //for looking up translations of contentfields declared from components (instead of declared by contentclass)
	private $bMandatory = false;
	
	private static $bDisplayOnlyMandatory = false;
	private static $bDoCollapsing = true;
	
	const INPUT_PREFIX = "anwpost";
	
	function __construct($sName)
	{
		$this->sName = $sName;
		
		if ($this instanceof AnwStructuredContentField_renderedAsMultiple)
		{
			$this->oMultiplicity = new AnwContentMultiplicity_multiple();
		}
		else
		{
			$this->oMultiplicity = new AnwContentMultiplicity_single();
		}
		
		if ($this instanceof AnwStructuredContentField_composed)
		{
			$this->setCollapsed(true);
		}
	}
	
	//overridable
	function init()
	{
		
	}
	
	function setRelatedComponent($oRelatedComponent)
	{
		$this->oRelatedComponent = $oRelatedComponent;
	}
	
	function getRelatedComponent()
	{
		if ($this->oRelatedComponent)
		{
			return $this->oRelatedComponent;
		}
		
		if ($this->getContainer() instanceof AnwStructuredContentField_composed)
		{
			return $this->getContainer()->getRelatedComponent();
		}
		
		return null;
	}
	
	function setMultiplicity($oMultiplicity)
	{
		$this->oMultiplicity = $oMultiplicity;
		
		if ($this instanceof AnwStructuredContentField_renderedAsMultiple 
			&& !($oMultiplicity instanceof AnwContentMultiplicity_multiple))
		{
			throw new AnwUnexpectedException("content field _renderedAsMultiple must have multiple multiplicity!");
		}
	}
	
	function setMandatory($bMandatory)
	{
		$this->bMandatory = $bMandatory;
	}
	
	/**
	 * A mandatory field will always be displayed, even when bDisplayOnlyMandatory=true.
	 * Mandatory status only affects field displaying, it doesn't affects checks that are performed on contentfield's values.
	 */
	function isMandatory()
	{
		return $this->bMandatory;
	}
	
	function setCollapsed($bCollapsed)
	{
		$this->bCollapsed = $bCollapsed;
	}
	
	function getName()
	{
		return $this->sName;
	}
	
	function getMultiplicity()
	{
		return $this->oMultiplicity;
	}
	
	function getContainer()
	{
		return $this->oContainer;
	}

	/**
	 * Get contentfield's default value.
	 */
	final function getDefaultValues()
	{
		if (!$this instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("getDefaultValues called on composed field");
		}
		
		if ($this->amDefaultValues !== null)
		{
			// we have set default values for this contentfield, return it!
			return $this->amDefaultValues;
		}
		else
		{
			// we have no default values, return contentfield's defaultAssistedValue
			if ($this->isMultiple())
			{
				return array();
			}
			else
			{
				$sContentFieldDefaultAssistedValue = $this->getDefaultAssistedValue(); //a defaultAssistedValue is always set
				return array($sContentFieldDefaultAssistedValue);
			}
		}		
	}
	
	/**
	 * Returns true if this contentfield has set default values.
	 * Else, it will return false, even if it has defaultAssistedValue.
	 */
	final function hasSetDefaultValues()
	{
		return $this->amDefaultValues!==null;
	}
	
	final function getDefaultValue()
	{
		if ($this->amDefaultValues !== null)
		{
			// we have set default values for this contentfield, return it!
			return reset($this->amDefaultValues);
		}
		else
		{
			// we have no default values, return contentfield's defaultAssistedValue
			$sContentFieldDefaultAssistedValue = $this->getDefaultAssistedValue();
			return $sContentFieldDefaultAssistedValue;
		}		
	}
	
	/**
	 * Set multiple contentfield's default value.
	 */
	final function setDefaultValues($asValues)
	{
		if ($this instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("getDefaultAssistedValues called on composed field");
		}
		if (!$this->isMultiple() && count($asValues)!=1)
		{
			throw new AnwUnexpectedException("setDefaultValues can be called on atomic field only with one value");
		}
		$this->amDefaultValues = $asValues;
	}
	
	/**
	 * Get the default value for assisting content edition on an atomic ContentField.
	 */
	final function getDefaultAssistedValue()
	{
		if ($this->sDefaultAssistedValueOverride!==null)
		{
			return $this->sDefaultAssistedValueOverride;
		}
		return $this->doGetDefaultAssistedValue();
	}
	
	/**
	 * Set the DEFAULT ASSISTED VALUE for assisting content edition on an atomic ContentField.
	 * WARNING, this function should have been named "setDefaultAssistedValue", but we have kept "setDefaultValue"
	 * to make contenclass development easier.
	 * This function sets default assisted value, it has NOTHING to do with setDefaultValues() which sets defaultValue(s).
	 */
	final function setDefaultValue($sValue)
	{
		$this->sDefaultAssistedValueOverride = $sValue;
	}
	
	
	
	/**
	 * Get the default value for assisting content edition on an atomic ContentField.
	 */
	function doGetDefaultAssistedValue() //overridable
	{
		return "";
	}
	
	/**
	 * Warning! This function may call overloaded functions by contentfields with tests consomming high cpu time
	 * (such as connecting to a database for checking that valid user/pwd have been edited).
	 * This function should be only called when it's really needed, and should never be called more than one time.
	 * This function performs ALL possible tests for checking contentfields values validity.
	 * 
	 * @param $oContentParent content for which fieldValues/subcontents will be set if the test is success
	 */
	final function testContentFieldValues($amFieldValuesOrSubContents, $oContentParent)
	{
		AnwUtils::checkFriendAccess(array("AnwStructuredContent","AnwStructuredContentField"));
		
		//test multiplicity
		$this->testContentFieldMultiplicity($amFieldValuesOrSubContents);
		
		if ($this instanceof AnwStructuredContentField_atomic)
		{
			//test each value
			foreach ($amFieldValuesOrSubContents as $sFieldValue)
			{
				if (is_array($sFieldValue)||is_object($sFieldValue))
				{
					throw new AnwUnexpectedException("testContentFieldValues on atomic: not a string");
				}
				
				//here we don't return a simple 'AnwInvalidContentFieldValueException' to prevent unauthorized users to access PHP source
				//this will display a big ACL error page instead of edit form...			
				if (AnwUtils::contentHasPhpCode($sFieldValue))
				{
					AnwCurrentSession::getUser()->checkPhpEditionAllowed();
				}
				
				//check JS permission
				if (AnwUtils::contentHasJsCode($sFieldValue) && !AnwCurrentSession::getUser()->isJsEditionAllowed())
				{
					$sError = AnwComponent::g_editcontent("err_contentfield_acl_js");
					throw new AnwInvalidContentFieldValueException($sError);
				}
				
				//specific tests for atomic fields
				$this->testContentFieldValueAtomic($sFieldValue);
			}
			
			//if no error, test all atomic values together
			$this->testAllContentFieldValuesAtomic($amFieldValuesOrSubContents);
		}
		else
		{
			//test each subcontents occurence
			foreach ($amFieldValuesOrSubContents as $oContent)
			{
				if (!$oContent instanceof AnwStructuredContent)
				{
					throw new AnwUnexpectedException("testContentFieldValues on composed: not a subcontent");
				}
				
				//test subsubcontents
				$aoSubContentFields = $this->getContentFields();
				foreach ($aoSubContentFields as $oSubContentField)
				{
					//recursive test
					$amSubValuesOrSubContents = null;
					$sSubContentFieldName = $oSubContentField->getName();
					if ($oSubContentField instanceof AnwStructuredContentField_atomic)
					{
						$amSubValuesOrSubContents = $oContent->getContentFieldValues($sSubContentFieldName);
					}
					else
					{
						$amSubValuesOrSubContents = $oContent->getSubContents($sSubContentFieldName);
					}
					$oSubContentField->testContentFieldValues($amSubValuesOrSubContents, $oContent);
				}
				
				//specific tests for composed fields - at last
				$this->testContentFieldValueComposed($oContent);
			}
			
			//if no error, test all subcontents together
			$this->testAllContentFieldValuesComposed($amFieldValuesOrSubContents);
		}
	}
	
	//overridable
	protected function testContentFieldMultiplicity($asFieldValues)
	{
		$this->getMultiplicity()->testContentFieldMultiplicity($this, $asFieldValues);
	}
	
	protected abstract function testContentFieldValueAtomic($mContentFieldValue);
	protected abstract function testContentFieldValueComposed($mContentFieldValue);
	
	//overridable
	protected function testAllContentFieldValuesAtomic($amContentFieldValues){}
	protected function testAllContentFieldValuesComposed($amContentFieldValues){}
	
	final function getInputName($sSuffix)
	{
		$sInputName = self::INPUT_PREFIX.$sSuffix.'-'.$this->getName();
		return $sInputName;
	}	
	
	protected final function getInputId($sInputName, $nInputNumber)
	{
		return $sInputName.'-'.$nInputNumber;
	}
	
	final function getValueFromPost($sSuffix)
	{
		if (!($this->getMultiplicity() instanceof AnwContentMultiplicity_single)) throw new AnwUnexpectedException("getValueFromPost called on non single multiplicity !");
		$asFieldValues = $this->getValuesFromPost($sSuffix);
		return $asFieldValues[0];
	}
	
	function setContainer($oContentFieldContainer)
	{
		$this->oContainer = $oContentFieldContainer;
	}
	
	final function getContentClass()
	{
		if ($this->getContainer() instanceof AnwStructuredContentClass)
		{
			return $this->getContainer();
		}
		else if ($this->getContainer() instanceof AnwStructuredContentField_composed)
		{
			return $this->getContainer()->getContentClass();
		}
		else
		{
			//should never go here
			throw new AnwUnexpectedException("getContentClass : not a contentclass or a contentfield composed");
		}
	}
	
	abstract function getComponent();
	
	
	static function setDisplayOnlyMandatory($bDisplayOnlyMandatory)
	{
		self::$bDisplayOnlyMandatory = $bDisplayOnlyMandatory;
	}
	static function getDisplayOnlyMandatory()
	{
		return self::$bDisplayOnlyMandatory;
	}
	function hasMandatoryChild()
	{
		if ($this instanceof AnwStructuredContentField_composed)
		{
			$aoSubContentFields = $this->getContentFields();
			foreach ($aoSubContentFields as $oSubContentField)
			{
				if ($oSubContentField->isMandatory() || $oSubContentField->hasMandatoryChild())
				{
					return true;
				}
			}
		}
		return false;
	}	
	function isMandatoryChild()
	{
		return ($this->getContainer() instanceof AnwStructuredContentField_composed 
				&& ($this->getContainer()->isMandatory() || $this->getContainer()->isMandatoryChild()));
	}
	function isDisplayed()
	{
		if (self::getDisplayOnlyMandatory() && !$this->isMandatory() && !$this->hasMandatoryChild() &&!$this->isMandatoryChild())
		{
			return false;
		}
		return true;
	}
	
	
	static function setDoCollapsing($bCollapsing)
	{
		self::$bDoCollapsing = $bCollapsing;
	}
	
	static function getDoCollapsing()
	{
		return self::$bDoCollapsing;
	}
	
	function isCollapsed()
	{
		//TODO: for the moment, a contentfield who has collapsed childs can't be collapsed.
		//otherwise, we would encounter serious edition problems as same edit inputs would appear twice in html source.
		if (self::getDoCollapsing() && $this->bCollapsed && !$this->hasCollapsedChild())
		{
			return true;
		}
		return false;
	}
	
	function isCollapsedChild()
	{
		return ($this->getContainer() instanceof AnwStructuredContentField_composed 
				&& $this->getContainer()->isCollapsed());
	}
	
	function hasCollapsedChild()
	{
		if ($this instanceof AnwStructuredContentField_composed)
		{
			$aoSubContentFields = $this->getContentFields();
			foreach ($aoSubContentFields as $oSubContentField)
			{
				if ($oSubContentField->isCollapsed() || $oSubContentField->hasCollapsedChild())
				{
					return true;
				}
			}
		}
		return false;
	}
	
	abstract protected function renderEditInput($sInputValue, $sSuffix, $nInputNumber);
	abstract protected function renderCollapsedInput($sInputValue, $sSuffix);
	
	final function getRenderedEditInput($sInputValue, $sSuffix, $nInputNumber)
	{
		if ($this->isCollapsed() || $this->isCollapsedChild())
		{
			if ($this instanceof AnwStructuredContentField_composed)
			{
				$sInputValueUncollapsed = array(
					AnwStructuredContent::IDX_SUBCONTENT=>$sInputValue[AnwStructuredContent::IDX_SUBCONTENT],
					AnwStructuredContent::IDX_RENDERED=>$sInputValue['UNCOLLAPSED']);
				$sUncollapsedHtml = $this->renderEditInput($sInputValueUncollapsed, $sSuffix, $nInputNumber);
				
				$sCollapsedHtml = $this->renderCollapsedInput($sInputValue, $sSuffix);
				
				return $this->getComponent()->tpl()->renderCollapsedInput($sUncollapsedHtml, $sCollapsedHtml, $this);
			}
			else
			{
				if ($this instanceof AnwStructuredContentField_renderedAsMultiple)
				{
					$sHtml = "";
					foreach ($sInputValue as $sOneValue)
					{
						$sHtml .= $this->renderCollapsedInput($sOneValue, $sSuffix);
					}
					return $sHtml;
				}
				return $this->renderCollapsedInput($sInputValue, $sSuffix);
			}
		}
		
		return $this->renderEditInput($sInputValue, $sSuffix, $nInputNumber);
	}
	
	final function renderEditInputs($amValues, $sSuffix, $bHasOverridingValues, $sFieldError=false)
	{
		$sInputHtml = $this->getMultiplicity()->doRenderEditInputs($this, $amValues, $sSuffix);
		
		$sFieldTip = "";
		$sMultiplicityTip = "";
		
		if (!$this->isCollapsed() && !$this->isCollapsedChild())
		{		
			$sMultiplicityTip = $this->getMultiplicity()->getMultiplicityTip($this);
			if ($this->getMultiplicity() instanceof AnwContentMultiplicity_multiple)
			{
				$sFieldTip = ""; //already written by renderEditInputN!
			}
			else
			{
				$sFieldTip = $this->getFieldTip();
			}
		}
		$sFieldExplain = $this->getFieldExplain();
		
		$sLabel = $this->getFieldLabel();
		
		$bShowHasOverridingValues = ($bHasOverridingValues && $this instanceof AnwContentFieldSettings);
		$bShowRevert = ($bShowHasOverridingValues && !($this->isCollapsed() || $this->isCollapsedChild())); //TODO
		
		if ($this instanceof AnwStructuredContentField_tab)
		{
			$sTabDivId = 'tab_'.$this->getInputName($sSuffix);
			return $this->getComponent()->tpl()->renderTabField($sTabDivId, $sLabel, $sInputHtml, $sFieldExplain, $sFieldError);
		}
		else if ($this instanceof AnwStructuredContentField_composed)
		{
			return $this->getComponent()->tpl()->renderComposedField($sLabel, $sInputHtml, $sFieldTip, $sFieldExplain, $sMultiplicityTip, $bShowHasOverridingValues, $sFieldError);
		}
		else
		{
			return $this->getComponent()->tpl()->renderInputField($sLabel, $sInputHtml, $sFieldTip, $sFieldExplain, $sMultiplicityTip, $bShowHasOverridingValues, $sFieldError);
		}
	}
	
	function renderAdditionalEditInput($sSuffix, $oContentParent)
	{
		AnwUtils::checkFriendAccess("AnwStructuredContent");
				
		if (!$this->isMultiple())
		{
			throw new AnwUnexpectedException("JS_AddMultipleContentField on non multiple field");
		}
		
		//temporary disable collapsing
		self::setDoCollapsing(false);
		
		$nNewSuffixNumber = AnwUtils::genUniqueIdNumeric();
		$sDefaultValue = null;
		if ($this instanceof AnwStructuredContentField_composed)
		{
			$sNewSuffix = $this->updateSuffix($sSuffix, $nNewSuffixNumber);
			$oSubContent = $oContentParent->newContent($this);
			$sSubRender = $oSubContent->renderEditHtmlForm(false, "", $sNewSuffix);
			$sDefaultValue = array(AnwStructuredContent::IDX_SUBCONTENT=>$oSubContent, AnwStructuredContent::IDX_RENDERED=>$sSubRender);
		}
		else
		{
			$aoValuesTmp = $this->getDefaultValues(); // in two lines for avoiding php warning
			$sDefaultValue = array_pop($aoValuesTmp); //TODO ??
		}
		$sHtmlRender = $this->renderEditInputN($sSuffix, $sDefaultValue, $nNewSuffixNumber);
		
		//enable collapsing again
		self::setDoCollapsing(true);
		
		return $sHtmlRender;
	}
	
	/**
	 * Render one instance of a multiple contentfield
	 * //!used by action_edit
	 */
	function renderEditInputN($sSuffix, $sValue, $nInputNumber)
	{
		$sInstancesClass = "instance_".$this->getInputName($sSuffix);
				
		$sRenderedInput = $this->getRenderedEditInput($sValue, $sSuffix, $nInputNumber);
		
		if ($this instanceof AnwStructuredContentField_composed)
		{
			if ($this->getMultiplicity() instanceof AnwContentMultiplicity_multiple)
			{
				$sInstancesName = $this->getInputName($sSuffix);
				$sRenderedInput .= <<<EOF
			
<input type="hidden" name="{$sInstancesName}[]" value="$nInputNumber"/>
EOF;
			}
			$sSuffix = $this->updateSuffix($sSuffix, $nInputNumber); //update suffix
		}
		
		if ($this->isCollapsed() || $this->isCollapsedChild())
		{
			//TODO
			if ($this instanceof AnwStructuredContentField_atomic)
			{
				$HTML = '<span style="padding:0.5em;">'.$sRenderedInput.'</span>';
			}
			else
			{
				//we want to sort contentfields even when they are collapsed!
				//$HTML = $sRenderedInput;
				$HTML = $this->getComponent()->tpl()->renderMultipleInputInstance($sInstancesClass, $this->getMultiplicity()->isSortable(), $sRenderedInput, $this->getFieldLabelSingle(), $this->getFieldTip());
			}
		}
		else
		{
			$HTML = $this->getComponent()->tpl()->renderMultipleInputInstance($sInstancesClass, $this->getMultiplicity()->isSortable(), $sRenderedInput, $this->getFieldLabelSingle(), $this->getFieldTip());
		}
		return $HTML;
	}
	
	protected function getFieldTranslation($sTranslationName, $asParameters=array(), $sTranslationIfEmpty=false)
	{
		$sCacheKey = $sTranslationName.md5(implode(".",$asParameters));
		if (!isset($this->amCachedFieldTranslations[$sCacheKey]))
		{
			//first, try to get it from component
			$sTranslation = $this->doGetFieldTranslation("contentfield", $sTranslationName, $asParameters, true);
			
			//if not found, try to get it from global
			if ($sTranslation === "")
			{
				$sTranslation = $this->doGetFieldTranslation("contentfield", $sTranslationName, $asParameters, false);
			}
			
			//if no translation found
			if ($sTranslation === "")
			{
				if ($sTranslationIfEmpty===false)
				{
					//return #TRANSLATIONID#
					$sTranslation = "#".$this->baseTranslationName("contentfield", 99, true)."#";
				}
				else
				{
					$sTranslation = $sTranslationIfEmpty;
				}
			}
			$this->amCachedFieldTranslations[$sCacheKey] = $sTranslation;
		}
		return $this->amCachedFieldTranslations[$sCacheKey];
	}
	
	/**
	 * Must be overriden by contentfield_page and contentfield_setting.
	 */
	protected abstract function t_structuredcontentfield($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false);
	
	/**
	 * Special override system for translations.
	 * Below there is an example applied to contentclass_menu.
	 * C means that the string comes from the class (ex: "menuItem" comes from "AnwContentFieldPage_menuItem")
	 * N means that the string comes from the name of the field (ex: "subitems" is the name of a field in "AnwContentFieldPage_menuItem")
	 * Here is the order for looking to translations.
	 * 
	 * contentfield_menu_items_subitems_link_title_label
	 *               C     N      N      N    N
	 * 
	 * contentfield_menuItem_subitems_link_title_label
	 *                  C       N       N    N
	 * 
	 * contentfield_menuSubItem_link_title_label
	 *                  C        N      N
	 * 
	 * contentfield_link_title_label
	 *                C    N
	 */
	protected final function doGetFieldTranslation($sBasePrefix, $sTranslationName, $asParameters, $bFromComponent, $nMaxDeep=-1)
	{
		if ($nMaxDeep == -1)
		{
			//initialize maxDeep to highest possible value
			$nHighestDeep = 0;
			$oTest = $this;
			while ($oTest->getContainer() instanceof AnwStructuredContentField_composed)
			{
				$oTest = $oTest->getContainer();
				$nHighestDeep++;
			}
			$nMaxDeep = $nHighestDeep;
			
			//try last level translation (special case)
			$sFullTranslationName = $this->baseTranslationName($sBasePrefix, $nMaxDeep, true).$sTranslationName;
			//print "<br/>**Max=".$nMaxDeep.": ".$sFullTranslationName."***<br/>";
			
			if ($bFromComponent)
			{
				$sTranslation = $this->t_structuredcontentfield($sFullTranslationName, $asParameters, false, "");
			}
			else
			{
				$sTranslation = AnwComponent::g_editcontent($sFullTranslationName, $asParameters, false, ""); //instance call required as it's abstract
			}
			if ($sTranslation !== "")
			{
				return $sTranslation;
			}
		}
		
		$sFullTranslationName = $this->baseTranslationName($sBasePrefix, $nMaxDeep, false).$sTranslationName;
		if ($bFromComponent)
		{
			$sTranslation = $this->t_structuredcontentfield($sFullTranslationName, $asParameters, false, "");
		}
		else
		{
			$sTranslation = AnwComponent::g_editcontent($sFullTranslationName, $asParameters, false, ""); //instance call required as it's abstract
		}
		
		//print "<br/>**Max=".$nMaxDeep.": ".$sFullTranslationName."***<br/>";
		if ($sTranslation === "")
		{
			$nMaxDeep--;
			if ($nMaxDeep >= 0)
			{
				$sTranslation = $this->doGetFieldTranslation($sBasePrefix, $sTranslationName, $asParameters, $bFromComponent, $nMaxDeep);
			}
		}
		
		return $sTranslation;
	}
	
	private final function baseTranslationName($sBasePrefix, $nMaxDeep, $bSpecialIncludingContentClass=false)
	{
		//we want something like: contentfield_<classname>_<subfieldname>
		//in example: contentfield_mysqlconnexion_user
		
		if ($nMaxDeep>0 && $this->getContainer() instanceof AnwStructuredContentField_composed) 
		{
			$nNewMaxDeep = $nMaxDeep-1;
			$sTranslationName = $this->getContainer()->baseTranslationName($sBasePrefix, $nNewMaxDeep, $bSpecialIncludingContentClass);
		}
		else if ($bSpecialIncludingContentClass && $this->getContainer() instanceof AnwStructuredContentClass)
		{
			$sClassName = str_replace(array('AnwContentClassPageDefault_','AnwContentClassPageOverride_', 'AnwContentClassSettings'),'',get_class($this->getContainer()));
			$sTranslationName = $sBasePrefix;
			if ($sClassName!="") $sTranslationName .= "_".$sClassName;
		}
		else 
		{
			$sTranslationName = $sBasePrefix;
		}
		
		if ($nMaxDeep==0 && !$bSpecialIncludingContentClass)
		{
			$sTranslationName .= "_".str_replace(array('AnwContentFieldPage_','AnwContentFieldSettings_'),'',get_class($this));
		}
		else
		{
			$sTranslationName .= "_".$this->getName();
		}
		return $sTranslationName;
	}
	
	function getFieldTip($asParameters=array())
	{
		$sTranslation = $this->getFieldTranslation("_tip", $asParameters, "");
		return $sTranslation;
	}
	
	function getFieldExplain($asParameters=array())
	{
		$sTranslation = $this->getFieldTranslation("_explain", $asParameters, "");
		return $sTranslation;
	}
	
	function getFieldLabel()
	{
		$sTranslation = $this->getFieldTranslation("_label");
		return $sTranslation;
	}
	
	function getFieldLabelSingle() 
	{
		$sTranslation = $this->getFieldTranslation("_single");
		return $sTranslation;
	}
	
	function getFieldLabelPlural() 
	{
		$sTranslation = $this->getFieldTranslation("_plural");
		return $sTranslation;
	}
	
	final function isMultiple()
	{
		return ($this->getMultiplicity() instanceof AnwContentMultiplicity_multiple);
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
	
	// --- BEGIN SHARED CODE FOR composed fields ---
	private $aoDefaultSubContents = null;
	
	function setDefaultSubContents($aoSubContents)
	{
		if (!$this instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException ("setDefaultSubContents called on atomic");
		}
		if (!$this->isMultiple())
		{
			throw new AnwUnexpectedException ("setDefaultSubContents called on monovalued");
		}
		$this->aoDefaultSubContents = $aoSubContents;
	}
	
	function hasDefaultSubContents()
	{
		if (!$this instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException ("hasDefaultSubContents called on atomic");
		}
		if (!$this->isMultiple())
		{
			throw new AnwUnexpectedException ("hasDefaultSubContents called on monovalued");
		}
		return ($this->aoDefaultSubContents!==null);
	}
	
	function getDefaultSubContents()
	{
		if (!$this instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException ("getDefaultSubContents called on atomic");
		}
		if (!$this->isMultiple())
		{
			throw new AnwUnexpectedException ("getDefaultSubContents called on monovalued");
		}
		return $this->aoDefaultSubContents;
	}
	// --- END OF SHARED CODE FOR composed fields ---
	
	
	
	//TODO : architecture problem, the contentfield shouldnt inherit from AnwStructuredContentFieldsContainer
	//so we just define required abstract functions, but it will never be used
	function getComponentName()
	{
		 throw new AnwUnexpectedException("ContentField is not a component");
	}
		
	static function getComponentsRootDir()
	{
		throw new AnwUnexpectedException("ContentField is not a component");
	}
	static function getComponentsDirsBegin()
	{
		throw new AnwUnexpectedException("ContentField is not a component");
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName;
	}	
	
	static function getMyComponentType()
	{
		throw new AnwUnexpectedException("ContentField is not a component");
	}
	
	static function discoverEnabledComponents()
	{
		throw new AnwUnexpectedException("ContentField is not a component");
	}
	
	static function loadComponent($sName)
	{
		throw new AnwUnexpectedException("ContentField is not a component");
	}
	
	protected static function debug($sMsg)
	{
		AnwDebug::log("(ContentField)".$sMsg);
	}
}

/**
 * Indicates that it is a content class (ContentClassPage or ContentClassSettings).
 * We need this for recognize these classes due to architecture problems.
 */
interface AnwStructuredContentClass
{
	
}

/**
 * Indicates that the contentfield isn't composed by any other contentfield (it has its own value).
 */
interface AnwStructuredContentField_atomic
{
}

/**
 * Indicates that the contentfield is composed by other contentfields.
 */
interface AnwStructuredContentField_composed
{
	function newContent();
}

/**
 * Indicates that the contentfield is an edition tab.
 */
interface AnwStructuredContentField_tab extends AnwStructuredContentField_composed
{
	
}

//Note : these two interfaces are EXCLUSIVE: a contentfield NEEDS to implements ONE of these two interfaces.  

/**
 * Indicates that the contentfield is always multiple, it can't be single.
 */
interface AnwStructuredContentField_renderedAsMultiple
{
	
}
?>