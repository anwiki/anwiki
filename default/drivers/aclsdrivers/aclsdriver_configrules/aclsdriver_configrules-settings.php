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
 * ContentFieldSettings for ACLs driver: file.
 * @package Anwiki
 * @version $Id: aclsdriver_configrules-settings.php 155 2009-02-14 17:28:18Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

//--------------------------
// ACL tab
//--------------------------

class AnwContentFieldSettings_aclsconfigrule_acls extends AnwContentFieldSettings_tab implements AnwISettings_aclsconfigrule_acls
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_rule(self::FIELD_RULES);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_aclsconfigrule_rule extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_rule
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_permission_user(self::FIELD_PERMISSION_USER);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_permission_actionglobal(self::FIELD_PERMISSION_ACTIONGLOBAL);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_permission_content(self::FIELD_PERMISSION_CONTENT);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_aclsconfigrule_permission_user extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_permission_user
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_POLICY);
		$asEnumValues = array(
			self::POLICY_ALL_USERS => $this->getComponent()->t_contentfieldsettings("policy_all_users"), 
			self::POLICY_LOGGED_USERS => $this->getComponent()->t_contentfieldsettings("policy_logged_users"), 
			self::POLICY_SELECTED_USERS => $this->getComponent()->t_contentfieldsettings("policy_selected_users"));
		$oContentField->setEnumValues($asEnumValues);
		$oContentField->setDefaultValue(self::POLICY_SELECTED_USERS);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_userlogin(self::FIELD_USERS);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
		
	
	function doTestContentFieldValueComposed($oSubContent)
	{
		//must select at least one user when selecting POLICY_SELECTED_USERS
		$sPolicy = $oSubContent->getContentFieldValue(self::FIELD_POLICY);
		$asUsers = $oSubContent->getContentFieldValues(self::FIELD_USERS);
		
		if ($sPolicy == self::POLICY_SELECTED_USERS)
		{
			if (count($asUsers)==0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_users_nouser");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
		else
		{
			if (count($asUsers)>0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_users_notselected");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
}

class AnwContentFieldSettings_aclsconfigrule_permission_actionglobal extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_permission_actionglobal
{
	function init()
	{
		parent::init();		
		
		//actions global policy
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_POLICY);
		$asEnumValues = array(
			self::POLICY_ALL_ACTIONS => $this->getComponent()->t_contentfieldsettings("policy_all_actionsglobal"),
			self::POLICY_SELECTED_ACTIONS => $this->getComponent()->t_contentfieldsettings("policy_selected_actionsglobal"),
			self::POLICY_NO_ACTION => $this->getComponent()->t_contentfieldsettings("policy_no_actionsglobal"));
		$oContentField->setEnumValues($asEnumValues);
		$oContentField->setDefaultValue(self::POLICY_NO_ACTION);
		$this->addContentField($oContentField);
		
		//actions global selection
		$oContentField = new AnwContentFieldSettings_checkboxGroup(self::FIELD_ACTIONS);
		$asActions = AnwComponent::getEnabledComponents(AnwComponent::TYPE_ACTION);
		$asEnumValues = array();
		foreach ($asActions as $sAction)
		{
			if (!AnwAction::isActionPage($sAction) && !AnwAction::isMagicAclAction($sAction))
			{
				$asEnumValues[$sAction] = $sAction;
			}
		}
		$oContentField->setEnumValues($asEnumValues);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}		
	
	function doTestContentFieldValueComposed($oSubContent)
	{
		//must select at least one action when selecting POLICY_SELECTED_ACTIONS
		$sPolicy = $oSubContent->getContentFieldValue(self::FIELD_POLICY);
		$asActions = $oSubContent->getContentFieldValues(self::FIELD_ACTIONS);
		
		if ($sPolicy != self::POLICY_SELECTED_ACTIONS)
		{
			if (count($asActions)>0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_actions_notselected");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
}

class AnwContentFieldSettings_aclsconfigrule_permission_content extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_permission_content
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_permission_actionpage(self::FIELD_PERMISSION_ACTIONPAGE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_contentmatch(self::FIELD_CONTENTMATCH);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_aclsconfigrule_permission_actionpage extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_permission_actionpage
{
	function init()
	{
		parent::init();		
		
		//actions page policy
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_POLICY);
		$asEnumValues = array(
			self::POLICY_ALL_ACTIONS => $this->getComponent()->t_contentfieldsettings("policy_all_actionspage"),
			self::POLICY_SELECTED_ACTIONS => $this->getComponent()->t_contentfieldsettings("policy_selected_actionspage"));
		$oContentField->setEnumValues($asEnumValues);
		$oContentField->setDefaultValue(self::POLICY_SELECTED_ACTIONS);
		$this->addContentField($oContentField);
		
		//actions page selection
		$oContentField = new AnwContentFieldSettings_checkboxGroup(self::FIELD_ACTIONS);
		$asActions = AnwComponent::getEnabledComponents(AnwComponent::TYPE_ACTION);
		$asEnumValues = array();
		foreach ($asActions as $sAction)
		{
			if (AnwAction::isActionPage($sAction) && !AnwAction::isMagicAclAction($sAction))
			{
				$asEnumValues[$sAction] = $sAction;
			}
		}
		$oContentField->setEnumValues($asEnumValues);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
		
	function doTestContentFieldValueComposed($oSubContent)
	{
		//must select at least one action when selecting POLICY_SELECTED_ACTIONS
		$sPolicy = $oSubContent->getContentFieldValue(self::FIELD_POLICY);
		$asActions = $oSubContent->getContentFieldValues(self::FIELD_ACTIONS);
		
		if ($sPolicy == self::POLICY_SELECTED_ACTIONS)
		{
			if (count($asActions)==0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_actions_noaction");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
		else
		{
			if (count($asActions)>0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_actions_notselected");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
}

class AnwContentFieldSettings_aclsconfigrule_contentmatch extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_contentmatch
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_NAME);
		$oContentField->addAllowedPattern('!^(.+)$!'); //deny empty values
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_permission_lang(self::FIELD_PERMISSION_LANG);
		$this->addContentField($oContentField);
	}
}


class AnwContentFieldSettings_aclsconfigrule_permission_lang extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_permission_lang
{
	function init()
	{
		parent::init();
		
		//lang policy
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_POLICY);
		$asEnumValues = array(
			self::POLICY_ALL_LANGS => $this->getComponent()->t_contentfieldsettings("policy_all_langs"),
			self::POLICY_SELECTED_LANGS => $this->getComponent()->t_contentfieldsettings("policy_selected_langs"));
		$oContentField->setEnumValues($asEnumValues);
		$oContentField->setDefaultValue(self::POLICY_ALL_LANGS);
		$this->addContentField($oContentField);
		
		//langs selection
		$oContentField = new AnwContentFieldSettings_checkboxGroup(self::FIELD_LANGS);
		$asLangs = AnwComponent::globalCfgLangs();
		$asEnumValues = array();
		foreach ($asLangs as $sLang)
		{
			$sLangName = $sLang." - ".Anwi18n::langName($sLang);
			$asEnumValues[$sLang] = $sLangName;
		}
		$oContentField->setEnumValues($asEnumValues);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
	
	function doTestContentFieldValueComposed($oSubContent)
	{
		//must select at least one lang when selecting POLICY_SELECTED_LANGS
		$sPolicy = $oSubContent->getContentFieldValue(self::FIELD_POLICY);
		$asLangs = $oSubContent->getContentFieldValues(self::FIELD_LANGS);
		
		if ($sPolicy == self::POLICY_SELECTED_LANGS)
		{
			if (count($asLangs)==0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_langs_nolang");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
		else
		{
			if (count($asLangs)>0)
			{
				$sError = $this->getComponent()->t_contentfieldsettings("err_setting_policy_selected_langs_notselected");
				throw new AnwInvalidContentFieldValueException($sError);
			}
		}
	}
}


//--------------------------
// Privileges tab
//--------------------------

class AnwContentFieldSettings_aclsconfigrule_privileges extends AnwContentFieldSettings_tab implements AnwISettings_aclsconfigrule_privileges
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_aclsconfigrule_privilegerule(self::FIELD_PRIVILEGERULES);
		$oMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_aclsconfigrule_privilegerule extends AnwContentFieldSettings_container implements AnwISettings_aclsconfigrule_privilegerule
{
	function init()
	{
		parent::init();		
		
		$oContentField = new AnwContentFieldSettings_userlogin(self::FIELD_USERS);
		$oMultiplicity = new AnwContentMultiplicity_multiple(1);
		$oContentField->setMultiplicity($oMultiplicity);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_PHP_EDITION);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_UNSAFE_EDITION);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_IS_ADMIN);
		$this->addContentField($oContentField);
	}
}


?>