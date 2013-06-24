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
 * Exporting contents from Anwiki to a XML file.
 * @package Anwiki
 * @version $Id: action_export.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_export extends AnwActionGlobal
{
	const XMLTAG_ROOT = "anwexport";
	const XMLTAG_PAGEGROUP = "anwpagegroup";
	const XMLTAG_PAGE = "anwpage";
	
	const CSS_FILENAME = "action_export.css";
	
	function getNavEntry()
	{
		return $this->createManagementGlobalNavEntry();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		$this->setTitle( $this->t_("title") );
		
		if (!AnwEnv::_POST("exportpages"))
		{
			$this->exportForm();
		}
		else
		{
			$this->exportProcess(AnwEnv::_POST("exportpages"));
		}		
	}
	
	private function exportForm()
	{
		//initialize filters
		list($asAllLangs, $asDisplayLangs) = $this->filterLangs(array("view"));
		list($asAllClasses, $asDisplayClasses) = $this->filterContentClasses();
		
		$sFilters = $this->tpl()->filterStart($this->linkMe())
					.$this->tpl()->filterLangs($asAllLangs, $asDisplayLangs)
					.$this->tpl()->filterClass($asAllClasses, $asDisplayClasses)
					.$this->tpl()->filterEnd();
			
		$this->out .= $this->tpl()->begin($this->linkMe(), $sFilters);		
		
		$aoPageGroups = AnwStorage::getPageGroups(true, $asDisplayLangs, $asDisplayClasses);
		foreach ($aoPageGroups as $oPageGroup)
		{
			$this->out .= $this->tpl()->rowGroupOpen();
			
			$aoTranslations = $oPageGroup->getPages();
			foreach ($aoTranslations as $oPage)
			{
				$bExportDisabled = false;
				$bNoticePhp = false;
				$bNoticeAcl = false;
				
				//check PHP permission
				if ($oPage->hasPhpCode() && !AnwCurrentSession::getUser()->isPhpEditionAllowed())
				{
					$bNoticePhp = true;
					$bExportDisabled = true;
				}
				//check ACL permission
				if (!AnwCurrentSession::isActionAllowed($oPage->getName(), "export", $oPage->getLang()))
				{
					$bNoticeAcl = true;
					$bExportDisabled = true;
				}
				
				$this->out .= $this->tpl()->rowTranslation($oPage, $bExportDisabled, $bNoticePhp, $bNoticeAcl);
			}
			
			$this->out .= $this->tpl()->rowGroupClose();
		}
		
		$this->out .= $this->tpl()->end();
	}
	
	private function exportProcess($anExportPages)
	{
		//prepare an array of pages to be exported
		$aaExportPageGroups = array();
		
		$aoPageGroups = AnwStorage::getPageGroups();
		foreach ($aoPageGroups as $oPageGroup)
		{
			$bPageExported = false;
			$aoExportPages = array();
			
			$aoTranslations = $oPageGroup->getPages();
			
			foreach ($aoTranslations as $oPage)
			{
				$bExportDisabled = false;
				
				//check that page has been checked for export
				if (in_array($oPage->getId(), $anExportPages))
				{
					//check PHP permission
					if ($oPage->hasPhpCode() && !AnwCurrentSession::getUser()->isPhpEditionAllowed())
					{
						$bExportDisabled = true;
					}
					//check ACL permission
					if (!AnwCurrentSession::isActionAllowed($oPage->getName(), "export", $oPage->getLang()))
					{
						$bExportDisabled = true;
					}
					
					//add page to pagegroup export array
					if (!$bExportDisabled)
					{
						$aoExportPages[] = $oPage;
						$bPageExported = true;
					}
				}
			}
			
			//add pagegroup to export array
			if ($bPageExported)
			{
				$aaExportPageGroups[] = array(
											"GROUP" => $oPageGroup,
											"PAGES"	=> $aoExportPages);
			}
		}
		
		//export now
		$sExportData = $this->exportData($aaExportPageGroups);
		
		//output as a file
		$this->out = $sExportData;
		$sBackupDate = str_replace('/', '-', Anwi18n::date(time()));
		$sBackupDate .= '-'.date("H").date("i").date("s");
		$this->printOutputDownload("wiki-".$sBackupDate.".xml");
	}
	
	private function exportData($aaExportPageGroups)
	{
		$oDoc = new DOMDocument("1.0", "UTF-8");
				
		//put information as comment
		$sComment = "";
		$sComment .= $this->t_("xmlcomment_info")."\n";
		$sComment .= ANWIKI_WEBSITE."\n\n";
		$sComment .= $this->t_("xmlcomment_time", 
			array("time" => Anwi18n::dateTime(time()) ))."\n";
		$sComment .= $this->t_("xmlcomment_version",
			array("version" => ANWIKI_VERSION_NAME))."\n";
		$sComment .= $this->t_("xmlcomment_user",
			array("user" => AnwCurrentSession::getUser()->getLogin() ))."\n";
		$sComment .= $this->t_("xmlcomment_from",
			array("url" => self::globalCfgUrlRoot()))."\n\n";
		$sComment .= $this->t_("xmlcomment_contents")."\n";
		
		//list exported contents as comment
		foreach ($aaExportPageGroups as $amPageGroup)
		{
			foreach ($amPageGroup['PAGES'] as $oPage)
			{
				$sPageTime = Anwi18n::dateTime( $oPage->getTime() );
				$sComment .= ' * '.$oPage->getName()." (".$oPage->getLang().") (".$sPageTime.")\n";
			}
		}
		
		$sCommentSeparator = "\n**************************************************\n";
		$sComment = " ".$sCommentSeparator.$sComment.$sCommentSeparator." ";
		$oCommentNode = $oDoc->createComment($sComment);
		$oDoc->appendChild($oCommentNode);
		//end comment
		
		
		
		//<anwexport time="" origin="">
		$oRootNode = $oDoc->createElement(self::XMLTAG_ROOT);
		$oRootNode->setAttribute("time", time());
		$oRootNode->setAttribute("from", AnwXml::xmlFileAttributeEncode(self::globalCfgUrlRoot()));
		$oRootNode->setAttribute("version_id", ANWIKI_VERSION_ID);
		$oRootNode->setAttribute("version_name", AnwXml::xmlFileAttributeEncode(ANWIKI_VERSION_NAME));
		$oDoc->appendChild($oRootNode);
		
		
			
		
		foreach ($aaExportPageGroups as $amPageGroup)
		{
			$oPageGroup = $amPageGroup['GROUP'];
			$sContentClassName = $oPageGroup->getContentClass()->getName();
			
			//<anwpagegroup>
			$oPageGroupNode = $oDoc->createElement(self::XMLTAG_PAGEGROUP);
			$oPageGroupNode->setAttribute("contentclass", AnwXml::xmlFileAttributeEncode($sContentClassName));

			foreach ($amPageGroup['PAGES'] as $oPage)
			{
				//add comment
				$sPageTime = Anwi18n::dateTime( $oPage->getTime() );
				$sComment = $oPage->getName()." (".$oPage->getLang().") (".$sPageTime.") (".$oPageGroup->getContentClass()->getLabel()."/".$sContentClassName.")";
				
				//$sComment = " \n*\n* ".$sComment."\n*\n ";
				$sCommentSeparator = "\n**************************************************\n";
				$sComment = " \n\n".$sCommentSeparator.$sComment.$sCommentSeparator." ";
				
				$oCommentNode = $oDoc->createComment($sComment);
				$oPageGroupNode->appendChild($oCommentNode);
				//end comment
				
				
				//using a CDATA node to preserve source breaklines :-)
				//$sPageContent = $oPage->getContent()->toXml();
				//$oPageContentNode = $oDoc->createCDATASection($sPageContent);
				$oContentNodeDoc = $oPage->getContent()->toXml()->documentElement; //here we got a <doc> node
				$oPageContentNodeDoc = $oDoc->importNode($oContentNodeDoc, true);
				
				//<anwpage name="" lang="" time="">
				$oPageNode = $oDoc->createElement(self::XMLTAG_PAGE);
				$oPageNode->setAttribute("name", AnwXml::xmlFileAttributeEncode($oPage->getName()));
				$oPageNode->setAttribute("lang", AnwXml::xmlFileAttributeEncode($oPage->getLang()));
				$oPageNode->setAttribute("time", $oPage->getTime());
				
				//we need to do this to squeeze the unwanted <doc> node in
				//WARNING - special loop ! childs are getting modified...
				while ($oChildNode = $oPageContentNodeDoc->childNodes->item(0))
				{
					$oPageNode->appendChild($oChildNode);
				}
				
				$oPageGroupNode->appendChild($oPageNode);
			}
			$oRootNode->appendChild($oPageGroupNode);
		}
		$sReturn = AnwUtils::xmlDumpNode($oRootNode);
		
		// even if final XML structure may be broken due to undeclared namespaces used in content,
		// we let raw content as it is for better compatibility in later versions.
		// $sReturn = AnwXml::prepareXmlValueToXml($sReturn);
		
		return $sReturn;
	}
}

?>