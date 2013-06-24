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
 * Anwiki main script.
 * @package Anwiki
 * @version $Id: index.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

try
{
	//should we load minimal mode?
	if (in_array(@$_GET['a'], array('install')) || isset($_GET['anwiki_mode_minimal']))
	{
		define('ANWIKI_MODE_MINIMAL', true);
	}
	
	require_once("engine.inc.php");
	
	//install redirect
	if (!file_exists(ANWIKI_INSTALL_LOCK) && AnwEnv::_GET('a')!='install')
	{
		$sUrlInstall = AnwEnv::_SERVER('SCRIPT_URI').'?a=install';
		header("Location: ".$sUrlInstall);
		print '<a href="'.AnwUtils::xQuote($sUrlInstall).'">'.AnwUtils::xQuote($sUrlInstall).'</a>';
		exit;
	}
	
	AnwDebug::startbench("preparing action", true);
	
	//load action code
	try
	{
		$sAction = strtolower( AnwEnv::_GET(AnwAction::GET_ACTIONNAME, "view") );
		$oAction = AnwAction::loadComponent($sAction);
	}
	catch(AnwException $e)
	{
		$sAction = "view";
		$oAction = AnwAction::loadComponent($sAction);
	}
	
	//security check
	if (ANWIKI_MODE_MINIMAL && !($oAction instanceof AnwActionMinimal))
	{
		AnwDieCriticalError("Unauthorized mode / bad URL");
	}
	
	$oAction->setAsCurrentAction();
	AnwDebug::log("Action : ".$sAction);
	AnwDebug::stopbench("preparing action");
	//run
	$oAction->runAndOutput();
}
catch(AnwException $e)
{
	errorApp($e);
}
?>