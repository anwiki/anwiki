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
 * Install step : initialize storage driver.
 * @package Anwiki
 * @version $Id: stepinstall_init_storage.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwStepInstallDefault_init_storage extends AnwStepInstallDefaultInitializeComponent
{
	protected function loadComponentToInitialize()
	{
		return AnwComponent::loadComponentGeneric(AnwComponent::globalCfgDriverStorage(), 'storagedriver');
	}
	
	protected function initializeAdditional()
	{
		$sInitLog = "";
		
		//-----------------------------
		// create default home page
		//-----------------------------
		$oContentClass = AnwContentClasses::getContentClass('page');
		$sPageName = AnwComponent::globalCfgHomePage();
		$sPageLang = AnwComponent::globalCfgLangDefault();
		$sChangeComment = "Installation assistant";
		
		$oContent = new AnwContentPage($oContentClass);
		$sHomeTitle = $this->t_("local_homepage_title", array(), $sPageLang);
		$sHomeHead = $this->tpl()->homePageHead($sPageLang);
		$sHomeBody = $this->tpl()->homePageBody($sPageLang, ANWIKI_WEBSITE);
		$oContent->setContentFieldValues(AnwIContentClassPageDefault_page::FIELD_TITLE, array($sHomeTitle));
		$oContent->setContentFieldValues(AnwIContentClassPageDefault_page::FIELD_HEAD, array($sHomeHead));
		$oContent->setContentFieldValues(AnwIContentClassPageDefault_page::FIELD_BODY, array($sHomeBody));
		
		$oPage = AnwPage::createNewPage($oContentClass, $sPageName, $sPageLang, $sChangeComment, $oContent);
		
		$sInitLog .= $this->t_("initlog_pagecreated", array('pagename'=>$sPageName)).'<br/>';
		
		
		//-----------------------------
		// create default menu
		//-----------------------------
		$oContentClass = AnwContentClasses::getContentClass('menu');
		$sPageName = 'en/_include/menu'; //TODO
		$sPageLang = (Anwi18n::langExists('en') ? 'en' : AnwComponent::globalCfgLangDefault()); //TODO
		$sChangeComment = "Installation assistant";		
		$oContent = new AnwContentPage($oContentClass);
		
		//menu title
		$sMenuTitle = $this->t_("local_menu_title", array(), $sPageLang);
		$oContent->setContentFieldValues(AnwIContentClassPageDefault_menu::FIELD_TITLE, array($sMenuTitle));
		
		//items
		$oContentFieldItems = $oContentClass->getContentField(AnwIContentClassPageDefault_menu::FIELD_ITEMS);
		$oSubContentItem = new AnwContentPage($oContentFieldItems);
		
				//main link
				$oContentFieldMainLink = $oContentFieldItems->getContentField(AnwIContentFieldPage_menu_menuItem::FIELD_MAINLINK);
				$oSubContentMainLink = new AnwContentPage($oContentFieldMainLink);
				
				$sMainLinkTitle = $this->t_("local_menu_mainlink_title", array(), $sPageLang);
				$oSubContentMainLink->setContentFieldValues(AnwIPage_link::FIELD_TITLE, array($sMainLinkTitle));
				$oSubContentMainLink->setContentFieldValues(AnwIPage_link::FIELD_URL, array(AnwComponent::globalCfgHomePage()));
				$oSubContentMainLink->setContentFieldValues(AnwIPage_link::FIELD_TARGET, array(AnwIPage_link::TARGET_SELF));
		
			$oSubContentItem->setSubContents(AnwIContentFieldPage_menu_menuItem::FIELD_MAINLINK, array($oSubContentMainLink));
			
		$oContent->setSubContents(AnwIContentClassPageDefault_menu::FIELD_ITEMS, array($oSubContentItem));
		
		$oPage = AnwPage::createNewPage($oContentClass, $sPageName, $sPageLang, $sChangeComment, $oContent);
		
		$sInitLog .= $this->t_("initlog_pagecreated", array('pagename'=>$sPageName)).'<br/>';
		
		return $sInitLog;
	}
}

?>