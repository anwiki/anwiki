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
 * ContentClass: navigation menu.
 * @package Anwiki
 * @version $Id: contentclass_menu.php 347 2010-12-05 12:41:20Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwContentFieldPage_menuSubItem extends AnwContentFieldPage_container implements AnwIContentFieldPage_menu_menuSubItem
{
	const PUB_LINK = "link";
	
	function init()
	{
		// sub link
		$oContentField = new AnwContentFieldPage_link(self::FIELD_LINK);
		$this->addContentField($oContentField);
		
		//active URL matches
		$oContentField = new AnwContentFieldPage_string(self::FIELD_URLMATCHES);
		$oContentField->setTranslatable(false);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
	}
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			case self::PUB_LINK:
				return $oContent->getSubContent(self::FIELD_LINK);
				break;
		}
	}
}

class AnwContentFieldPage_menuItem extends AnwContentFieldPage_container implements AnwIContentFieldPage_menu_menuItem
{
	const PUB_MAINLINK = "mainlink";
	const PUB_SUBITEMS = "subitems";
	
	function init()
	{
		// main link
		$oContentField = new AnwContentFieldPage_link(self::FIELD_MAINLINK);
		$this->addContentField($oContentField);
		
		// sub links
		$oContentField = new AnwContentFieldPage_menuSubItem(self::FIELD_SUBITEMS);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
		
		//active URL matches
		$oContentField = new AnwContentFieldPage_string(self::FIELD_URLMATCHES);
		$oContentField->setTranslatable(false);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentMultiplicity->setSortable(false);
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
	}
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			//TODO: executeHtmlAndPhpCode
			case self::PUB_MAINLINK:
				return $oContent->getSubContent(self::FIELD_MAINLINK);
				break;
			
			case self::PUB_SUBITEMS:
				return $oContent->getSubContents(self::FIELD_SUBITEMS);
				break;
		}
	}
}

class AnwContentClassPageDefault_menu extends AnwContentClassPage implements AnwIContentClassPageDefault_menu
{
	const PUB_TITLE = "title";
	const PUB_ITEMS = "items";
	
	function init()
	{
		// menu title
		$oContentField = new AnwContentFieldPage_string(self::FIELD_TITLE);
		$oContentField->setDefaultValue('Untitled menu');
		$oContentField->indexAs(self::PUB_TITLE);
		$this->addContentField($oContentField);
				
		$oContentField = new AnwContentFieldPage_menuItem(self::FIELD_ITEMS);
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$oOutputHtml = new AnwOutputHtml( $oPage );
		$oOutputHtml->setTitle( $oContent->getContentFieldValue( self::FIELD_TITLE, 0, true ) );
		$oOutputHtml->setBody( $this->generateFullMenu($oContent) );
		return $oOutputHtml;
	}
	
	/**
	 * Get rendered menu with the choosen render mode.
	 */
	function toHtmlCustom($oPage, $mRenderMode)
	{
		$oOutputHtml = null;
		$sCallback = null;
		switch ($mRenderMode)
		{
			case self::RENDER_MODE_FULL_MENU:
				$sCallback = 'generateFullMenu';
				break;
				
			case self::RENDER_MODE_ALL_MAINITEMS_ACTIVE_SUBITEMS:
				$sCallback = 'generateMainItemsAndActiveSubItems';
				break;
			
			case self::RENDER_MODE_MAIN_ITEMS:
				$sCallback = 'generateMainItemsOnly';
				break;
			
			case self::RENDER_MODE_SUBITEMS_FROM_ACTIVE_MAINITEM:
				$sCallback = 'generateActiveSubItems';
				break;
			
			default:
				throw new AnwUnexpectedException("Unknown render mode : ".$mRenderMode);
		}		
		$oOutputHtml = $this->getCachedOutput($oPage, $sCallback);		
		return $oOutputHtml;
	}
	
	//------------------------------------
	
