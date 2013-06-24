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
 * ACL Driver: file.
 * @package Anwiki
 * @version $Id: aclsdriver_configrules.php 256 2010-03-10 20:50:10Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

//--------------------------
// Rules tab
//--------------------------
interface AnwISettings_aclsconfigrule_acls
{
	const FIELD_RULES = "rules";
}

interface AnwISettings_aclsconfigrule_rule
{
	const FIELD_PERMISSION_USER = "permissionuser";
	const FIELD_PERMISSION_CONTENT = "permissioncontent";
	const FIELD_PERMISSION_ACTIONGLOBAL = "permissionactionglobal";
}

interface AnwISettings_aclsconfigrule_permission_user
{
	const FIELD_POLICY = "policy";
	const FIELD_USERS = "users";
	
	const POLICY_ALL_USERS = "ALL_USERS";
	const POLICY_LOGGED_USERS = "LOGGED_USERS";
	const POLICY_SELECTED_USERS = "SELECTED_USERS";
}

interface AnwISettings_aclsconfigrule_permission_content
{
	const FIELD_PERMISSION_ACTIONPAGE = "permissionactionpage";
	const FIELD_CONTENTMATCH = "contentmatch";
}

interface AnwISettings_aclsconfigrule_permission_actionpage extends AnwISettings_aclsconfigrule_actionpolicy
{
}

interface AnwISettings_aclsconfigrule_contentmatch
{
	const FIELD_NAME = "name";
	const FIELD_PERMISSION_LANG = "permissionlang";
}

interface AnwISettings_aclsconfigrule_permission_lang
{
	const FIELD_POLICY = "policy";
	const FIELD_LANGS = "langs";
	
	const POLICY_ALL_LANGS = "ALL_LANGS";
	const POLICY_SELECTED_LANGS = "SELECTED_LANGS";
}

interface AnwISettings_aclsconfigrule_permission_actionglobal extends AnwISettings_aclsconfigrule_actionpolicy
{
}

interface AnwISettings_aclsconfigrule_actionpolicy
{
	//just to factorize some code
	const FIELD_POLICY = "policy";
	const FIELD_ACTIONS = "actions";
	
	const POLICY_ALL_ACTIONS = "ALL_ACTIONS";
	const POLICY_SELECTED_ACTIONS = "SELECTED_ACTIONS";
	const POLICY_NO_ACTION = "NO_ACTION";
}

//--------------------------
// Privileges tab
//--------------------------
interface AnwISettings_aclsconfigrule_privileges
{
	const FIELD_PRIVILEGERULES = "privilegerules";
}

interface AnwISettings_aclsconfigrule_privilegerule
{
	const FIELD_USERS = "users";
	const FIELD_PHP_EDITION = "php_edition";
	const FIELD_UNSAFE_EDITION = "unsafe_edition";
	const FIELD_IS_ADMIN = "is_admin";
}

define('ANWACL_ALL_USERS', '#ALL_USERS#');
define('ANWACL_LOGGED_USERS', '#LOGGED_USERS#');
define('ANWACL_ALL_PAGES', '#ALL_PAGES#');
define('ANWACL_ALL_LANGS', '#ALL_LANGS#');
define('ANWACL_ALL_ACTIONS', '#ALL_ACTIONS#');


class AnwAclsDriverDefault_configrules extends AnwAclsDriverReadWrite implements AnwConfigurable, AnwInitializable
{
	private $aoRulesGlobal = array();
	private $aoRulesPage = array();
	private $asPhpEditionAllowedUsers = array();
	private $asUnsafeEditionAllowedUsers = array();
	private $asAdminAllowedUsers = array();
	private $bRulesLoaded = false;
	
	const CFG_ACLS = "acls";
	const CFG_PRIVILEGES = "privileges";
	
	const CACHE_RULES_GLOBAL = "rules_global";
	const CACHE_RULES_PAGE = "rules_page";
	const CACHE_PHP_EDITION_USERS = "php_edition_users";
	const CACHE_UNSAFE_EDITION_USERS = "unsafe_edition_users";
	const CACHE_ADMIN_USERS = "admin_users";
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_acls(self::CFG_ACLS);
		$aoSettings[] = $oContentField;
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_privileges(self::CFG_PRIVILEGES);
		$aoSettings[] = $oContentField;		
		
