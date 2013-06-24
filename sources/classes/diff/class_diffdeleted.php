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
 * DiffDeleted definition, a node was deleted from the XML document.
 * @package Anwiki
 * @version $Id: class_diffdeleted.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwDiffDeleted extends AnwDiff
{
	private $oNode;
	private $oDiffDeletedParent;
	private $oMovedDiff;
	private $aoSubDeletedDiffs;
	private $bHasMovedDiff = false;
	private $bHasSubMovedDiff = false;
	
	function __construct($oNode, $oDiffParent=null)
	{
		$this->oNode = $oNode;
		$this->oDiffDeletedParent = $oDiffParent;
	}
	
	function hidePhpCode()
	{
		$this->oNode = AnwXml::xmlHidePhpCode($this->oNode);
	}
	
	function showPhpCode()
	{
		$this->oNode = AnwXml::xmlShowPhpCode($this->oNode);
	}
	
	function hasSubDeletedDiffs()
	{
		return (count($this->getSubDeletedDiffs()) > 0);
	}
	
	function getNode()
	{
		return $this->oNode;
	}
	
	function setNode($oNode)
	{
		$this->oNode = $oNode;
	}
	
	function setMovedDiff($oDiff)
	{
		$oDiff->setDiffDeleted($this);
		$this->oMovedDiff = $oDiff;
		$this->bHasMovedDiff = true;
		
		$this->notifyAncestorsHasSubMovedDiff();
	}
	
	private function notifyAncestorsHasSubMovedDiff()
	{
		$oDiffParent = $this->oDiffDeletedParent;
		if ($oDiffParent)
		{
			$oDiffParent->bHasSubMovedDiff = true;
			$oDiffParent->notifyAncestorsHasSubMovedDiff();
		}
	}
	
	//does this diff has moved subdeleted diffs?
	function hasSubMovedDiff()
	{
		return $this->bHasSubMovedDiff;
	}
	
	//is this diff a moved diff?
	function hasMovedDiff()
	{
		return $this->bHasMovedDiff;
	}
	
	function getMovedDiff()
	{
		return $this->oMovedDiff;
	}
	
	function getSubDeletedDiffs()
	{
		if (!$this->aoSubDeletedDiffs)
		{
			$this->aoSubDeletedDiffs = array();
			$this->findSubDeletedDiffs();
		}
		return $this->aoSubDeletedDiffs;
	}
	
	private function findSubDeletedDiffs()
	{
		$oNode = $this->oNode;
		if ( AnwXml::xmlIsTextNode($oNode) || AnwXml::xmlIsPhpNode($oNode) || AnwXml::xmlIsCommentNode($oNode) )
		{
			return;
		}
		
		$oChilds = $oNode->childNodes;
		if ($oChilds)
		{
			$this->aoSubDeletedDiffs = array();
			for ($i = 0; $i < $oChilds->length; $i ++)
			{
				$oChild = $oChilds->item($i);
				$oSubDiff = new AnwDiffDeleted($oChild, $this);
				$this->aoSubDeletedDiffs[] = $oSubDiff;
			}
		}
	}
	
	function debugHtml()
	{
		$sHtml = "<li>AnwDiffDeleted : ";
		if ($this->getMovedDiff()) $sHtml .= "[MOVED]";
		if ($this->hasSubMovedDiff()) $sHtml .= "(*) ";
		$sHtml .= htmlentities(AnwUtils::xmlDumpNode($this->getNode()));
		if ($this->hasSubDeletedDiffs())
		{
			$sHtml .= '<ul>Subdeleted:';
			$aoSubDiffs = $this->getSubDeletedDiffs();
			foreach ($aoSubDiffs as $oSubDiff)
			{
				$sHtml .= $oSubDiff->debugHtml();
			}
			$sHtml .= '</ul>';
		}
		$sHtml .= "</li>";
		return $sHtml;
	}
}
?>