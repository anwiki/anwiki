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
 * ContentField definition for components settings.
 * @package Anwiki
 * @version $Id: class_contentfield_settings.php 207 2009-04-09 20:40:45Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwContentFieldSettings extends AnwStructuredContentField
{
	function __construct($sName)
	{
		parent::__construct($sName);
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
		return $oComponentForTranslation->t_contentfieldsettings($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	function getComponent()
	{
		return $this->getContentClass()->getComponent();
	}
	
	protected function getFieldTranslation($sTranslationName, $asParameters=array(), $sTranslationIfEmpty=false)
	{
		//first, try to get it from component under "setting_"...
		$sTranslation = $this->doGetFieldTranslation("setting", $sTranslationName, $asParameters, true);
		
		//if not found, run parent
		if ($sTranslation === "")
		{
			$sTranslation = parent::getFieldTranslation($sTranslationName, $asParameters, $sTranslationIfEmpty);
		}
		return $sTranslation;
	}
	
	//
	// BELOW : Stuff artificially INHERITED from AnwStructuredContentFieldsContainerSettings
	// (multiple inherit is not supported by PHP5)
	//
	
	final function rebuildContentFromXml($sXmlValue)
	{
		return AnwContentSettings::rebuildContentFromXml($this, $sXmlValue);
	}
	
	function hasMandatorySettings()
	{
		$aoSettings = $this->getContentFields();
		foreach ($aoSettings as $oSetting)
		{
			if ($oSetting->isMandatory())
			{
				return true;
			}
			if ($oSetting instanceof AnwStructuredContentField_composed)
			{
				if ($oSetting->hasMandatorySettings())
				{
					return true;
				}
			}
		}
		return false;
	}
	
	final protected function testContentFieldMultiplicity($asFieldValues)
	{
		/*if (count($asFieldValues)==0 && !$this->isMultiple() && !$this->isMandatory())
		{
			//ignore errors, it means that default settings were not overloaded
		}
		else
		{*/
			$this->getMultiplicity()->testContentFieldMultiplicity($this, $asFieldValues);
		//}
	}
	
	//
	//End of Stuff artificially INHERITED from AnwStructuredContentFieldsContainerSettings
	//
	
	
	protected final function testContentFieldValueAtomic($mContentFieldValue)
	{
		if (!$this instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("testContentFieldValueAtomic on a non atomic field");
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
abstract class AnwContentFieldSettings_datatype extends AnwContentFieldSettings implements AnwStructuredContentField_atomic
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
		return $this->getDatatype()->getTip($asParameters=array());
	}
	
	function getValueFromArrayItem($mCfgItem)
	{
		return $this->getDatatype()->getValueFromArrayItem($mCfgItem);
	}
	function getArrayItemFromValue($mValue)
	{
		return $this->getDatatype()->getArrayItemFromValue($mValue);
	}
}

class AnwContentFieldSettings_xml extends AnwContentFieldSettings_datatype
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
		return $HTML;
	}
}

class AnwContentFieldSettings_xhtml extends AnwContentFieldSettings_xml
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_xhtml();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_date extends AnwContentFieldSettings_datatype
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
		return $HTML;
	}
}

class AnwContentFieldSettings_string extends AnwContentFieldSettings_datatype
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

class AnwContentFieldSettings_password extends AnwContentFieldSettings_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_password();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_ip_address extends AnwContentFieldSettings_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_ip_address();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_email_address extends AnwContentFieldSettings_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_email_address();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_url extends AnwContentFieldSettings_string
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

class AnwContentFieldSettings_system_directory extends AnwContentFieldSettings_string
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

class AnwContentFieldSettings_userlogin extends AnwContentFieldSettings_string
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_userlogin();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_boolean extends AnwContentFieldSettings_datatype
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

abstract class AnwContentFieldSettings_number extends AnwContentFieldSettings_datatype
{
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

class AnwContentFieldSettings_integer extends AnwContentFieldSettings_number
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_integer();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_delay extends AnwContentFieldSettings_integer
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_delay();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_float extends AnwContentFieldSettings_number
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_float();
		return $oDatatype;
	}
}

abstract class AnwContentFieldSettings_enum extends AnwContentFieldSettings_datatype
{
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

class AnwContentFieldSettings_radio extends AnwContentFieldSettings_enum
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_radio();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_select extends AnwContentFieldSettings_enum
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_select();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_checkboxGroup extends AnwContentFieldSettings_enum implements AnwStructuredContentField_renderedAsMultiple
{
	function initDatatype()
	{
		$oDatatype = new AnwDatatype_checkboxGroup();
		return $oDatatype;
	}
}

class AnwContentFieldSettings_componentSelection extends AnwContentFieldSettings_checkboxGroup
{
	private $mComponentType;
	
	function __construct($sName, $mComponentType)
	{
		parent::__construct($sName);
		
		// set component type
		$this->mComponentType = $mComponentType;
		
		// retrieve available components list
		$asComponents = AnwPlugin::getAvailableComponents($mComponentType);
		$asEnumValues = array();
		
		// special case for actions
		if ($mComponentType==AnwComponent::TYPE_ACTION) 
		{
			foreach ($asComponents as $sAction)
			{
				if (!AnwAction::isAlwaysEnabledAction($sAction))
				{
					$asEnumValues[$sAction] = $sAction;
				}
			}
		}
		else
		{
			$asEnumValues = $asComponents;
		}
		
		$this->setEnumValuesFromList($asEnumValues);
	}
	
