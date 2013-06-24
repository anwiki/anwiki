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
 * List of untranslated contents.
 * @package Anwiki
 * @version $Id: action_untranslated.php 348 2010-12-12 14:32:29Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_untranslated extends AnwActionGlobal implements AnwHarmlessAction
{
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function run()
	{
		//initialize filters - user can see all languages with view permission
		list($asAllLangs, $asDisplayLangs) = $this->filterLangs(array("view"), true);
		
		// did we already choose langs?
		if (count($asDisplayLangs) == 0) {
			// initialize default langs based on translate permission...
			//is user allowed to translate something?
			list($asLangsAllowedForTranslation, $null) = $this->filterLangs(array("translate"));
			if (count($asLangsAllowedForTranslation) > 0)
			{
				// user has translate permission on some languages, pre-select this languages by default
				$asDisplayLangs = $asLangsAllowedForTranslation;
			}
		}
		// if user has no translate permission, all languages with view permission will be pre-selected
		
		list($asAllClasses, $asDisplayClasses) = $this->filterContentClasses();
		
		//get untranslated pages
		$aoPages = AnwStorage::getUntranslatedPages($asDisplayLangs, $asDisplayClasses);
		foreach ($aoPages as $i => $oPage)
		{
			// FS#138 only check for view permission here (important for anonymous RSS)
			if (!$oPage->isActionAllowed('view'))
			{
				unset($aoPages[$i]);
			}
		}
		
		if (AnwEnv::_GET("feed"))
		{
			$this->showFeed($aoPages);
		}
		else
		{
			$this->showHtml($aoPages, $asAllLangs, $asDisplayLangs, $asAllClasses, $asDisplayClasses);
		}
	}
	
	
	function showHtml($aoPages, $asAllLangs, $asDisplayLangs, $asAllClasses, $asDisplayClasses)
	{
		$this->out .= $this->tpl()->begin();
		
		$this->out .= $this->tpl()->filterStart($this->linkMe())
					.$this->tpl()->filterLangs($asAllLangs, $asDisplayLangs)
					.$this->tpl()->filterClass($asAllClasses, $asDisplayClasses)
					.$this->tpl()->filterEnd();
		
		//rss link
		$sRssLink = AnwEnv::_SERVER('REQUEST_URI').'&feed=rss2';
		$this->head( $this->tpl()->headRss($sRssLink) );
		
		$this->out .= $this->tpl()->nav($sRssLink);
		
		//pages list
		$this->out .= $this->tpl()->openTable();
		foreach ($aoPages as $oPage)
		{
			$this->out .= $this->tpl()->untranslatedPageLine(
				$oPage
			);
		}
		$this->out .= $this->tpl()->closeTable();
	}
	
	
	function showFeed($aoPages)
	{
		$sFeedTitle = $this->t_("title")." - ".self::globalCfgWebsiteName();
		$oFeed = new AnwFeed( AnwEnv::_GET("feed"), $sFeedTitle, AnwUtils::aLinkAbsolute("untranslated") );
		
		foreach ($aoPages as $oPage)
		{
			//add to feed
			$sUserDisplayName = "";
			$sItemTitle = '['.$oPage->getLang().'] '.$oPage->getName().' ('.$oPage->getTranslatedPercent().'%) - '.Anwi18n::dateTime($oPage->getTime());
			$sPageLink = AnwUtils::linkAbsolute($oPage);
			
			$oFeedItem = new AnwFeedItem($sItemTitle, $sPageLink);
			$oFeedItem->setDate( $oPage->getTime() );
			$oFeedItem->setAuthor($sUserDisplayName);
			$oFeed->addItem($oFeedItem);
		}
		
		$oFeed->output();
	}
}

?>