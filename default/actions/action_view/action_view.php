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
 * Page rendering.
 * @package Anwiki
 * @version $Id: action_view.php 276 2010-09-06 21:48:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_view extends AnwActionPage implements AnwHarmlessAction, AnwAlwaysEnabledAction, AnwGrantAllUsersByDefaultAction
{
	const CSS_FILENAME = "action_view.css";
	
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		try
		{
			if (!$this->pageExists())
			{
				//try second chance...
				$oRedirectPage = $this->getoPage()->getSecondChance();
				if ($oRedirectPage)
				{
					//save _POST
					AnwUtils::httpPostToSession();
					
					//save _GET
					$asParams = $_GET;
					unset($asParams[AnwActionPage::GET_PAGENAME]); //avoid loop
					$sLink = AnwUtils::link($oRedirectPage->getName(), 'view', $asParams);
					AnwUtils::redirect($sLink);
				}
				else
				{
					$this->pagenotfound();
				}
			}
			
			$this->showCurrentPage();
		}
		catch(AnwBadPageNameException $e)
		{
			$this->error( self::g_("err_badpagename") );
		}
	}
	
	protected function pagenotfound()
	{
		if (AnwCurrentSession::isActionAllowed($this->getoPage()->getName(), 'create', -1))
		{
			AnwPlugins::hook('action_view_pagenotfound_create', $this->getoPage());
			AnwUtils::redirect( AnwUtils::link($this->getoPage(), "create") );
		}
		else
		{
			AnwPlugins::hook('action_view_pagenotfound_404', $this->getoPage());
			$this->error404();
		}
	}
	
	protected function showCurrentPage()
	{
		//prepare content for display
		try
		{
			$oOutputHtml = $this->getoPage()->toHtml();
		}
		catch (AnwException $e)
		{
			$oOutputHtml = new AnwOutputHtml($this->getoPage());
			$oOutputHtml->setBody( self::g_("err_rendercontent"), false );
			AnwDebug::reportError($e);
		}
		
		$this->head( $oOutputHtml->runHead() );
		$this->title = $oOutputHtml->runTitle();
		$sBody = $oOutputHtml->runBody();
		
		//if the translation is not complete, display a notice
		if (!$this->getoPage()->isTranslated() && $this->getoPage()->isActionAllowed('translate'))
		{
			$sNotice = $this->t_("local_notice_incomplete", 
								array(	"lnkopen" => '<a href="'.AnwUtils::link($this->getoPage(), "translate").'">',
										"lnkclose" => '</a>'));
			$this->out .= $this->tpl()->drawNotice($sNotice);
		}
		
		//page translations
		$aoTranslations = array();
		$aoPages = $this->getoPage()->getPageGroup()->getPages();
		foreach ($aoPages as $oPage)
		{
			if ($oPage->isActionAllowed('view'))
			{
				$bCurrent = ( $oPage->getName() == $this->getoPage()->getName() );
				$bTranslatedPercentEnough = ( $oPage->getTranslatedPercent() >= self::globalCfgViewUntranslatedMinpercent() ? true : false );
				$aoTranslations[] = array(
					'current' 	=> $bCurrent,
					'page'		=>	$oPage,
					'online'	=>	$bTranslatedPercentEnough
				);
			}
		}

		//display page content
		$this->out .= $this->tpl()->viewPage(
			self::g_("local_html_dir", array(), AnwAction::getActionLang()),
			AnwUtils::cssViewContent($this->getoPage()),
			$this->title,
			$sBody, 
			str_replace(',', ' ', Anwi18n::dateTime($this->getoPage()->getTime(), AnwAction::getActionLang())),
			$aoTranslations
		);
	}
}

?>