	protected function getCachedOutput($oPage, $sCallbackName)
	{
		// use callback name as cacheKey + currentpage
		$sCacheKey = $sCallbackName."-currentpage-".AnwActionPage::getCurrentPageName();
		try
		{
			$oOutputHtml = $oPage->getCachedOutputHtml($sCacheKey);
		}
		catch(AnwCacheNotFoundException $e)
		{
			// render now...
			$oContent = $oPage->getContent();
			$sRender = $this->$sCallbackName($oContent);
			
			// build outputhtml...
			$oOutputHtml = new AnwOutputHtml( $oPage );
			$oOutputHtml->setTitle( $oContent->getContentFieldValue( self::FIELD_TITLE, 0, true ) );
			$oOutputHtml->setBody( $sRender );
			
			// put in cache...
			$oOutputHtml->putCachedOutputHtml($sCacheKey);
		}
		return $oOutputHtml;
	}
	
	protected function generateFullMenu($oContent)
	{
		//quick fix
		$asCurrentPageNames = self::getCurrentPageNames();
		$aasTree = $this->getMenuTreeActive($oContent, $asCurrentPageNames);
				
		$HTML = $this->tpl()->openMenu();
		foreach ($aasTree as $asTree)
		{
			// generate main item with all subitems
			$bShowSubItems = true;
			$HTML .= $this->generateMainItem($asTree, $bShowSubItems);
		}
		$HTML .= $this->tpl()->closeMenu();
		return $HTML;
	}
	
	protected function generateMainItemsAndActiveSubItems($oContent)
	{
		//quick fix
		$asCurrentPageNames = self::getCurrentPageNames();
		$aasTree = $this->getMenuTreeActive($oContent, $asCurrentPageNames);
				
		$HTML = $this->tpl()->openMenu();
		foreach ($aasTree as $asTree)
		{
			// generate all main items, but only active subitems
			$bShowSubItems = isset($asTree['ISCURRENT']);
			$HTML .= $this->generateMainItem($asTree, $bShowSubItems);
		}
		$HTML .= $this->tpl()->closeMenu();
		return $HTML;
	}
	
	protected function generateMainItemsOnly($oContent)
	{
		//quick fix
		$asCurrentPageNames = self::getCurrentPageNames();
		$aasTree = $this->getMenuTreeActive($oContent, $asCurrentPageNames);
				
		$HTML = $this->tpl()->openMenu();
		foreach ($aasTree as $asTree)
		{
			// generate all main items, but no subitem
			$bShowSubItems = false;
			$HTML .= $this->generateMainItem($asTree, $bShowSubItems);
		}
		$HTML .= $this->tpl()->closeMenu();
		return $HTML;
	}
	
	protected function generateActiveSubItems($oContent)
	{
		//quick fix
		$asCurrentPageNames = self::getCurrentPageNames();
		$aasTree = $this->getMenuTreeActive($oContent, $asCurrentPageNames);
				
		$HTML = $this->tpl()->openMenu();
		foreach ($aasTree as $asTree)
		{
			// generate active subitems only
			$bIsActive = isset($asTree['ISCURRENT']);
			if ($bIsActive)
			{
				$asSubLinks = isset($asTree['SUBLINKS']) ? $asTree['SUBLINKS'] : array();
				$HTML .= $this->generateSubItems($asSubLinks);
				break;
			}
		}
		$HTML .= $this->tpl()->closeMenu();
		return $HTML;
	}
	
	//------------------------------------
	
	protected function generateMainItem($asTree, $bShowSubItems)
	{
		$sMainTitle = @$asTree['LINKTITLE'];
		$sMainURL = @$asTree['LINKURL'];
		$sMainTarget = @$asTree['LINKTARGET'];
		
		$HTML = "";
		
		$sRenderedSubItems = "";
		if ($bShowSubItems)
		{
			$asSubLinks = isset($asTree['SUBLINKS']) ? $asTree['SUBLINKS'] : array();
			$sRenderedSubItems = $this->generateSubItems($asSubLinks);
		}
		
		if ( isset($asTree['ISCURRENT']) )
		{
			$HTML .= $this->tpl()->mainItemCurrent($sMainTitle, $sMainURL, $sMainTarget, $sRenderedSubItems);
		}
		else
		{
			$HTML .= $this->tpl()->mainItem($sMainTitle, $sMainURL, $sMainTarget, $sRenderedSubItems);
		}
		
		return $HTML;
	}
	
