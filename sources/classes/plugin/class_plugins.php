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
 * Anwiki plugins manager.
 * @package Anwiki
 * @version $Id: class_plugins.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwPlugins
{
	private static $aoPlugins = array();
	private static $aaPluginsMapping = null;
	
	const TYPE_HOOK = 'hook';
	const TYPE_VHOOK = 'vhook';
	
	static function hook( /* $sHookName, [args...]*/ )
	{
		$amArgs = func_get_args();
		$sHookName = array_shift($amArgs);
		
		$aoPlugins = self::getHookingPlugins($sHookName, self::TYPE_HOOK);
		foreach ($aoPlugins as $oPlugin)
		{
			$fFunctionName = 'hook_'.$sHookName;
			//print_r($amArgs);
			call_user_func_array(array($oPlugin, $fFunctionName), $amArgs);
		}
	}
	
	
	
	/**
	 * $mReturnValue --> plugin1 --> plugin2 --> return.
	 */
	static function vhook( /* $sHookName, $mReturnValue, [args...]*/ )
	{
		$amArgs = func_get_args();
		$sHookName = array_shift($amArgs);
		$mReturnValue = array_shift($amArgs);
		
		$aoPlugins = self::getHookingPlugins($sHookName, self::TYPE_VHOOK);
		foreach ($aoPlugins as $oPlugin)
		{
			$fFunctionName = 'vhook_'.$sHookName;
			//print_r($amArgs);
			array_unshift($amArgs, $mReturnValue); //put new $mReturnValue
			$mReturnValue = call_user_func_array(array($oPlugin, $fFunctionName), $amArgs);
			array_shift($amArgs); //remove old $mReturnValue
		}
		return $mReturnValue;
	}
	
	//---------------------------------------------------
	
	private static function getHookingPlugins($sHookName, $sHookType)
	{
		if (!in_array($sHookType, array(self::TYPE_HOOK, self::TYPE_VHOOK)))
		{
			throw new AnwUnexpectedException("getHookingPlugins called with wrong parameter");
		}
		
		$aaPluginsMapping = self::getPluginsMapping();
		
		$aoPlugins = array();
		if (isset($aaPluginsMapping[$sHookType][$sHookName]))
		{
			foreach ($aaPluginsMapping[$sHookType][$sHookName] as $sPluginName)
			{
				$oPlugin = self::getPlugin($sPluginName);
				$aoPlugins[] = $oPlugin;
			}
		}
		return $aoPlugins;
	}
		
	private static function getPluginsMapping()
	{
		if (self::$aaPluginsMapping===null)
		{
			$bCachePluginsEnabled = AnwComponent::globalCfgCachePluginsEnabled();
			try
			{
				//load plugins mapping
				if (!$bCachePluginsEnabled) throw new AnwCacheNotFoundException();
				self::$aaPluginsMapping = AnwCache_pluginsMapping::getCachedPluginsMapping();
				self::debug("Loading plugin mapping from cache");
			}
			catch(AnwException $e)
			{
				//generate new mapping
				self::debug("Generating plugin mapping");
				self::$aaPluginsMapping = self::generatePluginsMapping();
				if ($bCachePluginsEnabled) AnwCache_pluginsMapping::putCachedPluginsMapping(self::$aaPluginsMapping);
			}
		}
		//print_r(self::$aaPluginsMapping);
		return self::$aaPluginsMapping;	
	}
	
	private static function generatePluginsMapping()
	{
		$aaMapping = array();
		
		$asEnabledPlugins = AnwComponent::globalCfgModulesPlugins();
		foreach ($asEnabledPlugins as $sEnabledPlugin)
		{
			try
			{
				//load plugin
				$sEnabledPlugin = strtolower($sEnabledPlugin);
				self::loadPlugin($sEnabledPlugin);
				
				//reflexion
				//we DONT want inherited methods from AnwPlugin, only overriden methods
				$sClassName = get_class(self::getPlugin($sEnabledPlugin));
				$asMethods = get_class_methods($sClassName);
				foreach ($asMethods as $sMethod)
				{
					$sMethod = strtolower($sMethod);
					if( preg_match('!^hook_(.*)$!si', $sMethod, $asMatches) )
					{
						$sHookName = $asMatches[1];
						$aaMapping[self::TYPE_HOOK][$sHookName][] = $sEnabledPlugin;
					}
					else if( preg_match('!^vhook_(.*)$!si', $sMethod, $asMatches) )
					{
						$sHookName = $asMatches[1];
						$aaMapping[self::TYPE_VHOOK][$sHookName][] = $sEnabledPlugin;
					}
				}
			}
			catch(AnwException $e)
			{
				AnwDebug::reportError($e);
			}
		}
		return $aaMapping;
	}
	
	private static function getPlugin($sPluginName)
	{
		if (!self::isPluginLoaded($sPluginName)) 
		{
			self::loadPlugin($sPluginName);
		}
		return self::$aoPlugins[$sPluginName];
	}
	
	private static function loadPlugin($sPluginName)
	{
		$sPluginName = strtolower($sPluginName);
		
		//is it already loaded?
		if (self::isPluginLoaded($sPluginName)) return;
		
		//load the plugin
		$oPlugin = AnwPlugin::loadComponent($sPluginName);
		$oPlugin->init();
		self::$aoPlugins[$sPluginName] = $oPlugin;
		self::debug("Loaded plugin : ".$sPluginName);
	}
	
	private static function isPluginLoaded($sPluginName)
	{
		return isset(self::$aoPlugins[$sPluginName]);
	}
		
	private static function debug($sMessage)
	{
		AnwDebug::log("(AnwPlugins)".$sMessage);
	}
}


