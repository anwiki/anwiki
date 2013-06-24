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
 * Loads Anwiki environment, overriden with custom settings.
 * @package Anwiki
 * @version $Id: index.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

//define the default file to load
define('ANWFILE_INC', 'anwiki.inc.php');

$sRootPath = dirname(__FILE__).'/';

//load Anwiki environment
if (file_exists($sRootPath."_anwiki-override.inc.php"))
{
	require_once($sRootPath."_anwiki-override.inc.php");
}
else
{		
	require_once($sRootPath.ANWFILE_INC);
}
?>