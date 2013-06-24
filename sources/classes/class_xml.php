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
 * Anwiki's tools for XML management.
 * @package Anwiki
 * @version $Id: class_xml.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */


class AnwXml
{
	const XML_NODENAME_TEXT = "#text";
	const XML_NODENAME_COMMENT = "#comment";
	const XML_NODENAME_PHP = "php";
	const XML_NODENAME_SCRIPT = "script";
	const XML_NODENAME_DOCUMENT = "#document";	
	
	
	static function xmlCreateElementWithChilds($oDocument, $sElementName, $sChildsXmlString)
	{
		$sChildsXmlString = '<'.$sElementName.'>'.$sChildsXmlString.'</'.$sElementName.'>';
		$oTmp = AnwUtils::loadXML($sChildsXmlString);
		$oNewNode = $oDocument->importNode($oTmp, true);
		return $oNewNode;
	}
	
	static function xmlGetChildsByTagName($sTagName, $oParentNode)
	{
		$aoFoundElements = array();
		
		$aoChilds = $oParentNode->childNodes;
		if ($aoChilds)
		{
			foreach ($aoChilds as $oChild)
			{
				if ($oChild->nodeName == $sTagName)
				{
					$aoFoundElements[] = $oChild;
				}
			}
		}
		return $aoFoundElements;
	}
	
	static function xmlIsValid($sContent)
	{
		try
		{
			AnwUtils::loadXML( '<doc>'.$sContent.'</doc>' );
			return true;
		}
		catch(Exception $e){}
		return false;
	}
	
	static function xmlIsTextNode($node)
	{
		if (!is_object($node))
		{
			throw new AnwUnexpectedException("xmlIsTextNode on empty node");
		}
		if ($node->nodeName == self::XML_NODENAME_TEXT)
		{
			if ($node->childNodes) throw new AnwUnexpectedException("Bug detected, please report this bug");
			return true;
		}
		return false;
	}
	
	static function xmlIsCommentNode($node)
	{
		return ($node->nodeName == self::XML_NODENAME_COMMENT);
	}
	
	static function xmlIsPhpNode($node)
	{
		return $node->nodeName == self::XML_NODENAME_PHP;
	}
	
	static function xmlIsEmptyNode($oNode)
	{
		//--begin performances improvements--
		if (self::xmlIsTextNode($oNode))
		{
			return self::xmlIsEmptyNodeTxt($oNode->nodeValue);
		}
		if ($oNode->hasChildNodes())
		{
			return false;
		}
		if ($oNode->nodeName && !self::xmlIsEmptyNodeTxt($oNode->nodeName))
		{
			return false;
		}
		//--end performances improvements--
		
		//in worst case... TODO: is it possible to go there?
		return self::xmlIsEmptyNodeTxt(AnwUtils::xmlDumpNode($oNode));
	}
	
	static function xmlIsEmptyNodeTxt($sTxt)
	{
		return trim($sTxt) == "";
	}
	
	static function xmlHidePhpCode($oNode)
	{
		if (self::xmlIsPhpNode($oNode))
		{
			// TODO see why we need to escape 2 times the &amp; for a correct display in diffs action
			$oNode = $oNode->ownerDocument->createElement("div", "&amp;lt;? hidden PHP code ?&gt;");
		}
		return $oNode;
	}
	
	static function xmlShowPhpCode($oNode)
	{
		if (self::xmlIsPhpNode($oNode))
		{
			// TODO see why we need to escape 2 times the &amp; for a correct display in diffs action
			$oNode = $oNode->ownerDocument->createElement("div", "&amp;lt;? ".htmlspecialchars($oNode->nodeValue)." &gt;");
		}
		return $oNode;
	}
	
	static function xmlIsRootNode($oNode)
	{
		return $oNode->parentNode->nodeName == self::XML_NODENAME_DOCUMENT;
	}
	
	static function xmlIsUntranslatedTxt($sTxt)
	{
		return ( substr(trim($sTxt), 0, strlen(AnwUtils::FLAG_UNTRANSLATED_OPEN)) == AnwUtils::FLAG_UNTRANSLATED_OPEN );
	}
	
	static function xmlIsUntranslated($oNode)
	{
		return self::xmlIsUntranslatedTxt($oNode->nodeValue);
	}
	
