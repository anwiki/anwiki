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
 * Anwiki cache management. This class can be extended to cache various objects.
 * @package Anwiki
 * @version $Id: class_cache.php 346 2010-11-01 10:02:39Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwCache
{
	const EXPIRY_UNLIMITED = -1;
	
	protected static function cachedirPageGroup($oPageGroup)
	{
		return ANWPATH_CACHECONTENT.'bygroup/'.$oPageGroup->getId().'/';
	}
	protected static function cachedirPage($oPage)
	{
		return self::cachedirPageGroup($oPage->getPageGroup()).$oPage->getId().'/';
	}
	protected static function cachedirOutput($oPage)
	{
		return self::cachedirPage($oPage).'output/';
	}
	
	static function clearCacheFromPageGroup($oPageGroup, $bDefinitive=false)
	{
		$sCacheDirectory = self::cachedirPageGroup($oPageGroup);
		self::debug("clearing cache from pagegroup : ".$sCacheDirectory);
		self::clearCacheDir($sCacheDirectory, $bDefinitive);
		
		//clear symlinks
		$aoPages = $oPageGroup->getPages();
		foreach ($aoPages as $oPage)
		{
			self::clearCacheFromPageByName($oPage->getName());
			self::clearCacheFromPageById($oPage->getId());
		}
	}
	static function clearCacheFromPageByName($sOldName)
	{
		$sSymlink = self::symlinkPageByName($sOldName);
		self::cacheUnlink($sSymlink, ANWPATH_CACHECONTENT); //don't test if it exists as symlink may be broken
	}
	static function clearCacheFromPageById($nPageId)
	{
		$sSymlink = self::symlinkPageById($nPageId);
		self::cacheUnlink($sSymlink, ANWPATH_CACHECONTENT); //don't test if it exists as symlink may be broken
	}	
	static function clearCachedOutputFromPage($oPage)
	{
		$sCacheDirectory = self::cachedirOutput($oPage);
		self::debug("clearing cachedOutput from page : ".$sCacheDirectory);
		self::clearCacheDir($sCacheDirectory, false);
	}
	
	protected static function symlinkPageByName($sPageName)
	{
		return ANWPATH_CACHECONTENT.'byname/anwpage-'.md5($sPageName);
	}
	protected static function symlinkPageById($nPageId)
	{
		$nPageId = (int)$nPageId;
		return ANWPATH_CACHECONTENT.'byid/anwpage-'.$nPageId;
	}
	
	//---------------------------------------------
	
	protected static function putCachedObject($sCacheFile, $oObject)
	{
		try
		{
			// we may have at most 2 subfolders to create: <pagegroupid>/<pageid>
			AnwUtils::makeSureDirExists($sCacheFile, 2);
			AnwUtils::putFileSerializedObject($sCacheFile, $oObject);
		}
		catch(AnwUnexpectedException $e)
		{
			//silently report error
			AnwDebug::reportError($e);
		}
	}
	
	protected static function getCachedObject($sCacheFile, $nDelayExpiry=self::EXPIRY_UNLIMITED)
	{
		if (!file_exists($sCacheFile))
		{
			self::debug("cache NOT found : ".$sCacheFile);
			throw new AnwCacheNotFoundException();
		}

		try
		 {
		 	//check delay if expiry time given
		 	if ($nDelayExpiry != self::EXPIRY_UNLIMITED)
		 	{
		 		$nElapsedTime = time() - filemtime($sCacheFile);
		 		if ($nElapsedTime > $nDelayExpiry)
		 		{
		 			//cache expired
		 			self::debug("cache (".$nDelayExpiry."s) expired : ".$sCacheFile);
		 			throw new AnwCacheNotFoundException();
		 		}
		 		else
		 		{
		 			self::debug("cache (".$nDelayExpiry."s) is valid, will expire in ".($nDelayExpiry-$nElapsedTime)."s");
		 		}
		 	}
		 	else
		 	{
		 		self::debug("cache is immortal");
		 	}
		 	$oCachedObject = AnwUtils::getFileSerializedObject($sCacheFile);
		 	return $oCachedObject;
		 }
		 catch(AnwUnexpectedException $e)
		 {
		 	throw new AnwCacheNotFoundException();
		 }
	}
	
	private static function clearCacheDir($sCacheDirectory, $bDefinitive)
	{
		if (is_dir($sCacheDirectory))
		{
			try
			{
				AnwUtils::rmdirFiles($sCacheDirectory, ANWPATH_CACHE);
				if ($bDefinitive)
				{
					AnwUtils::rmdir($sCacheDirectory, ANWPATH_CACHE);
				}
			}
			catch(AnwUnexpectedException $e)
			{
				AnwDebug::reportError($e);
			}
		}
	}
	
	protected static function debug($sMsg)
	{
		AnwDebug::log("(AnwCache) ".$sMsg);
	}
	
	//---------------------------------------------
	
	private static function relativefilenameCachedPage($sCacheKey)
	{
		return 'anwpage-'.md5($sCacheKey).'.php';
	}
	
	static function putCachedPage($oPage, $sCacheKey)
	{
		$sCacheFile = self::cachedirPage($oPage).self::relativefilenameCachedPage($sCacheKey);
		self::debug("putting cachedPage : ".$sCacheFile." (".$oPage->getName().")");
		self::putCachedObject($sCacheFile, $oPage);
		
		//do symlinks for quick access
		$nPageGroupId = $oPage->getPageGroup()->getId();
		$nPageId = $oPage->getId();
		
		//target of symlink
		$sTargetDir = 'bygroup/'.$nPageGroupId.'/'.$nPageId.'/';
		if (AnwComponent::globalCfgSymlinksRelative())
		{
			$sTargetDir = '../'.$sTargetDir;
		}
		else
		{
			$sTargetDir = ANWPATH_CACHECONTENT.$sTargetDir;
		}
		
		$sSymlink = self::symlinkPageByName($oPage->getName());
		self::cacheSymlink($sTargetDir, $sSymlink, ANWPATH_CACHECONTENT);
			
		$sSymlink = self::symlinkPageById($oPage->getId());
		self::cacheSymlink($sTargetDir, $sSymlink, ANWPATH_CACHECONTENT);
	}
	
	static function getCachedPageById($nPageId, $sCacheKey)
	{
		$sCacheFile = self::symlinkPageById($nPageId).'/'.self::relativefilenameCachedPage($sCacheKey);
		$oPage = self::getCachedObject($sCacheFile);
		if (!$oPage instanceof AnwPage)
	 	{
	 		self::debug("cachedPage invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedPage found : ".$sCacheFile." (".$oPage->getName()." | ".$oPage->getId().")");
	 	}
		return $oPage;
	}
	
	static function getCachedPageByName($sPageName, $sCacheKey)
	{
		$sCacheFile = self::symlinkPageByName($sPageName).'/'.self::relativefilenameCachedPage($sCacheKey);
		$oPage = self::getCachedObject($sCacheFile);
		if (!$oPage instanceof AnwPage)
	 	{
	 		self::debug("cachedPage invalid : ".$sCacheFile." (".$sPageName.")");
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedPage found : ".$sCacheFile." (".$oPage->getName()." | ".$oPage->getId().")");
	 	}
		return $oPage;
	}
	
	//---------------------------------------------
	// GENERIC ACCESS TO FILE SYSTEM (WIN/LINUX) 
	//---------------------------------------------
		
	static function cacheUnlink($sFilename, $sSafeTestRootDirectory)
	{
		if (AnwEnv::hasSymLink())
		{
			// linux way
			@AnwUtils::unlink($sFilename, $sSafeTestRootDirectory);
		}
		else
		{
			// windows way
			if (is_dir($sFilename))
			{
				@AnwUtils::rmdirFiles($sFilename, $sSafeTestRootDirectory);
			}
		}
	}
	
	static function cacheSymlink($sTarget, $sSymlink, $sSafeTestRootDirectory, $bUnlinkIfExists=true)
	{
		if (AnwEnv::hasSymLink())
		{
			// linux way
			AnwUtils::symlink($sTarget, $sSymlink, $sSafeTestRootDirectory, $bUnlinkIfExists);
		}
		else
		{
			// windows way
			// only copy files, we don't need to copy subfolders because they are only read from the cache bygroup
			AnwUtils::copyDirFiles($sTarget, $sSymlink, $bUnlinkIfExists);
		}
	}
}

/**
 * Indicates that the cache can be cleared.
 */
interface AnwCacheCleanable {
	public static function clearCache();
}

?>