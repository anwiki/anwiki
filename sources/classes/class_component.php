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
 * A component is a modular part of Anwiki, which can be enabled or disabled.
 * Each component can have its own translation files and configuration files.
 * Anwiki ContentClasses, actions, plugins, drivers are components.
 * @package Anwiki
 * @version $Id: class_component.php 363 2011-07-19 22:15:56Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwIComponent
{
	static function getComponentsRootDir();
	static function getComponentsDirsBegin();
	static function getComponentDir($sName); //TODO due to "stricts oo standards", have to duplicate this function in all components class...
	static function getMyComponentType();
	
	static function discoverEnabledComponents();
	static function loadComponent($sComponentName);	
}

abstract class AnwComponent implements AnwIComponent
{
	private $sName;
	private $bIsAddon;
	private $tpl;
	private $oContentClassSettings; //for those implementing AnwConfigurable
	private $oContentSettings; //for those implementing AnwConfigurable
	private $amCfg;
	
	private $bTranslationsContentFieldSettingsLoaded = false;
	private $bTranslationsContentFieldPageLoaded = false;
	private $bTranslationsEditContentLoaded = false;
	
	private static $aoComponentsLoaded = array();
	private static $aaComponentsMapping;
	
	private static $aaTmpAvailableComponents; //TODO quick hack for avoiding infinite loop
	
	abstract function getComponentName();
	
	static function getGlobalComponentFullDir()
	{
		return ANWDIR_GLOBAL.'global/'; //TODO
	}
	
	static function getGlobalComponent()
	{
		return AnwComponentGlobal::getInstance();
	}
	
	function isAddon()
	{
		return $this->bIsAddon;
	}
	
	function initComponent($sName, $bIsAddon)
	{
		$this->sName = $sName;
		$this->bIsAddon = $bIsAddon;
		
		//load translations
		try {
			Anwi18n::loadTranslationsFromPath($this->getComponentName(), $this->getMyComponentDir().ANWDIR_LANG, $this->isAddon(), $this->getComponentName());
		}
		catch(AnwException $e){}
	}
	
	function getName()
	{
		return $this->sName;
	}
	
	// template
	
	protected function tpl()
	{
		if (!$this->tpl)
		{
			//load template
			try {
				$this->tpl = $this->loadTemplate();
			}
			catch(AnwException $e)
			{
				$this->tpl = new AnwTemplateOverride_global($this);
			}
		}
		return $this->tpl;
	}
	
	// translation file
	
