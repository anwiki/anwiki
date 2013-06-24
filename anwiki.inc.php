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
 * Loads Anwiki environment.
 * @package Anwiki
 * @version $Id: anwiki.inc.php 352 2010-12-12 22:37:31Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

if ( version_compare( phpversion(), '5.0' ) < 0 )
{
    print "<h1>Unsupported PHP version: " . phpversion() . "</h1>";
    print "<p>Sorry, Anwiki requires PHP5 or later.</p>";
    exit;
}

anwdefine('ANWIKI_VERSION_NAME', '0.2.5');
anwdefine('ANWIKI_VERSION_ID', 7);
anwdefine('ANWIKI_DEVEL', false);

anwInitErrorReporting();

//initialize PHP settings
date_default_timezone_set('GMT');

// standard mode by default
anwdefine('ANWIKI_MODE_BATCH', false);
anwdefine('ANWIKI_MODE_MINIMAL', false);

//disable PHP eval() function by default
anwdefine('ANWIKI_PHPEVAL_ENABLED', false);

anwdefine('ANWDIR_DEFAULT', 'default/');
anwdefine('ANWDIR_ADDONS', '_addons/');
anwdefine('ANWDIR_OVERRIDE', '_override/');

anwdefine('ANWDIR_DEFAULT_STATIC', 'default-static/');
anwdefine('ANWDIR_ADDONS_STATIC', '_addons-static/');
anwdefine('ANWDIR_OVERRIDE_STATIC', '_override-static/');

anwdefine('ANWPATH_ROOT_SETUP', dirname(__FILE__).'/');
anwdefine('ANWPATH_ROOT_SHARED', dirname(__FILE__).'/');

anwdefine('ANWPATH_DEFAULT', ANWPATH_ROOT_SHARED.ANWDIR_DEFAULT);
anwdefine('ANWPATH_ADDONS', ANWPATH_ROOT_SETUP.ANWDIR_ADDONS);
anwdefine('ANWPATH_OVERRIDE', ANWPATH_ROOT_SETUP.ANWDIR_OVERRIDE);

anwdefine('ANWPATH_DEFAULT_STATIC', ANWPATH_ROOT_SHARED.ANWDIR_DEFAULT_STATIC);
anwdefine('ANWPATH_ADDONS_STATIC', ANWPATH_ROOT_SETUP.ANWDIR_ADDONS_STATIC);
anwdefine('ANWPATH_OVERRIDE_STATIC', ANWPATH_ROOT_SETUP.ANWDIR_OVERRIDE_STATIC);

anwdefine('ANWPATH_SOURCES', ANWPATH_ROOT_SHARED.'sources/');
anwdefine('ANWPATH_CLASSES', ANWPATH_SOURCES.'classes/');
anwdefine('ANWPATH_CLASSES_ABSTRACTION', ANWPATH_CLASSES.'abstraction/');
anwdefine('ANWPATH_LIB', ANWPATH_SOURCES.'lib/');
anwdefine('ANWPATH_WRITABLE', ANWPATH_ROOT_SETUP.'_writable/');
anwdefine('ANWPATH_CACHE', ANWPATH_WRITABLE.'cache/');
anwdefine('ANWPATH_CACHECONTENT', ANWPATH_CACHE.'content/');
anwdefine('ANWPATH_CACHESYSTEM', ANWPATH_CACHE.'system/');
anwdefine('ANWPATH_CACHECONFIG', ANWPATH_CACHE.'config/');
anwdefine('ANWPATH_LOGS', ANWPATH_WRITABLE.'logs/');
anwdefine('ANWPATH_ERRORLOG', ANWPATH_LOGS.'errors.log');
anwdefine('ANWPATH_TMP', ANWPATH_WRITABLE.'tmp/');
anwdefine('ANWPATH_STATUS', ANWPATH_WRITABLE.'status/');

anwdefine('ANWDIR_ACTIONS', 'actions/');
anwdefine('ANWDIR_ACTIONSPAGE', ANWDIR_ACTIONS.'actionspage/');
anwdefine('ANWDIR_ACTIONSGLOBAL', ANWDIR_ACTIONS.'actionsglobal/');
anwdefine('ANWDIR_CONFIG', 'config/');
anwdefine('ANWDIR_CONTENTCLASSES', 'contentclasses/');
anwdefine('ANWDIR_LANG', 'lang/');
anwdefine('ANWDIR_GLOBAL', 'global/');
anwdefine('ANWDIR_PLUGINS', 'plugins/');
anwdefine('ANWDIR_TEMPLATES', 'templates/');
anwdefine('ANWDIR_CSS', 'css/');
anwdefine('ANWDIR_IMG', 'img/');
anwdefine('ANWDIR_JS', 'js/');
anwdefine('ANWDIR_DRIVERS', 'drivers/');
anwdefine('ANWDIR_ACLSDRIVERS', ANWDIR_DRIVERS.'aclsdrivers/');
anwdefine('ANWDIR_SESSIONSDRIVERS', ANWDIR_DRIVERS.'sessionsdrivers/');
anwdefine('ANWDIR_STORAGEDRIVERS', ANWDIR_DRIVERS.'storagedrivers/');
anwdefine('ANWDIR_USERSDRIVERS', ANWDIR_DRIVERS.'usersdrivers/');