/**
 * Cache manager for plugins mapping.
 */
class AnwCache_pluginsMapping extends AnwCache
{

	private static function filenameCachedPluginsMapping()
	{
		return ANWPATH_CACHESYSTEM.'plugins_mapping.php';
	} 
	
	static function putCachedPluginsMapping($aaPluginsMapping)
	{
		$sCacheFile = self::filenameCachedPluginsMapping();
		self::debug("putting cachedPluginsMapping : ".$sCacheFile); 
		self::putCachedObject($sCacheFile, $aaPluginsMapping);
	}
	
	static function getCachedPluginsMapping()
	{
		$sCacheFile = self::filenameCachedPluginsMapping();
		if (!file_exists($sCacheFile))
		{
			throw new AnwCacheNotFoundException();
		}
		
		//mapping must be newer than enabled-plugins-settings
		try
		{
			$sConfigFileOverride = AnwUtils::getFileOverride("global.cfg.php", AnwComponent::getGlobalComponentFullDir());
			if ( filemtime($sCacheFile) < filemtime($sConfigFileOverride))
			{
				self::debug("cachedPluginsMapping obsoleted by settings");
				throw new AnwCacheNotFoundException();
			}
		}
		catch(AnwFileNotFoundException $e){} //no override config
		
		//mapping must be newer than each enabled plugin
		$asEnabledPlugins = AnwComponent::globalCfgModulesPlugins();
		foreach ($asEnabledPlugins as $sEnabledPlugin)
		{
			$asPluginFilesLocations = array();
			
			$sPluginFile = 'plugin_'.$sEnabledPlugin.'.php';
			$sPluginDir = AnwPlugin::getComponentDir($sEnabledPlugin);
			list($sFilePluginDefault, $null) = AnwUtils::getFileDefault($sPluginFile, $sPluginDir);
			$asPluginFilesLocations[] = $sFilePluginDefault;
			
			try 
			{
				$sFilePluginOverride = AnwUtils::getFileOverride($sPluginFile, $sPluginDir);
				$asPluginFilesLocations[] = $sFilePluginOverride;
			}
			catch(AnwFileNotFoundException $e){} //no override file found
			
			foreach ($asPluginFilesLocations as $sPluginFileLocation)
			{
				if ( file_exists($sPluginFileLocation) && filemtime($sCacheFile) < filemtime($sPluginFileLocation) )
				{
					self::debug("cachedPluginsMapping obsoleted by plugin : ".$sEnabledPlugin);
					throw new AnwCacheNotFoundException();
				}
			}
		}
		
		//load it from cache
		$oObject = (array)self::getCachedObject($sCacheFile);
		if (!is_array($oObject))
	 	{
	 		self::debug("cachedPluginsMapping invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedPluginsMapping found : ".$sCacheFile);
	 	}
		return $oObject;
	}
	
}
?>