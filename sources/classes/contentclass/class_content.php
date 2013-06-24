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
 * XML Content, related to a ContentClass or a ContentField.
 * @package Anwiki
 * @version $Id: class_content.php 362 2011-07-19 21:49:28Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwIStructuredContent
{
	static function rebuildContentFromXml($oContentFieldsContainer, $sXmlValue);
	static function rebuildContentFromArray($oContentFieldsContainer, $amArray);
}

abstract class AnwStructuredContent implements AnwPubcallCapability, AnwIStructuredContent
{
	protected $oContentFieldsContainer;
	protected $aasContentFieldsValues = array();
	protected $aaoSubContents = array();
	
	private $oCachedXml;
	private $sCachedXmlString;
	private $bHtmlEditFormHasErroneousFields;
	private $nHtmlEditFormCountErroneousChild;
	private $bHtmlEditFormHasPhpCode;
	private $bHtmlEditFormHasJsCode;
	
	const XMLNODE_VALUE="anwv";
	
	const IDX_SUBCONTENT = 'SUBCONTENT';
	const IDX_RENDERED = 'RENDERED';
	
	function __construct($oContentFieldsContainer)
	{
		$this->oContentFieldsContainer = $oContentFieldsContainer;
	}
	
	//abstract static function newContent($oContentFieldsContainer);
	
	function hasSetContentFieldValues($sFieldName)
	{
		return isset($this->aasContentFieldsValues[$sFieldName]) && $this->aasContentFieldsValues[$sFieldName]!==null;
	}
	
	function hasSetSubContents($sFieldName)
	{
		return isset($this->aaoSubContents[$sFieldName]) && $this->aaoSubContents[$sFieldName]!==null;
	}

	
	function getSubContents($sFieldName)
	{
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		if (!$oContentField instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("getSubContents called on non composed field");
		}
		if (isset($this->aaoSubContents[$sFieldName]))
		{
			// we have override values
			return $this->aaoSubContents[$sFieldName];
		}
		else
		{
			// we don't have override values
			if ($oContentField->isMultiple())
			{
				// multiple composed fields may have set default subcontents
				if ($oContentField->hasDefaultSubContents())
				{
					return $oContentField->getDefaultSubContents();
				}
				else
				{
					return array(); //??
				}
				
				/*$aoSubContents = array();
				$nDefaultSubContents = $oContentField->getDefaultSubContentsNumber();
				for ($i=0; $i<$nDefaultSubContents; $i++)
				{
					$aoSubContents[] = $this->newContent($oContentField); //create as many subcontents as specified in default values (no more than 1 in fact)
				}
				return $aoSubContents;*/
			}
			else
			{
				return array($this->newContent($oContentField)); //if monovalued, return new empty subcontent to allow algorithms to iterate on it's subcontentfields
			}
		}
	}
	
	function getSubContent($sFieldName)
	{
		$aoSubContents = $this->getSubContents($sFieldName);
		if (!isset( $aoSubContents[0])) throw new AnwUnexpectedException();
		$oSubContent = $aoSubContents[0];
		return $oSubContent;
	}
	
	function getContentFieldValues($sFieldName, $bStripUntr=false, $bDeprecatedParameter=-1)
	{
		if ($bDeprecatedParameter!==-1) {
			//TODO temporary check to remove
			print "usage of deprecated parameter for getContentFieldValues";
			exit;
		}
		
		//check that contentfield exists (or throw an exception)
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		
		if (!$oContentField instanceof AnwStructuredContentField_atomic)
		{
			// you should use getSubContents instead...
			throw new AnwUnexpectedException("getContentFieldValues called on composed field");
		}
		
		//return contentfield's values
		if (isset($this->aasContentFieldsValues[$sFieldName]))
		{
			$asReturn = $this->aasContentFieldsValues[$sFieldName];
			if ($bStripUntr)
			{
				foreach ($asReturn as $i=>$null)
				{
					$asReturn[$i] = AnwUtils::stripUntr($asReturn[$i]);
				}
			}
		}
		else
		{
			AnwDebug::log("WARNING: returning default value for getContentFieldValues(".$sFieldName.")");
			$asReturn = $oContentField->getDefaultValues();
		}
		return $asReturn;
	}
	
	function getContentFieldValue($sFieldName, $i=0, $bStripUntr=false)
	{
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		if (!$oContentField instanceof AnwStructuredContentField_atomic)
		{
			// you should use getSubContent instead...
			throw new AnwUnexpectedException("getContentFieldValues called on composed field");
		}
		if ($oContentField->isMultiple())
		{
			throw new AnwUnexpectedException("getContentFieldValue called on multiple field");
		}
		$asValues = $this->getContentFieldValues($sFieldName, $bStripUntr);
		if (isset($asValues[$i]))
		{
			$sValue = $asValues[$i];
		}
		else
		{
			throw new AnwContentFieldValueNotFoundException("NO value found for getContentFieldValues(".$sFieldName.")");
		}
		return $sValue;
	}
	
	//------------------------------------------------------------------
	
	function getContentFieldOutput($sFieldName)
	{
		$sValue = $this->getContentFieldValue($sFieldName);
		
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		return $oContentField->output($sValue);
	}
	
	function getContentFieldDataAsXml($sFieldName)
	{
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		
		//create XML
		$oDoc = AnwUtils::newDomDocument();
		$oNodeRoot = $oDoc->createElement('values');
		$oDoc->appendChild($oNodeRoot);
		
		if ($oContentField instanceof AnwStructuredContentField_atomic)
		{
			$asFieldValues = $this->getContentFieldValues($sFieldName);
			
			//export field values
			foreach ($asFieldValues as $sFieldValue) //!!Use foreach instead of while which skips empty strings
			{
				$oValueNode = AnwXml::xmlCreateElementWithChilds($oNodeRoot->ownerDocument, self::XMLNODE_VALUE, $sFieldValue);
				$oNodeRoot->appendChild($oValueNode);
			}
		}
		else
		{
			$aoSubContents = $this->getSubContents($sFieldName);
			foreach ($aoSubContents as $oSubContent)
			{
				$sSubContentValue =  $oSubContent->toXmlString();
				$oValueNode = AnwXml::xmlCreateElementWithChilds($oNodeRoot->ownerDocument, self::XMLNODE_VALUE, $sSubContentValue);
				$oNodeRoot->appendChild($oValueNode);
			}
		}
		return $oNodeRoot;
	}
	
	function setContentFieldValues($sFieldName, $asFieldValues)
	{
		//check that contentfield exists or throw an exception
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		
		if (!$oContentField instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("setContentFieldValues called on non atomic field");
		}
		
		//test values: this is commented for performances issues.
		//in normal situation, we shouldn't need it as testContentFieldValues() should be
		//explicitely called each time we need it.
		//$oContentField->testContentFieldValues($asFieldValues);
		
		$this->aasContentFieldsValues[$sFieldName] = $asFieldValues;
		$this->setXmlChanged();
	}
	
