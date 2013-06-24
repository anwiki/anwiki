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
 * Interfaces for ContentFieldPage.
 * They are separated from ContentFieldPage for performances improvements (avoids loading ContentFieldPage in some cases).
 * @package Anwiki
 * @version $Id: class_contentfield_page_interface.php 330 2010-09-19 17:37:10Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwIPage_link
{
	const FIELD_TITLE = "title";
	const FIELD_URL = "url";
	const FIELD_TARGET = "target";
	
	const TARGET_SELF = "_self";
	const TARGET_BLANK = "_blank";
	
}

interface AnwIPage_daterange
{
	const FIELD_BEGIN = "begin";
	const FIELD_END = "end";
	
	const PUB_BEGIN = "begin";
	const PUB_END = "end";
}

?>