	protected function generateSubItems($asSubLinks)
	{
		$HTML = "";
		foreach ($asSubLinks as $asSubLink)
		{
			$sSubTitle = $asSubLink['LINKTITLE'];
			$sSubUrl = $asSubLink['LINKURL'];
			$sSubTarget = $asSubLink['LINKTARGET'];
			
			if (isset($asSubLink['ISCURRENT']))
			{
				$HTML .= $this->tpl()->subItemCurrent($sSubTitle, $sSubUrl, $sSubTarget);
			}
			else
			{
				$HTML .= $this->tpl()->subItem($sSubTitle, $sSubUrl, $sSubTarget);
			}
		}
		return $HTML;
	}
	
	//------------------------------------
	
	protected static function getCurrentPageNames()
	{
		$asCurrentPageNames = array();
		$sCurrentPageName = AnwActionPage::getCurrentPageName();
		if (!$sCurrentPageName)
		{
			// FS#144 - avoids AnwBadPageNameException
			return $asCurrentPageNames;
		}
		//allow currentPageNames even if it doesn't exist (useful for action_output)
		$asCurrentPageNames[] = $sCurrentPageName;
		try{
			$oCurrentPage = new AnwPageByName( $sCurrentPageName );
			$oCurrentPage->setSkipLoadingTranslationsContent(true);
			$aoCurrentPageTranslations = $oCurrentPage->getPageGroup()->getPages($oCurrentPage);
			foreach ($aoCurrentPageTranslations as $oTranslation)
			{
				$asCurrentPageNames[] = $oTranslation->getName();
			}
		}
		catch(AnwPageNotFoundException $e){}
		return $asCurrentPageNames;
	}
	
	protected static function isActiveMenuItem($asTree, $asCurrentPageNames)
	{
		//test urls
		if (isset($asTree['ALLURLS']))
		{
			if ( count(array_intersect($asCurrentPageNames, $asTree['ALLURLS']))>0 )
			{
				return true;
			}
		}
		
		//test active url matches
		if (isset($asTree['ALLURLMATCHES']) && self::testUrlMatches($asTree['ALLURLMATCHES'], $asCurrentPageNames))
		{
			return true;
		}
		return false;
	}
	
	protected static function isActiveMenuSubItem($asSubLink, $asCurrentPageNames)
	{
		//test urls
		if (in_array($asSubLink['LINKURL'], $asCurrentPageNames))
		{
			return true;
		}
		
		//test active url matches
		if (isset($asSubLink['URLMATCHES']) && self::testUrlMatches($asSubLink['URLMATCHES'], $asCurrentPageNames))
		{
			return true;
		}
		return false;
	}
	
	protected static function testUrlMatches($asUrlMatches, $asCurrentPageNames)
	{
		foreach ($asUrlMatches as $sUrlMatch)
		{
			$sUrlMatchRegexp = '!'.$sUrlMatch.'!i';
			foreach ($asCurrentPageNames as $sCurrentPageName)
			{
				if (preg_match($sUrlMatchRegexp, $sCurrentPageName))
				{
					return true;
				}
			}
		}
		return false;
	}
	
	protected function getMenuTreeActive($oContent, $asCurrentPageNames)
	{
		$aasTree = $this->getMenuTree($oContent);
		
		$bCurrentFound = false;
		foreach ($aasTree as $i => $asTree)
		{
			if (self::isActiveMenuItem($asTree, $asCurrentPageNames))
			{
				$aasTree[$i]['ISCURRENT'] = 1;
				$bCurrentFound = true;
				
				//find current sublink
				if (isset($asTree['SUBLINKS']))
				{
					foreach ($asTree['SUBLINKS'] as $j => $asSubLink)
					{
						if (self::isActiveMenuSubItem($asSubLink, $asCurrentPageNames))
						{
							$aasTree[$i]['SUBLINKS'][$j]['ISCURRENT'] = 1;
							break;
						}
					}
				}				
				break;
			}
		}
		/*
		if (!$bCurrentFound)
		{
			$aasTree[0]['ISCURRENT'] = 1;
		}*/
		return $aasTree;
	}
	