anwdefine('ANWIKI_WEBSITE', 'http://www.anwiki.com/');
anwdefine('ANWIKI_WEBPING', 'http://ping.anwiki.com/');
anwdefine('ANWIKI_INSTALL_LOCK', ANWPATH_STATUS.'install.lock.php');
anwdefine('ANWIKI_INSTALL_STATUS', ANWPATH_STATUS.'install.status.php');

spl_autoload_register('anwiki__autoload');

loadAppSafe (ANWPATH_CLASSES."class_debug.php");
AnwDebug::startBench("GLOBAL", true);
AnwDebug::startBench("loading files", true);
loadAppSafe (ANWPATH_CLASSES."class_env.php");
loadAppSafe (ANWPATH_CLASSES."class_utils.php");
loadAppSafe (ANWPATH_CLASSES."class_exception.php");
loadApp (ANWPATH_CLASSES."class_cache.php");
loadApp (ANWPATH_CLASSES."class_component.php");
loadApp (ANWPATH_CLASSES."class_i18n.php");

loadApp (ANWPATH_CLASSES."_drivers.php");

loadApp (ANWPATH_CLASSES."class_acls.php");

loadApp (ANWPATH_CLASSES."plugin/class_plugin.php");
loadApp (ANWPATH_CLASSES."plugin/class_plugins.php");

loadApp (ANWPATH_CLASSES."session/class_session.php");
loadApp (ANWPATH_CLASSES."session/class_sessions.php");

loadApp (ANWPATH_CLASSES."class_storage.php");

loadApp (ANWPATH_CLASSES."user/class_user.php");
loadApp (ANWPATH_CLASSES."user/class_users.php");

loadApp (ANWPATH_CLASSES."class_action.php");
loadApp (ANWPATH_CLASSES."class_page.php");
loadApp (ANWPATH_CLASSES."class_pagegroup.php");
loadApp (ANWPATH_CLASSES."class_naventry.php");

//require global template
loadApp (ANWPATH_DEFAULT.AnwComponent::getGlobalComponentFullDir()."global.tpl.php");
loadApp (ANWPATH_OVERRIDE.AnwComponent::getGlobalComponentFullDir()."global.tpl.php");

//are we in minimal/batch/standard mode?
if (ANWIKI_MODE_BATCH)
{
	loadApp (ANWPATH_CLASSES."session/class_currentsession-batch.php");
}
else if (ANWIKI_MODE_MINIMAL)
{
	loadApp (ANWPATH_CLASSES."session/class_currentsession-minimal.php");
}
else
{
	loadApp (ANWPATH_CLASSES."session/class_currentsession.php");
}

AnwDebug::setTraceEnabled(AnwComponent::globalCfgTraceEnabled());

AnwEnv::init();

AnwDebug::stopBench("loading files");

/**
 * We use it for more flexibility.
 * That way, you can previously define some constants for your needs depending on your hosting strategy.
 */
function anwdefine($sConstant, $sValue)
{
	if (!defined($sConstant)) define($sConstant, $sValue);
}


function anwInitErrorReporting()
{
	if (ANWIKI_DEVEL)
	{
		error_reporting(E_ALL|E_STRICT);
	}
	else
	{
		error_reporting(E_ALL^(E_NOTICE | E_WARNING));
	}
}

