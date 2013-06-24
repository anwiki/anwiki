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
 * ContentFieldSettings for global component.
 * @package Anwiki
 * @version $Id: global-settings.php 230 2010-01-09 21:41:39Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

// -------- setup --------

class AnwContentFieldSettings_globalSetup extends AnwContentFieldSettings_tab implements AnwISettings_globalSetup
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalSetupLocation(self::FIELD_LOCATION);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSetupI18n(self::FIELD_I18N);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSetupCookies(self::FIELD_COOKIES);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSetupPrefixes(self::FIELD_PREFIXES);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSetupLocation extends AnwContentFieldSettings_container implements AnwISettings_globalSetupLocation
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_url(self::FIELD_URLROOT, array(AnwDatatype_url::TYPE_FULL));
		$oContentField->addAllowedPattern("!^http://(.+)/$!");
		$oContentField->addAllowedPattern("!^https://(.+)/$!");
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_url(self::FIELD_HOMEPAGE, array(AnwDatatype_url::TYPE_RELATIVE));
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_FRIENDLYURL_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_NOINDEXURL_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_WEBSITE_NAME);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSetupI18n extends AnwContentFieldSettings_container implements AnwISettings_globalSetupI18n
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_select(self::FIELD_LANG_DEFAULT);
		$asEnumValues = array();
		$asLangs = AnwComponent::globalCfgLangs();
		foreach ($asLangs as $sLang)
		{
			$sLangName = $sLang." - ".Anwi18n::langName($sLang);
			$asEnumValues[$sLang] = $sLangName;
		}
		$oContentField->setEnumValues($asEnumValues);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_LANGS);
		$oContentField->addAllowedPattern("!^.{".Anwi18n::MINLEN_LANG.",".Anwi18n::MAXLEN_LANG."}$!");
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_select(self::FIELD_TIMEZONE_DEFAULT);
		$anEnumValues = array();
		$anTimezones = AnwUsers::getTimezones();
		foreach ($anTimezones as $nTimezone)
		{
			$sTimezoneName = Anwi18n::timezoneName($nTimezone);
			$anEnumValues[$nTimezone] = $sTimezoneName;
		}
		$oContentField->setEnumValues($anEnumValues);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSetupCookies extends AnwContentFieldSettings_container implements AnwISettings_globalSetupCookies
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_url(self::FIELD_PATH, array(AnwDatatype_url::TYPE_ABSOLUTE));
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_DOMAIN);
		$oContentField->addForbiddenPattern("!^\.example\.com!");
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSetupPrefixes extends AnwContentFieldSettings_container implements AnwISettings_globalSetupPrefixes
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_SESSION);
		$oContentField->addAllowedPattern("!^[a-z_]*$!");
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_string(self::FIELD_COOKIES);
		$oContentField->addAllowedPattern("!^[a-z_]*$!");
		$this->addContentField($oContentField);
	}
}

// -------- components --------

class AnwContentFieldSettings_globalComponents extends AnwContentFieldSettings_tab implements AnwISettings_globalComponents
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalComponentsDrivers(self::FIELD_DRIVERS);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalComponentsModules(self::FIELD_MODULES);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalComponentsDrivers extends AnwContentFieldSettings_container implements AnwISettings_globalComponentsDrivers
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_STORAGE);
		$asEnumValues = AnwStorageDriver::getAvailableComponents(AnwComponent::TYPE_STORAGEDRIVER);
		$oContentField->setEnumValuesFromList($asEnumValues);
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_SESSIONS);
		$asEnumValues = AnwSessionsDriver::getAvailableComponents(AnwComponent::TYPE_SESSIONSDRIVER);
		$oContentField->setEnumValuesFromList($asEnumValues);
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_USERS);
		$asEnumValues = AnwUsersDriver::getAvailableComponents(AnwComponent::TYPE_USERSDRIVER);
		$oContentField->setEnumValuesFromList($asEnumValues);
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_radio(self::FIELD_ACLS);
		$asEnumValues = AnwAclsDriver::getAvailableComponents(AnwComponent::TYPE_ACLSDRIVER);
		$oContentField->setEnumValuesFromList($asEnumValues);
		$oContentField->setMandatory(true);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalComponentsModules extends AnwContentFieldSettings_container implements AnwISettings_globalComponentsModules
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_componentSelection(self::FIELD_PLUGINS, AnwComponent::TYPE_PLUGIN);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(true);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_componentSelection(self::FIELD_CONTENTCLASSES, AnwComponent::TYPE_CONTENTCLASS);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(true);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_componentSelection(self::FIELD_ACTIONS, AnwComponent::TYPE_ACTION);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(true);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);		
	}
}

// -------- prefs --------