	// make sure that selected component is correctly configured
	function doTestContentFieldValue($mContentFieldValue)
	{
		parent::doTestContentFieldValue($mContentFieldValue);
		
		$sComponentName = $mContentFieldValue;
		$oComponent = AnwComponent::loadComponentGeneric($sComponentName, $this->mComponentType);
		if ($oComponent instanceof AnwConfigurable)
		{
			try 
			{
				$oComponent->checkConfigurableContentValidity();
			}
			catch(AnwInvalidContentFieldValueException $e) {
				$sError = AnwComponent::g_editcontent("err_enabling_unconfigured_component", array("componentname"=>$oComponent->getComponentName()));
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
	
	// check conflicts between selected components
	function testAllContentFieldValuesAtomic($amContentFieldValues)
	{
		// rebuild selected components list
		$aoComponents = array();
		foreach ($amContentFieldValues as $sComponentName)
		{
			// here we can load components safely, because this function is only called when all previous tests were successful
			$aoComponents[] = AnwComponent::loadComponentGeneric($sComponentName, $this->mComponentType);
		}
		
		// check dependancies
		foreach ($aoComponents as $oComponent)
		{
			if ($oComponent instanceof AnwDependancyManageable)
			{
				$aoDependancies = $oComponent->getComponentDependancies();
				foreach ($aoDependancies as $oDependancy)
				{
					$oDependancy->checkDependancies($aoComponents);
				}
			}
		}
	}
	
	// automatic conflict solver
	function getValuesFromPost($sSuffix)
	{
		$asFieldValues = parent::getValuesFromPost($sSuffix);
		
		try
		{
			// rebuild selectioned valid components list, with same indices as original values
			$aoComponents = array();
			foreach ($asFieldValues as $i => $sComponentName)
			{
				try
				{
					$this->testContentFieldValueAtomic($sComponentName);
					$aoComponents[$i] = AnwComponent::loadComponentGeneric($sComponentName, $this->mComponentType);
				}
				catch(Exception $e)
				{
					// just ignore this erroneous value...
				}
			}
			
			// solve dependancies
			foreach ($aoComponents as $oComponent)
			{
				if ($oComponent instanceof AnwDependancyManageable)
				{
					$aoDependancies = $oComponent->getComponentDependancies();
					foreach ($aoDependancies as $oDependancy)
					{
						$asFieldValues = $oDependancy->solveDependancies($aoComponents, $asFieldValues);
					}
				}
			}
		}
		catch(Exception $e)
		{
			// if dependancies can't be solved, contentfield will appear as erroneous, but we can't throw exception here
		}
		
		return $asFieldValues;
	}
}

//----------------------------------------------

abstract class AnwContentFieldSettings_container extends AnwContentFieldSettings implements AnwStructuredContentField_composed
{
	final function newContent()
	{
		return AnwContentSettings::newContent($this);
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



abstract class AnwContentFieldSettings_tab extends AnwContentFieldSettings_container implements AnwStructuredContentField_tab
{
	final protected function renderEditInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		return $sRendered;
	}
	
	//a tab is never collapsed
	final function isCollapsed()
	{
		return false;
	}
	
	final function setMultiplicity($oMultiplicity)
	{
		throw new AnwUnexpectedException("no multiplicity can be set for tabs");
	}
	
	function renderEditTab($sSuffix)
	{
		$sTabDivId = 'tab_'.$this->getInputName($sSuffix);
		return $this->getComponent()->tpl()->renderEditTab($this->getEditTab(), $sTabDivId);
	}
	
	//overridable
	function getEditTab()
	{
		$HTML = $this->getFieldLabel();
		return $HTML;
	}
}




class AnwContentFieldSettings_mysqlconnexion extends AnwContentFieldSettings_container implements AnwISettings_mysqlconnexion
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_USER);
		$oContentField->addAllowedPattern("!(.+)!");
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_password(self::FIELD_PASSWORD);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_HOST);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_DATABASE);
		$oContentField->addAllowedPattern("!(.+)!");
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_PREFIX);
		$this->addContentField($oContentField);
	}
	
	protected function doTestContentFieldValueComposed($oSubContent)
	{
		$sUser = $oSubContent->getContentFieldValue(self::FIELD_USER);
		$sPassword = $oSubContent->getContentFieldValue(self::FIELD_PASSWORD);
		$sHost = $oSubContent->getContentFieldValue(self::FIELD_HOST);
		$sDatabase = $oSubContent->getContentFieldValue(self::FIELD_DATABASE);
		$sPrefix = $oSubContent->getContentFieldValue(self::FIELD_PREFIX);
		
		try {
			$oDbLinkTest = AnwMysql::getInstance($sUser, $sPassword, $sHost, $sDatabase, $sPrefix);
		}
		catch(AnwDbConnectException $e) {
			$sError = AnwComponent::g_editcontent("err_contentfield_mysqlconnexion_dbconnect", array("details"=>$e->getMessage()));
			throw new AnwInvalidContentFieldValueException($sError);
		}
		//print "<br/>Test::user={$sUser}, pwd={$sPassword}, db={$sDatabase}, host={$sHost}, prefix={$sPrefix}<br/>";
		
	}
	
	function renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		$sUser = $oSubContent->getContentFieldValue(self::FIELD_USER);
		$sPassword = $oSubContent->getContentFieldValue(self::FIELD_PASSWORD);
		$sHost = $oSubContent->getContentFieldValue(self::FIELD_HOST);
		$sDatabase = $oSubContent->getContentFieldValue(self::FIELD_DATABASE);
		$sPrefix = $oSubContent->getContentFieldValue(self::FIELD_PREFIX);
		
		$asParameters = array('user'=>'<i>'.$sUser.'</i>', 'host'=>'<i>'.$sHost.'</i>', 'database'=>'<i>'.$sDatabase.'</i>', 'prefix'=>'<i>'.$sPrefix.'</i>');
		return $this->getFieldTranslation("_collapsed", $asParameters);
	}
}
?>