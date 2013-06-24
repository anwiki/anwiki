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
 * This is maybe a future simplified API for Anwiki.
 * @package Anwiki
 * @version $Id: class_anwiki.php 160 2009-02-28 13:22:40Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnWiki
{
	static function includePage($sPageName, $sCurrentLang, $bAutoLoadTranslatedPage=true, $bUseCache=true, $sCacheKey="")
	{
		//$oPage = new AnwPageByName($sPageName);
		$oPage = AnwStorage::getPageByName($sPageName, false, false, $sCurrentLang);
		
		//load translation if available
		if ($bAutoLoadTranslatedPage && $oPage->getLang() != $sCurrentLang)
		{
			$oPage = $oPage->getPageGroup()->getPreferedPage($sCurrentLang);
		}
		
		//check ACL
		if (!AnwCurrentSession::isActionAllowed($oPage->getName(), 'view', $oPage->getLang()))
		{
			throw new AnwAclException();
		}
		
		$oOutputHtml = $oPage->toHtml($bUseCache, $sCacheKey);
		$sReturn = $oOutputHtml->runBody();
		//$sContentHtmlDir = AnwComponent::g_("local_html_dir", array(), $oPage->getLang());
		//$sReturn = '<div dir="'.$sContentHtmlDir.'">'.$sReturn.'</div>';
		return $sReturn;
	}
	
	static function isCurrentPage($sPageName)
	{
		try
		{
			$sCurrentPage = AnwActionPage::getCurrentPageName();
			if ($sPageName == $sCurrentPage)
			{
				AnwDebug::log("isCurrentPage (".$sPageName.") : YES (currently ".$sCurrentPage.")");
				return true;
			}
			
			//check translations if page exists
			$oPage = new AnwPageByName($sCurrentPage);
			if ($oPage->exists())
			{
				$aoPages = $oPage->getPageGroup()->getPages();
				foreach ($aoPages as $oPageTranslation)
				{
					if ($oPageTranslation->getName() == $sPageName)
					{
						AnwDebug::log("isCurrentPage (".$sPageName.") : YES (currently ".$sCurrentPage.")");
						return true;
					}
				}
			}
		}
		catch(AnwException $e){}
		AnwDebug::log("isCurrentPage (".$sPageName.") : NO (currently ".$sCurrentPage.")");
		return false;
	}
	
	static function tag_anwloop($sMatch, $sContentClass, $asLangs, $nLimit, $sSortUser, $sOrder, $asFilters)
	{
		AnwDebug::startBench("anwloop", true);
		$oContentClass = AnwContentClasses::getContentClass($sContentClass);
		
		$aoPages = AnwStorage::fetchPagesByClass(array($sMatch), $oContentClass, $asLangs, $nLimit, $sSortUser, $sOrder, $asFilters);
		AnwDebug::stopBench("anwloop");
		return $aoPages;
	}

}

?>