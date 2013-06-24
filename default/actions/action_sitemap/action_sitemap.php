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
 * Anwiki sitemap.
 * @package Anwiki
 * @version $Id: action_sitemap.php 208 2009-04-09 23:30:28Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_sitemap extends AnwActionGlobal implements AnwHarmlessAction
{
	const CSS_FILENAME = 'action_sitemap.css';
	
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		//initialize filters
		list($asAllLangs, $asDisplayLangs) = $this->filterLangs(array("view"));
		list($asAllClasses, $asDisplayClasses) = $this->filterContentClasses();
		
		
		$this->setTitle( $this->t_("title") );
		
		$aoPageGroups = AnwStorage::getPageGroups(true, $asDisplayLangs, $asDisplayClasses);
		
		$sFilters = $this->tpl()->filterStart($this->linkMe())
					.$this->tpl()->filterLangs($asAllLangs, $asDisplayLangs)
					.$this->tpl()->filterClass($asAllClasses, $asDisplayClasses)
					.$this->tpl()->filterEnd();
		
		$this->out .= $this->tpl()->begin($sFilters);
			
		foreach ($aoPageGroups as $oPageGroup)
		{
			$tmpOut = "";
			
			$aoTranslations = $oPageGroup->getPages();
			foreach ($aoTranslations as $oPage)
			{
				if ($oPage->isActionAllowed('view'))
				{
					$tmpOut .= $this->tpl()->rowTranslation($oPage, self::getPageNavEntriesAllowed($oPage));
				}
			}
			
			if ($tmpOut != "")
			{
				$this->out .= $this->tpl()->rowGroupOpen();
				$this->out .= $tmpOut;
				$this->out .= $this->tpl()->rowGroupClose();
			}
		}
		$this->out .= $this->tpl()->end();	
	}
}

?>