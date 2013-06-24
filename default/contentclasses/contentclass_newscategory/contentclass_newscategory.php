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
 * ContentClass: news category.
 * @package Anwiki
 * @version $Id: contentclass_newscategory.php 341 2010-10-14 23:21:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwContentClassPageDefault_newscategory extends AnwContentClassPage implements AnwDependancyManageable
{
	const FIELD_TITLE = "title";
	const FIELD_DESCRIPTION = "description";
	
	const PUB_TITLE = "title";
	const PUB_DESCRIPTION = "intro";
	const PUB_NEWSLIST = "newslist";
	
	const NEWS_CLASS = "news";
	
	function getComponentDependancies()
	{
		$aoDependancies = array();
		/*
		 * Depends on contentclass_news.
		 */
		$aoDependancies[] = new AnwDependancyRequirement($this, AnwComponent::TYPE_CONTENTCLASS, self::NEWS_CLASS);
		return $aoDependancies;
	}
	
	function init()
	{
		// news title
		$oContentField = new AnwContentFieldPage_string( self::FIELD_TITLE );
		$oContentField->indexAs(self::PUB_TITLE);
		$this->addContentField($oContentField);
		
		// news intro
		$oContentField = new AnwContentFieldPage_xhtml( self::FIELD_DESCRIPTION );
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$oOutputHtml = new AnwOutputHtml( $oPage );
		$oOutputHtml->setTitle( $oContent->getContentFieldValue(self::FIELD_TITLE) );
		
		$sNewsCategoryTitle = $oContent->getContentFieldValue( self::FIELD_TITLE );
		$sNewsCategoryIntro = $oContent->getContentFieldValue( self::FIELD_DESCRIPTION );
		//try{$aoNewsList = $this->getNewsList($oPage);}catch(Exception $e){print_r($e);}
		
		//render news list
		$aoNewsList = self::getNewsList($oPage);
		$sHtmlNewsList = "";
		if (count($aoNewsList)>0)
		{
			$sHtmlNewsList .= $this->tpl()->newsListStart();
			foreach ($aoNewsList as $oNewsPage)
			{
				$oNewsContent = $oNewsPage->getContent();
				$sNewsTitle = $oNewsContent->getContentFieldValue( AnwContentClassPageDefault_news::FIELD_TITLE );
				$sNewsIntro = $oNewsContent->getContentFieldValue( AnwContentClassPageDefault_news::FIELD_INTRO );
				$sNewsDate = Anwi18n::date(AnwUtils::dateToTime($oNewsContent->getContentFieldValue( AnwContentClassPageDefault_news::FIELD_DATE )), $oPage->getLang());
				$sNewsUrl = AnwUtils::link($oNewsPage);
				$sHtmlNewsList .= $this->tpl()->newsListItem($sNewsTitle, $sNewsIntro, $sNewsDate, $sNewsUrl, $oNewsPage->getLang());
			}
			$sHtmlNewsList .= $this->tpl()->newsListEnd();
		}
		unset($aoNewsList);
		
		//render the newscategory
		$sHtmlBody = $this->tpl()->showNewscategory($sNewsCategoryTitle, $sNewsCategoryIntro, $sHtmlNewsList, $oPage->getLang());
		
		$oOutputHtml->setBody( $sHtmlBody );
		return $oOutputHtml;
	}
	
	protected static function getNewsList($oPage)
	{
		//fetch news linked to this category
		$asPatterns = array();
		$oContentClass = AnwContentClasses::getContentClass(self::NEWS_CLASS);
		$asLangs = array($oPage->getLang());
		$nLimit = 0;
		$sSortUser = AnwContentClassPageDefault_news::PUB_DATE;
		$sOrder = AnwUtils::SORTORDER_ASC;
		$asFilters = array();
		$asFilters[] = array(
						'FIELD' => AnwContentClassPageDefault_news::PUB_CATEGORIES,
						'OPERATOR' => AnwUtils::FILTER_OP_EQUALS,
						'VALUE' => $oPage->getPageGroup()->getId()
		);
		$aoNewsPages = AnwStorage::fetchPagesByClass($asPatterns, $oContentClass, $asLangs, $nLimit, $sSortUser, $sOrder, $asFilters);
		return $aoNewsPages;
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
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			//TODO: executeHtmlAndPhpCode
			case self::PUB_TITLE:
				return $oContent->getContentFieldValue(self::FIELD_TITLE);
				break;
			
			case self::PUB_DESCRIPTION:
				return $oContent->getContentFieldValue(self::FIELD_DESCRIPTION);
				break;
			
			/*case self::PUB_NEWS:
				return $oContent->getNewsList($oContent);
				break;*/
		}
	}
	
	//delete cache from related news on category change
	function onChange($oPage, $oPreviousContent=null)
	{
		$aoPagesNews = self::getNewsList($oPage);
		foreach ($aoPagesNews as $oPageNews)
		{
			AnwCache::clearCacheFromPageGroup($oPageNews->getPageGroup());
		}
	}
	
}

?>