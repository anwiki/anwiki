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
 * An ActioMinimal is an action which can be executed when almost everything is down (no configured drivers).
 * @package Anwiki
 * @version $Id: class_actionminimal.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
abstract class AnwActionMinimal extends AnwAction
{
	protected final function initializeAction()
	{
		//dont check permissions, but require minimal mode
		if (!ANWIKI_MODE_MINIMAL)
		{
			AnwDieCriticalError("not in minimal mode");
		}
	}
	
	function output($bEmergencyError=false)
	{
		//render head
		$this->renderHeadForOutput();
		
		//minimal template
		$this->out = $this->tpl()->globalBodyMinimal($this->out);
		
		$this->out = $this->tpl()->globalHtml(
			self::g_("local_html_lang", array(), self::getActionLang()),		//content lang
			"",//self::g_("local_html_dir", array(), self::getActionLang()/*AnwCurrentSession::getLang()*/), //session lang
			$this->title, 
			$this->getHead(),
			$this->out
		);
		$this->printOutput();
	}
	
	
}
?>