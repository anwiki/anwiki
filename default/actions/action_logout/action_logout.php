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
 * Logging out.
 * @package Anwiki
 * @version $Id: action_logout.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_logout extends AnwActionGlobal implements AnwPublicAction, AnwAlwaysEnabledAction
{
	function run()
	{
		AnwCurrentSession::logout();
		$sUrlRedirect = AnwEnv::_GET("redirect", false);
		$this->redirectInfo($sUrlRedirect, $this->t_("t_loggedout"), $this->t_("p_loggedout"));
	}
}

?>