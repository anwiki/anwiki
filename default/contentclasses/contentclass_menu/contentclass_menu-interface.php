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
 * Interfaces for content-class menu.
 * They are separated from ContentClassPage for performances improvements.
 * @package Anwiki
 * @version $Id: contentclass_menu-interface.php 161 2009-02-28 13:42:09Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwIContentClassPageDefault_menu
{
	const FIELD_TITLE = "title";
	const FIELD_ITEMS = "items";
	
	/**
	 * All mainitems with all subitems.
	 */
	const RENDER_MODE_FULL_MENU = "FULL_MENU";
	/**
	 * All main items, but only subitems for active main item.
	 */
	const RENDER_MODE_ALL_MAINITEMS_ACTIVE_SUBITEMS = "ALL_MAINITEMS_ACTIVE_SUBITEMS";
	/**
	 * Get all main items, but no subitem.
	 */
	const RENDER_MODE_MAIN_ITEMS = "MAIN_ITEMS";
	/**
	 * Get active subitems only.
	 */
	const RENDER_MODE_SUBITEMS_FROM_ACTIVE_MAINITEM = "SUBITEMS_FROM_ACTIVE_MAINITEM";
	
}

interface AnwIContentFieldPage_menu_menuItem
{
	const FIELD_MAINLINK = "mainlink";
	const FIELD_SUBITEMS = "subitems";
	const FIELD_URLMATCHES = "urlmatches";
}

interface AnwIContentFieldPage_menu_menuSubItem
{
	const FIELD_LINK = "link";
	const FIELD_URLMATCHES = "urlmatches";
	
}

?>