	protected function getMenuTree($oContent)
	{
		$aasTree = array();
		$oFieldItem = $this->getContentField(self::FIELD_ITEMS);
		$aoContentsMenuItems = $oContent->getSubContents(self::FIELD_ITEMS);
		foreach ($aoContentsMenuItems as $oContentMenuItem)
		{
			$asMENUITEM = array();
			
			//$oFieldMenuItems = $this->getContentField(self::FIELD_ITEMS);
			//$oFieldMainLink = $oFieldMenuItems->getContentField(AnwContentFieldPage_menuItem::FIELD_MAINLINK);
			
			$oContentMainLink = $oContentMenuItem->getSubContent(AnwContentFieldPage_menuItem::FIELD_MAINLINK);
			
			$asMENUITEM['LINKTITLE'] = $oContentMainLink->getContentFieldValue(AnwIPage_link::FIELD_TITLE, 0, true);
			$asMENUITEM['LINKURL'] = $oContentMainLink->getContentFieldValue(AnwIPage_link::FIELD_URL, 0, true);
			$asMENUITEM['LINKTARGET'] = $oContentMainLink->getContentFieldValue(AnwIPage_link::FIELD_TARGET, 0, true);
			$asMENUITEM['ALLURLS'][] = $asMENUITEM['LINKURL'];
			
			$aoContentsSubItems = $oContentMenuItem->getSubContents(AnwContentFieldPage_menuItem::FIELD_SUBITEMS);
			foreach ($aoContentsSubItems as $oContentSubItem)
			{
				$oContentSubLink = $oContentSubItem->getSubContent(AnwContentFieldPage_menuSubItem::FIELD_LINK);
				
				$asUrlmatches = $oContentSubItem->getContentFieldValues(AnwContentFieldPage_menuSubItem::FIELD_URLMATCHES);
				foreach ($asUrlmatches as $sUrlmatch)
				{
					$asMENUITEM['ALLURLMATCHES'][] = $sUrlmatch;
				}
				
				$asMENUITEM['SUBLINKS'][] = array(
					'LINKTITLE' => $oContentSubLink->getContentFieldValue(AnwIPage_link::FIELD_TITLE, 0, true),
					'LINKURL' => $oContentSubLink->getContentFieldValue(AnwIPage_link::FIELD_URL, 0, true),
					'LINKTARGET' => $oContentSubLink->getContentFieldValue(AnwIPage_link::FIELD_TARGET, 0, true),
					'URLMATCHES' => $asUrlmatches
				);
				$asMENUITEM['ALLURLS'][] = $oContentSubLink->getContentFieldValue(AnwContentFieldPage_link::FIELD_URL, 0, true);
			}
			
			$asUrlmatches = $oContentMenuItem->getContentFieldValues(AnwContentFieldPage_menuItem::FIELD_URLMATCHES);
			foreach ($asUrlmatches as $sUrlmatch)
			{
				$asMENUITEM['ALLURLS'][] = $sUrlmatch;
				$asMENUITEM['ALLURLMATCHES'][] = $sUrlmatch;
			}
			
			$aasTree[] = $asMENUITEM;
		}
		return $aasTree;
	}
	
	function toFeedItem($oContent, $oPage)
	{
		$oFeedItem = new AnwFeedItem(
			$oContent->getContentFieldValue(self::FIELD_TITLE, 0, true),
			AnwUtils::linkAbsolute($oPage),
			"..."
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
			
			case self::PUB_ITEMS:
				//return $oContent->getContentFieldValue(self::FIELD_ITEMS);
				return $oContent->getSubContents(self::FIELD_ITEMS);
				break;
		}
	}
}

?>