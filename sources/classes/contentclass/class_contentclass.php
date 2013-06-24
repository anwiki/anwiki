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
 * ContentFieldsContainer definition.
 * @package Anwiki
 * @version $Id: class_contentclass.php 337 2010-10-10 21:05:55Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

//TODO : Everything should be static... not possible because of 
//PHP5 bug inherited static attributes shared accross all subclasses...

//TODO : architecture problem : this class should not inherit directly from AnwComponent, this should be ContentClass and ContentClassSettings, 
//but it's not possible without copying a lot of code between classes because multiple inherit is not supported by PHP.
abstract class AnwStructuredContentFieldsContainer extends AnwComponent
{
	private $aoContentFields = array();
	
	//public
	function getContentFields()
	{
		return $this->aoContentFields;
	}
	
	function getContentField($sContentFieldName)
	{
		$aoContentFields = $this->getContentFields();
		if (!isset($aoContentFields[$sContentFieldName]))
		{
			throw new AnwUnexpectedException("ContentField not found : ".$sContentFieldName);
		}
		return $aoContentFields[$sContentFieldName];
	}
	
	//public for plugins
	/*protected*/ function addContentField($oContentField)
	{
		$sFieldName = $oContentField->getName();
		if (isset($this->aoContentFields[$sFieldName]))
		{
			throw new AnwUnexpectedException("ContentField ".$sFieldName." is already defined");
		}
		$oContentField->setContainer($this);
		// we must initialize AFTER setting the container, 
		// as the container may be required for contentfield initialization
		$oContentField->init();
		$this->aoContentFields[$sFieldName] = $oContentField;
	}
	
	abstract function rebuildContentFromXml($sDefaultValue);
	
	/*
	function runCallbackOnContentFields($oCallbackInstance, $fCallback, $asParameters, $oContent, $sSuffix, $nInputNumber)
	{
		$oContent = $oCallbackInstance->$fCallback($asParameters, $this, $oContent, $sSuffix, $nInputNumber);
		foreach ($this->getContentFields() as $oSubContentField)
		{
			$oSubContent = $oContent->getSubContent($oSubContentField->getName());
			$sReturn .= $oSubContentField->runCallbackOnContentFields($oCallbackInstance, $fCallback, $asParameters, $oSubContent, $sSuffix, $nInputNumber);
		}
		return $sReturn;
	}*/
	
	/**
	 * You can put all default values into a php array and just call this method on the top contentFieldsContainer.
	 * This will initialize all contentFields's default values with values found in php array.
	 */
	function setContentFieldsDefaultValuesFromArray($cfg, $bOnlyForSubContents=false)
	{
		$aoContentFields = $this->getContentFields();
		
		foreach ($aoContentFields as $oContentField)
		{
			$sFieldName = $oContentField->getName();
			
			if (isset($cfg[$sFieldName]))
			{
				if ($oContentField instanceof AnwStructuredContentField_atomic)
				{
					if (!$bOnlyForSubContents)
					{
						/*
						 * ATOMIC field
						 */
						
						//get values from config
						$asValues = AnwStructuredContent::getContentFieldValuesFromArrayItems($oContentField, $cfg[$sFieldName]);
						
						//set default values for the contentfield
						$oContentField->setDefaultValues($asValues);
					}
				}
				else
				{
					/*
					 * COMPOSED field
					 */
					
					if (!$oContentField->isMultiple())
					{
						// monovalued composed field: just create a temporary subcontent to loop on it's contentfields and set default values on it.
						$oContentField->setContentFieldsDefaultValuesFromArray($cfg[$sFieldName], $bOnlyForSubContents); //recursive call
					}
					else
					{
						// composed and multivalued
						$aoSubContents = AnwStructuredContent::getSubContentsFromArrayItems($oContentField, $cfg[$sFieldName]);
						foreach ($aoSubContents as $oSubContent)
						{
							// recursive call, only for setting defaultSubContents, but NOT for setting atomic contentfield's default values!
							$mSubCfg = array_shift($cfg[$sFieldName]);
							$oContentField->setContentFieldsDefaultValuesFromArray($mSubCfg, true); //recursive call
							//print htmlentities($oSubContent->toXmlString());
						}
						$oContentField->setDefaultSubContents($aoSubContents);
						
						// more than 1 multivalued value: we can't do anything, it wouldn't make sense,
						// every subcontents are linked to the same subcontentfields, so iterating on subcontents
						// would just override subcontentfields default values set from previous subcontent.
						// To conclude, contentfields from multivalued composed fields can't have distinct default values for each default subcontent.
						
						/*
						$nCountSubContents = count($cfg[$sFieldName]);
						if ($nCountSubContents>1)
						{
							// more than 1 multivalued value: we can't do anything, it wouldn't make sense,
							// every subcontents are linked to the same subcontentfields, so iterating on subcontents
							// would just override subcontentfields default values set from previous subcontent.
							// To conclude, contentfields from multivalued composed fields can't have distinct default values for each default subcontent.
						}
						else
						{
							// ok, we can allow it if there is no more than 1 value
							if ($nCountSubContents==1)
							{
								$oContentField->setDefaultSubContentsNumber(1); //remind the number of subcontents for restituing it back in getSubContents()
								$oContentField->setContentFieldsDefaultValuesFromArray($cfg[$sFieldName][0]); //recursive call
							}
							else
							{
								//0 subcontents, nothing to do...
							}
						}*/
					}
				}
			}
		}
	}
}

?>