		return $aoSettings;
	}
	
	protected function doInit($bForceReload=false)
	{
		if (!$this->bRulesLoaded || $bForceReload)
		{
			try
			{
				if ($bForceReload)
				{
					throw new AnwCacheNotFoundException();
				} 
				$sConfigFileOverride = $this->getConfigurableFileOverride();
				$aaSettings = AnwCache_aclsdriver_configrules_settings::getCachedSettings($sConfigFileOverride);
			}
			catch(AnwCacheNotFoundException $e)
			{
				$aaSettings = self::generateSettingsFromCfg();
				AnwCache_aclsdriver_configrules_settings::putCachedSettings($aaSettings);
			}
			catch(AnwFileNotFoundException $e)
			{
				$aaSettings = self::generateSettingsFromCfg();
				AnwCache_aclsdriver_configrules_settings::putCachedSettings($aaSettings);
			}
			//print_r($aaSettings);
			$this->aoRulesGlobal = $aaSettings[self::CACHE_RULES_GLOBAL];
			$this->aoRulesPage = $aaSettings[self::CACHE_RULES_PAGE];
			$this->asPhpEditionAllowedUsers = $aaSettings[self::CACHE_PHP_EDITION_USERS];
			$this->asUnsafeEditionAllowedUsers = $aaSettings[self::CACHE_UNSAFE_EDITION_USERS];
			$this->asAdminAllowedUsers = $aaSettings[self::CACHE_ADMIN_USERS];
			$this->bRulesLoaded = true;
			
			unset($aaSettings);
		}
	}
	
	protected function getRulesPage()
	{
		$this->doInit();
		return $this->aoRulesPage;
	}
	
	protected function getRulesGlobal()
	{
		$this->doInit();
		return $this->aoRulesGlobal;
	}
	
	function getPhpEditionAllowedUsers()
	{
		$this->doInit();
		return $this->asPhpEditionAllowedUsers;
	}
	
	function getUnsafeEditionAllowedUsers()
	{
		$this->doInit();
		return $this->asUnsafeEditionAllowedUsers;
	}
	
	function getAdminAllowedUsers()
	{
		$this->doInit();
		return $this->asAdminAllowedUsers;
	}
	
	//------------------------------------------------
	// Read capabilities
	//------------------------------------------------
	function isActionAllowed($oUser, $sPage/*=-1*/, $sAction, $sLang/*=-1*/)
	{
		$aoRulesPage = $this->getRulesPage();
		foreach ($aoRulesPage as $oRulePage)
		{
			if (
				$oRulePage->userMatch($oUser) && $oRulePage->actionMatch($sAction) 
				&& ($sPage == -1 || $oRulePage->pageMatch($sPage))
				&& ($sLang == -1 || $oRulePage->langMatch($sLang))
			)
			{
				return true;
			}
		}
		return false;
	}
	
	function isActionGlobalAllowed($oUser, $sAction)
	{
		$aoRulesGlobal = $this->getRulesGlobal();
		foreach ($aoRulesGlobal as $oRuleGlobal)
		{
			if ($oRuleGlobal->userMatch($oUser) && $oRuleGlobal->actionMatch($sAction))
			{
				return true;
			}
		}
		return false;
	}
	
	function isPhpEditionAllowed($oUser)
	{
		return ($oUser instanceof AnwUserReal && in_array($oUser->getLogin(), $this->getPhpEditionAllowedUsers()));
	}
	
	function isJsEditionAllowed($oUser)
	{
		return ($oUser instanceof AnwUserReal && in_array($oUser->getLogin(), $this->getUnsafeEditionAllowedUsers()));
	}
	
	function isAdminAllowed($oUser)
	{
		return ($oUser instanceof AnwUserReal && in_array($oUser->getLogin(), $this->getAdminAllowedUsers()));
	}
	
	private function getUsersFromPermission($oSubContentPermissionUser)
	{
		$sPolicy = $oSubContentPermissionUser->getContentFieldValue(AnwISettings_aclsconfigrule_permission_user::FIELD_POLICY);
			
		$asAllowedUsers = array();
		if ($sPolicy == AnwISettings_aclsconfigrule_permission_user::POLICY_ALL_USERS)
		{
			$asAllowedUsers = array(ANWACL_ALL_USERS);
		}
		else if ($sPolicy == AnwISettings_aclsconfigrule_permission_user::POLICY_LOGGED_USERS)
		{
			$asAllowedUsers = array(ANWACL_LOGGED_USERS);
		}
		else if ($sPolicy == AnwISettings_aclsconfigrule_permission_user::POLICY_SELECTED_USERS)
		{
			$asAllowedUsers = $oSubContentPermissionUser->getContentFieldValues(AnwISettings_aclsconfigrule_permission_user::FIELD_USERS);
		}
		
		return $asAllowedUsers;
	}
	
	private function getActionsFromPermission($oSubContentPermissionAction)
	{
		$sPolicy = $oSubContentPermissionAction->getContentFieldValue(AnwISettings_aclsconfigrule_actionpolicy::FIELD_POLICY);
			
		$asAllowedActions = array();
		if ($sPolicy == AnwISettings_aclsconfigrule_actionpolicy::POLICY_ALL_ACTIONS)
		{
			$asAllowedActions = array(ANWACL_ALL_ACTIONS);
		}
		else if ($sPolicy == AnwISettings_aclsconfigrule_actionpolicy::POLICY_SELECTED_ACTIONS)
		{
			$asAllowedActions = $oSubContentPermissionAction->getContentFieldValues(AnwISettings_aclsconfigrule_actionpolicy::FIELD_ACTIONS);
		}
		
		return $asAllowedActions;
	}
	
	private function getLangsFromPermission($oSubContentPermissionLang)
	{
		$sPolicy = $oSubContentPermissionLang->getContentFieldValue(AnwISettings_aclsconfigrule_permission_lang::FIELD_POLICY);
		
		$asAllowedLangs = array();
		if ($sPolicy == AnwISettings_aclsconfigrule_permission_lang::POLICY_ALL_LANGS)
		{
			$asAllowedLangs = array(ANWACL_ALL_LANGS);
		}
		else if ($sPolicy == AnwISettings_aclsconfigrule_permission_lang::POLICY_SELECTED_LANGS)
		{
			$asAllowedLangs = $oSubContentPermissionLang->getContentFieldValues(AnwISettings_aclsconfigrule_permission_lang::FIELD_LANGS);
		}
		
		return $asAllowedLangs;
	}
	
	
	protected function generateSettingsFromCfg()
	{
		$aaSettings = array(
			self::CACHE_RULES_GLOBAL => array(),
			self::CACHE_RULES_PAGE => array(),
			self::CACHE_PHP_EDITION_USERS => array(),
			self::CACHE_UNSAFE_EDITION_USERS => array(),
			self::CACHE_ADMIN_USERS => array()
		);
		
		$oCfgContent = $this->getConfigurableContent();
		
		//------------------------------
		// Read ACLs
		//------------------------------
		
		//browse rules
		$oSubContentAcls = $oCfgContent->getSubContent(self::CFG_ACLS);
		$aoSubContentRules = $oSubContentAcls->getSubContents(AnwISettings_aclsconfigrule_acls::FIELD_RULES);
		foreach ($aoSubContentRules as $oSubContentRule)
		{
			//allowed users
			$oSubContentPermissionUser = $oSubContentRule->getSubContent(AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_USER);
			$asAllowedUsers = $this->getUsersFromPermission($oSubContentPermissionUser);
			
			if (count($asAllowedUsers) > 0)
			{
			
				//actions global
				$oSubContentPermissionActionsGlobal = $oSubContentRule->getSubContent(AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_ACTIONGLOBAL);
				$asAllowedActionsGlobal = $this->getActionsFromPermission($oSubContentPermissionActionsGlobal);
				
				//content permissions
				$aoSubContentPermissionsContent = $oSubContentRule->getSubContents(AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_CONTENT);
				
				
				
				//1) browse actions global
				
				//loop actions global
				foreach ($asAllowedActionsGlobal as $sAllowedActionGlobal)
				{
					//loop allowed users
					foreach ($asAllowedUsers as $sAllowedUser)
					{
						//create simple rule
						$aaSettings[self::CACHE_RULES_GLOBAL][] = new AnwAclRuleGlobal($sAllowedUser, $sAllowedActionGlobal);
					}
				}
				
				
				//2) browse rules on content
				
				//loop rules on content
				foreach ($aoSubContentPermissionsContent as $oSubContentPermissionContent)
				{
					//content matches
					$aoSubContentContentMatches = $oSubContentPermissionContent->getSubContents(AnwISettings_aclsconfigrule_permission_content::FIELD_CONTENTMATCH);
					
					//actions page
					$oSubContentPermissionActionsPage = $oSubContentPermissionContent->getSubContent(AnwISettings_aclsconfigrule_permission_content::FIELD_PERMISSION_ACTIONPAGE);
					$asAllowedActionsPage = $this->getActionsFromPermission($oSubContentPermissionActionsPage);
					
					
					//loop content matches
					if (count($aoSubContentContentMatches) > 0)
					{
						foreach ($aoSubContentContentMatches as $oSubContentContentMatch)
						{
							//matching names
							$asNames =  $oSubContentContentMatch->getContentFieldValues(AnwISettings_aclsconfigrule_contentmatch::FIELD_NAME);
							if (count($asNames) == 0)
							{
								$asNames = array(ANWACL_ALL_PAGES);
							}
							
							//matching langs
							$oSubContentPermissionLang = $oSubContentContentMatch->getSubContent(AnwISettings_aclsconfigrule_contentmatch::FIELD_PERMISSION_LANG);
							$asAllowedLangs = $this->getLangsFromPermission($oSubContentPermissionLang);
							
							
							//loop content names
							foreach ($asNames as $sName)
							{
								//loop content langs
								foreach ($asAllowedLangs as $sLang)
								{
									//loop actions page
									foreach ($asAllowedActionsPage as $sAllowedActionPage)
									{
										//loop allowed users
										foreach ($asAllowedUsers as $sAllowedUser)
										{
											//create simple rule
											$aaSettings[self::CACHE_RULES_PAGE][] = new AnwAclRulePage($sAllowedUser, $sAllowedActionPage, $sLang, $sName);
										}
									}
								}
							}
						}
					}
					else
					{
						//no content filter, give permission on any content
						
						//loop actions page
						foreach ($asAllowedActionsPage as $sAllowedActionPage)
						{
							//loop allowed users
							foreach ($asAllowedUsers as $sAllowedUser)
							{
								$aaSettings[self::CACHE_RULES_PAGE][] = new AnwAclRulePage($sAllowedUser, $sAllowedActionPage, ANWACL_ALL_LANGS, ANWACL_ALL_PAGES);
							}
						}
					}
				}
			}
		}
		
		
		//------------------------------
		// Read privileges
		//------------------------------
		
		//browse rules
		$oSubContentPrivileges = $oCfgContent->getSubContent(self::CFG_PRIVILEGES);
		$aoSubContentPrivilegeRules = $oSubContentPrivileges->getSubContents(AnwISettings_aclsconfigrule_privileges::FIELD_PRIVILEGERULES);
		foreach ($aoSubContentPrivilegeRules as $oSubContentPrivilegeRule)
		{
			//allowed users
			$asAllowedUsers = $oSubContentPrivilegeRule->getContentFieldValues(AnwISettings_aclsconfigrule_privilegerule::FIELD_USERS);
			
			$bIsPhpEditionAllowed = $oSubContentPrivilegeRule->getContentFieldValue(AnwISettings_aclsconfigrule_privilegerule::FIELD_PHP_EDITION);
			$bIsUnsafeEditionAllowed = $oSubContentPrivilegeRule->getContentFieldValue(AnwISettings_aclsconfigrule_privilegerule::FIELD_UNSAFE_EDITION);
			$bIsAdminAllowed = $oSubContentPrivilegeRule->getContentFieldValue(AnwISettings_aclsconfigrule_privilegerule::FIELD_IS_ADMIN);
			
			foreach ($asAllowedUsers as $sAllowedUserLogin)
			{
				//php edition
				if ($bIsPhpEditionAllowed)
				{
					$aaSettings[self::CACHE_PHP_EDITION_USERS][] = $sAllowedUserLogin;
				}
				
				//unsafe edition
				if ($bIsUnsafeEditionAllowed)
				{
					$aaSettings[self::CACHE_UNSAFE_EDITION_USERS][] = $sAllowedUserLogin;
				}
				
				//admin
				if ($bIsAdminAllowed)
				{
					$aaSettings[self::CACHE_ADMIN_USERS][] = $sAllowedUserLogin;
				}
			}
		}
		return $aaSettings;
	}
	
	//------------------------------------------------
	// Write capabilities
	//------------------------------------------------
	function grantUserAdminOnInstall($oUser)
	{
		//is this user already an admin?
		if (!self::isAdminAllowed($oUser))
		{
			$oCfgContent = $this->getConfigurableContent();			
			$oSubContentPrivileges = $oCfgContent->getSubContent(self::CFG_PRIVILEGES);
			
			//read existing privilege rules
			$aoSubContentsPrivilegeRules = $oSubContentPrivileges->getSubContents(AnwISettings_aclsconfigrule_privileges::FIELD_PRIVILEGERULES);
			
			//create a new privilege rule
			$oContentFieldPrivilegeRule = $oSubContentPrivileges->getContentFieldsContainer()->getContentField(AnwISettings_aclsconfigrule_privileges::FIELD_PRIVILEGERULES);
			$oSubContentPrivilegeRuleNew = new AnwContentSettings($oContentFieldPrivilegeRule);
			
			//set user login
			$sUserLogin = $oUser->getLogin();
			$oSubContentPrivilegeRuleNew->setContentFieldValues(AnwISettings_aclsconfigrule_privilegerule::FIELD_USERS, array($sUserLogin));
			
			//set as admin
			$oSubContentPrivilegeRuleNew->setContentFieldValues(AnwISettings_aclsconfigrule_privilegerule::FIELD_IS_ADMIN, array(AnwDatatype_boolean::VALUE_TRUE));
			
			//add the privilege rule
			$aoSubContentsPrivilegeRules[] = $oSubContentPrivilegeRuleNew;
			$oSubContentPrivileges->setSubContents(AnwISettings_aclsconfigrule_privileges::FIELD_PRIVILEGERULES, $aoSubContentsPrivilegeRules);
			$oCfgContent->setSubContents(self::CFG_PRIVILEGES, array($oSubContentPrivileges));
			
			//write config
			$oCfgContent->writeSettingsOverride();
			
			//reload configuration to update cache and make sure user is recognized as admin
			$this->doInit(true);
			if (!self::isAdminAllowed($oUser))
			{
				throw new AnwUnexpectedException("User is still not admin!");
			}
		}
	}
	
	protected function getGrantAllUsersByDefaultActions()
	{
		$asGrantActionGlobal = array();
		$asGrantActionPage = array();
		$aoGrantAllUsersByDefaultActions = AnwAction::getGrantAllUsersByDefaultActions();
		foreach ($aoGrantAllUsersByDefaultActions as $sAction)
		{
			if (AnwAction::isActionPage($sAction))
			{
				$asGrantActionPage[] = $sAction;
			}
			else
			{
				$asGrantActionGlobal[] = $sAction;
			}
		}
		return array($asGrantActionGlobal, $asGrantActionPage);
	}
	
	//------------------------------------------------
	// INITIALIZE
	//------------------------------------------------
	function initializeComponent()
	{
		//set default permissions
		
		// first, make sure that config is writable
		$sConfigFileOverride = $this->getConfigurableFileOverride();
		if (!is_writable($sConfigFileOverride))
		{
			$sError = Anwi18n::g_err_need_write_file($sConfigFileOverride);
			throw new AnwComponentInitializeException($sError);
		}
		
		//find out which actions should be allowed by default to everyone
		list($asGrantActionGlobal, $asGrantActionPage) = self::getGrantAllUsersByDefaultActions();
			
		$oCfgContent = $this->getConfigurableContent();
		$oSubContentTabACL = $oCfgContent->getSubContent(self::CFG_ACLS);
		
		//read existing actionpage rules
		$aoSubContentsACLs = $oSubContentTabACL->getSubContents(AnwISettings_aclsconfigrule_acls::FIELD_RULES);
		$oContentFieldACLs = $oSubContentTabACL->getContentFieldsContainer()->getContentField(AnwISettings_aclsconfigrule_acls::FIELD_RULES);
			
		//create a new actionpage rule			
		$aaSubContentValue = array(			
			// permissions for all users...
			AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_USER => array(
				AnwISettings_aclsconfigrule_permission_user::FIELD_POLICY => AnwISettings_aclsconfigrule_permission_user::POLICY_ALL_USERS
			),
			
			// permissions on global actions...
			AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_ACTIONGLOBAL => array(
				AnwISettings_aclsconfigrule_permission_actionglobal::FIELD_POLICY => AnwISettings_aclsconfigrule_permission_actionglobal::POLICY_SELECTED_ACTIONS,
				AnwISettings_aclsconfigrule_permission_actionglobal::FIELD_ACTIONS => $asGrantActionGlobal
			),
			
			// permissions on contents...
			AnwISettings_aclsconfigrule_rule::FIELD_PERMISSION_CONTENT => array(
				array(
					AnwISettings_aclsconfigrule_permission_content::FIELD_PERMISSION_ACTIONPAGE => array(
						AnwISettings_aclsconfigrule_permission_actionpage::FIELD_POLICY => AnwISettings_aclsconfigrule_permission_actionpage::POLICY_SELECTED_ACTIONS,
						AnwISettings_aclsconfigrule_permission_actionpage::FIELD_ACTIONS => $asGrantActionPage
					),
					AnwISettings_aclsconfigrule_permission_content::FIELD_CONTENTMATCH => array(
						// no filter, to match any content
					)
				)
			),			
		);
		$oNewSubContentACLs = AnwContentSettings::rebuildContentFromArray($oContentFieldACLs, $aaSubContentValue);
			
		//add the actionpage rule
		$aoSubContentsACLs[] = $oNewSubContentACLs;
		$oSubContentTabACL->setSubContents(AnwISettings_aclsconfigrule_acls::FIELD_RULES, $aoSubContentsACLs);
		$oCfgContent->setSubContents(self::CFG_ACLS, array($oSubContentTabACL));
		
		//print $oCfgContent->toXmlString();
		//exit;
		
		//write config
		$oCfgContent->writeSettingsOverride();
		
		//reload configuration to update cache
		$this->doInit(true);
				
		//execution log
		$sInitializationLog = $this->t_contentfieldsettings("init_rules_intro_list");
		$sInitializationLog .= "<ul>";
		$sListActionGlobal = (count($asGrantActionGlobal)>0 ? implode(', ', $asGrantActionGlobal) : $this->t_contentfieldsettings("init_rules_action_none"));
		$sListActionPage = (count($asGrantActionPage)>0 ? implode(', ', $asGrantActionPage) : $this->t_contentfieldsettings("init_rules_action_none"));
		$sInitializationLog .= "<li>".$this->t_contentfieldsettings("init_rules_actionglobal", array('actions'=>$sListActionGlobal))."</li>";
		$sInitializationLog .= "<li>".$this->t_contentfieldsettings("init_rules_actionpage", array('actions'=>$sListActionPage))."</li>";
		$sInitializationLog .= "</ul>";		
		return $sInitializationLog;
	}
	
	function getSettingsForInitialization()
	{
		list($asGrantActionGlobal, $asGrantActionPage) = self::getGrantAllUsersByDefaultActions();
		$sInfo = $this->t_contentfieldsettings("init_rules_intro_explain");
		$sInfo .= "<br/><br/>".$this->t_contentfieldsettings("init_rules_intro_list");
		$sInfo .= "<ul>";
		$sListActionGlobal = (count($asGrantActionGlobal)>0 ? implode(', ', $asGrantActionGlobal) : $this->t_contentfieldsettings("init_rules_action_none"));
		$sListActionPage = (count($asGrantActionPage)>0 ? implode(', ', $asGrantActionPage) : $this->t_contentfieldsettings("init_rules_action_none"));
		$sInfo .= "<li>".$this->t_contentfieldsettings("init_rules_actionglobal", array('actions'=>$sListActionGlobal))."</li>";
		$sInfo .= "<li>".$this->t_contentfieldsettings("init_rules_actionpage", array('actions'=>$sListActionPage))."</li>";
		$sInfo .= "</ul>";
		return $sInfo;
	}
	
	function isComponentInitialized()
	{
		return count($this->getRulesPage())>0;
	}	
}