	function setSubContents($sFieldName, $aoSubContents)
	{
		//check that contentfield exists or throw an exception
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		
		if (!$oContentField instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("setSubContents called on non composed field");
		}
		
		foreach ($aoSubContents as $oSubContent)
		{
			if (!$oSubContent instanceof AnwStructuredContent)
			{
				throw new AnwUnexpectedException("setSubContents with non AnwContent object");
			}
		}
		
		$this->aaoSubContents[$sFieldName] = $aoSubContents;
		$this->setXmlChanged();
	}
	
	/*function setContentFieldValuesFromXml($sFieldName, $oDoc)
	{
		$oRootNode = $oDoc;//$oDoc->documentElement;
		$asValues = array();
		
		//check that contentfield exists or throw an exception
		$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
		
		//loop for each <anwvalue> tag
		$aoValueElements = AnwXml::xmlGetChildsByTagName(self::XMLNODE_VALUE, $oRootNode);
		foreach ($aoValueElements as $oValueElement)
		{
				$mFieldValue = AnwUtils::xmlDumpNodeChilds($oValueElement);
				$asValues[] = $mFieldValue;
		}
		$this->setContentFieldValues($sFieldName, $asValues);
	}*/
	
	//warning, it returns doc and not documentElement!
	function toXml()
	{
		if (true || $this->oCachedXml==null) //TODO caching disabled temporarily
		{
			$oDoc = AnwUtils::newDomDocument();
			$oNodeRoot = $oDoc->createElement('doc');
			$oDoc->appendChild($oNodeRoot);
			
			//export fields
			$aoFields = $this->getContentFieldsContainer()->getContentFields();
			foreach ($aoFields as $sFieldName => $oContentField)
			{
				try
				{
					$oNodeField = $oDoc->createElement($sFieldName);
					if ($oContentField instanceof AnwStructuredContentField_atomic)
					{
						$asFieldValues = $this->getContentFieldValues($sFieldName);
						
						//export field values
						//TODO : use getContentFieldValuesAsXml ?
						
						foreach ($asFieldValues as $sFieldValue) //!!Use foreach instead of while which skips empty strings
						{
							$oValueNode = AnwXml::xmlCreateElementWithChilds($oNodeRoot->ownerDocument, self::XMLNODE_VALUE, $sFieldValue);
							$oNodeField->appendChild($oValueNode);
						}
					}
					else
					{
						//composed field
						//$aoSubContents = $this->getSubContents($sFieldName, $bWithDefaultValues, $bWithMissingValues);
						$aoSubContents = $this->getSubContents($sFieldName);
						foreach ($aoSubContents as $oSubContent)
						{
							$sSubContentValue =  $oSubContent->toXmlString();
							$oValueNode = AnwXml::xmlCreateElementWithChilds($oNodeRoot->ownerDocument, self::XMLNODE_VALUE, $sSubContentValue);
							$oNodeField->appendChild($oValueNode);
							
							//$oSubXmlNew = $oDoc->importNode($oSubXml->documentElement, true);
						}
						//print htmlentities(AnwUtils::xmlDumpNode($oNodeField));
					}
					
					$oNodeRoot->appendChild($oNodeField);
				}
				catch(AnwContentFieldValueNotFoundException $e)
				{
					//we skip this node as it has no value and we requested $bThrowExceptionWhenMissing
				}
			}
		
			$this->oCachedXml = $oDoc;
		}
		
		return clone $this->oCachedXml;
	}
	
	function toXmlString() //TODO caching disabled temporarily
	{
		if (true || $this->sCachedXmlString==null)
		{
			$oDoc = $this->toXml();
			$this->sCachedXmlString = AnwUtils::xmlDumpNodeChilds($oDoc->documentElement);
		}
		return $this->sCachedXmlString;
	}
	
	protected function loadContentFromXml($sXmlValue)
	{//print "<br/>".$this->getContentFieldsContainer()->getName()."::".htmlentities($sXmlValue)."<br/>";
		$oNodeRoot = AnwUtils::loadXML( '<doc>'.$sXmlValue.'</doc>' );
		
		$aoContentFields = $this->getContentFieldsContainer()->getContentFields();		
		foreach ($aoContentFields as $sFieldName => $oContentField)
		{
			//search <fieldname> tag in XML
			$aoFieldNameElements = AnwXml::xmlGetChildsByTagName($sFieldName, $oNodeRoot);
			//print "FIELDNAME:".$sFieldName."<br/>";
			//print htmlentities(AnwUtils::xmlDumpNode($oNodeRoot));
			//print_r($aoFieldNameElements);
			if (count($aoFieldNameElements)>0)
			{
				$asValues = array();
				//we shouldn't get more than one <fieldname> tag
				if (count($aoFieldNameElements)>1)
				{
					throw new AnwUnexpectedException("Found more than 1 tag for ".$sFieldName);
				}
				$oFieldNameElement = array_pop($aoFieldNameElements);
				//print_r($oFieldNameElement);
				//print htmlentities(AnwUtils::xmlDumpNodeChilds($oFieldNameElement));
				//loop for each <anwvalue> tag
				$aoValueElements = AnwXml::xmlGetChildsByTagName(self::XMLNODE_VALUE, $oFieldNameElement);
				foreach ($aoValueElements as $oValueElement)
				{
					$mFieldValue = AnwUtils::xmlDumpNodeChilds($oValueElement);
					//print "VALUE:".htmlentities($mFieldValue)."<br/>";
					$asValues[] = $mFieldValue;
				}
				
				if ($oContentField instanceof AnwStructuredContentField_composed)
				{
					$this->aaoSubContents[$sFieldName] = array();
					
					// recursive call for composed contentfield
					foreach ($asValues as $sSubContentValue)
					{
						//using $this insted of self:: for php inheritance pb
						$oSubContent = $this->rebuildContentFromXml($oContentField, $sSubContentValue);
						$this->aaoSubContents[$sFieldName][] = $oSubContent;						
					}
				}
				else
				{
					// set atomic contentfield value
					$this->aasContentFieldsValues[$sFieldName] = $asValues;
				}
			}
			else
			{
				unset($this->aasContentFieldsValues[$sFieldName]);
				unset($this->aaoSubContents[$sFieldName]);
			}
		}
		$this->setXmlChanged();
	}
	
	protected function loadContentFromArray($amArray)
	{
		$aoContentFields = $this->getContentFieldsContainer()->getContentFields();		
		foreach ($aoContentFields as $sFieldName => $oContentField)
		{
			if (isset($amArray[$sFieldName]))
			{
				$asValues = $amArray[$sFieldName];
				if (!$oContentField->isMultiple())
				{
					$asValues = array($asValues);
				}
				
				if ($oContentField instanceof AnwStructuredContentField_composed)
				{
					$this->aaoSubContents[$sFieldName] = array();
					
					// recursive call for composed contentfield
					foreach ($asValues as $sSubContentValue)
					{
						//using $this insted of self:: for php inheritance pb
						$oSubContent = $this->rebuildContentFromArray($oContentField, $sSubContentValue);
						$this->aaoSubContents[$sFieldName][] = $oSubContent;						
					}
				}
				else
				{
					// set atomic contentfield value
					$this->aasContentFieldsValues[$sFieldName] = $asValues;
				}
			}
			else
			{
				unset($this->aasContentFieldsValues[$sFieldName]);
				unset($this->aaoSubContents[$sFieldName]);
			}
		}
		$this->setXmlChanged();
	}
	