	public function t_($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		return Anwi18n::t_($id, $asParams, $this->getComponentName(), $sLangIfLocal, $sValueIfNotFound);
	}		
	public static function g_($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		return self::getGlobalComponent()->t_($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	
	public function t_contentfieldsettings($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false) //used by contentfield
	{
		$sPrefix = $this->getComponentName().'-settings';
		
		if (!$this->bTranslationsContentFieldSettingsLoaded)
		{
			//load translations
			$this->bTranslationsContentFieldSettingsLoaded = true;
			try {
				Anwi18n::loadTranslationsFromPath($sPrefix, $this->getMyComponentDir().ANWDIR_LANG, $this->isAddon(), $sPrefix);
			}
			catch(AnwException $e){}
		}
		
		return Anwi18n::t_($id, $asParams, $sPrefix, $sLangIfLocal, $sValueIfNotFound);
	}	
	
	public function t_contentfieldpage($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		$sPrefix = $this->getComponentName().'-contentfields';
		
		if (!$this->bTranslationsContentFieldSettingsLoaded)
		{
			//load translations
			$this->bTranslationsContentFieldSettingsLoaded = true;
			try {
				Anwi18n::loadTranslationsFromPath($sPrefix, $this->getMyComponentDir().ANWDIR_LANG, $this->isAddon(), $sPrefix);
			}
			catch(AnwException $e){}
		}
		
		$sTranslation = AnwPlugins::vhook('component_t_contentfieldpage_override', null, $this, $id, $asParams, $sLangIfLocal, $sValueIfNotFound);
		if (!$sTranslation) {
			$sTranslation = Anwi18n::t_($id, $asParams, $sPrefix, $sLangIfLocal, $sValueIfNotFound);
		}
		return $sTranslation;
	}	
	
	
	public function t_editcontent($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false) //used by contentfield
	{
		$sPrefix = $this->getComponentName().'-editcontent';
		
		if (!$this->bTranslationsEditContentLoaded)
		{
			//load translations
			$this->bTranslationsEditContentLoaded = true;
			try {
				Anwi18n::loadTranslationsFromPath($sPrefix, $this->getMyComponentDir().ANWDIR_LANG, $this->isAddon(), $sPrefix);
			}
			catch(AnwException $e){}
		}
		
		return Anwi18n::t_($id, $asParams, $sPrefix, $sLangIfLocal, $sValueIfNotFound);
	}
	public static function g_editcontent($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false) //used by contentfield
	{
		return self::getGlobalComponent()->t_editcontent($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	
	
	
	function loadContentFieldsTranslations()
	{
		//load translations
		
	}
	
	function loadContentFieldsSettings()
	{
		//load translations
		try {
			Anwi18n::loadTranslationsFromPath($this->getComponentName().'-settings', $this->getMyComponentDir().ANWDIR_LANG, $this->isAddon(), $this->getComponentName().'-settings');
		}
		catch(AnwException $e){}
	}
	
	
	// configuration file
	// TODO: these methods should only exist for components implementing AnwConfigurable
	// not possible as multiple inherit isn't possible
	
	protected function cfg($sConfigItemName)
	{
		static $abWasHere=array(); //quick hack for dead loops
		
		//we can't use any kind of cache here because of dead loop cfg()->cfg()
		if (!$this->amCfg)
		{
			if (!isset($abWasHere[$this->getMyComponentType()][$this->getComponentName()])) {
				$abWasHere[$this->getMyComponentType()][$this->getComponentName()] = true;	
				
				// normal process here...
				try {
					$this->amCfg = AnwCache_componentConfiguration::getCachedComponentConfiguration($this);
				}
				catch(AnwException $e) {
					//$this->amCfg = $this->retrieveSettingsArrayFromScratch();
					//load config, then put it in cache...
					$this->amCfg = AnwUtils::getSettingsFromFile($this->getComponentName().".cfg.php", $this->getMyComponentDir());
					AnwCache_componentConfiguration::putCachedComponentConfiguration($this, $this->amCfg);
				}
			}
			else {
				// special process for quick hack...
				AnwDebug::log("cfg - deadloop detected, loading settings using alternate way for ".$this->getMyComponentType()."/".$this->getComponentName());
				
				//TODO: quick hack for dead loops problem when global settings are not in cache first time...
				//when calling retrieveSettingsArrayFromScratch(), it will initialize ContentFieldSettings() which may call again cfg().
				//to prevent dead loops, we just use the old way for loading config just during the time we generate config correctly and put it in cache.
				/*$this->amCfg = AnwUtils::getSettingsFromFile($this->getComponentName().".cfg.php", $this->getMyComponentDir());
				return $this->amCfg;*/
				
				//TODO temporary check, to remove
				print "dead lock detected";
				exit;
			}
			
		}
		
		if (!isset($this->amCfg[$sConfigItemName]))
		{
			throw new AnwUnexpectedException("Unable to get config : ".$sConfigItemName);
		}
		
		return $this->amCfg[$sConfigItemName];
	}
	
	private function retrieveSettingsArrayFromScratch() {
		$oConfigurableContent = $this->getConfigurableContent();
		$amSettings = $oConfigurableContent->toFusionedCfgArray();
		return $amSettings;
	}
	
	/**
	 * Only for unittest usage.
	 */
	function ___unittest_getContentClassSettings($cfgDefault)
	{
		AnwUtils::checkFriendAccess("AnwSettingsTestCase");
		if (is_array($cfgDefault))
		{
			$this->getContentClassSettings()->setContentFieldsDefaultValuesFromArray($cfgDefault);
		}
		return $this->getContentClassSettings();
	}
	
	protected function getContentClassSettings()
	{
		if (!$this->oContentClassSettings)
		{
			//specific contentfieldsettings are declared in a separate file for performances improvement
			try	
			{
				loadApp($this->getMyComponentPathDefault().$this->getComponentName().'-settings.php');
			}
			catch(AnwException $e){}
			
			$this->oContentClassSettings = new AnwContentClassSettings($this);
			$aoSettings = $this->getConfigurableSettings();
			foreach ($aoSettings as $oSetting)
			{
				$this->oContentClassSettings->addContentField($oSetting);
			}
			
			//initialize contentfield's default values
			try
			{
				$cfgDefault = $this->getCfgArrayDefault();
				$this->oContentClassSettings->setContentFieldsDefaultValuesFromArray($cfgDefault);
			}
			catch(AnwFileNotFoundException $e){} //no default settings
		}
		return $this->oContentClassSettings;
	}
	
	function getCfgArrayDefault()
	{
		try
		{
			$sFileDefault = $this->getConfigurableFileDefault(); //throws exception
			$cfg = null; //defined in file
			(require ($sFileDefault)) or die("Unable to load configuration file : ".$sFileDefault);
			return $cfg;
		}
		catch(AnwFileNotFoundException $e)
		{
			throw $e;
		}
	}
	
	function getCfgArrayOverride()
	{
		$sFileOverride = $this->getConfigurableFileOverride();
		if (file_exists($sFileOverride))
		{
			$cfg = null; //defined in file
			(require ($sFileOverride)) or die("Unable to load configuration file : ".$sFileOverride);
			return $cfg;
		}
		else
		{
			throw new AnwFileNotFoundException("No override configuration found for this component");
		}
	}
	
	protected function getConfigurableContent()
	{
		if (!$this->oContentSettings)
		{
			//initialize contentclass
			$oContentClassSettings = $this->getContentClassSettings();			
			//$oContentClassSettings->init();
			
			$this->oContentSettings = new AnwContentSettings($oContentClassSettings);
			$this->oContentSettings->readSettings();
			
			/*print htmlentities($this->oContentSettings->toXmlString());
			print '<hr/>';
			$this->oContentSettings->writeSettingsOverride($sFilePathOverride);
			print '<hr/>';*/
		}
		return $this->oContentSettings;
	}
	
	/**
	 * Private function, only for notifications from AnwContentSettings.
	 */
	public function ___notifyConfigurableContentChanged()
	{
		AnwUtils::checkFriendAccess("AnwContentSettings");
		$this->oContentSettings = null;
	}
	
	/**
	 * Check configurable content for errors.
	 */
	function checkConfigurableContentValidity()
	{
		$this->getConfigurableContent()->checkContentValidity();
	}

	function getConfigurableFileDefault()
	{
		list($sFilePathDefault, $null) =  AnwUtils::getFileDefault($this->getComponentName().".cfg.php", $this->getMyComponentDir()); //throws exception
		return $sFilePathDefault;
	}
	
	function getConfigurableFileOverride()
	{
		$sFilePathOverride =  AnwUtils::getFileOverride($this->getComponentName().".cfg.php", $this->getMyComponentDir(), true); //return even if it doesnt exist
		return $sFilePathOverride;
	}
	
	
	protected static function getComponentsMapping()
	{
		if (!self::$aaComponentsMapping)
		{
			$bCacheAvailableComponentsEnabled = self::globalCfgCacheComponentsEnabled();
			try
			{
				//load available components mapping
				if (!$bCacheAvailableComponentsEnabled) throw new AnwCacheNotFoundException();
				self::$aaComponentsMapping = AnwCache_componentsMapping::getCachedComponentsMapping();
				AnwDebug::log("Loading available components mapping from cache");
			}
			catch(AnwException $e)
			{
				//generate new mapping
				AnwDebug::log("Generating available components mapping");
				self::$aaComponentsMapping = self::generateComponentsMapping();
				if ($bCacheAvailableComponentsEnabled) AnwCache_componentsMapping::putCachedComponentsMapping(self::$aaComponentsMapping);
			}
		}
		return self::$aaComponentsMapping;
	}
	
	const MAPPING_AVAILABLE_COMPONENTS = "AVAILABLE_COMPONENTS";
	const MAPPING_CONFIGURABLE_COMPONENTS = "CONFIGURABLE_COMPONENTS";
	const MAPPING_ENABLED_COMPONENTS = "ENABLED_COMPONENTS";
	
	private static function generateComponentsMapping()
	{
		$aaComponentsMapping = array(
			self::MAPPING_AVAILABLE_COMPONENTS => array(),
			self::MAPPING_CONFIGURABLE_COMPONENTS => array(),
			self::MAPPING_ENABLED_COMPONENTS => array()
		);
		
		$asComponentsTypes = self::getComponentsTypes();
		foreach ($asComponentsTypes as $sComponentType)
		{
			$aaComponentsMapping[self::MAPPING_AVAILABLE_COMPONENTS][$sComponentType] = array();
			$aaComponentsMapping[self::MAPPING_CONFIGURABLE_COMPONENTS][$sComponentType] = array();
			$aaComponentsMapping[self::MAPPING_ENABLED_COMPONENTS][$sComponentType] = array();
			
			$asAvailableComponents = self::discoverAvailableComponents($sComponentType);
			
			//TODO quick hack
			self::$aaTmpAvailableComponents[$sComponentType] = $asAvailableComponents;
			
			//enabled components
			$asEnabledComponents = self::discoverComponentsEnabled($sComponentType); //do not use oComponent->isMyComponentEnabled() else eternal loop
			
			foreach ($asAvailableComponents as $sComponentName)
			{
				//available components
				$aaComponentsMapping[self::MAPPING_AVAILABLE_COMPONENTS][$sComponentType][] = $sComponentName;
				
				//configurable components
				$oComponent = self::loadComponentGeneric($sComponentName, $sComponentType);
				if ($oComponent instanceof AnwConfigurable)
				{
					$aaComponentsMapping[self::MAPPING_CONFIGURABLE_COMPONENTS][$sComponentType][] = $sComponentName;
				}
				
				if (in_array($sComponentName, $asEnabledComponents))
				{
					$aaComponentsMapping[self::MAPPING_ENABLED_COMPONENTS][$sComponentType][] = $sComponentName;
				}
			}
		}
		return $aaComponentsMapping;	
	}
	
	const TYPE_GLOBAL = 'global';
	const TYPE_ACTION = 'action';
	const TYPE_PLUGIN = 'plugin';
	const TYPE_CONTENTCLASS = 'contentclass';
	const TYPE_STORAGEDRIVER = 'storagedriver';
	const TYPE_SESSIONSDRIVER = 'sessionsdriver';
	const TYPE_USERSDRIVER = 'usersdriver';
	const TYPE_ACLSDRIVER = 'aclsdriver';
	
	protected static function getComponentsTypes()
	{
		$asComponentsTypes = array();
		$asComponentsTypes['AnwComponentGlobal'] = self::TYPE_GLOBAL;
		$asComponentsTypes['AnwAction'] = self::TYPE_ACTION;
		$asComponentsTypes['AnwPlugin'] = self::TYPE_PLUGIN;
		$asComponentsTypes['AnwContentClassPage'] = self::TYPE_CONTENTCLASS;
		$asComponentsTypes['AnwStorageDriver'] = self::TYPE_STORAGEDRIVER;
		$asComponentsTypes['AnwSessionsDriver'] = self::TYPE_SESSIONSDRIVER;
		$asComponentsTypes['AnwUsersDriver'] = self::TYPE_USERSDRIVER;
		$asComponentsTypes['AnwAclsDriver'] = self::TYPE_ACLSDRIVER;
		return $asComponentsTypes;
	}
	
	protected static function getComponentTypeForClass($sClassName)
	{
		$asComponentsTypes = self::getComponentsTypes();
		if (!isset($asComponentsTypes[$sClassName])) throw new AnwUnexpectedException("getComponentTypeForClass not found: ".$sClassName);
		return $asComponentsTypes[$sClassName];
	}
	
	protected static function getComponentClassForType($sType)
	{
		$asComponentsTypes = self::getComponentsTypes();
		foreach ($asComponentsTypes as $sOneClassName => $sOneType)
		{
			if ($sOneType == $sType)
			{
				return $sOneClassName;
			}
		}
		throw new AnwUnexpectedException("getComponentClassForType not found: ".$sType);
	}
	
	
	static function getAvailableComponents($sComponentType)
	{
		//todo quick hack
		if(isset(self::$aaTmpAvailableComponents[$sComponentType]))
		{
			return self::$aaTmpAvailableComponents[$sComponentType];
		}
		
		$aaMapping = self::getComponentsMapping();
		return $aaMapping[self::MAPPING_AVAILABLE_COMPONENTS][$sComponentType];
	}
	
	private static function discoverAvailableComponents($sType)
	{
		//TODO quick hack
		if ($sType == self::TYPE_GLOBAL) return array('global');
		
		$sClassName = self::getComponentClassForType($sType);
		
		//TODO $sComponentsDir = self::getComponentsRootDir();
		$sComponentsDir = call_user_func_array(array($sClassName, 'getComponentsRootDir'), array());
		
		//TODO $sComponentsDirBegin = self::getComponentsDirsBegin();
		$sComponentsDirBegin = call_user_func_array(array($sClassName, 'getComponentsDirsBegin'), array());
		$sComponentsDirBeginLen = strlen($sComponentsDirBegin);
		if ($sComponentsDirBeginLen<4) //just in case of...
		{
			throw new AnwUnexpectedException("nComponentsDirBeginLen suspect");
		}
		
		$asAvailableComponents = array();
		$asAvailableComponents = self::doDiscoverAvailableComponents(ANWPATH_DEFAULT, $sComponentsDir, $asAvailableComponents, $sComponentsDirBegin, $sComponentsDirBeginLen);
		$asAvailableComponents = self::doDiscoverAvailableComponents(ANWPATH_ADDONS, $sComponentsDir, $asAvailableComponents, $sComponentsDirBegin, $sComponentsDirBeginLen);
		return $asAvailableComponents;
	}
	
	private static function doDiscoverAvailableComponents($sRootPath, $sComponentsDir, $asAvailableComponents, $sComponentsDirBegin, $sComponentsDirBeginLen)
	{
		$sFullComponentsRootDir = $sRootPath.$sComponentsDir;
		if (!is_dir($sFullComponentsRootDir) || !$mDirHandle = opendir($sFullComponentsRootDir))
		{
			//addons directory may not be created
			return $asAvailableComponents;
		}
		while (false !== ($sFilename = readdir($mDirHandle)))
		{
			$sFilenameFull = $sFullComponentsRootDir.$sFilename.'/';
			if (is_dir($sFilenameFull) && substr($sFilename, 0, $sComponentsDirBeginLen) == $sComponentsDirBegin)
			{
				$sComponentName = substr($sFilename, $sComponentsDirBeginLen);
				$asAvailableComponents[$sComponentName] = $sComponentName;
			}
		}
		closedir($mDirHandle);
		return $asAvailableComponents;
	}
	
	static function getEnabledComponents($sComponentType)
	{
		$aaMapping = self::getComponentsMapping();
		return $aaMapping[self::MAPPING_ENABLED_COMPONENTS][$sComponentType];
	}
	
	static function getConfigurableComponents($sComponentType)
	{
		$aaMapping = self::getComponentsMapping();
		return $aaMapping[self::MAPPING_CONFIGURABLE_COMPONENTS][$sComponentType];
	}
	
	static function getAllConfigurableComponents()
	{
		$aaConfigurableComponents = array();
		
		$aaComponentsTypes = self::getComponentsTypes();
		foreach ($aaComponentsTypes as $sComponentType)
		{
			$aaConfigurableComponents[$sComponentType] = self::getConfigurableComponents($sComponentType);
		}
		
		return $aaConfigurableComponents;
	}
	
	private static function discoverComponentsEnabled($sComponentType)
	{
		$sClassName = self::getComponentClassForType($sComponentType);
		$asEnabledComponents = call_user_func_array(array($sClassName, 'discoverEnabledComponents'), array());
		return $asEnabledComponents;
	}
	
	static function isComponentEnabled($sName, $sComponentType)
	{
		return in_array($sName, self::getEnabledComponents($sComponentType));
	}
	
	function isMyComponentEnabled()
	{
		return self::isComponentEnabled($this->getName(), $this->getMyComponentType());
	}
	
	protected static function getComponentGenericIfLoaded($sComponentName, $sComponentType)
	{
		if (!isset(self::$aoComponentsLoaded[$sComponentType][$sComponentName]))
		{
			throw new AnwUnexpectedException("getComponentGenericIfLoaded: not loaded");
		}
		return self::$aoComponentsLoaded[$sComponentType][$sComponentName];
	}
	
	protected static function registerComponentGenericLoaded($sComponentName, $sComponentType, $oComponent)
	{
		if (isset(self::$aoComponentsLoaded[$sComponentType][$sComponentName]))
		{
			throw new AnwUnexpectedException("registerComponentGenericLoaded: already loaded: ".$sComponentName."/".$sComponentType);
		}
		self::$aoComponentsLoaded[$sComponentType][$sComponentName] = $oComponent;
	}
	
	
	static function loadComponentGeneric($sComponentName, $sComponentType)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sComponentName, $sComponentType);
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}
		
		$sClassName = self::getComponentClassForType($sComponentType);
		$oComponent = call_user_func_array(array($sClassName, 'loadComponent'), array($sComponentName));
				
		//no need to register component here, it has been done by loadComponent()
		return $oComponent;
	}
	
	protected static function requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix, $bIsAddon=-1)
	{
		$sClassNameDefault = str_replace('%%', 'Default', $sClassNamePrefix); //from ANWPATH_DEFAULT and ANWPATH_ADDONS
		$sClassNameOverride = str_replace('%%', 'Override', $sClassNamePrefix);
		
		//skip if already loaded
		//TODO: (in some case, we may have already loaded a contentclass 
		//when several contentclasses are defined in the same file)
		if (class_exists($sClassNameOverride, false)) //disable autoload to avoids multiple class loading
		{
			//throw new AnwUnexpectedException("requireCustomOrDefault : ".$sClassNameOverride." is ALREADY LOADED!");
			AnwDebug::log("requireCustomOrDefault : ".$sClassNameOverride." is ALREADY LOADED!");
			list($null, $bIsAddon) = AnwUtils::getFileDefault($sFile, $sDir);
			return array($sClassNameOverride, $bIsAddon);
		}
		else if (class_exists($sClassNameDefault, false)) //disable autoload to avoids multiple class loading
		{
			//throw new AnwUnexpectedException("requireCustomOrDefault : ".$sClassNameDefault." is ALREADY LOADED!");
			AnwDebug::log("requireCustomOrDefault : ".$sClassNameDefault." is ALREADY LOADED!");
			list($null, $bIsAddon) = AnwUtils::getFileDefault($sFile, $sDir);
			return array($sClassNameDefault, $bIsAddon);
		}
		
		//firstly, load default
		if ($bIsAddon!=-1)
		{
			//we already know where is the default file
			$sFileName = ($bIsAddon ? ANWPATH_ADDONS : ANWPATH_DEFAULT).$sDir.$sFile;
		}
		else
		{
			//we don't know where is the default file... let's search in ADDONS and DEFAULT directories
			list($sFileName, $bIsAddon) = AnwUtils::getFileDefault($sFile, $sDir);
		}
		AnwDebug::log("Loading default : ".$sFileName);
		loadApp($sFileName);
		$classname = $sClassNameDefault;
		
		//load override if any
		try {
			$sFileName = AnwUtils::getFileOverride($sFile, $sDir);
			AnwDebug::log("Loading override : ".$sFileName);
			loadApp($sFileName);
			$classname = $sClassNameOverride;
		}
		catch(AnwException $e){}
		
		return array($classname, $bIsAddon);
	}
	
