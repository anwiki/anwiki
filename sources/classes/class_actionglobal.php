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
 * An ActionGlobal is an action which is not related to a specific page.
 * @package Anwiki
 * @version $Id: class_actionglobal.php 298 2010-09-12 11:02:25Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
abstract class AnwActionGlobal extends AnwAction
{
	protected final function initializeAction()
	{
		//check permissions
		$this->checkActionAllowed();
	}
	
	//overridable
	protected function checkActionAllowed()
	{
		if (!AnwCurrentSession::isActionGlobalAllowed($this->getName()))
		{
			throw new AnwAclException("You are not allowed to execute this action");
		}
	}
	
	protected function createNavEntry($sTitle=null, $sImg=null)
	{
		if ($sTitle===null)
		{
			$sTitle = $this->t_("naventry");
		}
		if ($sImg===null)
		{
			$sImg = $this->getMyComponentUrlStaticDefault()."action_".$this->getName().".gif";
		}
		return new AnwGlobalNavEntry($this, $sTitle, $sImg);
	}
	
	protected function createManagementGlobalNavEntry($sTitle=null, $sDescription=null)
	{
		if ($sTitle===null)
		{
			$sTitle = $this->t_("naventry");
		}
		if ($sDescription===null)
		{
			$sDescription = $this->t_("naventry_desc");
		}
		return new AnwManagementGlobalNavEntry($this, $sTitle, $sDescription);
	}
	
	protected function linkMe($asParams=array())
	{
		return AnwUtils::alink($this->getName(),$asParams);
	}	
}
?>