abstract class AnwAclRule
{
	protected static function _userMatch($mRuleValue, $oUser)
	{
		//all users?
		if ($mRuleValue == ANWACL_ALL_USERS)
		{
			$bMatch = true;
		}
		else
		{
			//logged user?
			if ($mRuleValue == ANWACL_LOGGED_USERS)
			{
				$bMatch = ($oUser instanceof AnwUserReal);
			}
			else
			{
				//check login
				$bMatch = ($oUser instanceof AnwUserReal && $oUser->getLogin() == $mRuleValue);
			}
		}
		return $bMatch;
	}
	
	protected static function _pageMatch($mRuleValue, $sPage)
	{
		//all pages?
		if ($mRuleValue == ANWACL_ALL_PAGES)
		{
			$bMatch = true;
		}
		else
		{
			//check pagename
			$bMatch = (preg_match($mRuleValue, $sPage) > 0);
		}
		return $bMatch;
	}
	
	protected static function _actionMatch($mRuleValue, $sAction)
	{
		//all pages?
		if ($mRuleValue == ANWACL_ALL_ACTIONS)
		{
			$bMatch = true;
		}
		else
		{
			//check action
			$bMatch = ($sAction == $mRuleValue);
		}
		return $bMatch;
	}
	
	protected static function _langMatch($mRuleValue, $sLang)
	{
		//all langs?
		if ($mRuleValue == ANWACL_ALL_LANGS)
		{
			$bMatch = true;
		}
		else
		{
			//check lang
			$bMatch = ($sLang == $mRuleValue);
		}
		return $bMatch;
	}
}