	static function xmlGetUntranslatedTxt($sTxt, $bUntranslated)
	{
		$sNewTxt = str_replace(array(AnwUtils::FLAG_UNTRANSLATED_OPEN, AnwUtils::FLAG_UNTRANSLATED_CLOSE), "", $sTxt);
		if ($bUntranslated)
		{
			$sNewTxt = AnwUtils::FLAG_UNTRANSLATED_OPEN.trim($sNewTxt).AnwUtils::FLAG_UNTRANSLATED_CLOSE;
			$sNewTxt = self::xmlPreserveTextLayoutStrict($sNewTxt, $sTxt);
		}
		return $sNewTxt;
	}
	
	static function xmlSetTextUntranslated($oNode, $bUntranslated)
	{
		if ( !self::xmlIsTextNode($oNode) )
		{
			throw new AnwUnexpectedException("setUntranslated on non translatable node!");
		}
		if (!self::xmlIsTextNode($oNode)) throw new AnwUnexpectedException("setUntranslated on non text node!");
		$sValue = self::xmlGetUntranslatedTxt($oNode->nodeValue, $bUntranslated);
		//$oNode->nodeValue = $sValue;
		$oNode = self::xmlReplaceTextNodeValue($oNode, $sValue);
		return $oNode;
	}
	
	private static function xmlGetTextLayoutPrefix($sOriginal) 
	{
		$asLayoutChars = self::getLayoutChars();
		$nLenOriginal = strlen($sOriginal);
		
		$sTranslationPrefix = "";
		for ($i=0; $i<$nLenOriginal && in_array($sOriginal[$i], $asLayoutChars); $i++)
		{
			$sTranslationPrefix .= $sOriginal[$i];
		}
		
		return $sTranslationPrefix;
	}
	
	private static function xmlGetTextLayoutSuffix($sOriginal) 
	{
		$asLayoutChars = self::getLayoutChars();
		$nLenOriginal = strlen($sOriginal);
		
		$sTranslationSuffix = "";		
		for ($i=$nLenOriginal-1; $i>=0 && in_array($sOriginal[$i], $asLayoutChars); $i--)
		{
			$sTranslationSuffix = $sOriginal[$i].$sTranslationSuffix;
		}
		
		return $sTranslationSuffix;
	}
	
	static function xmlTrimTextLayout($sOriginal) 
	{
		$asLayoutChars = self::getLayoutChars();
		
		$nLenOriginal = strlen($sOriginal);
		
		//preserve xml layout before the translation string
		$nLenPrefix = strlen(self::xmlGetTextLayoutPrefix($sOriginal));
		$nLenSuffix = strlen(self::xmlGetTextLayoutSuffix($sOriginal));
		
		//now, do the manual trim
		$sOriginal = substr($sOriginal, $nLenPrefix);
		$sOriginal = substr($sOriginal, 0, strlen($sOriginal)-$nLenSuffix);
		
		return $sOriginal;
	}
	
	static function xmlPreserveTextLayoutStrict($sTranslation, $sOriginal)
	{
		$sTranslationPrefix = self::xmlGetTextLayoutPrefix($sOriginal);
		$sTranslationSuffix = self::xmlGetTextLayoutSuffix($sOriginal);
		
		//copy strictly the layout from original
		$sTranslation = $sTranslationPrefix.trim($sTranslation).$sTranslationSuffix;
		return $sTranslation;
	}
	
	static function xmlPreserveTextLayout($sTranslation, $sOriginal)
	{
		$sTranslationPrefix = self::xmlGetTextLayoutPrefix($sOriginal);
		$sTranslationSuffix = self::xmlGetTextLayoutSuffix($sOriginal);
		
		//just allow one space before and one space after the translation
		$sTranslation = self::trimOneSpaceMax($sTranslation, $sTranslationPrefix, $sTranslationSuffix);
		return $sTranslation;
	}
	
