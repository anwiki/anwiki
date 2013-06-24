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
 * ContentClass: translation file.
 * @package Anwiki
 * @version $Id: contentclass_trfile.php 341 2010-10-14 23:21:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */


class AnwContentFieldPage_trfileItem extends AnwContentFieldPage_container
{
	const FIELD_ID = "id";
	const FIELD_VALUE = "value";
	
	const PUB_ID = "id";
	const PUB_VALUE = "value";
	
	function init()
	{
		// translation id
		$oContentField = new AnwContentFieldPage_string(self::FIELD_ID);
		$oContentField->setTranslatable(false);
		$this->addContentField($oContentField);
		
		// translation value
		$oContentField = new AnwContentFieldPage_xhtml(self::FIELD_VALUE);
		$this->addContentField($oContentField);
	}
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			case self::PUB_ID:
				return $oContent->getContentFieldValue(self::FIELD_ID);
				break;
			
			case self::PUB_VALUE:
				return $oContent->getContentFieldValue(self::FIELD_VALUE);
				break;
		}
	}
	
	function renderCollapsedInputComposed($oSubContent, $sRendered, $sSuffix)
	{
		$sId = $oSubContent->getContentFieldValue(self::FIELD_ID);
		$sValue = $oSubContent->getContentFieldValue(self::FIELD_VALUE);
		
		return '<span style="width:20em; display:block; float:left;">['.$sId.']</span> '.$sValue;
	}
}

class AnwContentClassPageDefault_trfile extends AnwContentClassPage
{
	const FIELD_NAME = "name";
	const FIELD_ITEMS = "items";
	
	const PUB_NAME = "name";
	const PUB_ITEMS = "items";
	
	function init()
	{
		// translation file name
		$oContentField = new AnwContentFieldPage_string( self::FIELD_NAME );
		$oContentField->setTranslatable(false);
		$oContentField->indexAs(self::PUB_NAME);
		$this->addContentField($oContentField);
		
		// translation file items
		$oContentField = new AnwContentFieldPage_trfileItem( self::FIELD_ITEMS );
		$oContentMultiplicity = new AnwContentMultiplicity_multiple();
		$oContentField->setMultiplicity($oContentMultiplicity);
		$this->addContentField($oContentField);
	}
	
	function toHtml($oContent, $oPage)
	{
		$sFileName = $oContent->getContentFieldValue( self::FIELD_NAME );
		
		$oOutputHtml = new AnwOutputHtml( $oPage );
		$oOutputHtml->setTitle( $this->t_('local_title', array('name'=>$sFileName), $oPage->getLang()) );
		
		$sHtmlBody = "";
		$sHtmlBody .= $this->tpl()->startTrfile( $sFileName, $oPage->getLang() );
		
		$aoContentsTrfileItems = $oContent->getSubContents(self::FIELD_ITEMS);
		foreach ($aoContentsTrfileItems as $oContentTrfileItem)
		{
			$sTranslationId = $oContentTrfileItem->getContentFieldValue(AnwContentFieldPage_trfileItem::FIELD_ID, 0, true);
			$sTranslationValue = $oContentTrfileItem->getContentFieldValue(AnwContentFieldPage_trfileItem::FIELD_VALUE);
			$sHtmlBody .= $this->tpl()->translationItem($sTranslationId, $sTranslationValue);
		}
		$sHtmlBody .= $this->tpl()->stopTrfile();
		
		$oOutputHtml->setBody( $sHtmlBody );
		return $oOutputHtml;
	}
	
	function toFeedItem($oContent, $oPage)
	{
		$oFeedItem = new AnwFeedItem(
			$oContent->getContentFieldValue(self::FIELD_NAME, 0, true),
			AnwUtils::linkAbsolute($oPage)
		);
		return $oFeedItem;
	}
	
	function pubcall($sArg, $oContent, $oPage)
	{
		switch($sArg)
		{
			case self::PUB_NAME:
				return $oContent->getContentFieldValue(self::FIELD_NAME);
				break;
			
			case self::PUB_ITEMS:
				return $oContent->getSubContents(self::FIELD_ITEMS);
				break;
		}
	}
	
}

?>