	function getContentFieldsContainer()
	{
		return $this->oContentFieldsContainer;
	}

	function pubcall($sArg)
	{
		return $this->getContentFieldsContainer()->pubcall($sArg, $this);
	}
	
	private function setXmlChanged()
	{
		$this->oCachedXml = null;
		$this->sCachedXmlString = null;
	}
	
	
	function getContentFieldsTabs()
	{
		$aoFieldsTabs = array();
		
		$aoFields = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoFields as $oField)
		{
			if ($oField instanceof AnwStructuredContentField_tab)
			{
				if ($oField->isDisplayed())
				{
					$aoFieldsTabs[] = $oField;
				}
			}
		}
		return $aoFieldsTabs;
	}
	
	
	/*
	 * Content edition throught HTML form.
	 */
	
	function renderEditHtmlForm($bFromPost, $sFormUrl="", $sSuffix="", $oContentOriginal=null)
	{
		$sHtmlEditForm = "";
		
		//special process for the first call
		if ($oContentOriginal == null)
		{
			$oContentOriginal = $this;
			$oContentOriginal->bHtmlEditFormHasErroneousFields = false;
			$oContentOriginal->bHtmlEditFormHasPhpCode = false;
			$oContentOriginal->bHtmlEditFormHasJsCode = false;
			$oContentOriginal->nHtmlEditFormCountErroneousChild = 0;
			
			$sFormUrl = str_replace('&amp;', '&', $sFormUrl);
			AnwAction::headJs('var g_editcontentform_url="'.AnwUtils::escapeQuote($sFormUrl).'";');
			AnwAction::headEditContent();
			
			//render tabs if any
			$aoFieldsTabs = $this->getContentFieldsTabs();
			if (count($aoFieldsTabs)>0)
			{
				$sHtmlTabs = "";
				foreach ($aoFieldsTabs as $oFieldTab)
				{
					$sHtmlTabs .= $oFieldTab->renderEditTab($sSuffix);
				}
				$sHtmlEditForm .= <<<EOF

<div class="contentfield_tabs">
	$sHtmlTabs
	<div class="break;"></div>
</div>
EOF;
			}
		}
		
		$aoContentFields = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoContentFields as $oContentField)
		{
			$nPreviousCountErroneousChild = $oContentOriginal->nHtmlEditFormCountErroneousChild;
			
			$sFieldName = $oContentField->getName();
			
			$asValuesForRender = array();
			$asFieldValues = array();
			$aoSubContents = array();
			$bHasOverridingValues = null;
			
			//do not load from post when contentfield is hidden
			if ($bFromPost && !$oContentField->isDisplayed())
			{
				$bReallyFromPost = false;
			}
			else
			{
				$bReallyFromPost = $bFromPost;
			}
			
			/*****************************************************************************
			 * CONTENTFIELD CONTAINER : recursively get value from it's subcontentfields
			 *****************************************************************************/
			
			if ($oContentField instanceof AnwStructuredContentField_composed)
			{
				if (!$bReallyFromPost)
				{
					// shared code for multiplicity single or multiple
					try
					{
						$aoSubContents = $this->getSubContents($sFieldName);
					}
					catch(AnwUnexpectedException $e)
					{
						//contentfield_container don't exist already. create it.
						$aoSubContents = array($this->newContent($oContentField));
					}
				}
				else
				{
					$aoSubContents = array();
					if ($oContentField->isMultiple())
					{
						//create as many empty subcontents as instances edited from post
						$asFieldsIdsFromPost = AnwEnv::_POST($oContentField->getInputName($sSuffix), array());
						
						foreach ($asFieldsIdsFromPost as $sFieldIdFromPost)
						{
							$aoSubContents[$sFieldIdFromPost] = $this->newContent($oContentField);
						}
					}
					else
					{
						//only 1 empty instance
						$aoSubContents[] = $this->newContent($oContentField);
					}
				}
						
				foreach ($aoSubContents as $sIndice => $oSubContent)
				{
					//update suffix
					if (!$bReallyFromPost)
					{
						$sSuffixId = AnwUtils::genUniqueIdNumeric();
					}
					else
					{
						$sSuffixId = $sIndice;
					}
					$sNewSuffix = $oContentField->updateSuffix($sSuffix, $sSuffixId);
					
					$sSubRender = $oSubContent->renderEditHtmlForm($bReallyFromPost, $sFormUrl, $sNewSuffix, $oContentOriginal); //recursive call
					
					//$asValuesForRender[$sSuffixId] = $sSubRender;
					$asValuesForRender[$sSuffixId] = array(self::IDX_SUBCONTENT=>$oSubContent, self::IDX_RENDERED=>$sSubRender);
					
					if ($oContentField->isCollapsed() || $oContentField->isCollapsedChild())
					{
						$bWasCollapsingEnabled = AnwStructuredContentField::getDoCollapsing();
						AnwStructuredContentField::setDoCollapsing(false);
						
						$sSubRenderUncollapsed = $oSubContent->renderEditHtmlForm($bReallyFromPost, $sFormUrl, $sNewSuffix, $oContentOriginal); //recursive call
						$asValuesForRender[$sSuffixId]['UNCOLLAPSED'] = $sSubRenderUncollapsed;
						
						AnwStructuredContentField::setDoCollapsing($bWasCollapsingEnabled);
					}
					
					//only after calling render on subContent!
					//$asFieldValues[] = $oSubContent->toXmlString();
				}
				
				$bHasOverridingValues = self::hasOverridingValues($oContentField, $aoSubContents);
				if ($oContentField->isMultiple())
				{
					if (!$oContentField->hasDefaultSubContents())
					{
						$bHasOverridingValues = false; //special case
					}
				}
				else
				{
					if (!$oContentField->hasSetDefaultValues())
					{
						$bHasOverridingValues = false; //special case
					}
				}
			}
			
				
			/*****************************************************************************
			 * CONTENTFIELD WITH DATA : get its value
			 *****************************************************************************/
			 
			else
			{
				if (!$bReallyFromPost)
				{
					$asFieldValues = $this->getContentFieldValues($sFieldName);
				}
				else
				{
					$asFieldValues = $oContentField->getValuesFromPost($sSuffix);
				}
				$asValuesForRender = $asFieldValues;
				
				$bHasOverridingValues = self::hasOverridingValues($oContentField, $asFieldValues);
				if (!$oContentField->hasSetDefaultValues())
				{
					$bHasOverridingValues = false; //special case
				}
			}
			
			$sFieldError = false;
			
			// - run the test for any _atomic field.
			// - only run the test for _composed fields which dont't have erroneous childs (important for security reasons, to not trigger test() procedure of these composed fields on unsafe values)
			if ($oContentOriginal->nHtmlEditFormCountErroneousChild==$nPreviousCountErroneousChild)
			{
				//test values and multiplicity (even for composed fields)
				try{
					if ($oContentField instanceof AnwStructuredContentField_atomic)
					{
						$oContentField->testContentFieldValues($asFieldValues, $this);
					}
					else
					{
						$oContentField->testContentFieldValues($aoSubContents, $this);
					}
				}
				//catch(AnwInvalidContentException $e){
				catch(AnwException $e){ //here we can get errors from php edition
					//display error
					$sInputName = $oContentField->getInputName($sSuffix);
					$sFieldError = str_replace('#CONTENTFIELDINPUTID#', $sInputName, $e->getMessage());
					$oContentOriginal->bHtmlEditFormHasErroneousFields = true;
					$oContentOriginal->nHtmlEditFormCountErroneousChild++;
					
					//quick hack to solve the following problem:
					//if user enters invalid XML code, in a contentfield such as _xml
					//the system would fail on setContentFieldValues() or later, 
					//when trying to load invalid XML from the content.
					//
					//so we replace erroneous values by a valid-xml string.
					//these erroneous values should never be read later :
					// - test for this contentfield was done before
					// - render is done on $asValuesForRender, so that user views erroneous in edit inputs
					// - if this contentfield is child of a container, the container won't do the test as it contains already erroneous values
					if ($e instanceof AnwInvalidContentFieldValueException 
						&& $oContentField instanceof AnwStructuredContentField_atomic)
					{
						$sErroneousValue = '#ERRONEOUSVALUE#'; 
						foreach ($asFieldValues as $nValueIndice => $null)
						{
							$asFieldValues[$nValueIndice] = $sErroneousValue;
						}
					}
				}/*
				catch(AnwException $e){
					//should never go here
					print 'ERROR 62';
					print_r($e); exit;
				}*/
			}
			/*else
			{
				$sFieldError = "(test skipped)";
			}*/
			
			//render it now
			if ($oContentField->isDisplayed())
			{
				$sHtmlEditForm .= $oContentField->renderEditInputs($asValuesForRender, $sSuffix, $bHasOverridingValues, $sFieldError);
			}
			
			if ($bReallyFromPost)
			{
				//update content for preview/save
				if ($oContentField instanceof AnwStructuredContentField_atomic)
				{
					$this->setContentFieldValues($sFieldName, $asFieldValues);
				}
				else
				{
					$this->setSubContents($sFieldName, $aoSubContents);
				}
			}
			
			if ($oContentField instanceof AnwStructuredContentField_atomic)
			{
				//check acls - are these checks still needed, as test was integrated in class_contentfield?
				foreach ($asFieldValues as $mContentFieldValue)
				{
					//check PHP permission
					$bHasPhpCode = AnwUtils::contentHasPhpCode($mContentFieldValue);
					if ($bHasPhpCode)
					{
						$oContentOriginal->bHtmlEditFormHasPhpCode = true;
						AnwCurrentSession::getUser()->checkPhpEditionAllowed();
					}
					
					//check JS permission
					$bHasJsCode = AnwUtils::contentHasJsCode($mContentFieldValue);
					if ($bHasJsCode)
					{
						$oContentOriginal->bHtmlEditFormHasJsCode = true;
						AnwCurrentSession::getUser()->checkJsEditionAllowed();
					}
				}
			}
		}		
		return $sHtmlEditForm;
	}
	
	function htmlEditFormHasErroneousFields()
	{
		return $this->bHtmlEditFormHasErroneousFields;
	}
	
	function htmlEditFormHasPhpCode()
	{
		return $this->bHtmlEditFormHasPhpCode;
	}
	
	function htmlEditFormHasJsCode()
	{
		return $this->bHtmlEditFormHasJsCode;
	}
	
	/**
	 * Check each contentfields values for errors.
	 */
	function checkContentValidity()
	{
		$aoContentFields = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoContentFields as $oContentField)
		{
			$sContentFieldName = $oContentField->getName();
			
			if ($oContentField instanceof AnwStructuredContentField_composed)
			{
				$aoSubContents = $this->getSubContents($sContentFieldName);
				$oContentField->testContentFieldValues($aoSubContents, $this);
				
				//no need to call recursively, it's already done by testContentFieldValues()
			}
			else
			{
				$amValues = $this->getContentFieldValues($sContentFieldName);
				$oContentField->testContentFieldValues($amValues, $this);
			}
		}
	}
	
	/**
	 * Returns true when $asValues are different than contentfield's default ones.
	 */
	protected static function hasOverridingValues($oContentField, $asValues)
	{
		if ($oContentField instanceof AnwStructuredContentField_atomic)
		{
			return self::contentFieldHasOverridingAtomicValues($oContentField, $asValues);
		}
		else
		{
			return self::contentFieldHasOverridingComposedValues($oContentField, $asValues);
		}
	}
	
	private static function contentFieldHasOverridingAtomicValues($oContentFieldAtomic, $asValues, $asDefaultValues=null)
	{
		if (!$oContentFieldAtomic instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("contentFieldHasOverridingAtomicValues called on composed contentfield");
		}
		
		if ($asValues===null)
		{
			return false;
		}
		
		$sContentFieldName = $oContentFieldAtomic->getName();
		
		//retrieve default values
		if ($asDefaultValues===null)
		{
			$asDefaultValues = $oContentFieldAtomic->getDefaultValues();
		}
		$bCheckIndices = ($oContentFieldAtomic->isMultiple() && $oContentFieldAtomic->getMultiplicity()->isSortable());			
		
		//compare values with default ones
		$bValuesAreDifferent = self::areSimilarValues($asDefaultValues, $asValues, $bCheckIndices);
		return $bValuesAreDifferent;
	}
	
	private static function contentFieldHasOverridingComposedValues($oContentFieldComposed, $aoContents, $aoDefaultContents=null)
	{
		if (!$oContentFieldComposed instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("contentFieldHasOverridingComposedValues called on atomic contentfield");
		}
		/*
		if (!$oContentFieldComposed->isMultiple())
		{
			throw new AnwUnexpectedException("contentFieldHasOverridingComposedValues called on monovalued");
		}*/
		
		$sContentFieldName = $oContentFieldComposed->getName();
		
		if ($aoContents===null)
		{
			return false;
		}
		
		if ($oContentFieldComposed->isMultiple())
		{
			// MULTIPLE CONTENTFIELD
			
			if (!$oContentFieldComposed->hasDefaultSubContents())
			{
				return true;
			}		
			
			//retrieve default subcontents
			if ($aoDefaultContents===null)
			{
				$aoDefaultContents = $oContentFieldComposed->getDefaultSubContents();
			}
			
			//compare occurences count
			if (count($aoContents)!=count($aoDefaultContents))
			{
				return true;
			}
			
			//compare each content values with default ones
			foreach ($aoContents as $oContent)
			{
				$oDefaultContent = array_shift($aoDefaultContents); //TODO ignore compare order when not sortable?
				$aoSubContentFields = $oContentFieldComposed->getContentFields();
				foreach ($aoSubContentFields as $oSubContentField)
				{
					$sSubContentFieldName = $oSubContentField->getName();
					$bValuesAreDifferent = null;
					if ($oSubContentField instanceof AnwStructuredContentField_composed)
					{
						//composed field
						$aoSubContents = $oContent->getSubContents($sSubContentFieldName);
						$aoSubContentsDefault = $oDefaultContent->getSubContents($sSubContentFieldName);
						// we have to provide default contents for recursive call, because default values won't be set on this subcontent
						$bValuesAreDifferent = self::contentFieldHasOverridingComposedValues($oSubContentField, $aoSubContents, $aoSubContentsDefault);//recursive call
					}
					else 
					{
						//atomic field
						$asSubValues = $oContent->getContentFieldValues($sSubContentFieldName);
						$asSubValuesDefault = $oDefaultContent->getContentFieldValues($sSubContentFieldName);
						// we have to provide default contents for recursive call, because default values won't be set on this subfield
						$bValuesAreDifferent = self::contentFieldHasOverridingAtomicValues($oSubContentField, $asSubValues, $asSubValuesDefault);
					}
					if ($bValuesAreDifferent)
					{
						return true;
					}
				}
			}
			return false;
		}
		else
		{
			// ATOMIC CONTENTFIELD
			$oContent = reset($aoContents);
			$aoSubContentFields = $oContentFieldComposed->getContentFields();
			
			foreach ($aoSubContentFields as $oSubContentField)
			{
				$sSubContentFieldName = $oSubContentField->getName();
				if ($oSubContentField instanceof AnwStructuredContentField_composed)
				{
					//composed field
					$aoSubContents = $oContent->getSubContents($sSubContentFieldName);
					// we don't have to provide default content for recursive call, because default values are already set on this subcontent
					$bValuesAreDifferent = self::contentFieldHasOverridingComposedValues($oSubContentField, $aoSubContents);//recursive call
				}
				else 
				{
					//atomic field
					$asSubValues = $oContent->getContentFieldValues($sSubContentFieldName);
					// we don't have to provide default contents for recursive call, because default values are already set on this subfield
					$bValuesAreDifferent = self::contentFieldHasOverridingAtomicValues($oSubContentField, $asSubValues);
				}
				if ($bValuesAreDifferent)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	protected static function areSimilarValues($asDefaultValues, $asSettingValues, $bCheckIndices)
	{
		if ($bCheckIndices)
		{
			//check indices
			foreach ($asDefaultValues as $i => $sDefaultValue)
			{
				if (!isset($asSettingValues[$i]) || $asSettingValues[$i]!=$sDefaultValue)
				{
					self::debug("areSimilarValues: value not found or different in override values: ".$i);
					return true;
				}
			}
			foreach ($asSettingValues as $i => $sSettingValue)
			{
				if (!isset($asDefaultValues[$i]) || $asDefaultValues[$i]!=$sSettingValue)
				{
					self::debug("areSimilarValues: value not found or different in default values: ".$i);
					return true;
				}
			}
		}
		else
		{
			//only check values
			foreach ($asDefaultValues as $i => $sDefaultValue)
			{
				if (!in_array($sDefaultValue, $asSettingValues))
				{
					self::debug("areSimilarValues: indice not found in override values: ".$i);
					return true;
				}
			}
			foreach ($asSettingValues as $i => $sSettingValue)
			{
				if (!in_array($sSettingValue, $asDefaultValues))
				{
					self::debug("areSimilarValues: indice not found in default values: ".$i);
					return true;
				}
			}
		}
		return false;
	}
	
	function renderAdditionalEditInput($sFieldName, $sSuffix)
	{
		// search subcontent by fieldname
		// TODO: search by suffix
		$oContentParent = $this->searchSubContent($sFieldName);
		$oContentField = $oContentParent->getContentFieldsContainer()->getContentField($sFieldName);
		$sHtml = $oContentField->renderAdditionalEditInput($sSuffix, $oContentParent);
		return $sHtml;
	}
	
	/**
	 * Search the (sub)content containing the searched contentField.
	 */
	//used by action_edit
	/*protected*/ function searchSubContent($sContentFieldName)
	{
		try 
		{
			$this->getContentFieldsContainer()->getContentField($sContentFieldName); //check if contentfield exists
			return $this; //return parent content for searched contentField
		}
		catch (AnwUnexpectedException $e)
		{
			foreach ($this->getContentFieldsContainer()->getContentFields() as $oContentField)
			{
				if ($oContentField instanceof AnwStructuredContentField_composed)
				{
					$oSubContent = $this->newContent($oContentField);
					try
					{
						return $oSubContent->searchSubContent($sContentFieldName);
					}
					catch(AnwUnexpectedException $e){}
				}
			}
			throw new AnwUnexpectedException("ContentField not found : ".$sContentFieldName);
		}
	}
	
	//------------------------------------------------------------------
	
	//used by AnwStructuredContentFieldsContainer
	static function getContentFieldValuesFromArrayItems($oContentField, $cfg)
	{
		if (!$oContentField instanceof AnwStructuredContentField_atomic)
		{
			throw new AnwUnexpectedException("getContentFieldValuesFromArrayItems called on composed field");
		}
		$asValues = array();
		
		$sFieldName = $oContentField->getName();
		if ($oContentField->isMultiple())
		{
			foreach ($cfg as $mArrayItem)
			{
				$sValue = $oContentField->getValueFromArrayItem($mArrayItem);
				$sValue = AnwUtils::standardizeCRLF($sValue); //required for comparing override values against default values
				$asValues[] = $sValue;
			}
		}
		else
		{
			$sValue = $oContentField->getValueFromArrayItem($cfg);
			$sValue = AnwUtils::standardizeCRLF($sValue); //required for comparing override values against default values
			$asValues[] = $sValue;
		}
		return $asValues;
	}
	
	//used by AnwStructuredContentFieldsContainer
	static function getSubContentsFromArrayItems($oContentField, $cfg)
	{
		if (!$oContentField instanceof AnwStructuredContentField_composed)
		{
			throw new AnwUnexpectedException("getSubContentsFromArrayItems called on atomic field");
		}
		if (!$oContentField->isMultiple())
		{
			throw new AnwUnexpectedException("getSubContentsFromArrayItems called on non composed field");
		}
		$aoSubContents = array();
		
		// browse subcontent occurences
		foreach ($cfg as $mCfg)
		{
			// prepare new subcontent
			$oSubContent = $oContentField->newContent();
			$oSubContent->setContentFieldsValuesFromArray($mCfg);
			//print htmlentities($oSubContent->toXmlString());
			$aoSubContents[] = $oSubContent;
		}
		return $aoSubContents;
	}
	
	protected function setContentFieldsValuesFromArray($cfg)
	{
		$aoConfigurableSettings = $this->getContentFieldsContainer()->getContentFields();
		
		foreach ($aoConfigurableSettings as $oSetting)
		{
			$sSettingName = $oSetting->getName();
			
			if (isset($cfg[$sSettingName]))
			{
				if ($oSetting instanceof AnwStructuredContentField_atomic)
				{
					/*
					 * $oSetting is an ATOMIC setting
					 */
					
					if (!$this->hasSetContentFieldValues($sSettingName))
					{
						$asValues = array();
						
						//get values from config
						$asValues = self::getContentFieldValuesFromArrayItems($oSetting, $cfg[$sSettingName]);
						
						//set values
						$this->setContentFieldValues($sSettingName, $asValues);
					}
				}
				else
				{
					/*
					 * $oSetting is a COMPOSED setting
					 */
					
					//get values from config
					if ($oSetting->isMultiple())
					{
						$aaSubCfg = $cfg[$sSettingName];
					}
					else
					{
						$aaSubCfg = array($cfg[$sSettingName]);
					}
					
					if (!$this->hasSetSubContents($sSettingName))
					{
						//create empty subcontents
						$aoSubContents = array();
						foreach ($aaSubCfg as $amSubCfg)
						{
							$oSubContent = $this->newContent($oSetting);
							$oSubContent->setContentFieldsValuesFromArray($amSubCfg); //recursive
							$aoSubContents[] = $oSubContent;
						}
						
						//set subcontents
						$this->setSubContents($sSettingName, $aoSubContents);
					}
					else
					{
						//here, we are doing the second pass to complete missing values with default ones
						if (!$oSetting->isMultiple())
						{
							//complete existing subcontents for monovalued fields only!
							$oSubContent = reset($this->getSubContents($sSettingName));
							$oSubContent->setContentFieldsValuesFromArray();
							$this->setSubContents($sSettingName, array($oSubContent));
						}
						else
						{
							//an overriden value already for this multiple contentfield. let it as it is!
						}
					}
				}
			}
		}
	}
	
	protected static function debug($sMsg)
	{
		AnwDebug::log("(AnwContent) ".$sMsg);
	}
}

class AnwContentPage extends AnwStructuredContent
{
	private $tmp_nTranslatedValuesTranslated;
	private $tmp_nTranslatedValuesTotal;
	
	function __construct($oContentFieldsContainer)
	{
		parent::__construct($oContentFieldsContainer);
	}
	
	static function newContent($oContentFieldsContainer)
	{
		return new AnwContentPage($oContentFieldsContainer);
	}
	
	static function rebuildContentFromXml($oContentFieldsContainer, $sXmlValue)
	{
		$oContent = new AnwContentPage($oContentFieldsContainer);
		$oContent->loadContentFromXml($sXmlValue);
		return $oContent;
	}
	
	static function rebuildContentFromArray($oContentFieldsContainer, $amArray)
	{
		$oContent = new AnwContentPage($oContentFieldsContainer);
		$oContent->loadContentFromArray($amArray);
		return $oContent;
	}
	
	private function prepareForOutput($oPage)
	{
		AnwPlugins::hook("content_prepare_for_output", $this, $oPage);
	}
	
	/**
	 * Used for content execution preview (by action edit).
	 */
	function toHtml($oPage)
	{
		$this->prepareForOutput($oPage);
		AnwPlugins::hook('contentpage_tohtml_before', $this, $oPage);
		$oOutputHtml = $this->getContentFieldsContainer()->toHtml($this, $oPage);
		AnwPlugins::hook('contentpage_tohtml_after', $this, $oPage);
		return $oOutputHtml;
	}
	
	function toFeedItem($oPage)
	{
		$this->prepareForOutput($oPage);
		return $this->getContentFieldsContainer()->toFeedItem($this, $oPage);
	}
	
	function setUntranslated()
	{
		$aoContentFields = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoContentFields as $sFieldName => $oContentField)
		{
			if ($oContentField->isTranslatable())
			{
				$asValues = array();
				if ($oContentField instanceof AnwStructuredContentField_composed)
				{
					$aoSubContents = $this->getSubContents($sFieldName);
					foreach ($aoSubContents as $i => $oSubContent)
					{
						$oSubContent->setUntranslated();
						//$asValues[] = AnwUtils::xmlDumpNodeChilds($oSubContent->toXml()->documentElement);
					}
				}
				else
				{
					$asValues = $this->getContentFieldValues($sFieldName);
					foreach ($asValues as $i => $sValue)
					{
						$oValueXml = AnwUtils::loadXML('<doc>'.$sValue.'</doc>');
						$oValueXml = AnwXml::xmlAddTranslateTags($oValueXml); //contentfield already checked
						$asValues[$i] = AnwUtils::xmlDumpNodeChilds($oValueXml);
					}
					$this->setContentFieldValues($sFieldName, $asValues);
				}				
			}
		}
	}
	
	function isTranslated()
	{
		foreach ($this->aasContentFieldsValues as $sFieldName => $asContentFieldsValues)
		{
			$oContentField = $this->getContentFieldsContainer()->getContentField($sFieldName);
			if ($oContentField->isTranslatable())
			{
				$sTmp = implode(' ', $asContentFieldsValues);
				$bTranslated = (! preg_match(AnwUtils::getRegexpUntr(), $sTmp));
				if (!$bTranslated) return false;
			}
		}
		foreach ($this->aaoSubContents as $sFieldName => $aoSubContents)
		{
			foreach ($aoSubContents as $oSubContent)
			{
				if (!$oSubContent->isTranslated()) return false;
			}
		}
		return true;		
	}
	
	function getTranslatedPercent()
	{
		$this->tmp_nTranslatedValuesTranslated = 0;
		$this->tmp_nTranslatedValuesTotal = 0;
		
		$fOnValue = "getTranslatedPercent_onContentFieldValue";
		AnwUtils::runCallbacksOnTranslatableField($this, $this, $fOnValue);
		
		$nPercent = $this->tmp_nTranslatedValuesTotal>0 ? ceil(($this->tmp_nTranslatedValuesTranslated/$this->tmp_nTranslatedValuesTotal)*100) : 100;
		
		//AnwDebug::logdetail("getTranslatedPercent result : translated=".$this->tmp_nTranslatedValuesTranslated.", total=".$this->tmp_nTranslatedValuesTotal.", percent=".$nPercent."%");
		return $nPercent;
	}
	
	function getTranslatedPercent_onContentFieldValue($oContentField, $oXmlValue, $sInputName)
	{
		$fOnTextValue = "getTranslatedPercent_onTextValue";
		AnwUtils::runCallbacksOnTranslatableValue($this, $oXmlValue, $sInputName, $fOnTextValue);
	}
	
	function getTranslatedPercent_onTextValue($oRootNode, $sInputName)
	{
		$this->tmp_nTranslatedValuesTotal++;
		if (!AnwXml::xmlIsUntranslated($oRootNode))
		{
			$this->tmp_nTranslatedValuesTranslated++;
		}
	}
	
	private function doGetContentFieldIndexedValues($sIndexName)
	{
		$asAllContentFieldValues = false;
		foreach ($this->getContentFieldsContainer()->getContentFields() as $oContentField)
		{
			$sContentFieldName = $oContentField->getName();
			if ($oContentField instanceof AnwStructuredContentField_atomic)
			{
				// we are looking for the contentfield indexed as '$sIndexName'
				if ($oContentField->isIndexed() && $oContentField->getIndexName() == $sIndexName)
				{
					// we found the contentfield for this index!
					$asIndexedValues = array();
					$asContentFieldValues = $this->getContentFieldValues($sContentFieldName, true);
					foreach ($asContentFieldValues as $sIndexedValue)
					{
						$sIndexedValue = $oContentField->getIndexedValue($sIndexedValue);
						if (!empty($sIndexedValue))
						{
							$sIndexedValue = substr($sIndexedValue, 0, AnwUtils::MAXLEN_INDEXVALUE);
							$asIndexedValues[] = $sIndexedValue;
						}
					}
					return $asIndexedValues;
				}
			}
			else
			{
				// composed content field are not indexable, but may contain indexed atomic subcontentfields
				$aoSubContents = $this->getSubContents($sContentFieldName);
				foreach ($aoSubContents as $oSubContent)
				{
					$asContentFieldValuesIndexed = $oSubContent->doGetContentFieldIndexedValues($sIndexName);
					if ($asContentFieldValuesIndexed)
					{
						if ($asAllContentFieldValues === false)
						{
							$asAllContentFieldValues = array();
						}
						// we will return the list of ALL indexed values, in case of indexed value
						// belongs to a subcontentfield which is a child of a multivalued composed contentfield
						foreach ($asContentFieldValuesIndexed as $sContentFieldValueIndexed)
						{
							$asAllContentFieldValues[] = $sContentFieldValueIndexed;
						}
					}
				}
			}
		}
		
		if ($asAllContentFieldValues !== false)
		{
			// we may return an empty array if we have found the contentfield but have no value to index
			return $asAllContentFieldValues;
		}
		// no (sub)contentfield found for this index
		return false;
	}
	
	function getContentFieldIndexedValues($sIndexName)
	{
		$asAllContentFieldValuesIndexed = $this->doGetContentFieldIndexedValues($sIndexName);
		if ($asAllContentFieldValuesIndexed === false)
		{
			throw new AnwUnexpectedException("Indexed contentfield not found for ".$sIndexName);
		}
		return $asAllContentFieldValuesIndexed;
	}
}


class AnwContentSettings extends AnwStructuredContent
{
	function __construct($oContentFieldsContainer)
	{
		parent::__construct($oContentFieldsContainer);
	}
	
	static function newContent($oContentFieldsContainer)
	{
		return new AnwContentSettings($oContentFieldsContainer);
	}
	
	static function rebuildContentFromXml($oContentFieldsContainer, $sXmlValue)
	{
		$oContent = new AnwContentSettings($oContentFieldsContainer);
		$oContent->loadContentFromXml($sXmlValue);
		return $oContent;
	}
	
	static function rebuildContentFromArray($oContentFieldsContainer, $amArray)
	{
		$oContent = new AnwContentSettings($oContentFieldsContainer);
		$oContent->loadContentFromArray($amArray);
		return $oContent;
	}
	
	function getComponent()
	{
		return $this->getContentFieldsContainer()->getComponent();
	}
	
	/*
	 * I/O operations for settings files.
	 */
	function readSettings()
	{
		// default contentfields values have been already initialized
		try
		{
			//assign override values from override settings
			$cfgOverride = $this->getComponent()->getCfgArrayOverride();
			$this->setContentFieldsValuesFromArray($cfgOverride);
		}
		catch(AnwFileNotFoundException $e){} //no override configuration found
		
		/*print "===================================<br/>";
		print htmlentities($this->toXmlString()); //print AnwDebug::getLog();
		exit;*/
	}
	
	/**
	 * Only for unittest usage.
	 */
	function ___unittest_doReadSettings($cfg)
	{
		AnwUtils::checkFriendAccess("AnwSettingsTestCase");
		return $this->setContentFieldsValuesFromArray($cfg);
	}
	
	function writeSettingsOverride()
	{
		//just to be sure, we check again content validity just before writing it
		$this->checkContentValidity();
		
		$sConfigDefaultFile = "none";
		try
		{
			$sConfigDefaultFile = $this->getComponent()->getConfigurableFileDefault();
		}
		catch(AnwFileNotFoundException $e){} //no default config
		
		$cfg = $this->toOverrideCfgArray();
		$sPhpCode = '<?php '."\n";
		$sPhpCode .= ' /**'."\n";
		$sPhpCode .= '  * Anwiki override file.'."\n";
		$sPhpCode .= '  * This file can be edited directly from file system, or from Anwiki web interface.'."\n";
		$sPhpCode .= '  * '."\n";
		$sPhpCode .= '  * Overridden file: '.$sConfigDefaultFile."\n";
		$sPhpCode .= '  * Generated on: '.Anwi18n::datetime(time())."\n";
		$sPhpCode .= '  * By: '.AnwCurrentSession::getUser()->getLogin()."\n";
		$sPhpCode .= '  * Using version: '.ANWIKI_VERSION_NAME.' ('.ANWIKI_VERSION_ID.')'."\n";
		$sPhpCode .= '  */'."\n";
		$sPhpCode .= "\n";
		$sPhpCode .= '$cfg = '.AnwUtils::arrayToPhp($cfg)."\n";
		$sPhpCode .= '?>';
		
		$sFileOverride = $this->getComponent()->getConfigurableFileOverride();
		AnwUtils::file_put_contents($sFileOverride, $sPhpCode, LOCK_EX);
		
		// clear component's cache for configurableContent
		$this->getComponent()->___notifyConfigurableContentChanged();
	}
	
	function ___unittest_toOverrideCfgArray()
	{
		AnwUtils::checkFriendAccess("AnwSettingsTestCase");
		return $this->toOverrideCfgArray();
	}
	
	private function toOverrideCfgArray($bFirstCall=true)
	{
		$cfg = null; //important
		
		$aoConfigurableSettings = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoConfigurableSettings as $oSetting)
		{
			$sSettingName = $oSetting->getName();
			
			if ($oSetting instanceof AnwStructuredContentField_atomic)
			{
				$asSettingValues = $this->getContentFieldValues($sSettingName);
				
				// do we need to override?
				if ($this->hasOverridingValues($oSetting, $asSettingValues))
				{
					$cfg[$sSettingName] = array();				
					foreach ($asSettingValues as $sSettingValue)
					{
						//overriden values
						$cfg[$sSettingName][] = $oSetting->getArrayItemFromValue($sSettingValue);
					}
				}
			}
			else
			{
				//recursive call
				/*if (!$this->hasSetSubContents($sSettingName) && $oSetting->isMultiple())
				{
					// nothing to do, otherwise we will get an empty array and unittest testComponent1Example1 will fail
				}
				else
				{*/
				
				//composed				
				if (!$oSetting->isMultiple())
				{
					// monovalued: some fields may be overriden, some others not
					$oSubContent = $this->getSubContent($sSettingName);
					$oSubSetting = $oSubContent->getContentFieldsContainer();
					
					$amSubCfg = $oSubContent->toOverrideCfgArray(false);
					if ($amSubCfg!==null) //important, array can be empty but different from default value, so we must save an empty array than a non overriden setting
					{
						$cfg[$sSettingName][] = $amSubCfg;
					}
				}
				else
				{
					// multivalued: none or all fields must be copied
					$aoSubContents = $this->getSubContents($sSettingName);
					if ($this->hasOverridingValues($oSetting, $aoSubContents))
					{
						foreach ($aoSubContents as $oSubContent)
						{
							$cfg[$sSettingName][] = $oSubContent->toFusionedCfgArray();
						}
					}
				}
				//}
			}
			
			//atomic fields don't need to be in an array, as they have only 1 value
			if (!$oSetting->isMultiple() && isset($cfg[$sSettingName]))
			{
				$cfg[$sSettingName] = array_pop($cfg[$sSettingName]);
			}
		}
		
		// never return null as top level cfg array...
		if ($bFirstCall && $cfg===null)
		{
			$cfg = array();
		}
		return $cfg;
	}
	
	function ___unittest_toFusionedCfgArray()
	{
		AnwUtils::checkFriendAccess("AnwSettingsTestCase");
		return $this->toFusionedCfgArray();
	}
	
	/**
	 * Returns array of settings values (override values when present, default ones when not overriden).
	 */
	function toFusionedCfgArray()
	{
		AnwUtils::checkFriendAccess(array("AnwComponent", "AnwContentSettings"));
		
		$cfg = array();
		$aoConfigurableSettings = $this->getContentFieldsContainer()->getContentFields();
		foreach ($aoConfigurableSettings as $oSetting)
		{
			$sSettingName = $oSetting->getName();
			
			if ($oSetting instanceof AnwStructuredContentField_atomic)
			{
				$aoSettingValues = $this->getContentFieldValues($sSettingName);
				$cfg[$sSettingName] = array();
				foreach ($aoSettingValues as $oSettingValue)
				{
					$cfg[$sSettingName][] = $oSetting->getArrayItemFromValue($oSettingValue);
				}
			}
			else
			{
				//recursive call
				$aoSubContents = $this->getSubContents($sSettingName);
				$cfg[$sSettingName] = array();
				foreach ($aoSubContents as $oSubContent)
				{
					$amSubCfg = $oSubContent->toFusionedCfgArray();
					//if ($amSubCfg!==null)
					//{
						$cfg[$sSettingName][] = $amSubCfg;
					//}
				}
			}
			
			//atomic fields don't need to be in an array, as they have only 1 value
			if (!$oSetting->isMultiple() && isset($cfg[$sSettingName]))
			{
				$cfg[$sSettingName] = array_pop($cfg[$sSettingName]);
			}
		}
		return $cfg;
	}
}

