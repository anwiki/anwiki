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
 * DiffAdded definition, a node was added to the XML document.
 * @package Anwiki
 * @version $Id: class_diffadded.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwDiffAdded extends AnwDiff
{
	private $oNode;
	private $oDiffAddedParent;
	private $aoSubAddedDiffs;
	private $oMovedNode;
	private $bHasSubMovedNode = false;
	
	
	function __construct($oNode, $oDiffParent=null)
	{
		$this->oNode = $oNode;
		$this->oDiffAddedParent = $oDiffParent;
	}
	
	function hidePhpCode()
	{
		$this->oNode = AnwXml::xmlHidePhpCode($this->oNode);
	}
	
	function showPhpCode()
	{
		$this->oNode = AnwXml::xmlShowPhpCode($this->oNode);
	}
	
	function clearMovedNodes()
	{
		$this->oMovedNode = null;
		if ($this->hasSubAddedDiffs())
		{
			foreach ($this->getSubAddedDiffs() as $oSubDiffAdded)
			{
				$oSubDiffAdded->clearMovedNodes();
			}
		}
	}
	
	function hasSubAddedDiffs()
	{
		return ( count($this->getSubAddedDiffs()) > 0 );
	}
	
	function getNode()
	{
		return $this->oNode;
	}
	
	function setNode($oNode)
	{
		$this->oNode = $oNode;
	}
	
	function getSubAddedDiffs()
	{
		if (!$this->aoSubAddedDiffs)
		{
			$this->aoSubAddedDiffs = array();
			$this->findSubAddedDiffs();
		}
		return $this->aoSubAddedDiffs;
	}
	
	function getAllDiffs()
	{
		return $this->getSubAddedDiffs(); //needed for compatibility with applyDiffsToTranslation()
	}
	
	function setMovedNode($oNode)
	{
		$this->oMovedNode = $oNode;
		//$this->refreshNode();
	}
	
	function getMovedNode()
	{
		return $this->oMovedNode;
	}
	
	function getDiffAddedParent()
	{
		return $this->oDiffAddedParent;
	}
	
	function hasSubMovedNode()
	{
		return $this->bHasSubMovedNode;
	}
	
	/*
	//TODO : recursive ? pb untranslated pas a jour quand node modifi�e...
	function getNodeWithTranslateTags($oRootNode)//TODO......
	{
		$oNode = $this->oNode;
		$oSubChilds = $oNode->childNodes;
		if ($this->hasSubAddedDiffs())
		{
			//refresh node if a subnode has been linked to a moved node, set it to the moved node's value
			
			AnwDebug::log("getNodeWithTranslateTags : hasSubdiffs");
			$i = 0;
			
			foreach ($this->aoSubAddedDiffs as $oSubDiffAdded)
			{
				$oSubChild = $oSubChilds->item($i);
				
				if ($oSubDiffAdded->getMovedNode())
				{
					//a child has a moved node
					$oNewNode = $oNode->ownerDocument->importNode( $oSubDiffAdded->getMovedNode(), true );
					$oNode->replaceChild($oNewNode, $oSubChild);
					AnwDebug::log("getNodeWithTranslateTags : movedNode : ".AnwUtils::xmlDumpNode($oNode));
				}
				else
				{
					AnwXml::xmlAddTranslateTags($oSubChild, $oNode);
					AnwDebug::log("getNodeWithTranslateTags : NO movedNode : ".AnwUtils::xmlDumpNode($oSubChild));
				}
				$i++;
			}
		}
		else
		{
			AnwXml::xmlAddTranslateTags($oNode, $oRootNode);
			AnwDebug::log("getNodeWithTranslateTags : NO subdiffs : ".AnwUtils::xmlDumpNode($oNode));
		}
		return $oNode;
	}
	*/
	
	//!recursive
	function getNodeWithTranslateTags($bMarkAsUntranslated, $oContentField)
	{
		$oNode = null;
		if ($this->getMovedNode())
		{
			//return moved node and don't touch it's translation flags
			//AnwDebug::log("getNodeWithTranslateTags : NO subdiffs/movedNode");
			$oNode = $this->getMovedNode();
		}
		else
		{
			$oNode = $this->oNode;
			
			//can't remember why did I put that here?
			//if (AnwXml::xmlIsTranslatableParent($oNode))
			//{
			
			//we don't need to check ancestors, it was already checked by applyDiffsToTranslation
			if ($bMarkAsUntranslated == true && !AnwXml::xmlIsTranslatableParent($oNode))
			{
				//if we enter in a <fix> node, don't mark childs as untranslated !
				$bMarkAsUntranslated = false;
			}
			
			if ($this->hasSubAddedDiffs())
			{
				//if a child of $node has been linked to a moved node, replace this subnode by the related moved node
				
				$i = 0;
				$oSubChilds = $oNode->childNodes;
				foreach ($this->aoSubAddedDiffs as $oSubDiffAdded)
				{
					$oSubChild = $oSubChilds->item($i);
					
					//AnwDebug::log("getNodeWithTranslateTags : subdiffs --->");
					
					if ($oSubDiffAdded->getMovedNode())
					{
						//let's replace this child by the moved node
						$oNewNode = $oSubDiffAdded->getMovedNode();
						$oNewNode = $oNode->ownerDocument->importNode( $oNewNode, true );
						$oNode->replaceChild($oNewNode, $oSubChild);
					}
					else
					{
						//automatically replaced?
						$oNewNode = $oSubDiffAdded->getNodeWithTranslateTags($bMarkAsUntranslated, $oContentField);
					}
					//AnwDebug::log("getNodeWithTranslateTags : <--- subdiffs");
					
					$i++;
				}
			}
			else
			{
				//AnwDebug::log("getNodeWithTranslateTags : NO subdiffs/NO MovedNode -> addTranslateTags");
				if ($bMarkAsUntranslated)
				{
					//$oNode = AnwXml::xmlAddTranslateTags($oNode);
					$oNode = AnwXml::xmlSetContentFieldUntranslated($oNode, $oContentField);
				}
			}
			//}
		}
		//AnwDebug::log("getNodeWithTranslateTags result : ".htmlentities(AnwUtils::xmlDumpNode($oNode)));
		return $oNode;
	}
	
	private function findSubAddedDiffs()
	{	
		$oNode = $this->oNode;
		if ( AnwXml::xmlIsTextNode($oNode) || AnwXml::xmlIsPhpNode($oNode) || AnwXml::xmlIsCommentNode($oNode) )
		{
			return;
		}
		
		$oChilds = $oNode->childNodes;
		if ($oChilds)
		{
			$this->aoSubAddedDiffs = array();
			for ($i = 0; $i < $oChilds->length; $i ++)
			{
				$oChild = $oChilds->item($i);
				$oSubDiff = new AnwDiffAdded($oChild, $this);
				$this->aoSubAddedDiffs[] = $oSubDiff;
			}
		}
	}
	
	//function called on subdiffs
	function replaceBy($oSubMovedDiff)
	{
		$oDiffParent = $this->getDiffAddedParent();
		if (!$oDiffParent) throw new AnwUnexpectedException("AnwDiffAdded->replaceBy() called on non-subdiff");
		$oDiffParent->replaceSubAddedDiffByMovedDiff($this, $oSubMovedDiff);	
	}
	
	private function replaceSubAddedDiffByMovedDiff($oSubAddedDiff, $oSubMovedDiff)
	{
		$bFound = false;
		foreach ($this->aoSubAddedDiffs as $i=>$oDiff)
		{
			if ($oDiff === $oSubAddedDiff)
			{
				$this->aoSubAddedDiffs[$i] = $oSubMovedDiff;
				//AnwDebug::logdetail("->replaceSubAddedDiffBy : done");
				$this->setHasSubMovedNodes();
				$bFound = true;
			}
		}
		
		if (!$bFound)
		{
			throw new AnwUnexpectedException("replaceSubAddedDiffByMovedNode : subdiff not found");
		}
	}
	
	private function setHasSubMovedNodes()
	{
		//set
		$this->bHasSubMovedNode = true;
		
		//notify ancestors
		$oDiffParent = $this->getDiffAddedParent();
		if ($oDiffParent)
		{
			$oDiffParent->setHasSubMovedNodes();
		}
	}
	
	private function rebuildToAnwDiffs()
	{
		
	}
	
	function debugHtml()
	{
		$sHtml = "<li>AnwDiffAdded : ";
		if ($this->getMovedNode()) $sHtml .= "[MOVED???]";
		if ($this->hasSubMovedNode())
		{
			$sHtml .= "[SUBMOVED - &lt;".$this->getNode()->nodeName."&gt;]";
			$sHtml .= "<ul>";
			foreach ($this->aoSubAddedDiffs as $oSubDiff)
			{
				$sHtml .= $oSubDiff->debugHtml();
			} 
			$sHtml .= "</ul>";
		}
		else
		{
			$sHtml .= htmlentities(AnwUtils::xmlDumpNode($this->getNode()));
		}
		$sHtml .= "</li>";
		return $sHtml;
	}
}
?>