	/**
	 * @param $sText Text, eventually beginning/starting with one or more spaces
	 * @return $sTranslationPrefix.[1 space max].trim($sText).[1 space max].$sTranslationSuffix
	 */
	static function trimOneSpaceMax($sText, $sTranslationPrefix="", $sTranslationSuffix="")
	{
		$asLayoutChars = self::getLayoutChars();
		
		$nLenText = strlen($sText);
				
		$bPrefixEndsWithSpace = (substr($sTranslationPrefix, -1, 1)==" ");
		$bSuffixStartsWithSpace = (substr($sTranslationSuffix, 0, 1)==" ");
		
		//allow to add one space before or remove all spaces before
		for ($i=0; ($i)<$nLenText && in_array($sText[$i], $asLayoutChars); $i++);
		if (isset($sText[$i-1]) && $sText[$i-1]==" "){
			AnwDebug::log("We want a space before");			
			
			if ($bPrefixEndsWithSpace)
			{
				AnwDebug::log("denied, prefix already ends with a space");
			}
			else
			{
				$sTranslationPrefix .= " ";
				AnwDebug::log("allowed, adding a space before text");
			}
		}
		else
		{
			AnwDebug::log("We DON'T want a space before");
			if ($bPrefixEndsWithSpace)
			{
				AnwDebug::log("allowed, removing all spaces before text");
				$sTranslationPrefix = rtrim($sTranslationPrefix, " ");
			}
			else
			{
				AnwDebug::log("nothing to do, prefix doesn't ends with a space");
			}
		}
		
		//allow to add one space after or remove all spaces after
		for ($i=$nLenText-1; $i>=0 && in_array($sText[$i], $asLayoutChars); $i--);
		if (isset($sText[$i+1]) && $sText[$i+1]==" "){
			AnwDebug::log("We want a space after");
			
			if ($bSuffixStartsWithSpace)
			{
				AnwDebug::log("denied, suffix already has a space");
			}
			else 
			{
				$sTranslationSuffix = " ".$sTranslationSuffix;
				AnwDebug::log("allowed, adding a space after text");
			}
		}
		else
		{
			AnwDebug::log("We DON'T want a space after");
			if ($bSuffixStartsWithSpace)
			{
				AnwDebug::log("allowed, removing a space after text");
				$sTranslationSuffix = ltrim($sTranslationSuffix, " ");
			}
			else
			{
				AnwDebug::log("nothing to do, suffix doesn't start with a space");
			}
		}
		
		$sReturn = $sTranslationPrefix.trim($sText).$sTranslationSuffix;
		AnwDebug::log("trimOneSpaceMax: !".$sText."! ---->  !".$sReturn."!");
		return $sReturn;
	}

	private static function getLayoutChars()
	{
		return array(" ", "\n", "\t");
	}
	
	static function xmlReplaceTextNodeValue($oNode, $sValue)
	{
		if (!self::xmlIsTextNode($oNode))
		{
			throw new AnwUnexpectedException("xmlReplaceTextNodeValue called on a non text node");
		}
		$oNode->nodeValue = $sValue;
		return $oNode;
	}
	
	static function xmlSimilarStructure($oRootNode1, $oRootNode2, $bTestAttributesValues=true, $bDebugOnError=false)
	{
		return self::xmlAreSimilarNodes($oRootNode1, $oRootNode2, false, $bTestAttributesValues, false, $bDebugOnError);
	}
	
	static function xmlAreSimilarNodesAllowTextLayoutChange($oNode1, $oNode2)
	{
		//first, do classic tests but don't test nodeValue - for root node only (check childs!)...
		if (!self::xmlAreSimilarNodes($oNode1, $oNode2, true, true, true, false, true)) {
			return false;
		}
		
		//now, test nodeValue but allow textLayout changes...
		if (self::xmlTrimTextLayout($oNode1->nodeValue) != self::xmlTrimTextLayout($oNode2->nodeValue)) {
			return false;
		}
		
		return true;
	}
	