	protected function getMyComponentPathDefault()
	{
		$sPathRoot = ($this->isAddon() ? ANWPATH_ADDONS : ANWPATH_DEFAULT);
		$sUrlStatic = $sPathRoot.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	protected function getMyComponentPathOverride()
	{
		$sPathRoot = ANWPATH_OVERRIDE;
		$sUrlStatic = $sPathRoot.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	
	protected function getMyComponentPathStaticDefault()
	{
		$sPathRoot = ($this->isAddon() ? ANWPATH_ADDONS_STATIC : ANWPATH_DEFAULT_STATIC);
		$sUrlStatic = $sPathRoot.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	protected function getMyComponentPathStaticOverride()
	{
		$sPathRoot = ANWPATH_OVERRIDE_STATIC;
		$sUrlStatic = $sPathRoot.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	
	protected function getMyComponentUrlStaticDefault()
	{
		$sDirRootStatic = ($this->isAddon() ? ANWDIR_ADDONS_STATIC : ANWDIR_DEFAULT_STATIC);
		$sUrlStatic = self::cfgUrlStaticShared().$sDirRootStatic.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	protected function getMyComponentUrlStaticOverride()
	{
		$sDirRootStatic = ANWDIR_OVERRIDE_STATIC;
		$sUrlStatic = self::cfgUrlStaticSetup().$sDirRootStatic.$this->getMyComponentDir();
		return $sUrlStatic;
	}
	
	static function getGlobalPathDefault()
	{
		return self::getGlobalComponent()->getMyComponentPathDefault();
	}
	static function getGlobalPathOverride()
	{
		return self::getGlobalComponent()->getMyComponentPathOverride();
	}
	static function getGlobalUrlStaticDefault()
	{
		return self::getGlobalComponent()->getMyComponentUrlStaticDefault();
	}
	static function getGlobalUrlStaticOverride()
	{
		return self::getGlobalComponent()->getMyComponentUrlStaticOverride();
	}
	static function getGlobalPathStaticDefault()
	{
		return self::getGlobalComponent()->getMyComponentPathStaticDefault();
	}
	static function getGlobalPathStaticOverride()
	{
		return self::getGlobalComponent()->getMyComponentPathStaticOverride();
	}
	
	
	
	static function cfgUrlStaticShared($bFullUrl=false)
	{
		if (AnwComponent::globalCfgStaticsSharedEnabled())
		{
			$sUrlStatic = AnwComponent::globalCfgStaticsSharedUrl();
		}
		else
		{
			if (!$bFullUrl)
			{
				$sUrlStatic = AnwUtils::linkRelative();
			}
			else
			{
				$sUrlStatic = AnwComponent::globalCfgUrlRoot();
			}
		}
		return $sUrlStatic;
	}
	static function cfgUrlStaticSetup($bFullUrl=false)
	{
		if (AnwComponent::globalCfgStaticsSetupEnabled())
		{
			$sUrlStatic = AnwComponent::globalCfgStaticsSetupUrl();
		}
		else
		{
		if (!$bFullUrl)
			{
				$sUrlStatic = AnwUtils::linkRelative();
			}
			else
			{
				$sUrlStatic = AnwComponent::globalCfgUrlRoot();
			}
		}
		return $sUrlStatic;
	}
	
	
	
	
	
	protected function getMyComponentDir()
	{
		//we need to use $this instead of self:: otherwise php doesn't look for implemented methods in child class
		return $this->getComponentDir($this->getName());
	}
	
	private function loadTemplate()
	{
		$sName = $this->getComponentName();
		$sDir = $this->getMyComponentDir();
		$bIsAddon = $this->isAddon();
		
		$sFile = $sName.".tpl.php";
		$sClassNamePrefix = 'AnwTemplate%%_'.$sName;
		list($classname, $null) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix, $bIsAddon);
		
		$oTemplate = new $classname($this);
		return $oTemplate;
	}
	
	
	
	// CSS src
	protected function getCssSrcComponent($sCssFile)
	{
		$sHtml = "";
		if (file_exists($this->getMyComponentPathStaticDefault().$sCssFile))
		{
			$sHtml .= $this->tpl()->headCssSrc($this->getMyComponentUrlStaticDefault().$sCssFile);
		}
		if (file_exists($this->getMyComponentPathStaticOverride().$sCssFile))
		{
			$sHtml .= $this->tpl()->headCssSrc($this->getMyComponentUrlStaticOverride().$sCssFile);
		}
		return $sHtml;
	}
	protected function getCssSrcGlobal($sCssFile)
	{
		$sHtml = "";
		if (file_exists(AnwComponent::getGlobalPathStaticDefault().$sCssFile))
		{
			$sHtml .= $this->tpl()->headCssSrc(AnwComponent::getGlobalUrlStaticDefault().$sCssFile);
		}
		if (file_exists(AnwComponent::getGlobalPathStaticOverride().$sCssFile))
		{
			$sHtml .= $this->tpl()->headCssSrc(AnwComponent::getGlobalUrlStaticOverride().$sCssFile);
		}
		return $sHtml;
	}
	
	//JS src
	function getJsSrcComponent($sJsFile)
	{
		$sHtml = "";
		if (file_exists($this->getMyComponentPathStaticDefault().$sJsFile))
		{
			$sHtml .= $this->tpl()->headJsSrc($this->getMyComponentUrlStaticDefault().$sJsFile);
		}
		if (file_exists($this->getMyComponentPathStaticOverride().$sJsFile))
		{
			$sHtml .= $this->tpl()->headJsSrc($this->getMyComponentUrlStaticOverride().$sJsFile);
		}
		return $sHtml;
	}
	function getJsSrcGlobal($sJsFile)
	{
		$sHtml = "";
		if (file_exists(AnwComponent::getGlobalPathStaticDefault().$sJsFile))
		{
			$sHtml .= $this->tpl()->headJsSrc(AnwComponent::getGlobalUrlStaticDefault().$sJsFile);
		}
		if (file_exists(AnwComponent::getGlobalPathStaticOverride().$sJsFile))
		{
			$sHtml .= $this->tpl()->headJsSrc(AnwComponent::getGlobalUrlStaticOverride().$sJsFile);
		}
		return $sHtml;
	}
	
	
	
	
	
	
	// global setup
	
	static function globalCfgUrlRoot()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_LOCATION]
								[AnwISettings_globalSetupLocation::FIELD_URLROOT];
		return $sCfgValue;
	}
	static function globalCfgHomePage()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		return $amCfgSetup[AnwISettings_globalSetup::FIELD_LOCATION]
								[AnwISettings_globalSetupLocation::FIELD_HOMEPAGE];
	}
	static function globalCfgFriendlyUrlEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		return $amCfgSetup[AnwISettings_globalSetup::FIELD_LOCATION]
								[AnwISettings_globalSetupLocation::FIELD_FRIENDLYURL_ENABLED];
	}
	static function globalCfgNoIndexyUrlEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		return $amCfgSetup[AnwISettings_globalSetup::FIELD_LOCATION]
								[AnwISettings_globalSetupLocation::FIELD_NOINDEXURL_ENABLED];
	}
	static function globalCfgWebsiteName()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		return $amCfgSetup[AnwISettings_globalSetup::FIELD_LOCATION]
								[AnwISettings_globalSetupLocation::FIELD_WEBSITE_NAME];
	}
	static function globalCfgLangDefault()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_I18N]
								[AnwISettings_globalSetupI18n::FIELD_LANG_DEFAULT];
		return $sCfgValue;
	}
	static function globalCfgLangs()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_I18N]
								[AnwISettings_globalSetupI18n::FIELD_LANGS];
		return $sCfgValue;
	}
	static function globalCfgTimezoneDefault()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_I18N]
								[AnwISettings_globalSetupI18n::FIELD_TIMEZONE_DEFAULT];
		return $sCfgValue;
	}
	static function globalCfgCookiesPath()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_COOKIES]
								[AnwISettings_globalSetupCookies::FIELD_PATH];
		return $sCfgValue;
	}
	static function globalCfgCookiesDomain()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_COOKIES]
								[AnwISettings_globalSetupCookies::FIELD_DOMAIN];
		return $sCfgValue;
	}
	static function globalCfgPrefixSession()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_PREFIXES]
								[AnwISettings_globalSetupPrefixes::FIELD_SESSION];
		return $sCfgValue;
	}
	static function globalCfgPrefixCookies()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SETUP);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSetup::FIELD_PREFIXES]
								[AnwISettings_globalSetupPrefixes::FIELD_COOKIES];
		return $sCfgValue;
	}
	
	// components
	
	static function globalCfgDriverStorage()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_DRIVERS]
								[AnwISettings_globalComponentsDrivers::FIELD_STORAGE];
		return $sCfgValue;
	}
	static function globalCfgDriverSessions()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_DRIVERS]
								[AnwISettings_globalComponentsDrivers::FIELD_SESSIONS];
		return $sCfgValue;
	}
	static function globalCfgDriverUsers()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_DRIVERS]
								[AnwISettings_globalComponentsDrivers::FIELD_USERS];
		return $sCfgValue;
	}
	static function globalCfgDriverAcls()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_DRIVERS]
								[AnwISettings_globalComponentsDrivers::FIELD_ACLS];
		return $sCfgValue;
	}
	static function globalCfgModulesPlugins()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_MODULES]
								[AnwISettings_globalComponentsModules::FIELD_PLUGINS];
		return $sCfgValue;
	}
	static function globalCfgModulesContentClasses()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_MODULES]
								[AnwISettings_globalComponentsModules::FIELD_CONTENTCLASSES];
		return $sCfgValue;
	}
	static function globalCfgModulesActions()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_COMPONENTS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalComponents::FIELD_MODULES]
								[AnwISettings_globalComponentsModules::FIELD_ACTIONS];
		return $sCfgValue;
	}
	
	
	// preferences
		
	static function globalCfgLocksExpiry()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_LOCKS]
								[AnwISettings_globalPrefsLocks::FIELD_EXPIRY];
		return $sCfgValue;
	}
	static function globalCfgLocksRenewRate()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_LOCKS]
								[AnwISettings_globalPrefsLocks::FIELD_RENEWRATE];
		return $sCfgValue;
	}
	static function globalCfgLocksAlert()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_LOCKS]
								[AnwISettings_globalPrefsLocks::FIELD_ALERT];
		return $sCfgValue;
	}
	static function globalCfgLocksRefreshRate()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_LOCKS]
								[AnwISettings_globalPrefsLocks::FIELD_REFRESHRATE];
		return $sCfgValue;
	}
	static function globalCfgUsersRegisterEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_USERS]
								[AnwISettings_globalPrefsUsers::FIELD_REGISTER_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgUsersUniqueEmail()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_USERS]
								[AnwISettings_globalPrefsUsers::FIELD_UNIQUE_EMAIL];
		return $sCfgValue;
	}
	static function globalCfgUsersUniqueDisplayname()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_USERS]
								[AnwISettings_globalPrefsUsers::FIELD_UNIQUE_DISPLAYNAME];
		return $sCfgValue;
	}
	static function globalCfgUsersChangeDisplayname()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_USERS]
								[AnwISettings_globalPrefsUsers::FIELD_CHANGE_DISPLAYNAME];
		return $sCfgValue;
	}
	static function globalCfgHistoryExpiration()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_MISC]
								[AnwISettings_globalPrefsMisc::FIELD_HISTORY_EXPIRATION];
		return $sCfgValue;
	}
	static function globalCfgHistoryMinRevisions()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_MISC]
								[AnwISettings_globalPrefsMisc::FIELD_HISTORY_MIN_REVISIONS];
		return $sCfgValue;
	}
	static function globalCfgViewUntranslatedMinpercent()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_MISC]
								[AnwISettings_globalPrefsMisc::FIELD_VIEW_UNTRANSLATED_MINPERCENT];
		return $sCfgValue;
	}
	static function globalCfgShowExecTime()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_PREFS);
		$sCfgValue = $amCfgSetup[AnwISettings_globalPrefs::FIELD_MISC]
								[AnwISettings_globalPrefsMisc::FIELD_SHOW_EXECTIME];
		return $sCfgValue;
	}
	
	// security
	
	static function globalCfgHttpsEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_HTTPS]
								[AnwISettings_globalSecurityHttps::FIELD_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgHttpsUrl()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_HTTPS]
								[AnwISettings_globalSecurityHttps::FIELD_URL];
		return $sCfgValue;
	}
	static function globalCfgReauthEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_REAUTH]
								[AnwISettings_globalSecurityReauth::FIELD_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgReauthDelay()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_REAUTH]
								[AnwISettings_globalSecurityReauth::FIELD_DELAY];
		return $sCfgValue;
	}
	static function globalCfgSessionResumeEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_SESSION]
								[AnwISettings_globalSecuritySession::FIELD_RESUME_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgSessionCheckIp()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_SESSION]
								[AnwISettings_globalSecuritySession::FIELD_CHECKIP];
		return $sCfgValue;
	}
	static function globalCfgSessionCheckClient()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_SESSION]
								[AnwISettings_globalSecuritySession::FIELD_CHECKCLIENT];
		return $sCfgValue;
	}
	static function globalCfgSessionCheckServer()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_SESSION]
								[AnwISettings_globalSecuritySession::FIELD_CHECKSERVER];
		return $sCfgValue;
	}
	static function globalCfgPhpEvalEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SECURITY);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSecurity::FIELD_MISC]
								[AnwISettings_globalSecurityMisc::FIELD_PHPEVAL_ENABLED];
		return $sCfgValue;
	}
	
	// system
	
	static function globalCfgTraceEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_TRACE]
								[AnwISettings_globalSystemTrace::FIELD_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgTraceViewIps()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_TRACE]
								[AnwISettings_globalSystemTrace::FIELD_VIEW_IPS];
		return $sCfgValue;
	}
	static function globalCfgReportFileEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_REPORT]
								[AnwISettings_globalSystemReport::FIELD_FILE_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgReportMailEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_REPORT]
								[AnwISettings_globalSystemReport::FIELD_MAIL_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgReportMailAddresses()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_REPORT]
								[AnwISettings_globalSystemReport::FIELD_MAIL_ADDRESSES];
		return $sCfgValue;
	}
	static function globalCfgCachePluginsEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHEPLUGINS_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCacheComponentsEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHECOMPONENTS_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCacheActionsEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHEACTIONS_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCacheOutputEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHEOUTPUT_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCacheBlocksEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHEBLOCKS_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCacheLoopsEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHELOOPS_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgCachePagesEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_CACHEPAGES_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgLoopsAutoCacheTime()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_LOOPS_AUTO_CACHETIME];
		return $sCfgValue;
	}
	static function globalCfgLoopsAutoCacheblock()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_LOOPS_AUTO_CACHEBLOCK];
		return $sCfgValue;
	}
	static function globalCfgSymlinksRelative()
	{
		if (!AnwEnv::hasSymlink())
		{
			return false;
		}
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_CACHE]
								[AnwISettings_globalSystemCache::FIELD_SYMLINKS_RELATIVE];
		return $sCfgValue;
	}
	static function globalCfgKeepaliveDelay()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_SYSTEM);
		$sCfgValue = $amCfgSetup[AnwISettings_globalSystem::FIELD_MISC]
								[AnwISettings_globalSystemMisc::FIELD_KEEPALIVE_DELAY];
		return $sCfgValue;
	}
	
	// advanced
	
	static function globalCfgStaticsSharedEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_ADVANCED);
		$sCfgValue = $amCfgSetup[AnwISettings_globalAdvanced::FIELD_STATICS]
								[AnwISettings_globalAdvancedStatics::FIELD_SHARED]
								[AnwISettings_globalAdvancedStaticsItem::FIELD_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgStaticsSharedUrl()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_ADVANCED);
		$sCfgValue = $amCfgSetup[AnwISettings_globalAdvanced::FIELD_STATICS]
								[AnwISettings_globalAdvancedStatics::FIELD_SHARED]
								[AnwISettings_globalAdvancedStaticsItem::FIELD_URL];
		return $sCfgValue;
	}
	
	
	static function globalCfgStaticsSetupEnabled()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_ADVANCED);
		$sCfgValue = $amCfgSetup[AnwISettings_globalAdvanced::FIELD_STATICS]
								[AnwISettings_globalAdvancedStatics::FIELD_SETUP]
								[AnwISettings_globalAdvancedStaticsItem::FIELD_ENABLED];
		return $sCfgValue;
	}
	static function globalCfgStaticsSetupUrl()
	{
		$amCfgSetup = self::getGlobalComponent()->cfg(AnwComponentGlobal_global::CFG_ADVANCED);
		$sCfgValue = $amCfgSetup[AnwISettings_globalAdvanced::FIELD_STATICS]
								[AnwISettings_globalAdvancedStatics::FIELD_SETUP]
								[AnwISettings_globalAdvancedStaticsItem::FIELD_URL];
		return $sCfgValue;
	}
}

