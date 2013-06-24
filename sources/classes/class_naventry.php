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
 * Anwiki's modular navigation system for actions.
 * @package Anwiki
 * @version $Id: class_naventry.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
abstract class AnwNavEntry
{
	private $sTitle;
	private $sLink;
	private $sImg;
	
	function __construct($sTitle, $sLink, $sImg)
	{
		$this->sTitle = $sTitle;
		$this->sLink = $sLink;
		$this->sImg = $sImg;
	}
	
	function getTitle()
	{
		return $this->sTitle;
	}
	function getLink()
	{
		return $this->sLink;
	}
	function getImg()
	{
		return $this->sImg;
	}
	
	//overridable
	function isActionAllowed()
	{
		return true;
	}
}

class AnwGlobalNavEntry extends AnwNavEntry
{
	private $sActionName;
	
	function __construct($oActionGlobal, $sTitle, $sImg)
	{
		parent::__construct($sTitle, AnwUtils::alink($oActionGlobal->getName()), $sImg);
		
		$this->sActionName = $oActionGlobal->getName();
	}
	
	function isActionAllowed()
	{
		return AnwCurrentSession::isActionGlobalAllowed($this->sActionName);
	}
	
	function getActionName()
	{
		return $this->sActionName;
	}
}

class AnwManagementGlobalNavEntry extends AnwGlobalNavEntry
{
	private $sDescription;
	
	function __construct($oAction, $sTitle, $sDescription)
	{
		parent::__construct($oAction, $sTitle, "");
		$this->sDescription = $sDescription;
	}
	
	function getDescription()
	{
		return $this->sDescription;
	}
}

class AnwPageNavEntry extends AnwNavEntry
{
	private $sActionName;
	
	function __construct($oActionPage, $sTitle, $sImg)
	{
		parent::__construct($sTitle, "", $sImg);
		
		$this->sActionName = $oActionPage->getName();
	}
	
	function isActionPageAllowed($oCurrentPage)
	{
		return $oCurrentPage->isActionAllowed($this->sActionName);
	}
	
	function getActionName()
	{
		return $this->sActionName;
	}
	
	function getPageLink($oCurrentPage)
	{
		return AnwUtils::link($oCurrentPage, $this->sActionName);
	}
}

?>