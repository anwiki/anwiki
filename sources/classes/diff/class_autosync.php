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
 * Anwiki's magic Autosync feature.
 * @package Anwiki
 * @version $Id: class_autosync.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwAutoSync
{

	static function propagateContentEdition($oOldContent, $oNewContent, $oContentTranslation)
	{
		$oContentTranslation = clone $oContentTranslation; //don't edit content directly !
		
		// Warning: Diff is applied to the first-level contentfields, and not recursively.
		// This allows content to be moved from one subcontentfield to another without loosing translations.
		// 
		// Due to this non-recursive approach, subcontentfields translatable flags are not respected.
		// That's why we will need to run propagateUntranslatableContentEdition() at the end to fix this.
		//
		// TODO: find a cleaner way to do this.
		
		$aoContentFields = $oNewContent->getContentFieldsContainer()->getContentFields();
		foreach ($aoContentFields as $oContentField)
		{
			$sFieldName = $oContentField->getName();
			
			if ($oContentField->isTranslatable())
			{
				//determine FIELD value with diffs method (recalc same diffs)
				$oOldContentFieldValuesXml = $oOldContent->getContentFieldDataAsXml($sFieldName);
				$oContentFieldValuesXml = $oNewContent->getContentFieldDataAsXml($sFieldName);
				
				$oDiffs = new AnwDiffs($oOldContentFieldValuesXml, $oContentFieldValuesXml);
				//print $oDiffs->debugHtml();
				$oTranslateContentFieldValuesXml = $oContentTranslation->getContentFieldDataAsXml($sFieldName);
				$oTranslateContentFieldValuesXml = self::applyDiffs($oDiffs, $oTranslateContentFieldValuesXml, $oContentField);
				
				AnwDebug::log("setContentField(".$sFieldName.") : ".htmlentities(AnwUtils::xmlDumpNode($oTranslateContentFieldValuesXml)));
				
				//set content field value
				if ($oContentField instanceof AnwStructuredContentField_atomic)
				{
					$asValues = array();
					$oValuesNodes = $oTranslateContentFieldValuesXml->childNodes;
					for ($i=0; $i<$oValuesNodes->length; $i++)
					{
						$oChild = $oValuesNodes->item($i);
						$sValue = AnwUtils::xmlDumpNodeChilds($oChild); //TODO better parsing for anwv?
						$asValues[] = $sValue;
					}
					$oContentTranslation->setContentFieldValues($sFieldName, $asValues);
				}
				else
				{
					//extract subcontents from xml tree...
					$aoSubContents = array();
					$oSubContentsNodes = $oTranslateContentFieldValuesXml->childNodes;
					for ($i=0; $i<$oSubContentsNodes->length; $i++)
					{
						$oChild = $oSubContentsNodes->item($i);
						$sSubContentValue = AnwUtils::xmlDumpNodeChilds($oChild);
						//using $oContentTranslation instead of self for php inherance
						$oSubContent = $oContentTranslation->rebuildContentFromXml($oContentField, $sSubContentValue);
						$aoSubContents[] = $oSubContent;
					}
					$oContentTranslation->setSubContents($sFieldName, $aoSubContents);
				}
			}
			else
			{
				//not translatable = keep same value
				if ($oContentField instanceof AnwStructuredContentField_atomic)
				{
					$oContentTranslation->setContentFieldValues($sFieldName, $oNewContent->getContentFieldValues($sFieldName));
				}
				else
				{
					$oContentTranslation->setSubContents($sFieldName, $oNewContent->getSubContents($sFieldName));
				}
			}
		}
		
		// As explained below, we need to run propagateUntranslatableContentEdition() to respect subcontentfields' translatable attribute.
		
		//AnwDebug::log("####before####".htmlentities($oContentTranslation->toXmlString()));
		//print "####before####".htmlentities($oContentTranslation->toXmlString())."<hr/>";
		$oContentTranslation = self::propagateUntranslatableContentEdition($oContentTranslation, $oNewContent);
		//AnwDebug::log("####after####".htmlentities($oContentTranslation->toXmlString()));
		//print "####after####".htmlentities($oContentTranslation->toXmlString());exit;
		return $oContentTranslation;
	}
	
	private static function propagateUntranslatableContentEdition($oContentTranslation, $oContentModel)
	{
		//here, we are sure that $oContentTranslation and $oContentModel have *exactly* the same XML structure.
		//we only need to recursively analyze ContentFields related, and if any ContentField is flagged as "untranslatable", copy ContentField's new values from $oContentModel to $oContentTranslation (and set these values as translated). 
		
		$oContentField = $oContentTranslation->getContentFieldsContainer();
		$sFieldName = $oContentField->getName();
		
		
		//search for untranslatable subcontentfields
		$aoSubContentFields = $oContentField->getContentFields();
		foreach ($aoSubContentFields as $oSubContentField)
		{
			$sSubFieldName = $oSubContentField->getName();
			//AnwDebug::log("~~~~~~ BEGIN ".$sSubFieldName.": ".htmlentities(implode('/',$oContentTranslation->getContentFieldValues($sSubFieldName)))."~~~~~~");
			
			if (!$oSubContentField->isTranslatable())
			{
				if ($oSubContentField instanceof AnwStructuredContentField_atomic)
				{
					//contentfield is untranslatable: override with the new value
					//AnwDebug::log("==========>overriding ".$sSubFieldName);
					$asValues = $oContentModel->getContentFieldValues($sSubFieldName);
					if (count($asValues)>0)
					{
						//AnwDebug::log("============>>>".implode('/',$asValues));
						$oContentTranslation->setContentFieldValues($sSubFieldName, $asValues);
					}
				}
				else
				{
					$aoSubContents = $oContentModel->getSubContents($sSubFieldName);
					$oContentTranslation->setSubContents($sSubFieldName, $aoSubContents);
				}
			}
			else
			{
				//it's translatable...
				
				if ($oSubContentField instanceof AnwStructuredContentField_composed)
				{
					$aoSubContentsModel = $oContentModel->getSubContents($sSubFieldName);
					$aoSubContentsTranslation = $oContentTranslation->getSubContents($sSubFieldName);
					
					if (count($aoSubContentsModel) != count($aoSubContentsTranslation))
					{
						throw new AnwUnexpectedException("subcontents count should be equal");
					}
					
					if (count($aoSubContentsModel) > 0)
					{
						$aoNewSubContentsTranslation = array();
						foreach ($aoSubContentsModel as $oSubContentModel)
						{
							$oSubContentTranslation = array_shift($aoSubContentsTranslation);
							$oSubContentTranslation = self::propagateUntranslatableContentEdition($oSubContentTranslation, $oSubContentModel);
							$aoNewSubContentsTranslation[] = $oSubContentTranslation;
						}
						$oContentTranslation->setSubContents($sSubFieldName, $aoNewSubContentsTranslation);
					}
				}
				else
				{
					//translatable and atomic --> nothing to do, it has already the good value and it already has correct untransleted flag.
				}
			}
			//AnwDebug::log("~~~~~~ END ".$sSubFieldName.": ".htmlentities(implode('/',$oContentTranslation->getContentFieldValues($sSubFieldName)))."~~~~~~");
		}
		return $oContentTranslation;
	}
	
	private static function applyDiffs($oDiffs, $oContentXml, $oContentField)
	{
		AnwDebug::startBench("applyDiffs");
		
		//calculate diffs if not already done
		AnwDebug::log("#####applyDiffs() : step 1/3 - calc diffs#####");
		AnwDebug::startBench("applyDiffs-1",true);
		$oDiffs->getAllDiffs();
		AnwDebug::stopBench("applyDiffs-1");
		
				
		//associate deleted values to moved nodes
		AnwDebug::log("#####applyDiffs() : step 2/3 - prepare#####");
		AnwDebug::startBench("applyDiffs-2",true);
		self::prepareDiffsToTranslation($oDiffs, $oContentXml);	//update oDiffs
		AnwDebug::stopBench("applyDiffs-2");
		
		//print $oDiffs->debugHtml();
		
		//apply diffs
		AnwDebug::log("#####applyDiffs() : step 3/3 - apply#####");
		AnwDebug::startBench("applyDiffs-3",true);
		$oContentXml = self::applyDiffsToTranslation($oContentXml, $oDiffs, $oContentField);
		AnwDebug::stopBench("applyDiffs-3");
		
		//AnwDebug::logdetail("! applyDiffs result : ".htmlentities(AnwUtils::xmlDumpNode($oContentXml)));
		
		AnwDebug::stopBench("applyDiffs");
		return $oContentXml;
	}
		
	/**
	 * required before applying Diffs : $oDiffDeleted->getMovedDiff()->setMovedNode
	 */
	private static function prepareDiffsToTranslation($oDiffs, $oRootNode)
	{
		$aoDiffs = $oDiffs->getAllDiffs();
		$oChildNodes = $oRootNode->childNodes;
		
		$i = 0;
		
		foreach ($aoDiffs as $oDiff)
		{
			//get next child if it exists
			$oChild = $oChildNodes->item($i);
			if (!$oChild)
			{
				AnwDebug::log("oChild=null");
			}

			$sDiffType = $oDiff->getDiffType();
			
			$bSkipIncrement = false;
			switch($sDiffType)
			{
				case AnwDiff::TYPE_DELETED:
					//AnwDebug::logdetail(" * prepare:DELETED (->prepare sub)");
					self::prepareDiffsToTranslation_subdeleted($oDiff, $oChild);
					break;
					
				case AnwDiff::TYPE_EDITED:
					//AnwDebug::logdetail(" * prepare:EDITED (->prepare sub)");
					self::prepareDiffsToTranslation_subdeleted($oDiff->getDiffDeleted(), $oChild); //??
					break;
					
				case AnwDiff::TYPE_DIFFS:
					//AnwDebug::logdetail(" * prepare:DIFFS : ".htmlspecialchars(AnwUtils::xmlDumpNode($oChild))." (was : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNodeParent())).")");
					
					//recursive call
					//AnwDebug::logdetail("prepareDiffsToTranslation recursive-->");
					self::prepareDiffsToTranslation($oDiff, $oChild);
					//AnwDebug::logdetail("prepareDiffsToTranslation <--recursive");
					break;
					
				case AnwDiff::TYPE_ADDED:
					//AnwDebug::logdetail(" * prepare:ADDED (skipping)");
					$bSkipIncrement = true;
					break;
					
				case AnwDiff::TYPE_MOVED:
					//AnwDebug::logdetail(" * prepare:MOVED (skipping) ");
					$bSkipIncrement = true;
					break;
					
				case AnwDiff::TYPE_KEPT:
					//AnwDebug::logdetail(" * prepare:KEPT (skipping) : ".htmlentities(AnwUtils::xmlDumpNode($oChild))." (was : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode())).")");
					break;
					
				default:
					throw new AnwUnexpectedException("Unknown diff type :".$oDiff->getDiffType());
					break;
			}
			
			if (!$bSkipIncrement)
			{
				$i++;
				//AnwDebug::logdetail(" * Loop i++=".$i);
			}
		}
	}
	
	//set AnwDiffDeleted->getMovedDiff()->setMovedNode()
	private static function prepareDiffsToTranslation_subdeleted($oDiff, $oChild)
	{
		//AnwDebug::logdetail(" * prepare_subdeleted: ".htmlspecialchars(AnwUtils::xmlDumpNode($oChild))." ( was : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode())).")");
		if ($oDiff->getMovedDiff() && !$oDiff->getMovedDiff()->getMovedNode())
		{
			//AnwDebug::log("---setMovedNode!".htmlentities(AnwUtils::xmlDumpNode($oChild))." (was : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode())).")" );
			//((WARNING)) we use a COPY of the node to avoid many problems
			$oCopyChild = $oChild->cloneNode(true);
			$oDiff->getMovedDiff()->setMovedNode($oCopyChild);
		}
		else if ($oDiff->hasSubDeletedDiffs())
		{
			//analyze subdeleted nodes if it exists
			$oSubChilds = $oChild->childNodes;
			
			$i = 0;
			$aoSubDeletedDiffs = $oDiff->getSubDeletedDiffs();
			
			foreach ($aoSubDeletedDiffs as $oSubDeletedDiff)
			{
				$oSubChild = $oSubChilds->item($i);
				
				if ($oSubChild)
				{
					//AnwDebug::logdetail("prepare_subdeleted: -->recursive");
					self::prepareDiffsToTranslation_subdeleted($oSubDeletedDiff, $oSubChild);
					//AnwDebug::logdetail("prepare_subdeleted: <--recursive");
				}
				else
				{
					//this can happen when a page was translated with an empty string...
					//AnwDebug::logdetail("prepare_subdeleted: WARNING - subdeleted child not found");
				}
				$i++;
			}
		}
	}
	
	
	private static function applyDiffsToTranslation($oRootNode, $oDiffs, $oContentField)
	{
		//AnwDebug::log("Applying diffs to : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		
		$aoDiffs = $oDiffs->getAllDiffs();
		$oChildNodes = $oRootNode->childNodes;
		
		
		$i = 0;
		
		foreach ($aoDiffs as $oDiff)
		{
			$oChild = $oChildNodes->item($i);
			
			if (!$oChild)
			{
				//AnwDebug::logdetail("oChild=null");
			}
			
			AnwDebug::logdetail("//step dump: ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
			
			$sDiffType = $oDiff->getDiffType();
			
			$bSkipIncrement = false;
			switch($sDiffType)
			{
				case AnwDiff::TYPE_ADDED: 
					
					if ($oDiff->hasSubMovedNode()) //only create the node tag, then create recursively the childs
					{
						$oNodeRef = $oDiff->getNode();
						
						$oNewNode = $oRootNode->ownerDocument->createElement( $oNodeRef->nodeName );
						
						//no need to check for UnmodifiableBlockNodes, a comment/php node can't have submoved nodes
						AnwXml::xmlCopyNodeAttributes($oNodeRef, $oNewNode);

						//recursive call for SubAdded nodes
						
						AnwDebug::log(" * SUBADDED : ".htmlentities(AnwUtils::xmlDumpNode($oNewNode)));
						
						if ( $oChild )
						{
							AnwDebug::logdetail("added->insertBefore : before=".htmlentities(AnwUtils::xmlDumpNode($oChild)));
							$oRootNode->insertBefore($oNewNode, $oChild);
						}
						else
						{
							AnwDebug::logdetail("added->appendChild");
							$oRootNode->appendChild($oNewNode);
						}
						//continue on subadded diffs
						self::applyDiffsToTranslation($oNewNode, $oDiff, $oContentField);
					}
					else //import the whole node at once
					{
						//quick test for special case with xmlIsUnmodifiableBlockNode
						$oTmpNode = ($oDiff->getMovedNode() ? $this->getMovedNode() : $oDiff->getNode());
												
						if (AnwXml::xmlIsUnmodifiableBlockNode($oTmpNode))
						{
							//keep it unchanged
							$oNodeToImport = $oTmpNode;
						}
						else
						{
							//mark it as untranslated only if translatable (contentfield check)
							$bMarkAsUntranslated = AnwXml::xmlAreTranslatableAncestors($oRootNode, $oContentField);
							//TODO: move code from getNodeWithTranslateTags() here
							$oNodeToImport = $oDiff->getNodeWithTranslateTags($bMarkAsUntranslated, $oContentField);
						}
						$oNewNode = $oRootNode->ownerDocument->importNode( $oNodeToImport, true );
					
						AnwDebug::log(" * ADDED : ".htmlentities(AnwUtils::xmlDumpNode($oNewNode)));
						
						if ( $oChild )
						{
							AnwDebug::logdetail("added->insertBefore : before=".htmlentities(AnwUtils::xmlDumpNode($oChild)));
							$oRootNode->insertBefore($oNewNode, $oChild);
						}
						else
						{
							AnwDebug::logdetail("added->appendChild");
							$oRootNode->appendChild($oNewNode);
						}
					}
					break;
					
				case AnwDiff::TYPE_MOVED:
					//No need to import node as we get a node from the same document
					$oMovedNode = $oDiff->getMovedNode();
					
					//tmp check
					if (!$oMovedNode)
					{
						//print AnwDebug::log('-----'.AnwUtils::xmlDumpNode($oDiff->getDiffDeleted()->getNode()));
						AnwDebug::log("****ERROR**** getMovedNode() returned NULL on AnwDiffMoved !");
						throw new AnwUnexpectedException("ERROR getMovedNode() returned NULL on AnwDiffMoved !");
						break;
					}
					
					AnwDebug::log(" * MOVED : ".htmlentities(AnwUtils::xmlDumpNode($oMovedNode)));
					
					//did the textlayout change?
					if ($oDiff->hasTextLayoutChanged())
					{
						//we need to apply the new textlayout...
						$oMovedNode->nodeValue = AnwXml::xmlPreserveTextLayout($oMovedNode->nodeValue, $oDiff->getTextLayoutReferenceValue());
					}
					
					//the following operations will MOVE the node into the document
					if ( $oChild )
					{
						AnwDebug::log("added->insertBefore".htmlentities(AnwUtils::xmlDumpNode($oMovedNode)));
						$oRootNode->insertBefore($oMovedNode, $oChild);
					}
					else
					{
						AnwDebug::logdetail("added->appendChild");
						if (!$oRootNode->appendChild($oMovedNode)) throw new AnwUnexpectedException("appendChild failed");
					}
					break;
					
				case AnwDiff::TYPE_DELETED:					
					
					//if (!$oDiff->getMovedDiff())
					//{				
						AnwDebug::logdetail(" * DELETED : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode()))." == ".htmlentities(AnwUtils::xmlDumpNode($oChild)));
						//if (!$oRootNode->removeChild($oChild)) throw new AnwUnexpectedException("removeChild failed");
						//$bSkipIncrement = true;
						
						// !!! We don't delete nodes now, because we may need it if it contains moved nodes...
						// these nodes will be deleted later
						////self::$aoNodesMarkedForDeletion[] = array($oRootNode,$oChild);
						if (!$oRootNode->removeChild($oChild)) throw new AnwUnexpectedException("removeChild failed");
						$bSkipIncrement = true;
					//}
					//else
					//{
					//	AnwDebug::log(" * DELETED : deletion skipped (node will be moved)");
					//}
					
					break;
					
				case AnwDiff::TYPE_KEPT:
					//don't touch anything
					AnwDebug::log(" * KEPT : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode()))." == ".htmlentities(AnwUtils::xmlDumpNode($oChild)));
					break;
					
				case AnwDiff::TYPE_EDITED:
					AnwDebug::log(" * EDITED : ".htmlentities(AnwUtils::xmlDumpNode($oChild))." == ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getDiffAdded()->getNode())));
					
					//TODO: warning, this code is very crappy and needs to be cleaned and tested
					
					//update attributes
					
					$oNodeRef = $oDiff->getDiffAdded()->getNode();
					
					if (AnwXml::xmlIsUnmodifiableBlockNode($oChild))
					{
						$oNewNode = AnwXml::xmlReplaceUnmodifiableBlockNode($oChild, $oNodeRef);
					}
					else
					{
						if (!AnwXml::xmlIsTextNode($oChild))
						{
							throw new AnwUnexpectedException("not a text node in TYPE_EDITED");
						}
						
						if ($oDiff->isEmpty() 
							|| !AnwXml::xmlAreTranslatableAncestors($oRootNode, $oContentField))//TODO performances
						{
							AnwDebug::log("//edited : empty/untranslatable content, copying value but keeping it as translated");
							
							//copy new value, but keep it as translated
							$oNewNode = AnwXml::xmlReplaceTextNode($oChild, $oNodeRef);
						}
						else if ($oDiff->getDiffDeleted()->getMovedDiff())
						{
							//special - consider it as an added node : set new value & mark this as untranslated
							AnwDebug::log("//edited : special case, considering as added node");
							
							$oNewNode = AnwXml::xmlReplaceTextNode($oChild, $oNodeRef);
							$oNewNode = AnwXml::xmlSetTextUntranslated($oNewNode, true);
						}
						else
						{
							//content has really changed, mark it as untranslated if translatable (contentfield check)
							
							//current node is translatable... set new value & mark this as untranslated
							if ($oChild->nodeValue != $oNodeRef->nodeValue)
							{
								//we need to check if text has really changed, or if it's just some layout (spaces, breaklines...) which changed the nodeValue...
								$oNodeRefOld = $oDiff->getDiffDeleted()->getNode();
								if (AnwXml::xmlTrimTextLayout($oNodeRefOld->nodeValue) == AnwXml::xmlTrimTextLayout($oNodeRef->nodeValue))
								{
									AnwDebug::log("//edited : case 3.1, only textLayout has changed");
									$sOldValue = $oChild->nodeValue;
									//content has not really changed, we just added/removed a space, tab, line break before/after the text value
									//ex: "blah blah\n" --> "blah blah\n\n"
									//we silently apply the new text layout, without turning it untranslated
									$oChild->nodeValue = AnwXml::xmlPreserveTextLayout($oChild->nodeValue, $oNodeRef->nodeValue);
								}
								else
								{
									AnwDebug::log("//edited : case 3.2, content has really changed");
									$oNewNode = AnwXml::xmlReplaceTextNode($oChild, $oNodeRef);
									$oNewNode = AnwXml::xmlSetTextUntranslated($oNewNode, true);
								}
							}
							
							/*
							//current node is translatable... keep CURRENT value and mark it as untranslated
							$oNewNode = $oChild;
							if ($oNewNode->nodeValue != $oNodeRef->nodeValue)
							{
								$oNewNode = AnwXml::xmlSetTextUntranslated($oNewNode, true);
							}*/
						}
					}
					break;
					
				case AnwDiff::TYPE_DIFFS:
					$oNodeDiffRef = $oDiff->getNodeRootNew();
					AnwDebug::log(" * DIFFS : ".htmlspecialchars(AnwUtils::xmlDumpNode($oNodeDiffRef))." == ".htmlentities(AnwUtils::xmlDumpNode($oChild)));
					
					//update attributes
					AnwXml::xmlCopyNodeAttributes($oNodeDiffRef, $oChild);
					
					//recursive call
					self::applyDiffsToTranslation($oChild, $oDiff, $oContentField);
					break;
					
				default:
					AnwDebug::log("ERROR - Unknown DiffType :".$sDiffType);
			}
			
			//just to prevent erros...
			unset($oNewNode); 
			unset($oNodeRef);
			
			if (!$bSkipIncrement) 
			{
				//AnwDebug::logdetail(" * Loop i++");
				$i++;
				//AnwDebug::logdetail("//step dump_end : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
			}
			//AnwDebug::log("Loop[$i] applydiffs intermediate result : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		
		}
		//AnwDebug::log("applydiffs result : ".htmlentities(AnwUtils::xmlDumpNode($oRootNode)));
		return $oRootNode;
	}

	
}
?>