interface AnwConfigurable 
{
	function getConfigurableSettings();
}

interface AnwInitializable
{
	function isComponentInitialized();
	function initializeComponent();
	function getSettingsForInitialization();
}

interface AnwDependancyManageable
{
	function getComponentDependancies();
}


class AnwCache_componentsMapping extends AnwCache implements AnwCacheCleanable
{
	
	private static function filenameCachedComponentsMapping()
	{
		return ANWPATH_CACHESYSTEM.'components_mapping.php';
	} 
	
	static function putCachedComponentsMapping($aaMapping)
	{
		$sCacheFile = self::filenameCachedComponentsMapping();
		AnwDebug::log("putting cachedComponentsMapping : ".$sCacheFile); 
		self::putCachedObject($sCacheFile, $aaMapping);
	}
	
	static function getCachedComponentsMapping()
	{
		$sCacheFile = self::filenameCachedComponentsMapping();
		if (!file_exists($sCacheFile))
		{
			throw new AnwCacheNotFoundException();
		}
		
		//mapping must be newer than override-global-settings
		if ( filemtime($sCacheFile) < filemtime(AnwUtils::getFileOverride("global.cfg.php", AnwComponent::getGlobalComponentFullDir())))
		{
			AnwDebug::log("cachedComponentsMapping obsoleted by settings");
			throw new AnwCacheNotFoundException();
		}
		
		//TODO: mapping should be expired by each modified available component?
		
		//load it from cache
		$oObject = (array)self::getCachedObject($sCacheFile);
		if (!is_array($oObject))
	 	{
	 		AnwDebug::log("cachedComponentsMapping invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		AnwDebug::log("cachedComponentsMapping found : ".$sCacheFile);
	 	}
		return $oObject;
	}
	
	// used by action_editconfig
	static function clearCache() {
		$sCacheFile = self::filenameCachedComponentsMapping();
		AnwUtils::unlink($sCacheFile, ANWPATH_CACHESYSTEM);
	}
}


//------------------------------------------


abstract class AnwComponentGlobal extends AnwComponent
{
	private static $oComponentGlobal;
	const GLOBAL_COMPONENT_NAME = 'global';
	