	//!optimized : this is much faster rather than testing : xmlDumpNode($oNode1) == xmlDumpNode($oNode2)
	static function xmlAreSimilarNodes($oNode1, $oNode2, $bTestEquals=true, $bTestAttributesValues=true, $bTestRootNodes=true, $bDebugOnError=false, $bDontTestRootNodeValue=false)
	{
		if ($bTestRootNodes) //need to skip it if coming from xmlSimilarStructure
		{
			if (!$bTestEquals && !self::xmlIsTranslatableParent($oNode1))
			{
				$bTestEquals = true;
			}
			
			//text nodes
			if ( self::xmlIsTextNode($oNode1) || self::xmlIsTextNode($oNode2) )
			{
				if ( !self::xmlIsTextNode($oNode1) || !self::xmlIsTextNode($oNode2) )
				{
					//trying to compare uncomparable nodes
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - textnode null");
					return false;
				}
				if ($bTestEquals && !$bDontTestRootNodeValue)
				{
					$bReturn = ($oNode1->nodeValue == $oNode2->nodeValue);
					if (!$bReturn)
					{
						if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - textnodes values");
					}
					return $bReturn;
				}
				return true;
			}
			
			//php nodes
			if ( self::xmlIsPhpNode($oNode1) || self::xmlIsPhpNode($oNode2) )
			{
				if ( !self::xmlIsPhpNode($oNode1) || !self::xmlIsPhpNode($oNode2) )
				{
					//trying to compare uncomparable nodes
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - php null");
					return false;
				}
				
				//always test equality
				$bReturn = ($oNode1->nodeValue == $oNode2->nodeValue);
				if (!$bReturn)
				{
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - php values");
				}
				return $bReturn;
			}
			
			//comment nodes
			if ( self::xmlIsCommentNode($oNode1) || self::xmlIsCommentNode($oNode2) )
			{
				if ( !self::xmlIsCommentNode($oNode1) || !self::xmlIsCommentNode($oNode2) )
				{
					//trying to compare uncomparable nodes
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - comment null");
					return false;
				}
				
				//always test equality
				$bReturn = ($oNode1->nodeValue == $oNode2->nodeValue);
				if (!$bReturn)
				{
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - comment values");
				}
				return $bReturn;
			}
			
			//compare node names
			if ($oNode1->nodeName != $oNode2->nodeName)
			{
				if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - nodeNames");
				return false;
			}

			//compare attributes
			if ($oNode1->hasAttributes() || $oNode2->hasAttributes())
			{
				if (!$oNode1->hasAttributes() || !$oNode2->hasAttributes())
				{
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - attribute count1");
					return false;
				}
				
				if (count($oNode1->attributes) != count($oNode2->attributes))
				{
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - attribute count2");
					return false;
				}
				
				//compare attributes values
				if ($bTestAttributesValues)
				{
					foreach ($oNode1->attributes as $sAttrName => $oAttr)
					{
						if ($oNode1->getAttribute($sAttrName) != $oNode2->getAttribute($sAttrName))
						{
							if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - attribute eq1");
							return false;
						}
					}
					
					//warning! for a strange reason, count of attributes + previous test are not sufficient to pass testXmlAreSimilarNodes3
					foreach ($oNode2->attributes as $sAttrName => $oAttr)
					{
						if ($oNode1->getAttribute($sAttrName) != $oNode2->getAttribute($sAttrName))
						{
							//print htmlentities(AnwUtils::xmlDumpNode($oNode1->parentNode));print '<hr/>';
							//print htmlentities(AnwUtils::xmlDumpNode($oNode2->parentNode));
							if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - attribute eq2");
							return false;
						}
					}
				}
			}
		}
		
		//------------------------
		
		//now, check childs
		if ($oNode1->hasChildNodes() || $oNode2->hasChildNodes())
		{
			if (!$oNode1->hasChildNodes() || !$oNode2->hasChildNodes())
			{
				if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - childs null");
				return false;
			}
			
			$oChilds1 = $oNode1->childNodes;
			$oChilds2 = $oNode2->childNodes;
			
			if ( $oChilds1->length != $oChilds2->length )
			{
				if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - childs count".htmlentities(AnwUtils::xmlDumpNode($oNode1)." VS ".AnwUtils::xmlDumpNode($oNode2)));
				return false;
			}
			
			if ($bDontTestRootNodeValue) {
				$bDontTestRootNodeValue = false; //we are now browsing childs, so we need to test their values...
			}
			
			$n = $oChilds1->length;
			for($i=0; $i<$n; $i++)
			{
				$oChild1 = $oChilds1->item($i);
				$oChild2 = $oChilds2->item($i);
				
				if (!self::xmlAreSimilarNodes($oChild1, $oChild2, $bTestEquals, $bTestAttributesValues, true, $bDebugOnError))
				{
					if ($bDebugOnError) AnwDebug::log("xmlAreSimilarNodes - false - childs recursive");
					return false;
				}
			}
		}
		return true;
		//return (AnwUtils::xmlDumpNode($oNode1) == AnwUtils::xmlDumpNode($oNode2));
	}
	