class AnwAclRulePage extends AnwAclRule
{
	private $mUser;
	private $mPage;
	private $mAction;
	private $mLang;
	
	function __construct($mUser, $mAction, $mLang, $mPage)
	{
		$this->mUser = $mUser;
		$this->mPage = $mPage;
		$this->mAction = $mAction;
		$this->mLang = $mLang;
	}
	
	function userMatch($oUser)
	{
		return self::_userMatch($this->mUser, $oUser);
	}
	
	function pageMatch($sPage)
	{
		return self::_pageMatch($this->mPage, $sPage);
	}
	
	function actionMatch($sAction)
	{
		return self::_actionMatch($this->mAction, $sAction);
	}
	
	function langMatch($sLang)
	{
		return self::_langMatch($this->mLang, $sLang);
	}
}

class AnwAclRuleGlobal extends AnwAclRule
{
	private $mUser;
	private $mAction;
	
	function __construct($mUser, $mAction)
	{
		$this->mUser = $mUser;
		$this->mAction = $mAction;
	}
	
	function userMatch($oUser)
	{
		return self::_userMatch($this->mUser, $oUser);
	}
	
	function actionMatch($sAction)
	{
		return self::_actionMatch($this->mAction, $sAction);
	}
}




/**
 * Cache manager for aclsdriver_rules's settings.
 */
class AnwCache_aclsdriver_configrules_settings extends AnwCache
{
	private static function filenameCachedSettings()
	{
		return ANWPATH_CACHESYSTEM.'aclsdriver_configrules_settings.php';
	}
	
	static function putCachedSettings($aaSettings)
	{
		$sCacheFile = self::filenameCachedSettings();
		self::debug("putting cachedSettings : ".$sCacheFile); 
		self::putCachedObject($sCacheFile, $aaSettings);
	}
	
	static function getCachedSettings($sCfgFileOverride)
	{
		$sCacheFile = self::filenameCachedSettings();
		
		if (file_exists($sCacheFile) && file_exists($sCfgFileOverride) && filemtime($sCacheFile) < filemtime($sCfgFileOverride))
		{
			//cache expired by config file
			throw new AnwCacheNotFoundException();
		}
		
		$nDelayExpiry = self::EXPIRY_UNLIMITED;
		$aaSettings = self::getCachedObject($sCacheFile, $nDelayExpiry);
		
		if (!is_array($aaSettings))
	 	{
	 		self::debug("cachedSettings invalid");
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedSettings found");
	 	}
		return $aaSettings;
	}
}


?>