	function __construct($sName, $bIsAddon)
	{
		$this->initComponent($sName, $bIsAddon);
	}
	
	function getComponentName()
	{
		return 'global';
	}
	
	static function discoverEnabledComponents()
	{
		return array('global');
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_GLOBAL;
	}
	static function getComponentsDirsBegin()
	{
		return "";
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		//return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
		return self::getComponentsRootDir().'global/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_GLOBAL;
	}
	
	static function loadComponent($sName)
	{
		$oGlobal = self::getInstance();
		self::registerComponentGenericLoaded($sName, self::TYPE_GLOBAL, $oGlobal);		
		return $oGlobal;
	}
	
	static function getInstance()
	{
		if (!self::$oComponentGlobal)
		{
			self::$oComponentGlobal = new AnwComponentGlobal_global(self::GLOBAL_COMPONENT_NAME, false);
		}
		return self::$oComponentGlobal;
	}
}

// -------- setup --------

interface AnwISettings_globalSetup
{
	const FIELD_LOCATION = "location";
	const FIELD_I18N = "i18n";
	const FIELD_COOKIES = "cookies";
	const FIELD_PREFIXES = "prefixes";
}

interface AnwISettings_globalSetupLocation
{
	const FIELD_URLROOT = "urlroot";
	const FIELD_HOMEPAGE = "homepage";
	const FIELD_FRIENDLYURL_ENABLED = "friendlyurl_enabled";
	const FIELD_NOINDEXURL_ENABLED = "noindexurl_enabled";
	const FIELD_WEBSITE_NAME = "website_name";
}

interface AnwISettings_globalSetupI18n
{
	const FIELD_LANG_DEFAULT = "lang_default";
	const FIELD_LANGS = "langs";
	const FIELD_TIMEZONE_DEFAULT = "timezone_default";
}

interface AnwISettings_globalSetupCookies
{
	const FIELD_PATH = "path";
	const FIELD_DOMAIN = "domain";
}

interface AnwISettings_globalSetupPrefixes
{
	const FIELD_SESSION = "session";
	const FIELD_COOKIES = "cookies";
}

// -------- components --------

interface AnwISettings_globalComponents
{
	const FIELD_DRIVERS = "drivers";
	const FIELD_MODULES = "modules";
}

interface AnwISettings_globalComponentsDrivers
{
	const FIELD_STORAGE = "storage";
	const FIELD_SESSIONS = "sessions";
	const FIELD_USERS = "users";
	const FIELD_ACLS = "acls";
}

interface AnwISettings_globalComponentsModules
{
	const FIELD_PLUGINS = "plugins";
	const FIELD_CONTENTCLASSES = "contentclasses";
	const FIELD_ACTIONS = "actions";
}

// -------- prefs --------

interface AnwISettings_globalPrefs
{
	const FIELD_LOCKS = "locks";
	const FIELD_USERS = "users";
	const FIELD_MISC = "misc";
}

interface AnwISettings_globalPrefsLocks
{
	const FIELD_EXPIRY = "expiry";
	const FIELD_RENEWRATE = "renewrate";
	const FIELD_ALERT = "alert";
	const FIELD_REFRESHRATE = "refreshrate";
}

interface AnwISettings_globalPrefsUsers
{
	const FIELD_REGISTER_ENABLED = "register_enabled";
	const FIELD_UNIQUE_EMAIL = "unique_email";
	const FIELD_UNIQUE_DISPLAYNAME = "unique_displayname";
	const FIELD_CHANGE_DISPLAYNAME = "change_displayname";
}

interface AnwISettings_globalPrefsMisc
{
	const FIELD_HISTORY_EXPIRATION = "history_expiration";
	const FIELD_HISTORY_MIN_REVISIONS = "history_min_revisions";
	const FIELD_VIEW_UNTRANSLATED_MINPERCENT = "view_untranslated_minpercent";
	const FIELD_SHOW_EXECTIME = "show_exectime";
}

// -------- security --------

interface AnwISettings_globalSecurity
{
	const FIELD_HTTPS = "https";
	const FIELD_REAUTH = "reauth";
	const FIELD_SESSION = "session";
	const FIELD_MISC = "misc";
}

interface AnwISettings_globalSecurityHttps
{
	const FIELD_ENABLED = "enabled";
	const FIELD_URL = "url";
}

interface AnwISettings_globalSecurityReauth
{
	const FIELD_ENABLED = "enabled";
	const FIELD_DELAY = "delay";
}

interface AnwISettings_globalSecuritySession
{
	const FIELD_RESUME_ENABLED = "resume_enabled";
	const FIELD_CHECKIP = "checkip";
	const FIELD_CHECKCLIENT = "checkclient";
	const FIELD_CHECKSERVER = "checkserver";
}

interface AnwISettings_globalSecurityMisc
{
	const FIELD_PHPEVAL_ENABLED = "phpeval_enabled";
}

// -------- system --------

interface AnwISettings_globalSystem
{
	const FIELD_TRACE = "trace";
	const FIELD_REPORT = "report";
	const FIELD_CACHE = "cache";
	const FIELD_MISC = "misc";
}

interface AnwISettings_globalSystemTrace
{
	const FIELD_ENABLED = "enabled";
	const FIELD_VIEW_IPS = "view_ips";
}

interface AnwISettings_globalSystemReport
{
	const FIELD_FILE_ENABLED = "file_enabled";
	const FIELD_MAIL_ENABLED = "mail_enabled";
	const FIELD_MAIL_ADDRESSES = "mail_addresses";
}

interface AnwISettings_globalSystemCache
{
	const FIELD_CACHEPLUGINS_ENABLED = "cacheplugins_enabled";
	const FIELD_CACHECOMPONENTS_ENABLED = "cachecomponents_enabled";
	const FIELD_CACHEACTIONS_ENABLED = "cacheactions_enabled";
	const FIELD_CACHEOUTPUT_ENABLED = "cacheoutput_enabled";
	const FIELD_CACHEBLOCKS_ENABLED = "cacheblocks_enabled";
	const FIELD_CACHELOOPS_ENABLED = "cacheloops_enabled";
	const FIELD_CACHEPAGES_ENABLED = "cachepages_enabled";
	const FIELD_LOOPS_AUTO_CACHETIME = "loops_auto_cachetime";
	const FIELD_LOOPS_AUTO_CACHEBLOCK = "loops_auto_cacheblock";
	const FIELD_SYMLINKS_RELATIVE = "symlinks_relative";
}

interface AnwISettings_globalSystemMisc
{
	const FIELD_KEEPALIVE_DELAY = "keepalive_delay";
}

// -------- advanced --------

interface AnwISettings_globalAdvanced
{
	const FIELD_STATICS = "statics";
}

interface AnwISettings_globalAdvancedStatics
{
	const FIELD_SHARED = "shared";
	const FIELD_SETUP = "setup";
}

interface AnwISettings_globalAdvancedStaticsItem
{
	const FIELD_ENABLED = "enabled";
	const FIELD_URL = "url";
}

//

class AnwComponentGlobal_global extends AnwComponentGlobal implements AnwConfigurable
{
	const CFG_SETUP = "setup";
	const CFG_COMPONENTS = "components";
	const CFG_PREFS = "prefs";
	const CFG_SECURITY = "security";
	const CFG_SYSTEM = "system";
	const CFG_ADVANCED = "advanced";
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		
		$aoSettings[] = new AnwContentFieldSettings_globalSetup(self::CFG_SETUP);
		$aoSettings[] = new AnwContentFieldSettings_globalComponents(self::CFG_COMPONENTS);
		$aoSettings[] = new AnwContentFieldSettings_globalPrefs(self::CFG_PREFS);
		$aoSettings[] = new AnwContentFieldSettings_globalSecurity(self::CFG_SECURITY);
		$aoSettings[] = new AnwContentFieldSettings_globalSystem(self::CFG_SYSTEM);
		$aoSettings[] = new AnwContentFieldSettings_globalAdvanced(self::CFG_ADVANCED);
		
