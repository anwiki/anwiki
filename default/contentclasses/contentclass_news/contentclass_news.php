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
 * ContentClass: news.
 * @package Anwiki
 * @version $Id: contentclass_news.php 341 2010-10-14 23:21:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwContentClassPageDefault_news extends AnwContentClassPage implements AnwDependancyManageable
{
	const FIELD_TITLE = "title";
	const FIELD_INTRO = "intro";
	const FIELD_BODY = "body";
	const FIELD_DATE = "date";
	const FIELD_CATEGORIES = "categories";
	
	const PUB_TITLE = "title";
	const PUB_INTRO = "intro";
	const PUB_BODY = "body";
	const PUB_DATE = "date";
	const PUB_CATEGORIES = "categories";
	
	const NEWSCATEGORY_CLASS = "newscategory";
	const NEWS_CLASS = "news";
	
	const CSS_FILE = 'contentclass_news.css';
	
	
	function getComponentDependancies()
	{
		$aoDependancies = array();
		/*
		 * Depends on contentclass_newscategory.
		 */
		$aoDependancies[] = new AnwDependancyRequirement($this, AnwComponent::TYPE_CONTENTCLASS, self::NEWSCATEGORY_CLASS);
		return $aoDependancies;
	}
	
	function init()
	{
		// news title
		$oContentField = new AnwContentFieldPage_string( self::FIELD_TITLE );
		$oContentField->indexAs(self::PUB_TITLE);
		$this->addContentField($oContentField);
		
		// news intro
		$oContentField = new AnwContentFieldPage_xhtml( self::FIELD_INTRO );
		$this->addContentField($oContentField);
		
		// news body
		$oContentField = new AnwContentFieldPage_xhtml( self::FIELD_BODY );
		$oContentField->setDynamicParsingAllowed(true);
		$oContentField->setDynamicPhpAllowed(true);
		$this->addContentField($oContentField);

		// news date
		$oContentField = new AnwContentFieldPage_date( self::FIELD_DATE );
		$oContentField->setTranslatable(false);
		$oContentField->indexAs(self::PUB_DATE);
		$this->addContentField($oContentField);
		
		// news categories
		$oFetchingContentClass = AnwContentClasses::getContentClass(self::NEWSCATEGORY_CLASS);
		$oContentField = new AnwContentFieldPage_pageGroup( self::FIELD_CATEGORIES, $oFetchingContentClass );
		$oContentField->setTranslatable(false);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oContentMultiplicity);
		$oContentField->indexAs(self::PUB_CATEGORIES);
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$oOutputHtml = new AnwOutputHtml( $oPage );
		$oOutputHtml->setTitle( $oContent->getContentFieldValue(self::FIELD_TITLE) );
		
		//load contentclass CSS
		$oOutputHtml->setHead( $this->getCssSrcComponent(self::CSS_FILE) );
		
		$sNewsTitle = $oContent->getContentFieldValue( self::FIELD_TITLE );
		$sNewsIntro = $oContent->getContentFieldValue( self::FIELD_INTRO );
		$sNewsBody = $oContent->getContentFieldValue( self::FIELD_BODY );
		$sNewsDate = Anwi18n::date( AnwUtils::dateToTime($oContent->getContentFieldValue(self::FIELD_DATE)), $oPage->getLang() );
		
		//render categories
		$aoCategoriesPages = self::getCategoriesPages($oContent, $oPage);
		$sHtmlCategories = "";
		if (count($aoCategoriesPages)>0)
		{
			$sHtmlCategories .= $this->tpl()->categoriesStart();
			foreach($aoCategoriesPages as $oCategoryPage)
			{
				$oCategoryContent = $oCategoryPage->getContent();
				$sCategoryTitle = $oCategoryContent->getContentFieldValue( AnwContentClassPageDefault_newscategory::FIELD_TITLE );
				$sCategoryUrl = AnwUtils::link($oCategoryPage);
				$sHtmlCategories .= $this->tpl()->categoriesItem($sCategoryTitle, $sCategoryUrl);
			}
			$sHtmlCategories .= $this->tpl()->categoriesEnd();
		}
		unset($aoCategoriesPages);
		
		//render the news
		$sHtmlBody = $this->tpl()->showNews($sNewsTitle, $sNewsIntro, $sNewsBody, $sNewsDate, $sHtmlCategories, $oPage->getLang());
		
		$oOutputHtml->setBody( $sHtmlBody );
		return $oOutputHtml;
	}
	
	protected static function getCategoriesPages($oContent, $oPage)
	{
		$anCategoriesPageGroupIds = $oContent->getContentFieldValues( self::FIELD_CATEGORIES );
		$aoCategoriesPage = array();
		foreach ($anCategoriesPageGroupIds as $nCategoryPageGroupId)
		{
			try
			{
				$oCategoryPageGroup = new AnwPageGroup($nCategoryPageGroupId, self::NEWS_CLASS);
				if ($oCategoryPageGroup->exists())
				{
					//get category in current lang if available
					$oPageNews = $oCategoryPageGroup->getPreferedPage($oPage->getLang());
					$aoCategoriesPage[] = $oPageNews;
				}
			}
			catch(AnwException $e){}
		}
		return $aoCategoriesPage;
	}
	
	function toFeedItem($oContent, $oPage)
	{
		$oFeedItem = new AnwFeedItem(
			$oContent->getContentFieldValue(self::FIELD_TITLE, 0, true),
			AnwUtils::linkAbsolute($oPage),
			$oContent->getContentFieldValue(self::FIELD_INTRO, 0, true)
		);
		return $oFeedItem;
	}
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			//TODO: executeHtmlAndPhpCode
			case self::PUB_TITLE:
				return $oContent->getContentFieldValue(self::FIELD_TITLE);
				break;
			
			case self::PUB_INTRO:
				return $oContent->getContentFieldValue(self::FIELD_INTRO);
				break;
			
			case self::PUB_BODY:
				return $oContent->getContentFieldValue(self::FIELD_BODY);
				break;
			
			case self::PUB_DATE:
				return AnwUtils::dateToTime($oContent->getContentFieldValue(self::FIELD_DATE)); //TODO lang
				break;
			
			/*case self::PUB_CATEGORIES:
				return self::getCategoriesPages($oContent);
				break;*/
		}
	}
	
	//delete cache from related categories on news change
	function onChange($oPage, $oPreviousContent=null)
	{
		//clear cache from previous categories, in case of news is no more under this category
		if ($oPreviousContent!=null)
		{
			$aoPagesCategoriesPrevious = self::getCategoriesPages($oPreviousContent,$oPage);
			foreach ($aoPagesCategoriesPrevious as $oPageCategory)
			{
				AnwCache::clearCacheFromPageGroup($oPageCategory->getPageGroup());
			}
		}
		
		//clear cache from current categories, in case news was not already under these categories
		$aoPagesCategoriesCurrent = self::getCategoriesPages($oPage->getContent(),$oPage);
		foreach ($aoPagesCategoriesCurrent as $oPageCategory)
		{
			AnwCache::clearCacheFromPageGroup($oPageCategory->getPageGroup());
		}
	}
}

?>