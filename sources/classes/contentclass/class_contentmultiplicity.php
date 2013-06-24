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
 * ContentMultiplicity definition, for using repeated ContentFields.
 * @package Anwiki
 * @version $Id: class_contentmultiplicity.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwContentMultiplicity
{
	function getMultiplicityTip($oContentField){ return ""; }
	abstract function doRenderEditInputs($oContentField, $amValues, $sSuffix);
	abstract function testContentFieldMultiplicity($oContentField, $amContentFieldValue);
}


//one value
class AnwContentMultiplicity_single extends AnwContentMultiplicity
{
	function __construct()
	{
		
	}
	
	function doRenderEditInputs($oContentField, $amValues, $sSuffix)
	{
		if ( count($amValues) < 1 )
		{
			$sValue = $oContentField->getDefaultValue(); //TODO?
		}
		else
		{
			$sValue = array_pop($amValues);
		}
		
		if ($oContentField instanceof AnwStructuredContentField_composed)
		{
			$sSuffix = $oContentField->updateSuffix($sSuffix); //update suffix
		}
		$HTML = $oContentField->getRenderedEditInput($sValue, $sSuffix, 0);
		
		return $HTML;
	}
	
	function testContentFieldMultiplicity($oContentField, $amValues)
	{
		if (count($amValues) < 1)
		{
			$sError = AnwComponent::g_editcontent("err_contentmultiplicity_single_notfound");
			throw new AnwInvalidContentFieldMultiplicityException($sError);
		}
		if (count($amValues) > 1)
		{
			$sError = AnwComponent::g_editcontent("err_contentmultiplicity_single_unexpected");
			throw new AnwInvalidContentFieldMultiplicityException($sError);
		}
	}
}


//multiple values
class AnwContentMultiplicity_multiple extends AnwContentMultiplicity
{
	private $nMin;
	private $nMax;
	private $bSortable=true;
	const UNLIMITED=999999;
	
	function __construct($nMin=0, $nMax=self::UNLIMITED)
	{
		$this->nMin = $nMin;
		$this->nMax = $nMax;
	}
	
	function setSortable($bSortable)
	{
		$this->bSortable = $bSortable;
	}
	
	function isSortable()
	{
		return $this->bSortable;
	}
	
	/**
	 * Render all instances of a multiple contentfield.
	 */
	function doRenderEditInputs($oContentField, $amValues, $sSuffix)
	{
		//special case for _renderedAsMultiple
		if ($oContentField instanceof AnwStructuredContentField_renderedAsMultiple)
		{
			//same code as multiplicity_single
			
			if ($oContentField instanceof AnwStructuredContentField_composed)
			{
				$sSuffix = $oContentField->updateSuffix($sSuffix); //update suffix
			}
			$HTML = $oContentField->getRenderedEditInput($amValues, $sSuffix, 0);
			
			return $HTML;
		}
		
		$sDivId = "instance_".$oContentField->getInputName($sSuffix);
		
		$sContentFieldName = $oContentField->getName();
		$sInstancesClass = "instance_".$oContentField->getInputName($sSuffix);
		$sIsSortable = ($this->isSortable()?'true':'false');
		$JS = <<<EOF

new AnwContentFieldMultiple('$sInstancesClass', $this->nMin, $this->nMax, $('$sDivId'), '$sSuffix', '$sContentFieldName', $sIsSortable);
EOF;

		$sHtmlRenderedInstances = "";
		foreach($amValues as $i => $sValue)
		{
			$sHtmlRenderedInstances .= $oContentField->renderEditInputN($sSuffix, $sValue, $i);
		}
		
		$HTML = "";		
		if (!$oContentField->isCollapsedChild())
		{
			$sFieldName = AnwUtils::escapeApostrophe($oContentField->getName());
			$sTranslationAddButton = AnwComponent::g_editcontent("contentmultiplicity_multiple_contentfield_add", array('fieldname'=>$oContentField->getFieldLabelSingle()));
			
			$HTML .= <<<EOF

<div class="contentfield_multiple">
	<div id="$sDivId" class="contentfield_multiple_instances">
		{$sHtmlRenderedInstances}
	</div>
	<a class="contentmultiplicity_add" href="#" onclick="AnwContentFieldMultiple.get('$sInstancesClass').addInstance(); return false;">$sTranslationAddButton</a>
</div> <!-- end contentfield_multiple -->
<script type="text/javascript">$JS</script>
EOF;
		}
		else
		{
			$HTML = $sHtmlRenderedInstances;
		}
		return $HTML;
	}
	
	function testContentFieldMultiplicity($oContentField, $amValues)
	{
		$nCount = count($amValues);
		$sTranslation = $oContentField->getFieldLabelPlural();
		if ($nCount < $this->nMin)
		{
			$sError = AnwComponent::g_editcontent("err_contentmultiplicity_multiple_min",array("minval"=>$this->nMin, "elementname"=>$sTranslation));
			throw new AnwInvalidContentFieldMultiplicityException($sError);
		}
		if ($nCount > $this->nMax)
		{
			$sError = AnwComponent::g_editcontent("err_contentmultiplicity_multiple_max",array("maxval"=>$this->nMax, "elementname"=>$sTranslation));
			throw new AnwInvalidContentFieldMultiplicityException($sError);
		}
	}
	
	function getMultiplicityTip($oContentField)
	{
		$sTranslation = $oContentField->getFieldLabelPlural();
		if ($this->nMin > 0 && $this->nMax < self::UNLIMITED)
		{
			if ($this->nMin == $this->nMax)
			{
				return AnwComponent::g_editcontent("contentmultiplicity_multiple_tip_fixed", array("count"=>$this->nMin, "contentfield"=>$sTranslation));
			}
			return AnwComponent::g_editcontent("contentmultiplicity_multiple_tip_between", array("mincount"=>$this->nMin, "maxcount"=>$this->nMax, "contentfield"=>$sTranslation));
		}
		if ($this->nMin > 0)
		{
			return AnwComponent::g_editcontent("contentmultiplicity_multiple_tip_greater", array("mincount"=>$this->nMin, "contentfield"=>$sTranslation));
		}
		if ($this->nMax < self::UNLIMITED)
		{
			return AnwComponent::g_editcontent("contentmultiplicity_multiple_tip_lower", array("maxcount"=>$this->nMax, "contentfield"=>$sTranslation));
		}
		return AnwComponent::g_editcontent("contentmultiplicity_multiple_tip_any", array("contentfield"=>$sTranslation));
	}
}

?>