abstract class AnwStructuredContentEditionForm
{
	private $oContent;
	private $sFormUrl;
	private $sRender;
	
	const POSTED_INPUT_NAME = "editionformposted";
	
	function __construct($oContent, $sFormUrl)
	{
		$this->oContent = clone $oContent;
		$this->sFormUrl = $sFormUrl;
	}
	
	function getRender($bForceFromPost=false)
	{
		if (!$this->sRender)
		{
			$bFromPost = ($bForceFromPost || $this->isPosted());
			$this->sRender = $this->oContent->renderEditHtmlForm($bFromPost, $this->sFormUrl);
			$this->sRender .= '<input type="hidden" name="'.self::POSTED_INPUT_NAME.'" value="1"/>';
			$this->sRender = AnwPlugins::vhook('contenteditionform_render_html', $this->sRender, $this->oContent, $bFromPost);
		}
		return $this->sRender;
	}
	
	function hasErrors()
	{
		return $this->oContent->htmlEditFormHasErroneousFields();
	}
	
	function getContent()
	{
		return $this->oContent;
	}
	
	function isPosted()
	{
		return AnwEnv::_POST(self::POSTED_INPUT_NAME);
	}
}

class AnwStructuredContentEditionFormSettings extends AnwStructuredContentEditionForm
{
	private $oEditableComponent;
	
