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
 * ContentClass: syndication feed.
 * @package Anwiki
 * @version $Id: contentclass_feed.php 341 2010-10-14 23:21:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwContentClassPageDefault_feed extends AnwContentClassPage implements AnwConfigurable, AnwCachedOutputKeyDynamic
{
	const FIELD_TITLE = "title";
	const FIELD_DESCRIPTION = "description";
	const FIELD_MATCH = "match";
	const FIELD_CONTENTCLASS = "contentclass";
	const FIELD_LIMIT = "limit";
	
	const PUB_TITLE = "title";
	
	const GET_FEED = "feed";
	
	const CFG_CACHE_EXPIRY = "cache_expiry";
	
	const CSS_FILE = 'contentclass_feed.css';
	
	
	function getConfigurableSettings()
	{
		$aoSettings = array();
		$aoSettings[] = new AnwContentFieldSettings_delay(self::CFG_CACHE_EXPIRY);
		return $aoSettings;
	}
	
	
	function init()
	{
		// feed title
		$oContentField = new AnwContentFieldPage_string( self::FIELD_TITLE );
		$oContentField->indexAs(self::PUB_TITLE);
		$this->addContentField($oContentField);
		
		
		// feed description
		$oContentField = new AnwContentFieldPage_xhtml( self::FIELD_DESCRIPTION );
		$this->addContentField($oContentField);
		
		
		// feed match
		$oContentField = new AnwContentFieldPage_string( self::FIELD_MATCH );
		$oContentField->setDefaultValue('*');
		$oContentField->setTranslatable(false);
		$oContentField->addForbiddenPattern('/^$/');
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		
		// feed contentclass
		$oContentField = new AnwContentFieldPage_checkboxGroup( self::FIELD_CONTENTCLASS );
		$oContentField->setTranslatable(false);
		
		$asEnumValues = array();
		$aoContentClasses = AnwContentClasses::getContentClasses();
		foreach ($aoContentClasses as $oContentClass)
		{
			$asEnumValues[ $oContentClass->getName() ] = $oContentClass->getName();
		}
		$oContentField->setEnumValues($asEnumValues);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		
		// feed limit
		$oContentField = new AnwContentFieldPage_integer( self::FIELD_LIMIT );
		$oContentField->setTranslatable(false);
		$oContentField->setDefaultValue(15);
		
		$oContentField->setValueMin(1);
		$oContentField->setValueMax(50);
		
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$aoContentClasses = array();
		$asContentClassNames = $oContent->getContentFieldValues(self::FIELD_CONTENTCLASS);
		foreach ($asContentClassNames as $sContentClassName)
		{
			$oContentClass = AnwContentClasses::getContentClass($sContentClassName);
			$aoContentClasses[] = $oContentClass;
		}
		unset($asContentClassNames);
		
		//get feed items
		$aoMatchingPages = AnwStorage::fetchPages(
			$oContent->getContentFieldValues(self::FIELD_MATCH, true),
			$aoContentClasses,
			array($oPage->getLang()),
			$oContent->getContentFieldValue(self::FIELD_LIMIT),
			AnwUtils::SORT_BY_TIME,
			AnwUtils::SORTORDER_DESC
		);
		$aoFeedItems = array();
		foreach ($aoMatchingPages as $oMatchingPage)
		{
			$oFeedItem = $oMatchingPage->toFeedItem();
			$aoFeedItems[] = $oFeedItem;
		}
		
		if (AnwEnv::_GET(self::GET_FEED)!=AnwFeed::TYPE_RSS2)
		{
			$sUrlRss = AnwUtils::link($oPage,"view",array(self::GET_FEED=>AnwFeed::TYPE_RSS2));
			
			//show feed info
			$oOutputHtml = new AnwOutputHtml( $oPage );
			$oOutputHtml->setTitle( $oContent->getContentFieldValue(self::FIELD_TITLE) );
			
			$sHtmlItems = "";
			foreach ($aoFeedItems as $oFeedItem)
			{
				$sHtmlItems .= $this->tpl()->feedItem($oFeedItem->getTitle(), $oFeedItem->getLink());
			}
			
			$sHtmlBody = $this->tpl()->feedInfo( 
				$oContent->getContentFieldValue(self::FIELD_TITLE),
				$oContent->getContentFieldValue(self::FIELD_DESCRIPTION),
				$sUrlRss,
				$sHtmlItems,
				$oPage->getLang()
			);
			
			$oOutputHtml->setBody( $sHtmlBody );
			
			// load contentclass CSS
			$sHtmlHead = $this->getCssSrcComponent(self::CSS_FILE);
			
			$sHtmlHead .= $this->tpl()->headRss(
				$sUrlRss, 
				$oContent->getContentFieldValue(self::FIELD_TITLE)
			);
			$oOutputHtml->setHead($sHtmlHead);
		}
		else
		{
			$sUrlRss = AnwUtils::linkAbsolute($oPage,"view",array(self::GET_FEED=>AnwFeed::TYPE_RSS2));
			
			//export to rss
			$oFeed = new AnwFeed(
				AnwFeed::TYPE_RSS2,
				$oContent->getContentFieldValue(self::FIELD_TITLE),
				$sUrlRss,
				$oContent->getContentFieldValue(self::FIELD_DESCRIPTION)
			);
			
			foreach ($aoFeedItems as $oFeedItem)
			{
				$oFeed->addItem($oFeedItem);
			}
			
			$oFeed->output();
		}
		return $oOutputHtml;
	}
	
	function toFeedItem($oContent, $oPage)
	{
		$oFeedItem = new AnwFeedItem(
			$oContent->getContentFieldValue(self::FIELD_TITLE, 0, true),
			AnwUtils::linkAbsolute($oPage),
			$oContent->getContentFieldValue(self::FIELD_DESCRIPTION, 0, true)
		);
		return $oFeedItem;
	}
	
	function getCachedOutputKeyDynamic()
	{
		$sCacheKey = "";
		
		//we need this in order to get the dynamic link "?feed=rss2" working...
		if (AnwEnv::_GET(self::GET_FEED)==AnwFeed::TYPE_RSS2)
		{
			$sCacheKey .= AnwFeed::TYPE_RSS2;
		
			//rss links are absolute and may be in HTTP or HTTPS, depending on protocol currently in use
			$sCacheKey .= "|" . ( AnwEnv::isHttps() ? "HTTPS" : "HTTP" );
		}
		return $sCacheKey;
	}
	
	function getCachedOutputExpiry()
	{
		return $this->cfg(self::CFG_CACHE_EXPIRY);
	}
}

?>