	/*
	static function xmlAddTranslateTagsGet($oRootNode, $oParentNode=null)
	{
		if ($oRootNode->nodeName == "#text" && (!$oParentNode || $oParentNode->nodeName != "translate") && trim($oRootNode->nodeValue) != "")
		{
			$oTranslateNode = $oRootNode->ownerDocument->createElement("translate", $oRootNode->nodeValue);
			AnwDebug::log("xmlAddTranslateTagsGet...".htmlentities(AnwUtils::xmlDumpNode($oTranslateNode)));
			return $oTranslateNode;
		}
		
		$bChanged = false;
		
		$oChildNodes = $oRootNode->childNodes;
		if ($oChildNodes)
		{
			foreach ($oChildNodes as $oChildNode)
			{
				$oTmpNode = self::xmlAddTranslateTagsGet($oChildNode, $oRootNode);
				if ($oTmpNode)
				{
					$oRootNode->replaceChild($oTmpNode, $oChildNode);
					$bChanged=true;
				}
			}
		}
		if ($bChanged)
		{
			AnwDebug::log("xmlAddTranslateTagsGet...".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
			return $oRootNode;
		}
		else return null;
	}*/
	
	
	
	
	static function xmlIsTranslatableParent($oNode)
	{
		$asUntranslatableNodes = array(
			"style", 
			AnwUtils::XML_NODENAME_UNTRANSLATABLE, 
			self::XML_NODENAME_SCRIPT/*, self::XML_NODENAME_PHP, self::XML_NODENAME_COMMENT*/);
		if (in_array($oNode->nodeName, $asUntranslatableNodes))
		{
			return false;
		}
		return true;
	}
	
	
	
	
	
	static function xmlSetContentFieldUntranslated($oRootNode, $oContentField, $bUntranslated=true)
	{
		//AnwDebug::log("xmlSetContentFieldUntranslated");
		//AnwDebug::logdetail("xmlSetContentFieldUntranslated/start : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		
		if ( self::xmlAreTranslatableAncestors($oRootNode, $oContentField))
		{
			if ( self::xmlIsTextNode($oRootNode) )
			{
				if ( !self::xmlIsEmptyNode($oRootNode) )
				{
					$oRootNode = AnwXml::xmlSetTextUntranslated($oRootNode, $bUntranslated/*, $sQuickHackNewValue*/);
				}
			}
			else
			{
				$oChildNodes = $oRootNode->childNodes;
				if ($oChildNodes)
				{
					for ($i=0; $i<$oChildNodes->length; $i++)//foreach ($oChildNodes as $oChildNode)
					{
						$oChildNode = $oChildNodes->item($i);
						self::xmlSetContentFieldUntranslated($oChildNode, $oContentField, $bUntranslated);
						//AnwDebug::logdetail("xmlAddTr: ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
					}
					//AnwDebug::logdetail("xmlAddTr: ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
				}
			}
		}
		//AnwDebug::log("xmlSetContentFieldUntranslated/end : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		return $oRootNode;
	}
	
	/**
	 * Adds translate tags to an xml value of a contentfield_atomic !
	 * If you want to add translate tags to a contentfield_container, use xmlSetContentFieldUntranslated instead !
	 */
	static function xmlAddTranslateTags($oRootNode, $bUntranslated=true/*, $sQuickHackNewValue=false*/)
	{
		//AnwDebug::logdetail("xmlAddTranslateTags/start : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		
		if (self::xmlIsTranslatableParent($oRootNode))
		{
			if ( self::xmlIsTextNode($oRootNode) )
			{
				if ( !self::xmlIsEmptyNode($oRootNode) )
				{
					$oRootNode = self::xmlSetTextUntranslated($oRootNode, $bUntranslated/*, $sQuickHackNewValue*/);
				}
			}
			else
			{
				$oChildNodes = $oRootNode->childNodes;
				if ($oChildNodes)
				{
					for ($i=0; $i<$oChildNodes->length; $i++)//foreach ($oChildNodes as $oChildNode)
					{
						$oChildNode = $oChildNodes->item($i);
						self::xmlAddTranslateTags($oChildNode, $bUntranslated/*, $sQuickHackNewValue*/);
						//AnwDebug::logdetail("xmlAddTr: ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
					}
					//AnwDebug::logdetail("xmlAddTr: ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
				}
			}
		}
		//AnwDebug::logdetail("xmlAddTranslateTags/end : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		return $oRootNode;
	}
	
	
	
