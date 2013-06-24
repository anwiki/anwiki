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
 * ContentClass: classic XHTML page.
 * @package Anwiki
 * @version $Id: contentclass_page.php 341 2010-10-14 23:21:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwContentClassPageDefault_page extends AnwContentClassPage implements AnwIContentClassPageDefault_page
{
	
	function init()
	{
		// page head
		$oContentField = new AnwContentFieldPage_xhtml(self::FIELD_HEAD);
		$oContentField->setTranslatable(false);
		$oContentField->setDynamicParsingAllowed(true);
		$oContentField->setDynamicPhpAllowed(true);
		$this->addContentField($oContentField);
		
		
		// page body
		$oContentField = new AnwContentFieldPage_xhtml(self::FIELD_BODY);
		$oContentField->setDynamicParsingAllowed(true);
		$oContentField->setDynamicPhpAllowed(true);
		$this->addContentField($oContentField);
		
		
		// page title
		$oContentField = new AnwContentFieldPage_string(self::FIELD_TITLE);
		$oContentField->indexAs(self::PUB_TITLE);
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$oOutputHtml = new AnwOutputHtml( $oPage );
		
		$sTitleValue = $oContent->getContentFieldOutput(self::FIELD_TITLE);
		$sHeadValue = $oContent->getContentFieldOutput(self::FIELD_HEAD);
		$sBodyValue = $oContent->getContentFieldOutput(self::FIELD_BODY);
		
		$oOutputHtml->setTitle( $sTitleValue );
		$oOutputHtml->setHead( $this->tpl()->toHtmlHead($sHeadValue, $sTitleValue) );
		$oOutputHtml->setBody( $this->tpl()->toHtmlBody($sBodyValue, $sTitleValue) );
		
		$oOutputHtml->setTitleDependancy( $this->getContentField(self::FIELD_TITLE) );
		$oOutputHtml->setHeadDependancy( $this->getContentField(self::FIELD_HEAD) );
		$oOutputHtml->setBodyDependancy( $this->getContentField(self::FIELD_BODY) );
		
		//$oOutputHtml->setTitle( self::getTitleForOutput($oContent, $oPage, $oOutputHtml) );
		//$oOutputHtml->setHead( self::getHeadForOutput($oContent, $oPage, $oOutputHtml) );
		//$oOutputHtml->setBody( self::getBodyForOutput($oContent, $oPage, $oOutputHtml) );
		return $oOutputHtml;
	}
	
	function toFeedItem($oContent, $oPage)
	{
		$oFeedItem = new AnwFeedItem(
			$oContent->getContentFieldOutput(self::FIELD_TITLE),
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
			
			//head & body disabled for security reasons
		}
	}
	
	//------------------------------------------
	
	/*
	private static function getTitleForOutput($oContent, $oPage, $oOutputHtml)
	{		
		$sHtmlAndPhp = $oContent->getContentFieldValue( self::FIELD_TITLE );
		$sHtmlAndPhp = AnwPlugins::vhook("contentclass_page_output_title", $sHtmlAndPhp, $oContent, $oPage);
		return $sHtmlAndPhp;
	}
	
	private static function getHeadForOutput($oContent, $oPage, $oOutputHtml)
	{		
		$sHtmlAndPhp = $oContent->getContentFieldValue( self::FIELD_HEAD );
		$sHtmlAndPhp = AnwPlugins::vhook("contentclass_page_output_head", $sHtmlAndPhp, $oContent, $oPage);
		return $sHtmlAndPhp;
	}
	
	private static function getBodyForOutput($oContent, $oPage, $oOutputHtml)
	{		
		$sHtmlAndPhp = $oContent->getContentFieldValue( self::FIELD_BODY );
		$sHtmlAndPhp = AnwPlugins::vhook("contentclass_page_output_body", $sHtmlAndPhp, $oContent, $oPage);
		return $sHtmlAndPhp;
	}*/
}

?>