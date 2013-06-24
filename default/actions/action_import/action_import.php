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
 * Importing contents into Anwiki from an XML file.
 * @package Anwiki
 * @version $Id: action_import.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_import extends AnwActionGlobal
{
	const XMLTAG_PAGEGROUP = "anwpagegroup";
	const XMLTAG_PAGE = "anwpage";
	
	//inputs
	const IN_UPLOAD = "uploadfile";
	const IN_FILENAME = "filename";
	const IN_PREFIX = "importprefix";
	const IN_PAGENAMES = "importpages";
	const IN_CONTINUE_ON_ERRORS = "continueonerrors";
	
	const CSS_FILENAME = "action_import.css";
	
	
	function getNavEntry()
	{
		return $this->createManagementGlobalNavEntry();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		$this->setTitle( $this->t_("title") );
		
		if (AnwEnv::_POST(self::IN_FILENAME) && AnwEnv::_POST(self::IN_PAGENAMES))
		{
			//4) do import
			$bContinueOnErrors = (AnwEnv::_POST(self::IN_CONTINUE_ON_ERRORS)=='true' ? true : false);
			$this->doImport(AnwEnv::_POST(self::IN_FILENAME), AnwEnv::_POST(self::IN_PAGENAMES), $bContinueOnErrors);
		}
		else if(AnwEnv::_GET(self::IN_FILENAME))
		{
			//3) form: select pages to import
			$this->importSelectionForm(AnwEnv::_GET(self::IN_FILENAME), AnwEnv::_GET(self::IN_PREFIX, ""));
		}
		else if (AnwEnv::_FILES(self::IN_UPLOAD))
		{
			//2) do upload of an export file
			$this->doUpload(AnwEnv::_FILES(self::IN_UPLOAD), AnwEnv::_POST(self::IN_PREFIX, ""));
		}
		else
		{
			//1) form: upload an export file
			$this->uploadForm();
		}
	}
	
	private function uploadForm()
	{
		$this->out .= $this->tpl()->uploadForm($this->linkMe(), self::IN_UPLOAD, self::IN_PREFIX);
	}
	
	private function doUpload($mUploadedFile, $sPrefix)
	{
		//move uploaded file
		$sDestinationFileName = AnwUtils::genStrongRandMd5();
		AnwUtils::move_uploaded_file($mUploadedFile, self::tmpFilename($sDestinationFileName));
		
		//redirect
		$sUrl = AnwUtils::aLink($this->getName(), 
							array(self::IN_FILENAME=>$sDestinationFileName,
							self::IN_PREFIX=>$sPrefix));
		AnwUtils::redirect($sUrl);
	}
	
	private function importSelectionForm($sUploadedFile, $sImportPrefix)
	{
		//load XML from file
		$aaData = $this->getDataFromXmlFile(self::tmpFilename($sUploadedFile));
		
		$sExportTime = Anwi18n::dateTime($aaData['TIME']);
		$sExportFrom = $aaData['FROM'];
		$sExportVersionName = $aaData['VERSION_NAME'];
		$nExportVersionId = $aaData['VERSION_ID'];
		
		//select pages to import
		$this->out .= $this->tpl()->beginSelection($this->linkMe(), $sUploadedFile, self::IN_FILENAME, self::IN_CONTINUE_ON_ERRORS, $sExportTime, $sExportFrom, $sExportVersionName);
		
		foreach ($aaData['PAGEGROUPS'] as $aaDataGroup)
		{
			$this->out .= $this->tpl()->rowGroupOpen($sExportTime, $sExportFrom);
			
			foreach ($aaDataGroup['PAGES'] as $aaDataPage)
			{
				$asNotices = array();
				$bImportDisabled = false;
				
				$sOriginalPageName = $aaDataPage['NAME'];
				$sPageName = $sImportPrefix.$sOriginalPageName; //prefix
				$sPageLang = $aaDataPage['LANG'];
				$nPageTime = $aaDataPage['TIME'];
				$sPageContent = $aaDataPage['CONTENT'];
				
				try
				{
					//check pagename and content
					$asNotices = $this->checkPermissions($sPageName, $sPageLang, $sPageContent);
					
					foreach ($asNotices as $sNotice)
					{
						//don't disable import checkbox for pages already existing, as user may change the destination name
						if ($sNotice != $this->t_("notice_exists"))
						{
							$bImportDisabled = true;
						}
					}
				}
				catch(AnwInvalidContentException $e)
				{
					$asNotices[] = $this->t_("notice_content");
					$bImportDisabled = true;
				}
				catch(AnwException $e)
				{
					$asNotices[] = $this->t_("notice_unknown");
					$bImportDisabled = true;
				}
				
				//check lang
				if (!Anwi18n::langExists($sPageLang))
				{
					$asNotices[] = $this->t_("notice_lang");
					$bImportDisabled = true;
				}
				
				$sInputPageName = $this->getInputPageName($sOriginalPageName);
				$sInputPageLang = $this->getInputPageLang($sOriginalPageName);
				$this->out .= $this->tpl()->rowTranslation(self::IN_PAGENAMES, $sInputPageName, $sInputPageLang, $sOriginalPageName, $sPageName, $sPageLang, $nPageTime, $bImportDisabled, $asNotices);
			}
			
			$this->out .= $this->tpl()->rowGroupClose();
		}
		
		$this->out .= $this->tpl()->endSelection();
	}
	
	private function getInputPageName($sPageName)
	{
		return "import_".md5($sPageName)."_name";
	}
	
	private function getInputPageLang($sPageName)
	{
		return "import_".md5($sPageName)."_lang";
	}
	
	/**
	 * Import selected files.
	 *
	 */
	private function doImport($sUploadedFile, $asSelectedPages, $bContinueOnErrors)
	{
		// we will display the import result after running the whole import
		// that's why we save the output in a temporary buffer during this process
		$sOutBuffer = "";
		
		//load XML from file
		$aaData = $this->getDataFromXmlFile(self::tmpFilename($sUploadedFile));
		
		//delete tmp file
		AnwUtils::unlink(self::tmpFilename($sUploadedFile), ANWPATH_TMP);
		
		$nCountImportErrors = 0;
		$nCountImportSuccess = 0;
		$bMustRollback = false; // only when $bContinueOnErrors = false
		
		// MAIN TRANSACTION - only when $bContinueOnErrors = false
		if ($bContinueOnErrors)
		{
			// won't revert whole import if errors
			$bMainTransaction = false;
			// will commit immediately each imported content
			$bSubTransaction = true;
		}
		else
		{
			// will revert whole import if errors
			$bMainTransaction = true;
			// won't commit immediately each imported content
			$bSubTransaction = false;
		}
		
		if ($bMainTransaction)
		{
			AnwStorage::transactionStart();
		}
		
		try
		{
			foreach ($aaData['PAGEGROUPS'] as $aaDataGroup)
			{
				$sOutBuffer .= $this->tpl()->rowGroupOpen();
				
				$oFirstPage = null;
				$oContentClass = AnwContentClasses::getContentClass($aaDataGroup['CONTENTCLASS']);
	
				foreach ($aaDataGroup['PAGES'] as $aaDataPage)
				{
					$asNotices = array();
					
					$sOriginalPageName = $aaDataPage['NAME'];
					$sPageName = AnwEnv::_POST($this->getInputPageName($sOriginalPageName));
					$sPageLang = AnwEnv::_POST($this->getInputPageLang($sOriginalPageName));
					if (!$sPageName || !$sPageLang)
					{
						throw new AnwUnexpectedException("PageName or PageLang not found for imported content: ".$sOriginalPageName);
					}
					
					$sPageContent = $aaDataPage['CONTENT'];
					
					//do we want to import this page?
					if (in_array($sOriginalPageName, $asSelectedPages) && $sPageName && $sPageLang)
					{
						//check pagename and content
						
						$asNotices = $this->checkPermissions($sPageName, $sPageLang, $sPageContent);
						
						if (count($asNotices) == 0)
						{
							// SUB TRANSACTION - only when $bContinueOnErrors = true
							if ($bSubTransaction)
							{
								AnwStorage::transactionStart();
							}
							
							try
							{
								// create the new page
								$oContent = $oContentClass->rebuildContentFromXml($sPageContent);
								
								if (!$oFirstPage)
								{
									$oPage = AnwPage::createNewPage($oContentClass, $sPageName, $sPageLang, "", $oContent);
								}
								else
								{
									$oPage = $oFirstPage->createNewTranslation($sPageName, $sPageLang, "", $oContent);
								}
								
								if ($bSubTransaction)
								{
									AnwStorage::transactionCommit();
								}
								
								// wait for everything to be completed before affecting $oFirstPage, in case of it fails
								if (!$oFirstPage)
								{
									$oFirstPage = $oPage;
								}
							}
							catch(AnwException $e)
							{
								// special errors management, see FS#62
								$asNotices[] = $this->t_("notice_unknown")." (".$e->getMessage().")";
								//print $e->getFile().'!'.$e->getLine();print_r($e->getTrace());
								
								if ($bMainTransaction)
								{
									// we will have to rollback, but we still continue to get the whole report
									$bMustRollback = true;
								}
								if ($bSubTransaction)
								{
									AnwStorage::transactionRollback();
								}
								
								AnwDebug::reportError($e);
							}
						}
						
						// import result
						if (count($asNotices) == 0)
						{
							$nCountImportSuccess++; // at least we got a success!
							$sOutBuffer .= $this->tpl()->rowTranslationProcess_success($oPage->link());
						}
						else
						{
							$nCountImportErrors++;
							$sOutBuffer .= $this->tpl()->rowTranslationProcess_failed($sPageName, $sPageLang, $asNotices);
						}
					}
					else
					{
						$sOutBuffer .= $this->tpl()->rowTranslationProcess_skipped($sPageName, $sPageLang);
					}
				}
				$sOutBuffer .= $this->tpl()->rowGroupClose();
			}
			
			if ($bMainTransaction)
			{
				if (!$bMustRollback)
				{
					AnwStorage::transactionCommit();
				}
				else
				{
					AnwStorage::transactionRollback();
				}
			}
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		
		
		// output, with import results before the detailled report
		
		$this->out .= $this->tpl()->beginProcess();
		
		if ($nCountImportErrors > 0)
		{
			if ($nCountImportSuccess > 0)
			{
				if ($bContinueOnErrors)
				{
					$this->out .= $this->tpl()->importResultErrorsContinued($nCountImportSuccess, $nCountImportErrors);
				}
				else
				{
					$this->out .= $this->tpl()->importResultErrorsCancelled($nCountImportSuccess, $nCountImportErrors);
				}
			}
			else
			{
				$this->out .= $this->tpl()->importResultFailed($nCountImportErrors);
			}
		}
		else
		{
			$this->out .= $this->tpl()->importResultSuccess($nCountImportSuccess);
		}
		
		$this->out .= $this->tpl()->importDetails($sOutBuffer);
	}
	
	
	//----------------
	
	
	private function checkPermissions($sPageName, $sPageLang, $sPageContent)
	{
		$asNotices = array();
		
		//check that page don't exist
		if (!AnwPage::isAvailablePageName($sPageName))
		{
			$asNotices[] = $this->t_("notice_exists");
		}
		
		//check PHP permission
		if (AnwUtils::contentHasPhpCode($sPageContent) && !AnwCurrentSession::getUser()->isPhpEditionAllowed())
		{
			$asNotices[] = $this->t_("notice_php");
		}
		
		//check JS permission
		if (AnwUtils::contentHasJsCode($sPageContent) && !AnwCurrentSession::getUser()->isJsEditionAllowed())
		{
			$asNotices[] = $this->t_("notice_js");
		}
		
		//check ACL permission : create and edit
		if (!AnwCurrentSession::isActionAllowed($sPageName, "create", $sPageLang) 
			||!AnwCurrentSession::isActionAllowed($sPageName, "edit", $sPageLang))
		{
			$asNotices[] = $this->t_("notice_acl");
		}
		
		return $asNotices;
	}
	
	
	private static function tmpFilename($sFilename)
	{
		// FS#59 protection against path traversal
		$sFilename = preg_replace('/\W/', '', $sFilename);
		return ANWPATH_TMP.$sFilename.'.xml';
	}
	
	
	private function getDataFromXmlFile($sFilename)
	{
		//load XML from file
		if (!file_exists($sFilename)) throw new AnwBadCallException("import file not found");
		$sFileContent = AnwUtils::file_get_contents($sFilename);
		$oRootNode = AnwUtils::loadXml($sFileContent);
				
		$aaData = array();	
		$aaData['PAGEGROUPS'] = array();
		$aaData['TIME'] = $oRootNode->getAttribute("time");
		$aaData['FROM'] = AnwXml::xmlFileAttributeDecode($oRootNode->getAttribute("from"));
		$aaData['VERSION_ID'] = $oRootNode->getAttribute("version_id");
		$aaData['VERSION_NAME'] = AnwXml::xmlFileAttributeDecode($oRootNode->getAttribute("version_name"));
		
		$aoPageGroupsNodes = AnwXml::xmlGetChildsByTagName(self::XMLTAG_PAGEGROUP, $oRootNode);
		foreach ($aoPageGroupsNodes as $oPageGroupNode)
		{
			$aaDataGroup = array();
			$aaDataGroup['CONTENTCLASS'] = AnwXml::xmlFileAttributeDecode($oPageGroupNode->getAttribute("contentclass"));
			$aaDataGroup['PAGES'] = array();
			
			$aoTranslationsNodes = AnwXml::xmlGetChildsByTagName(self::XMLTAG_PAGE, $oPageGroupNode);
			foreach ($aoTranslationsNodes as $oPageNode)
			{
				$asDataPage = array();
				$asDataPage['NAME'] = AnwXml::xmlFileAttributeDecode($oPageNode->getAttribute("name"));
				$asDataPage['LANG'] = AnwXml::xmlFileAttributeDecode($oPageNode->getAttribute("lang"));
				$asDataPage['TIME'] = (int)$oPageNode->getAttribute("time");
				$asDataPage['CONTENT'] = AnwUtils::xmlDumpNodeChilds($oPageNode);
				
				$aaDataGroup['PAGES'][] = $asDataPage;
			}			
			$aaData['PAGEGROUPS'][] = $aaDataGroup;
		}
		return $aaData;
	}
}

?>