	//-------------- below: mainly used by autosync --------------
	
	
	static function xmlAreTranslatableAncestors($oNode, $oContentField)
	{
		//print $oNode->nodeName.'... ';
		
		//check node
		if (!self::xmlIsTranslatableParent($oNode))
		{
			//print 'not translatable parent';
			return false;
		}
		
		//check contentfields
		if ($oContentField instanceof AnwStructuredContentField_composed)
		{
			try
			{
				$oSubContentField = $oContentField->getContentField($oNode->nodeName);
				//subcontentfield has been found!
				if (!$oSubContentField->isTranslatable())
				{
					//print $oSubContentField->getName()." is not translatable !";
					return false;
				}
				//print $oSubContentField->getName()." is translatable !";
				
				//now, check back to make sure that subnodes have not been set as untranslatable
				return $oSubContentField;
			}
			catch(AnwException $e)
			{
				//print 'e';
			}
		}
		else
		{
			//print "no container";
		}
		
					
		$oNodeParent = $oNode->parentNode;
		if ($oNodeParent)
		{
			$mReturn = self::xmlAreTranslatableAncestors($oNodeParent, $oContentField);
			//print "<br/>backtrack:".$oNode->nodeName."<br/>";
			//backtracking
			if ($mReturn instanceof AnwStructuredContentField_composed)
			{
				$oContentFieldReturned = $mReturn;
				try
				{
					$oSubContentField = $oContentFieldReturned->getContentField($oNode->nodeName);
					if (!$oSubContentField->isTranslatable())
					{
						//print $oSubContentField->getName()." is NOT translatable(checked back)!";
						return false;
					}
					else
					{
						//print $oSubContentField->getName()." is translatable(checked back)";
					}
				}
				catch(AnwException $e){ 
					//print 'e';
				//we may be in at on a <anwvalue> node
				}
				//if it's the first call, return will be considered as true even if we return an object...
				//otherwise, object will be transmited to continue the backtrack...
			}
			else
			{
				//print 'nocontainer';
			}
			return $mReturn;
		}
		return true;
	}
	
	/**
	 * Special nodes such as PHP nodes and COMMENT nodes, which may be considered
	 * as AnwDiffs nodes (containing a #text node) but that we should consider as
	 * something which can never be edited, which can never have attributes and 
	 * which can never be set as translated/untranslated.
	 */
	static function xmlIsUnmodifiableBlockNode($oNode)
	{
		return (self::xmlIsPhpNode($oNode) || self::xmlIsCommentNode($oNode));
	}
	
	static function xmlReplaceUnmodifiableBlockNode($oNode, $oNodeRef)
	{
		if ( !AnwXml::xmlIsUnmodifiableBlockNode($oNode)
			|| !AnwXml::xmlIsUnmodifiableBlockNode($oNodeRef) )
		{
			throw new AnwUnexpectedException("xmlReplaceUnmodifiableBlockNode called on a non Unmodifiable node");
		}
		$oNode->nodeValue = $oNodeRef->nodeValue;
		return $oNode;
	}
	
	static function xmlReplaceTextNode($oNode, $oNodeForReplace)
	{
		//ensure it's a text node
		if (!self::xmlIsTextNode($oNode)) throw new AnwUnexpectedException("ReplaceTextNode on non text node");
		
		$oNode = AnwXml::xmlReplaceTextNodeValue($oNode, $oNodeForReplace->nodeValue);
		return $oNode;
	}
	
	static function xmlCopyNodeAttributes($oNodeRef, $oNode)
	{
		if (self::xmlIsUnmodifiableBlockNode($oNode) || self::xmlIsTextNode($oNode))
		{
			throw new AnwUnexpectedException("xmlCopyNodeAttributes called on PhpNode or CommentNode");
		}
		/*
		if ( self::xmlIsPhpNode($oNode) || self::xmlIsCommentNode($oNode) )
		{
			//php detected, copying full node
			$oNode->nodeValue = $oNodeRef->nodeValue;
		}
		else if ( self::xmlIsTextNode($oNode) )
		{
			//text node detected, copying nothing
		}
		else
		{*/
		
			//unset existing attributes
			//WARNING - we can't read attributes list while deleting attributes, we *NEED* to read it in a first time, then delete attributes in second time. 
			$asAttributesNames = self::xmlGetAttributesNames($oNode);
			foreach ($asAttributesNames as $sAttrName)
			{
				$oNode->removeAttribute($sAttrName);
			}
			
			//set attributes from ref
			//WARNING - we can't read attributes list while deleting attributes, we *NEED* to read it in a first time, then delete attributes in second time.
			$asAttributesNames = self::xmlGetAttributesNames($oNodeRef);
			foreach ($asAttributesNames as $sAttrName)
			{
				$oNode->setAttribute($sAttrName, $oNodeRef->getAttribute($sAttrName));
			}
			
		//}
	}
	