function anwiki__autoload($sClassName)
{
	static $asClasses;
	
	if (!$asClasses)
	{
		$asClasses = array();
		
		$asClasses['AnwMysql'] = ANWPATH_CLASSES_ABSTRACTION."class_mysql.php";
		
		$asClasses['AnwOutput'] = ANWPATH_CLASSES."class_output.php";
		$asClasses['AnwOutputHtml'] = ANWPATH_CLASSES."class_output.php";
		$asClasses['AnwParser'] = ANWPATH_CLASSES."class_parser.php";
		
		$asClasses['AnwAutoSync'] = ANWPATH_CLASSES."diff/class_autosync.php";
		$asClasses['AnwXml'] = ANWPATH_CLASSES."class_xml.php";
		$asClasses['AnwDiffAdded'] = ANWPATH_CLASSES."diff/class_diffadded.php";
		$asClasses['AnwDiffDeleted'] = ANWPATH_CLASSES."diff/class_diffdeleted.php";
		$asClasses['AnwDiffEdited'] = ANWPATH_CLASSES."diff/class_diffedited.php";
		$asClasses['AnwDiffKept'] = ANWPATH_CLASSES."diff/class_diffkept.php";
		$asClasses['AnwDiffMoved'] = ANWPATH_CLASSES."diff/class_diffmoved.php";
		
		$asClasses['AnwDiff'] = ANWPATH_CLASSES."diff/class_diffs.php";
		$asClasses['AnwDiffs'] = ANWPATH_CLASSES."diff/class_diffs.php";
		
		$asClasses['AnwFeed'] = ANWPATH_CLASSES."class_feed.php";
		$asClasses['AnwFeedItem'] = ANWPATH_CLASSES."class_feed.php";
		
		$asClasses['AnwActionGlobal'] = ANWPATH_CLASSES."class_actionglobal.php";
		$asClasses['AnwActionPage'] = ANWPATH_CLASSES."class_actionpage.php";
		$asClasses['AnwActionMinimal'] = ANWPATH_CLASSES."class_actionminimal.php";
		
		$asClasses['AnwChange'] = ANWPATH_CLASSES."class_change.php";
		$asClasses['AnwLock'] = ANWPATH_CLASSES."class_lock.php";
		$asClasses['AnWiki'] = ANWPATH_CLASSES."class_anwiki.php";
		
		$asClasses['AnwDependancy'] = ANWPATH_CLASSES."dependancy/class_dependancy.php";
		$asClasses['AnwDependancyConflict'] = ANWPATH_CLASSES."dependancy/class_dependancy.php";
		$asClasses['AnwDependancyRequirement'] = ANWPATH_CLASSES."dependancy/class_dependancy.php";
		
		$asClasses['AnwGoogleMap'] = ANWPATH_CLASSES."external/google/maps/class_googlemap.php";
	}
	
	if (class_exists('AnwDebug')) AnwDebug::log("***AUTOLOAD***: ".$sClassName);
	
	/*if ($sClassName == "AnwContentClasses") {
		try{
			throw new Exception();
		}
		catch(Exception $e){
		print_r($e->getTrace());
		}
	}*/
	
	if (isset($asClasses[$sClassName]))
	{
		loadApp($asClasses[$sClassName]);
	}
	else
	{
		//interface of contentclass
		$sPattern = '!AnwIContentFieldPage_([^_]*?)_(.*?)$!i'; //ie: AnwIContentFieldPage_menu_menuSubItem belongs to "menu"
		if (preg_match($sPattern, $sClassName, $asMatches))
		{
			$sContentClassName = $asMatches[1];
			//AnwContentClasses::loadContentClassInterface($sContentClassName);
			AnwUtils::loadContentClassInterfaceFaster($sContentClassName);
		}
		else
		{
			$sPattern = '!AnwIContentClassPageDefault_(.*?)$!i'; //ie: AnwIContentClassPageDefault_menu belongs to "menu"
			if (preg_match($sPattern, $sClassName, $asMatches))
			{
				$sContentClassName = $asMatches[1];
				//AnwContentClasses::loadContentClassInterface($sContentClassName);
				AnwUtils::loadContentClassInterfaceFaster($sContentClassName);
			}
			else
			{
				//contentclass: default
				$sPattern = '!AnwContentClassPageDefault_(.*)$!i';
				if (preg_match($sPattern, $sClassName, $asMatches))
				{
					$sContentClassName = $asMatches[1];
					AnwContentClasses::loadContentClass($sContentClassName);
				}
				else
				{
					//contentclass: override
					$sPattern = '!AnwContentClassPageOverride_(.*)$!i';
					if (preg_match($sPattern, $sClassName, $asMatches))
					{
						$sContentClassName = $asMatches[1];
						AnwContentClasses::loadContentClass($sContentClassName);
					}
					else
					{
						$sPattern = '!AnwDatatype(.*)$!i';
						if (preg_match($sPattern, $sClassName))
						{
							loadApp (ANWPATH_CLASSES."class_datatype.php");
						}
						else
						{
							//__autoload is called for {AnwContentField, AnwContentMultiplicity...} althought we don't really need it
							$sPattern = '!(AnwStructuredContentFieldsContainer|AnwContent|AnwContentPage|AnwSettingsContent|AnwContentClasses|AnwStructuredContent(.*)|AnwContentSettings)$!i';
							if (preg_match($sPattern, $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentclasses.php");
								loadApp (ANWPATH_CLASSES."contentclass/class_contentclass.php");
								loadApp (ANWPATH_CLASSES."contentclass/class_content.php");
								loadApp (ANWPATH_CLASSES."contentclass/class_contentfield.php");
								loadApp (ANWPATH_CLASSES."contentclass/class_contentmultiplicity.php");
							}
							else if (preg_match('!(AnwStructuredContentFieldsContainerPage|AnwContentClassPage)$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentclass_page.php");
							}
							else if (preg_match('!(AnwStructuredContentFieldsContainerSettings|AnwContentClassSettings)$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentclass_settings.php");
							}
							else if (preg_match('!(AnwContentFieldPage(.*)|AnwContentFieldPage_(.*))$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentfield_page.php");
							}
							else if (preg_match('!(AnwContentFieldSettings(.*))$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentfield_settings.php");
							}
							else if (preg_match('!(AnwISettings_(.*))$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentfield_settings_interface.php");
							}
							else if (preg_match('!(AnwIPage_(.*))$!i', $sClassName))
							{
								loadApp (ANWPATH_CLASSES."contentclass/class_contentfield_page_interface.php");
							}
							else
							{
								if (class_exists('AnwDebug')) AnwDebug::log('***AUTOLOAD*** failed');
							}
						}
					}
				}
			}
		}
	}
}
function loadAppSafe($sFileName)
{
	(require_once($sFileName)) or die("Unable to load ".$sFileName);
	if (class_exists("AnwDebug")) AnwDebug::log("loadApp: ".$sFileName);
}
function loadApp($sFileName)
{
	if (!preg_match('!^([0-9a-zA-Z\-_\.\/\\\:[:space:]])*\.php$!si', $sFileName)) throw new AnwUnexpectedException("Insecure file required : ".$sFileName);
	if (strstr($sFileName, '..')) throw new AnwUnexpectedException("Insecure file required : ".$sFileName);
	if (!file_exists($sFileName)) throw new AnwUnexpectedException("File not found : ".$sFileName); //TODO : great html error msg and die
	loadAppSafe($sFileName);
}

function errorApp($e)
{
	$bLogEnvAvailable = (class_exists("AnwComponent") && class_exists("AnwUtils"));
	
	$nErrorNumber = false;
	if ($bLogEnvAvailable)
	{
		$nErrorNumber = AnwDebug::reportError($e);
	}
	
	print '<h1>Error</h1>';
	print '<div style="margin:0px auto; width:550px">';
	
	/*if ($e instanceof PhpRuntimeException)
	{
		print '<h1>Error</h1>';
		
		if (class_exists("AnwUtils") && AnwUtils::isViewDebugAuthorized())
		{
			print '<div style="margin:0px auto; width:550px">' .
			'<p>'.$e->getMessage().' ('.get_class($e).')<br/>' .
			'<span style="font-size:12px">'.$e->getFile().', line '.$e->getLine().'</span></p>' .
			'</div>';
		}
	}
	*/
	
	//display error details if user is allowed to view it
	
	if (   ($bLogEnvAvailable && AnwUtils::isViewDebugAuthorized()) 
		|| (class_exists("AnwDebug")&&AnwDebug::isEmergencyDebugEnabled()) )
	{
		print '<p>'.$e->getMessage().' ('.get_class($e).')<br/>';
		print '<span style="font-size:12px">'.$e->getFile().', line '.$e->getLine().'</span>';
		print '</p>';
	}
	else
	{
		print '<p>A problem occurred. Please try again later or contact an administrator.<br/>' .
				'We apologize for inconvenience.</p>';
	}
	
	if ($nErrorNumber)
	{
		print '<p>Error has been logged. Please contact the administrator with the following error number : <b>'.$nErrorNumber.'</b></p>';
	}
	
	print '</div>';
	
	//display error trace and debug log if user is allowed to view it
	if ($bLogEnvAvailable && AnwUtils::isViewDebugAuthorized())
	{
		//display trace
		print '<p>Trace :<br/><ul>';
		$asTrace = $e->getTrace();
		$bFirst = true;
		foreach ($asTrace as $sTrace)
		{
			$sCss = ( $bFirst ? 'color:red' : '' );
			print '<li style="font-size:14px;'.$sCss.'">';
			if (isset($sTrace['class'])) print $sTrace['class'];
			if (isset($sTrace['type'])) print $sTrace['type'];
			print $sTrace['function'];
			
			if (isset($sTrace['args']))
			{
				//hide args for security reasons
				foreach ($sTrace['args'] as $i => $sArg)
				{
					$sTrace['args'][$i] = 'p'.($i+1);
				}
				print '('.implode($sTrace['args'],", ").')';
			}
			print '<br/><span style="font-size:12px">File '.@$sTrace['file'].', line '.@$sTrace['line'].'</span><br/>';
			print '</li>';
			$bFirst = false;
		}
		print '</ul></p>';
	
		//display debug
		if (class_exists("AnwDebug"))
		{
			print '<p>Debug :<br/><div style="font-size:12px">'.AnwDebug::getLog().'</font></p>';
		}
	}	
	exit;
}

?>