		return $aoSettings;
	}
}

/**
 * Cache manager for component configuration.
 */
class AnwCache_componentConfiguration extends AnwCache
{

	private static function filenameCachedComponentConfiguration($oComponent)
	{
		return ANWPATH_CACHECONFIG.$oComponent->getMyComponentType().'/'.$oComponent->getComponentName().".php";
	} 
	
	static function putCachedComponentConfiguration($oComponent, $amConfig)
	{
		$sCacheFile = self::filenameCachedComponentConfiguration($oComponent);
		self::debug("putting cachedComponentConfiguration : ".$sCacheFile); 
		self::putCachedObject($sCacheFile, $amConfig);
	}
	
	static function getCachedComponentConfiguration($oComponent)
	{
		$sCacheFile = self::filenameCachedComponentConfiguration($oComponent);
		if (!file_exists($sCacheFile))
		{
			throw new AnwCacheNotFoundException();
		}
		
		//mapping must be newer than default and override config file
		$nCachedFileTime = filemtime($sCacheFile);
		if ( (file_exists($oComponent->getConfigurableFileOverride()) && $nCachedFileTime < filemtime($oComponent->getConfigurableFileOverride())) 
			|| $nCachedFileTime < filemtime($oComponent->getConfigurableFileDefault()) )
		{
			self::debug("cachedComponentConfiguration obsoleted by settings");
			throw new AnwCacheNotFoundException();
		}
		
		//load it from cache
		$oObject = (array)self::getCachedObject($sCacheFile);
		if (!is_array($oObject))
	 	{
	 		self::debug("cachedComponentConfiguration invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedComponentConfiguration found : ".$sCacheFile);
	 	}
		return $oObject;
	}
	
}
?>