	static function xmlGetAttributesNames($oNode)
	{
		$asAttributesNames = array();
		foreach ($oNode->attributes as $sAttrName => $oAttribute)
		{
			$asAttributesNames[] = $sAttrName;
		}
		return $asAttributesNames;
	}
	

	
	const FIX_XMLNS_DEFAULT_RENAME = "_anwdefaultxmlns_";
	const FIX_NS_RENAME = "_anwns_";
	
	static function prepareXmlValueToXml($sValueOriginal)
	{
		$sValue = $sValueOriginal;
		
		// FS#100 XMLNS attributes are breaking XML structures when not prefixed with a namespace - Thx Trev!
		
		// replacing <foo xmlns="http://bar/"> with <foo anwdefaultxmlns="http://bar/">
		// and <foo bar:xmlns="http://bar/"> with <foo bar:anwdefaultxmlns="http://bar/">
		$sValue = preg_replace('/(<([^<>"\']|"[^"<>]*"|\'[^\'<>]\')+?\s)xmlns=/', '$1'.self::FIX_XMLNS_DEFAULT_RENAME.'=', $sValue);
		
		// replacing <foo:bar attr="value"/> with <foo_anwns_bar attr="value"/>
		// and <tag foo:bar="value"/> with <tag foo_anwns_bar="value"/>
		// be careful with CSS attributes!
		do
		{
			$sValue = preg_replace('/(<([^<>"\']|"[^"<>]*"|\'[^\'<>]\')+?):/', '$1'.self::FIX_NS_RENAME, $sValue, -1, $nCount);
		} while ($nCount > 0);
		
		
		self::debug("prepareXmlValueToXml: ".htmlentities($sValueOriginal)." -- becomes -- ".htmlentities($sValue));
		
		return $sValue;
	}
	
	static function prepareXmlValueFromXml($sValueOriginal)
	{
		$sValue = $sValueOriginal;
		
		// FS#100 XMLNS attributes are breaking XML structures when not prefixed with a namespace - Thx Trev!
		// now, we have to restore the original value to keep original value unchanged
		
		// reverting <foo_anwns_bar attr="value"/> with <foo:bar attr="value"/>
		// and <tag foo_anwns_bar="value"/> to <tag foo:bar="value"/>
		// be careful with CSS attributes!
		do
		{
			$sValue = preg_replace('/(<([^<>"\']|"[^"<>]*"|\'[^\'<>]\')+?)'.self::FIX_NS_RENAME.'/', '$1:', $sValue, -1, $nCount);
		} while ($nCount > 0);
		
		// reverting <foo anwdefaultxmlns="http://bar/"> to <foo xmlns="http://bar/">
		// and <foo bar:anwdefaultxmlns="http://bar/"> to <foo bar:xmlns="http://bar/">
		$sValue = preg_replace('/(<([^<>"\']|"[^"<>]*"|\'[^\'<>]\')+?\s)'.self::FIX_XMLNS_DEFAULT_RENAME.'=/', '$1xmlns=', $sValue);
		
		self::debug("prepareXmlValueFromXml: ".htmlentities($sValueOriginal)." -- becomes -- ".htmlentities($sValue));
		
		return $sValue;
	}
	
	/**
	 * Encodes a string to be written in XML file as node attribute.
	 */
	static function xmlFileAttributeEncode($sString)
	{
		$sString = htmlentities($sString, ENT_QUOTES, "UTF-8");
		return $sString;
	}
	
	/**
	 * Decodes an attribute value encoded with xmlFileAttributeEncode().
	 */
	static function xmlFileAttributeDecode($sString)
	{
		$sString = html_entity_decode($sString, ENT_QUOTES, "UTF-8");
		return $sString;
	}

	
	protected static function debug($sMsg)
	{
		AnwDebug::log("(AnwXml) ".$sMsg);
	}
}


?>