	function __construct($oEditableComponent, $oConfigurableContent, $sFormUrl)
	{
		parent::__construct($oConfigurableContent, $sFormUrl);
		
		$this->oEditableComponent = $oEditableComponent;
	}
	
	function getRender($bForceFromPost=false)
	{
		$sRender = parent::getRender($bForceFromPost);
		
		$sConfigFileOverride = $this->getEditableComponent()->getConfigurableFileOverride();
		$sRender .= '<div class="explain" style="float:right; width:65%">'.AnwComponent::g_editcontent("editsettings_info", array('filename'=>'<span style="font-size:0.8em">'.$sConfigFileOverride.'</span>')).'</div>';
		
		return $sRender;
	}
	
	function saveEdition()
	{
		try
		{
			//make sure file is writable
			$sConfigFileOverride = $this->getEditableComponent()->getConfigurableFileOverride();
			if (!file_exists($sConfigFileOverride))
			{
				try
				{
					AnwUtils::file_put_contents($sConfigFileOverride, "");
				}
				catch(AnwException $e){}
			}
			if (!is_writable($sConfigFileOverride))
			{
				$sError = Anwi18n::g_err_need_write_file($sConfigFileOverride);
				throw new AnwStructuredContentEditionFormException($sError);
			}
			
			//update content from post
			$this->getRender(true);
			
			//check errors
			if ($this->hasErrors())
			{
				throw new AnwInvalidContentException();
			}
			
			//save
			$this->getContent()->writeSettingsOverride();		
		}
		catch (AnwInvalidContentException $e)
		{
			$sError = AnwComponent::g_("err_contentinvalid");
			throw new AnwStructuredContentEditionFormException($sError);
		}
		catch(AnwUnexpectedException $e)
		{
			$sError = AnwComponent::g_("err_ex_unexpected_p");
			$nErrorNumber = AnwDebug::reportError($e);
			if ($nErrorNumber)
			{
				$sError .= '<br/>'.$this->g_("err_ex_report",array("errornumber"=>$nErrorNumber));
			}
			throw new AnwStructuredContentEditionFormException($sError);
		}
	}
	
