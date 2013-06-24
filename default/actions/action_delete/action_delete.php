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
 * Deleting a Page.
 * @package Anwiki
 * @version $Id: action_delete.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_delete extends AnwActionPage
{
	private static $DELETE_PAGE=1;
	private static $DELETE_GROUP=2;
	
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function preinit()
	{
		$this->checkPageExists();
	}
	
	function run()
	{
		$this->setTitle( $this->t_('title') );
		
		$sDeleteType = AnwEnv::_GET("t");
		if (!$sDeleteType)
		{
			$this->selectDeletionType();
		}
		else
		{
			if ($sDeleteType == self::$DELETE_PAGE)
			{
				$this->deletePage();
			}
			else if ($sDeleteType == self::$DELETE_GROUP)
			{
				$this->deleteGroup();
			}
			else
			{
				$this->error($this->g_("err_badcall"));
			}
		}
	}
	
	private function selectDeletionType()
	{
		$sLinkDeletePage = AnwUtils::link($this->getPageName(), "delete", array("t"=>self::$DELETE_PAGE));
		$sLinkDeleteGroup = AnwUtils::link($this->getPageName(), "delete", array("t"=>self::$DELETE_GROUP));
		$this->out .= $this->tpl()->selectDeletionType(
			$sLinkDeletePage,
			($this->isDeleteGroupAllowed() ? $sLinkDeleteGroup : false)
		);
	}
	
	private function deleteGroup()
	{
		if (!$this->isDeleteGroupAllowed())
		{
			throw new AnwAclException("permission delete denied");
		}
		$this->getoPage()->getPageGroup()->deletePages();
		AnwUtils::redirect();
	}
	
	private function deletePage()
	{
		$this->getoPage()->delete();
		AnwUtils::redirect();
	}
	
	private function isDeleteGroupAllowed()
	{
		$aoTranslations = $this->getoPage()->getPageGroup()->getPages();
		$bDeleteGroupAllowed = true;
		foreach ($aoTranslations as $oTranslation)
		{
			if (!$oTranslation->isActionAllowed("delete"))
			{
				$bDeleteGroupAllowed = false;
				break;
			}
		}
		return $bDeleteGroupAllowed;
	}
	
}

?>