class AnwContentFieldSettings_globalPrefs extends AnwContentFieldSettings_tab implements AnwISettings_globalPrefs
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalPrefsLocks(self::FIELD_LOCKS);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalPrefsUsers(self::FIELD_USERS);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalPrefsMisc(self::FIELD_MISC);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalPrefsLocks extends AnwContentFieldSettings_container implements AnwISettings_globalPrefsLocks
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_EXPIRY);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_RENEWRATE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_ALERT);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_REFRESHRATE);
		$this->addContentField($oContentField);
		
	}
}

class AnwContentFieldSettings_globalPrefsUsers extends AnwContentFieldSettings_container implements AnwISettings_globalPrefsUsers
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_REGISTER_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_UNIQUE_EMAIL);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_UNIQUE_DISPLAYNAME);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CHANGE_DISPLAYNAME);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalPrefsMisc extends AnwContentFieldSettings_container implements AnwISettings_globalPrefsMisc
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_HISTORY_EXPIRATION);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_integer(self::FIELD_HISTORY_MIN_REVISIONS);
		$oContentField->setValueMin(1);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_integer(self::FIELD_VIEW_UNTRANSLATED_MINPERCENT);
		$oContentField->setValueMin(0);
		$oContentField->setValueMax(100);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_SHOW_EXECTIME);
		$this->addContentField($oContentField);
	}
}

// -------- security --------

class AnwContentFieldSettings_globalSecurity extends AnwContentFieldSettings_tab implements AnwISettings_globalSecurity
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalSecurityHttps(self::FIELD_HTTPS);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSecurityReauth(self::FIELD_REAUTH);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSecuritySession(self::FIELD_SESSION);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSecurityMisc(self::FIELD_MISC);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSecurityHttps extends AnwContentFieldSettings_container implements AnwISettings_globalSecurityHttps
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_url(self::FIELD_URL, array(AnwDatatype_url::TYPE_FULL));
		$oContentField->addAllowedPattern("!^https://!");
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSecurityReauth extends AnwContentFieldSettings_container implements AnwISettings_globalSecurityReauth
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_DELAY);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSecuritySession extends AnwContentFieldSettings_container implements AnwISettings_globalSecuritySession
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_RESUME_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CHECKIP);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CHECKCLIENT);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CHECKSERVER);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSecurityMisc extends AnwContentFieldSettings_container implements AnwISettings_globalSecurityMisc
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_PHPEVAL_ENABLED);
		$this->addContentField($oContentField);
	}
}


// -------- system --------

class AnwContentFieldSettings_globalSystem extends AnwContentFieldSettings_tab implements AnwISettings_globalSystem
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalSystemTrace(self::FIELD_TRACE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSystemReport(self::FIELD_REPORT);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSystemCache(self::FIELD_CACHE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalSystemMisc(self::FIELD_MISC);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSystemTrace extends AnwContentFieldSettings_container implements AnwISettings_globalSystemTrace
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_ip_address(self::FIELD_VIEW_IPS);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);		
	}
}

class AnwContentFieldSettings_globalSystemReport extends AnwContentFieldSettings_container implements AnwISettings_globalSystemReport
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_FILE_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_MAIL_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_email_address(self::FIELD_MAIL_ADDRESSES);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);		
	}
}

class AnwContentFieldSettings_globalSystemCache extends AnwContentFieldSettings_container implements AnwISettings_globalSystemCache
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHEPLUGINS_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHECOMPONENTS_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHEACTIONS_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHEOUTPUT_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHEBLOCKS_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHELOOPS_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_CACHEPAGES_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_LOOPS_AUTO_CACHETIME);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_LOOPS_AUTO_CACHEBLOCK);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_SYMLINKS_RELATIVE);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalSystemMisc extends AnwContentFieldSettings_container implements AnwISettings_globalSystemMisc
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_KEEPALIVE_DELAY);
		$this->addContentField($oContentField);
	}
}


// -------- advanced --------

class AnwContentFieldSettings_globalAdvanced extends AnwContentFieldSettings_tab implements AnwISettings_globalAdvanced
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalAdvancedStatics(self::FIELD_STATICS);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalAdvancedStatics extends AnwContentFieldSettings_container implements AnwISettings_globalAdvancedStatics
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_globalAdvancedStaticsItem(self::FIELD_SHARED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_globalAdvancedStaticsItem(self::FIELD_SETUP);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_globalAdvancedStaticsItem extends AnwContentFieldSettings_container implements AnwISettings_globalAdvancedStaticsItem
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_boolean(self::FIELD_ENABLED);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_url(self::FIELD_URL, array(AnwDatatype_url::TYPE_FULL));
		$oContentField->addAllowedPattern("!^http://!");
		$this->addContentField($oContentField);
	}
}


?>