	function getEditableComponent()
	{
		return $this->oEditableComponent;
	}
	
	
}



class AnwStructuredContentEditionFormPage extends AnwStructuredContentEditionForm
{
	function __construct($oContentPage, $sFormUrl)
	{
		parent::__construct($oContentPage, $sFormUrl);
	}
	
	function updateContentFromEdition()
	{
		try
		{
			//update content from post
			$this->getRender(true);
			
			//check errors
			if ($this->hasErrors())
			{
				throw new AnwInvalidContentException();
			}
			
			return $this->getContent();
		}
		catch (AnwInvalidContentException $e)
		{
			$sError = AnwComponent::g_("err_contentinvalid");
			throw new AnwStructuredContentEditionFormException($sError);
		}
		catch (AnwAclPhpEditionException $e)
		{
			$sError = $e->getMessage();
			throw new AnwStructuredContentEditionFormException($sError);
		}
		catch(AnwUnexpectedException $e)
		{
			$sError = AnwComponent::g_("err_ex_unexpected_p");
			$nErrorNumber = AnwDebug::reportError($e);
			if ($nErrorNumber)
			{
				$sError .= '<br/>'.$this->g_("err_ex_report",array("errornumber"=>$nErrorNumber));
			}
			throw new AnwStructuredContentEditionFormException($sError);
		}
	}
}

?>