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
 * DiffEdited, a node was edited in the XML document.
 * @package Anwiki
 * @version $Id: class_diffedited.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwDiffEdited extends AnwDiff
{
	private $oDiffAdded;
	private $oDiffDeleted;
	
	function __construct($oDiffAdded, $oDiffDeleted)
	{
		$this->oDiffAdded = $oDiffAdded;
		$this->oDiffDeleted = $oDiffDeleted;
	}
	
	function hidePhpCode()
	{
		$oNodeAdded = AnwXml::xmlHidePhpCode($this->oDiffAdded->getNode());
		$this->oDiffAdded->setNode($oNodeAdded);
		$oNodeDeleted = AnwXml::xmlHidePhpCode($this->oDiffDeleted->getNode());
		$this->oDiffDeleted->setNode($oNodeDeleted);
	}
	
	function showPhpCode()
	{
		$oNodeAdded = $this->oDiffAdded->getNode();
		$this->oDiffAdded->setNode( AnwXml::xmlShowPhpCode($oNodeAdded) );
		$oNodeDeleted = $this->oDiffDeleted->getNode();
		$this->oDiffDeleted->setNode( AnwXml::xmlShowPhpCode($oNodeDeleted) );
	}
	
	function getDiffAdded()
	{
		return $this->oDiffAdded;
	}
	
	function getDiffDeleted()
	{
		return $this->oDiffDeleted;
	}
	
	function isEmpty()
	{
		return (trim($this->getDiffAdded()->getNode()->nodeValue) == "");
	}
	
	function debugHtml()
	{
		$sHtml = "<li>AnwDiffEdited : ".($this->isEmpty() ? " (empty)":"")."<ul>";
		$sHtml .= $this->getDiffAdded()->debugHtml();
		$sHtml .= $this->getDiffDeleted()->debugHtml();
		//$sHtml .= "<li> edit added : ".htmlentities(AnwUtils::xmlDumpNode($this->getDiffAdded()->getNode()))."</li>";
		//$sHtml .= "<li> edit deleted : ".htmlentities(AnwUtils::xmlDumpNode($this->getDiffDeleted()->getNode()))."</li>";
		$sHtml .= "</ul></li>";
		return $sHtml;
	}
}
?>