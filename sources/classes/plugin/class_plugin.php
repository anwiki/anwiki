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
 * Anwiki plugin model.
 * @package Anwiki
 * @version $Id: class_plugin.php 363 2011-07-19 22:15:56Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
abstract class AnwPlugin extends AnwComponent
{
	final function __construct($sPluginName, $bIsAddon)
	{
		$this->initComponent($sPluginName, $bIsAddon);
	}
		
	//methods below can be overriden
	
	function init(){}
	
	protected function hook_action_view_pagenotfound_create($oPage){}
	protected function hook_action_view_pagenotfound_404($oPage){}
	
	protected function hook_page_onchange($oPage, $oPreviousContent=null){}	
	//also dynamic hook by contentclass name  such as: hook_page_onchange_byclassname_news
	//use it for better performances, to avoid loading plugin on changes of any contentclass
	
	protected function vhook_page_notfound_secondchance($oPageNull, $sPageName){ return $oPageNull; } //return a AnwPage object, or false if no page corresponds
	
	protected function vhook_contenteditionform_render_html($sHtml, $oContent, $bFromPost){ return $sHtml; }
	
	protected function hook_contentclass_init($oContentClass){}
	protected function hook_contentclass_init_byname_ANYCONTENTCLASSNAME($oContentClass){}
	
	protected function hook_content_prepare_for_output($oContent, $oPage){}
	
	protected function hook_contentpage_tohtml_before($oContent, $oPage){}
	protected function hook_contentpage_tohtml_after($oContent, $oPage){}
	
	protected function vhook_component_t_contentfieldpage_override($sTranslationValue, $oComponent, $id, $asParams, $sLangIfLocal, $sValueIfNotFound){return $sTranslationValue;}
	
	//clean output before caching
	protected function vhook_output_clean($sContentHtmlAndPhp, $oPage){ return $sContentHtmlAndPhp; }
	protected function vhook_output_run_before($sContentHtml, $oPage){ return $sContentHtml; }
	protected function vhook_output_run($sContentHtml, $oPage){ return $sContentHtml; }
	protected function vhook_outputhtml_clean_title($sHtmlAndPhp){ return $sHtmlAndPhp; }
	protected function vhook_outputhtml_clean_head($sHtmlAndPhp){ return $sHtmlAndPhp; }
	protected function vhook_outputhtml_clean_body($sHtmlAndPhp){ return $sHtmlAndPhp; }
	protected function vhook_outputhtml_clean_title_html($sHtmlOnly){ return $sHtmlOnly; }
	protected function vhook_outputhtml_clean_head_html($sHtmlOnly){ return $sHtmlOnly; }
	protected function vhook_outputhtml_clean_body_html($sHtmlOnly){ return $sHtmlOnly; }
	
	//clean output before each execution
	protected function vhook_outputhtml_run_title($sHtml){ return $sHtml; }
	protected function vhook_outputhtml_run_head($sHtml){ return $sHtml; }
	protected function vhook_outputhtml_run_body($sHtml){ return $sHtml; }
	
	protected function vhook_contentclass_pubcalloperator_default($sOperatorValue, $sOperatorName){ return $sOperatorValue; }
	protected function vhook_parser_pubcallcontext_default($sOperatorName){ return $sOperatorName; }
	
	protected function vhook_datatype_xhtml_cleanvaluefrompost($sInputValue){ return $sInputValue; }
	
	//checks
	protected function hook_check_valid_pagename($sPageName){} //throw new AnwPluginInterruptionException if not valid
	protected function hook_check_valid_login($sLogin){} //throw new AnwPluginInterruptionException if not valid
	protected function hook_check_valid_displayname($sDisplayName){} //throw new AnwPluginInterruptionException if not valid
	protected function hook_check_valid_email($sEmail){} //throw new AnwPluginInterruptionException if not valid
	protected function hook_check_valid_password($sPassword){} //throw new AnwPluginInterruptionException if not valid
	
	protected function hook_check_available_login($sLogin){} //throw new AnwPluginInterruptionException if not available
	protected function hook_check_available_displayname($sDisplayName){} //throw new AnwPluginInterruptionException if not available
	protected function hook_check_available_email($sEmail){}	//throw new AnwPluginInterruptionException if not available
	
	protected function hook_users_nonunique_email_allowed(){}	//throw new AnwPluginInterruptionException if the plugin doesn't allow nonunique emails
	protected function hook_users_nonunique_displayname_allowed(){}	//throw new AnwPluginInterruptionException if the plugin doesn't allow nonunique displaynames
	
	//user events
	protected function hook_user_created($oUser, $sPassword){}
	protected function hook_user_changed_password($oUser, $sNewPassword){}
	protected function hook_user_changed_lang($oUser, $sNewLang){}
	protected function hook_user_changed_timezone($oUser, $sNewTimezone){}
	protected function hook_user_changed_displayname($oUser, $sNewDisplayName){}
	protected function hook_user_changed_email($oUser, $sNewEmail){}
	protected function hook_user_loggedin($oUser, $sPassword, $bResume){}
	protected function hook_user_loggedout($oUser){}
	
	protected function hook_session_keepalive_any($oUser){}
	protected function hook_session_keepalive_loggedin($oUser){}
	protected function hook_session_keepalive_loggedout($oUser){}
	
	//--------
	
	protected function debug($sMessage)
	{
		return AnwDebug::log("(".get_class($this).")".$sMessage);
	}
	
	function getComponentName()
	{
		return 'plugin_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_PLUGINS;
	}
	static function getComponentsDirsBegin()
	{
		return 'plugin_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}	
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_PLUGIN;
	}
	
	static function discoverEnabledComponents()
	{
		return AnwComponent::globalCfgModulesPlugins();
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'plugin');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}
		
		$sFile = 'plugin_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwPlugin%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oPlugin = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'plugin', $oPlugin);
		
		return $oPlugin;
	}
	
	
}

?>