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
 * DiffMoved, a node was moved but unchanged in the XML document.
 * @package Anwiki
 * @version $Id: class_diffmoved.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwDiffMoved extends AnwDiff
{
	private $oMovedNode;
	private $sTextLayoutReferenceValue=false;
	
	function __construct()
	{
		
	}
	
	function hidePhpCode()
	{
		if ($this->oMovedNode) $this->oMovedNode = AnwXml::xmlHidePhpCode($this->oMovedNode);
	}
	
	function showPhpCode()
	{
		if ($this->oMovedNode) $this->oMovedNode = AnwXml::xmlShowPhpCode($this->oMovedNode);
	}
	
	function clearMovedNodes()
	{
		$this->oMovedNode = null;
	}
	
	function setDiffDeleted($oDiffDeleted)
	{
		$this->oDiffDeleted = $oDiffDeleted;
	}
	
	function getDiffDeleted()
	{
		return $this->oDiffDeleted; //warning : from original document
	}
	
	function setMovedNode($oNode)
	{
		$this->oMovedNode = $oNode;
	}
	
	function getMovedNode()
	{
		return $this->oMovedNode;
	}
	
	function setTextLayoutReferenceValue($sNodeValue)
	{
		$this->sTextLayoutReferenceValue = $sNodeValue;
	}
	
	function hasTextLayoutChanged()
	{
		return $this->sTextLayoutReferenceValue ? true : false;
	}
	
	function getTextLayoutReferenceValue()
	{
		return $this->sTextLayoutReferenceValue;
	}
	
	
	function debugHtml()
	{
		$sHtml = "<li>AnwDiffMoved : ";
		if ($this->getMovedNode())
		{
			$sHtml .= htmlentities(AnwUtils::xmlDumpNode($this->getMovedNode()));
		}
		else
		{
			$sHtml .= "***NOT SET***";
		}
		$sHtml .= "(was ".htmlentities(AnwUtils::xmlDumpNode($this->getDiffDeleted()->getNode())).")";
		$sHtml .= "</li>";
		return $sHtml;
	}
}
?>