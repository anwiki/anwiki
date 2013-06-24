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
 * Contentfields interfaces for components settings.
 * They are separated from ContentFieldSettings for performances improvements (avoids loading ContentFieldSettings in some cases).
 * @package Anwiki
 * @version $Id: class_contentfield_settings_interface.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwISettings_mysqlconnexion
{
	const FIELD_USER = "user";
	const FIELD_PASSWORD = "password";
	const FIELD_DATABASE = "database";
	const FIELD_HOST = "host";
	const FIELD_PREFIX = "prefix";
}

?>