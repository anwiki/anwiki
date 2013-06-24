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
 * A Page encapsulates a AnwStructuredContent. Each page is accessed via an URL, has it's own language and is associated to a PageGroup.
 * @package Anwiki
 * @version $Id: class_page.php 239 2010-02-19 21:18:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

interface AnwPubcallCapability
{
	function pubcall($sArg);
}

abstract class AnwPage implements AnwPubcallCapability
{
	protected $nId;
	protected $sName;
	protected $oPageGroup;
	protected $sLang;
	protected $nTime;
	protected $nChangeId; //related ChangeId (either if it's an active page or an archived page)
	protected $sContentFromRebuild; //when rebuilding a page, keep content as string instead of building oContent
	protected $bTranslated;
	protected $nTranslatedPercent;
	protected $nCreationTime;
	
	protected $oContent;
	protected $oPreviousContent; //previous content or null
	protected $bIsArchive = false; //is it an archived version
	protected $bExists; //does the page exist in database (either if it's an active page or archived page)
	protected $bActiveExists=-1; //does an active page of this ID exist and is it active?
	protected $oChange; //related change (either if it's an active page or an archived page)	
	protected $bInfoLoaded = false; //did we load existing page's info
	//protected $aoDrafts;
	
	protected $bSkipLoadingTranslationsContent = false;
	protected $bSkipLoadingContent = false;
	
	//cached values from contentclass
	public $sCachedOutputKey;
	protected $nCachedOutputExpiry;
	
	//temporary
	private static $sUpdateOutgoingLink_oldLinkBase;
	private static $sUpdateOutgoingLink_newLinkBase;
	private static $sUpdateOutgoingLink_linkLang;
	
	// see FS#53
	protected static $asREGEXP_LINKS = array('!([[:space:]](?:href|action)=[\'"])(.*?)([\'"])!si'/*, '!(anwlink:)(.*?)([[:space:],;\(\)<>\'"])!si'*/);
	
	const MINLEN_PAGENAME=1;
	const MAXLEN_PAGENAME=150; /* used by storage drivers */
	
	const PUB_MYNAME = "myname";
	const PUB_MYURL = "myurl";
	const PUB_MYTIME = "mytime";
		
	
	function __construct($nChangeIdArchive=false)
	{
		if ($nChangeIdArchive)
		{
			$this->nChangeId = $nChangeIdArchive;
			$this->bIsArchive = true;
		}
	}
	
	function __sleep()
	{
		if (!$this->bExists)
		{
			throw new AnwUnexpectedException("trying to cache an unexistant page");
		}
		if ($this->bIsArchive)
		{
			throw new AnwUnexpectedException("trying to cache an archived page");
		}
		if (!$this->bInfoLoaded)
		{
			throw new AnwUnexpectedException("trying to cache a page not loaded");
		}
		
		//cache some values from contentclass
		//FOR STRANGE REASONS, DOES AN INTERNAL ERROR ON SOME SERVERS...
		//So we force loading these values directly in rebuildPage()
		//$this->getCachedOutputKey();
		//$this->getCachedOutputExpiry();
		
		$asUnusedVars = array('oContent', 'oPreviousContent', 'bIsArchive', 'bExists', 'oChange', 'bInfoLoaded', 'bSkipLoadingTranslationsContent', 'bSkipLoadingContent'
								 );
		return AnwUtils::instanceVars(get_object_vars($this), $asUnusedVars);
	}
	
	function __wakeup()
	{
		$this->bIsArchive = false;
		$this->bExists = true;
		$this->bInfoLoaded = true;
	}
	
	function setSkipLoadingTranslationsContent($bSkip)
	{
		$this->bSkipLoadingTranslationsContent = $bSkip;
	}
	
	function setSkipLoadingContent($bSkip)
	{
		$this->bSkipLoadingContent = $bSkip;
	}

	//------------------------------------------------
	// SETTERS
	//------------------------------------------------
	
	/**
	 * This function must be called before page creation.
	 * @throws AnwUnexpectedException
	 */
	private function setPageGroupBeforePageCreation($oPageGroup)
	{
		if ($this->exists())
		{
			throw new AnwUnexpectedException("Can't set the PageGroup for an existing page");
		}
		$this->oPageGroup = $oPageGroup;
	}
	
	/**
	 * This function must be called after page creation (to avoid pagegroup to be unsynchronized when page creation fails).
	 * @throws AnwUnexpectedException
	 */
	private function setPageGroupAfterPageCreation($oPageGroup)
	{
		$oPageGroup->addPage($this);
	}
	
	/**
	 * @throws AnwUnexpectedException, AnwBadLangException
	 */
	function setLang($sLang)
	{
		if ($this->exists())
		{
			throw new AnwUnexpectedException("Can't set the lang for an existing page");
		}
		if (!Anwi18n::isValidLang($sLang))
		{
			throw new AnwBadLangException();
		}
		$this->sLang = $sLang;
	}
	
	function revertToRevision($oPageArchive, $nTime=null, $sChangeComment="")
	{
		if ($this->getName()!=$oPageArchive->getName())
		{
			$bUpdateLinks = false;
			$this->rename($oPageArchive->getName(), $bUpdateLinks, $sChangeComment, $nTime);
		}
		
		if ($this->getLang()!=$oPageArchive->getLang())
		{
			$this->changeLang($oPageArchive->getLang(), $sChangeComment, $nTime);
		}
		
		$this->_setContent($oPageArchive->getContent(), -1); //we don't check content here, the caller must do the check by himself
		$sChangeInfo = $oPageArchive->getTime();
		$this->doUpdate(AnwChange::TYPE_PAGE_REVERT, $sChangeComment, $nTime, $sChangeInfo);
	}
	
	function saveTranslation($oNewContent, $nTime=null)
	{
		$nChangeType = AnwChange::TYPE_PAGE_TRANSLATION;
		$sChangeComment = "";
		$sChangeInfo = $this->getTranslatedPercent()."%";
		
		$aoSimilarPageTranslations = $this->getPageGroup()->getPages($this);
		if ( count($aoSimilarPageTranslations) > 0 )
		{
			$oSimilarPageTranslation = array_pop($aoSimilarPageTranslations);
			$oSimilarContent = $oSimilarPageTranslation->getContent();
		}
		else
		{
			$oSimilarContent = $this->getContent();
		}
		
		// don't check for php/js edition permissions for updating translation
		$bSecurityChecks = false;
		$this->_setContent($oNewContent, $oSimilarContent, $bSecurityChecks);
		
		$bClearCachedOutputForPageGroupsLinking = false; //no need to clear cachedoutput for linked pagegroups, as only translatable values have changed, not structure
		$this->doUpdate($nChangeType, $sChangeComment, $nTime, $sChangeInfo, $bClearCachedOutputForPageGroupsLinking);
	}
	
	function saveEditAndDeploy($oNewContent, $nChangeType, $sComment="", $sChangeInfo="", $nTime=null)
	{
		//TODO
		ini_set('max_execution_time',600);
		ini_set('memory_limit','64M');
		
		AnwDebug::startBench("saveEditAndDeploy");
		
		//----------------------------------------------------------
		//update all translations but don't save it now
		//----------------------------------------------------------
		
		$aoPageTranslations = $this->getPageGroup()->getPages($this);
		foreach ($aoPageTranslations as $oPageTranslation)
		{
			$this->debug("saveEditAndDeploy: updating translation : ".$oPageTranslation->getName());
			//print "saveEdit: updating translation : ".$oPageTranslation->getName();flush();
			
			$oContentNewTranslation = AnwAutoSync::propagateContentEdition($this->getContent(), $oNewContent, $oPageTranslation->getContent());
			$oPageTranslation->_setContent($oContentNewTranslation, $oNewContent);
		}
		$this->debug("saveEditAndDeploy: all updates OK, comitting...");
		
		//----------------------------------------------------------
		//all updates ok, comitting...
		//----------------------------------------------------------
		
		AnwStorage::transactionStart();
		try
		{
			//save changes to the current page
			$this->_setContent($oNewContent, -1);
			$this->doUpdate($nChangeType, $sComment, $nTime, $sChangeInfo);
			
			//save others translations
			foreach ($aoPageTranslations as $oPageTranslation)
			{
				$oPageTranslation->doUpdate(AnwChange::TYPE_PAGE_EDITION_DEPLOY, $sComment, $nTime, $this->getName());
			}
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		AnwDebug::stopBench("saveEditAndDeploy");
	}	
	
	//------------------------------------------------
	// ACCESSORS
	//------------------------------------------------
	
	function getName()
	{
		return $this->sName;
	}
	
	function getId()
	{
		if (!$this->exists()) throw new AnwPageNotFoundException();
		return $this->nId;
	}
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getLang()
	{
		if (!$this->sLang) $this->loadInfo();
		if (!$this->sLang) throw new AnwPageNotFoundException();
		return $this->sLang;
	}
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getTime()
	{
		if (!$this->nTime) $this->loadInfo();
		if (!$this->nTime) throw new AnwPageNotFoundException();
		return $this->nTime;
	}
	
	function getChangeId()
	{
		if (!$this->nChangeId) $this->loadInfo();
		if (!$this->nChangeId) throw new AnwPageNotFoundException();
		return $this->nChangeId;
	}
			
	function getContent()
	{
		if (!$this->oContent)
		{
			if (!$this->sContentFromRebuild) $this->loadInfo();
			$this->loadContentFromRebuild();
		}
		if (!$this->oContent) throw new AnwUnexpectedException("getContent on null content");
		return $this->oContent;
	}
	
	/**
	 * @throws AnwPageNotFoundException
	 */
	function getPageGroup()
	{
		if (!$this->oPageGroup) $this->loadInfo();
		if (!$this->oPageGroup) throw new AnwPageNotFoundException();
		return $this->oPageGroup;
	}
	
	function getPageRevisions($nLimit=false)
	{
		if (!$this->exists()) throw new AnwUnexpectedException("Can't get page revisions for a non-existing page");
		$aoChanges = AnwStorage::getLastChanges($nLimit, 0, null, null, null, $this);
		
		$aoPageRevisions = array();
		foreach ($aoChanges as $oChange)
		{
			if ($oChange->getPage() && $oChange->getPage()->exists())
			{
				//$aoPageRevisions[] = new AnwPageById($this->getId(), $oChange->getTime(), $oChange);
				$aoPageRevisions[] = $oChange->getPage();
			}
		}
		return $aoPageRevisions;
	}
	
	/**
	 * Get page revision, either if this revision is active page or archived page.
	 */
	function getPageRevision($nChangeId)
	{
		if ($nChangeId == $this->getChangeId())
		{
			return $this;
		}
		
		if (!$this->isArchive()) 
		{
			//we are sure that it's an archive
			return new AnwPageById($this->getId(), $nChangeId);
		}
		
		//here we dont know if it's the active page or an archive... lets try the 2 possibilites
		$oRevisionPage = new AnwPageById($this->getId());
		if ($oRevisionPage->getChangeId() != $nChangeId)
		{
			$oRevisionPage = new AnwPageById($this->getId(), $nChangeId);
		}
		return $oRevisionPage;
	}
	
	function getChange()
	{
		if (!$this->oChange)
		{
			// we shouldn't need this as change data should already be initialized
//			if ($this->isArchive())
//			{
//				//$this->oChange = AnwStorage::getChangeForPageArchive($this);
//			}
		}
		return $this->oChange;
	}
	
	function isArchive()
	{
		return $this->bIsArchive;
	}
	
	function exists()
	{
		$this->loadInfo();
		return $this->bExists;
	}
	
	function activeExists()
	{
		if ($this->bActiveExists === -1) //first call to this function
		{
			if ($this->getActivePage())
			{
				$this->bActiveExists = true;
			}
			else
			{
				$this->bActiveExists = false;
			}
		}
		return $this->bActiveExists;
	}
	
	//we don't cache this information, as it's not used so much but would slow down the cache
	function getActivePage()
	{
		//are we currently the active page?
		if (!$this->isArchive() && $this->exists())
		{
			return $this;
		}
		
		//does this active page exists?
		$nPageId = $this->getId();
		$oPage = new AnwPageById($nPageId);
		if ($oPage->exists())
		{
			return $oPage;
		}
		
		return null;
	}
	
	function getPreviousArchive($nMaxChangeId=null)
	{
		if (!$nMaxChangeId && $this->exists())
		{
			$nMaxChangeId = $this->getChangeId();
		}
		return AnwStorage::getPageArchivePrevious($this, $nMaxChangeId);
	}
	/*
	function getDrafts()
	{
		if (!$this->aoDrafts)
		{
			$this->aoDrafts = AnwStorage::getPageDrafts($this);
		}
		return $this->aoDrafts;
	}*/
	
	/*function getDraft($nDraftTime)
	{
		$aoDrafts = $this->getDrafts();
		if (!isset($aoDrafts[$nDraftTime]))
		{
			throw new AnwUnexpectedException("Draft not found : ".$nDraftTime);
		}
		return $aoDrafts[$nDraftTime];
	}*/
	
	static function buildTranslationNameDefault($sPageName, $sPageLang, $sTranslationLang)
	{
		$sRegexp = '!(.*)([\-_/\.])'.$sPageLang.'([\-_/\.])(.*)!si';
		if (preg_match($sRegexp,'/'.$sPageName.'/', $asMatches))
		{
			// dir/en/pagename => dir/fr/pagename
			// pagename.en.ext => pagename.fr.ext
			// pagename-en-ext => pagename-fr-ext
			$sTranslationNameDefault = $asMatches[1].$asMatches[2].$sTranslationLang.$asMatches[3].$asMatches[4];
			$sTranslationNameDefault = substr($sTranslationNameDefault, 1, strlen($sTranslationNameDefault)-2);
		}
		else
		{
			// pagename => pagename_fr
			$sTranslationNameDefault = $sPageName.'_'.$sTranslationLang;
		}
		return $sTranslationNameDefault;
	}
	
	function getTranslationNameDefault($sTranslationLang)
	{
		return self::buildTranslationNameDefault($this->getName(), $this->getLang(), $sTranslationLang);
	}
	
	static function isValidPageName($sPageName)
	{
		//common checks
		$nLen = strlen($sPageName);
		if (!($nLen >= self::MINLEN_PAGENAME && $nLen <= self::MAXLEN_PAGENAME && strip_tags($sPageName) == $sPageName))
		{
			return false;
		}
		
		//starting slashe is forbidden, to avoid confusion
		if (substr($sPageName,0,1) == '/')
		{
			return false;
		}
		
		//ending slashe is forbidden, to avoid confusion
		if (substr($sPageName,-1,1) == '/')
		{
			return false;
		}
		
		//check for forbidden chars
		$asForbiddenChars = array('&','?', '=', '#');
		foreach ($asForbiddenChars as $sForbiddenChar)
		{
			if (strstr($sPageName, $sForbiddenChar))
			{
				return false;
			}
		}
		
		//additional checks
		try
		{
			AnwPlugins::hook('check_valid_pagename', $sPageName);
		}
		catch(AnwPluginInterruptionException $e)
		{
			return false;
		}		
		return true;
	}
	
	function isActionAllowed($sAction)
	{
		try 
		{
			$this->checkActionAllowed($sAction);
			return true;
		}
		catch(AnwAclException $e)
		{
			return false;
		}
	}
	
	/**
	 * Special case for global actions which also require with the "view" right on a specific page.
	 */
	function isGlobalAndViewActionAllowed($sAction)
	{
		try 
		{
			$this->checkGlobalAndViewActionAllowed($sAction);
			return true;
		}
		catch(AnwAclException $e)
		{
			return false;
		}
	}
	
	function checkActionAllowed($sAction, $bSkipTranslatedPercentPermission=false)
	{
		if (!$bSkipTranslatedPercentPermission)
		{
			if ($sAction == 'view')
			{
				//check min translated percent and throw a special exception for distinction
				if (!$this->isActionAllowed('translate') && $this->getTranslatedPercent() < AnwComponent::globalCfgViewUntranslatedMinpercent())
				{
					throw new AnwAclMinTranslatedPercentException($this->getTranslatedPercent());
				}
			}
		}
		
		if (!AnwCurrentSession::isActionAllowed($this->getName(), $sAction, $this->getLang()))
		{
			throw new AnwAclException("permission denied on page action: ".$sAction);
		}
	}
	
	/**
	 * Special case for global actions which also require with the "view" right on a specific page.
	 */
	function checkGlobalAndViewActionAllowed($sGlobalAction)
	{
		// check global action allowed
		if (!AnwCurrentSession::isActionGlobalAllowed($sGlobalAction))
		{
			throw new AnwAclException("permission denied on global action: ".$sGlobalAction);
		}
		
		// check view action allowed - skip translated percent permission
		$this->checkActionAllowed('view', true);
	}
	
	/**
	 * Warning: for performances issues, we don't check that session has PHP permissions.
	 * So this function should *only* be used for linking to actions, not to really check permission.
	 * If people doesn't have PHP permission, he will get an error message after clicking on edit
	 */
	/*function getActionsAllowed()
	{
		$asAllActions = array("view","edit","translate","newtranslation","history","rename","changelang","delete");
		$asAllowedActions = array();
		foreach ($asAllActions as $sAction)
		{
			if ($this->isActionAllowed($sAction))
			{
				//if ( ! ($sAction == "edit" && $this->hasPhpCode() && !AnwCurrentSession::getUser()->isPhpEditionAllowed()))
				//{
					$asAllowedActions[] = array (
						'action' => $sAction,
						'link' => AnwUtils::link($this, $sAction), 
						'translation' => AnwComponent::g_("action_".$sAction)
					);
				//}
			}
		}
		return $asAllowedActions;
	}*/
	
	function hasPhpCode()
	{
		return AnwUtils::contentHasPhpCode($this->getContent()->toXmlString());
	}
	
	function hasJsCode()
	{
		return AnwUtils::contentHasJsCode($this->getContent()->toXmlString());
	}
	
	function isTranslated()
	{
		//$this->getContent(); //make sure to load bTranslated attribute
		return $this->bTranslated;
	}
	
	function getTranslatedPercent()
	{
		//$this->getContent(); //make sure to load nTranslatedPercent attribute
		return $this->nTranslatedPercent;
	}
	
	function getCreationTime()
	{
		return $this->nCreationTime;
	}
	
	function getOutgoingLinks()
	{
		$sContent = $this->getContent()->toXmlString();
		return self::getOutgoingLinksFromContent($sContent);
	}
	
	//used by class_output
	static function getOutgoingLinksFromContent($sContent)
	{
		$aoOutgoingLinks = array();
		
		//search links
		foreach (self::$asREGEXP_LINKS as $sRegexp)
		{
			preg_match_all($sRegexp, $sContent, $asMatches);
			foreach ($asMatches[2] as $sLink)
			{
				//only keep valid pagenames, and the base of the link, not args or anchor
				list($sOutgoingLink, $oPageTarget) = self::doGetValidOutgoingLink($sLink);
				if ($sOutgoingLink && $oPageTarget)
				{
					$aoOutgoingLinks[$sOutgoingLink] = $oPageTarget;
				}
			}
		}
		
		//$asOutgoingLinks = array_unique($asOutgoingLinks);
		return $aoOutgoingLinks;
	}
	
	/**
	 * @param $sLink link such as /en/page?param1=value1#anchor
	 * @return array(<base link: en/page>, <params: ?param1=value1#anchor>)
	 */
	private static function explodeLinkPage($sLink) {
		$asArgDelimiters = array('?', '#');
		
		if (substr($sLink, 0, 1) == '/') $sLink = substr($sLink, 1);
		
		// search for $nPosArgs as starting indice for args (if any)
		$nPosArgs = false;
		foreach ($asArgDelimiters as $sArgDelimiter) {
			$nPosCandidate = strpos($sLink, $sArgDelimiter);
			if ($nPosCandidate !== false) {
				if ($nPosArgs === false || $nPosCandidate < $nPosArgs) {
					$nPosArgs = $nPosCandidate;
				}					
			}				
		}
		
		if ($nPosArgs !== false) {
			// we have args in da link
			$sLinkBase = substr($sLink, 0, $nPosArgs);
			$sLinkArgs = substr($sLink, $nPosArgs);
		}
		else {
			$sLinkBase = $sLink;
			$sLinkArgs = "";
		}		
		return array($sLinkBase, $sLinkArgs);
	}
	
	private static function doGetValidOutgoingLink($sLinkOriginal)
	{
		$sLink = $sLinkOriginal;
		if (!strstr($sLink, '://') && substr($sLink,0,1) != '#' && substr($sLink,0,1) != '{') //skip {$p.myurl}
		{
			list($sLinkBase, $sLinkArgs) = self::explodeLinkPage($sLink);
			
			if (self::isValidPageName($sLinkBase))
			{
				$oPageTarget = AnwPageByName::getByNameOrSecondChance($sLinkBase);
				if ($oPageTarget) {
					self::debug("outgoingLink valid: ".$sLinkOriginal." -> ".$oPageTarget->getName()." (".$sLinkArgs.")");
					return array($sLinkOriginal, $oPageTarget);
				}
			}
		}
		self::debug("outgoingLink not valid: ".$sLinkOriginal);
		return array(false, false);
	}
	
	function pubcall($sArg)
	{
		switch($sArg)
		{
			case self::PUB_MYNAME:
				return $this->getName();
				break;
			
			case self::PUB_MYURL:
				return AnwUtils::link($this->getName());
				break;
			
			case self::PUB_MYTIME:
				return $this->getTime();
				break;
			
			default:
				//is it an access to an array element?
				$sRegexp = '!^(.+)\[(.+)\]$!si';
				if (preg_match($sRegexp, $sArg, $asMatches))
				{
					$sItemArray = $asMatches[1];
					$mArrayIndice = $asMatches[2];
					$mArrayValue = $this->getPageGroup()->getContentClass()->pubcall($sItemArray, $this->getContent());
					if (is_array($mArrayValue) && isset($mArrayValue[$mArrayIndice]))
					{
						return $mArrayValue[$mArrayIndice];
					}
					else
					{
						self::debug("pubCall: array indice ".$mArrayIndice." not found");
					}
				}
				
				return $this->getPageGroup()->getContentClass()->pubcall($sArg, $this->getContent(), $this);
				break;
		}		
	}
	
		
	//------------------------------------------------
	// STATIC VALIDATION
	//------------------------------------------------
	
	function isValidContent($sContent)
	{
		if (trim($sContent) == "" || strstr($sContent,'<translate><translate>'))
		{
			return false;
		}
		AnwDebug::startBench("isValidContent");
		
		//prepare for validation test
		try
		{
			AnwXml::xmlIsValid($sContentTmp);
			$bTest = true;
		}
		catch(AnwUnexpectedException $e)
		{
			$bTest = false;
		}
		
		if ($this->bExists) //TODO
		{
			try
			{
				$this->getPageGroup()->getContentClass()->testContent($sContent);
			}
			catch(AnwInvalidContentException $e)
			{
				$bTest = false;
			}
		}
		
		AnwDebug::stopBench("isValidContent");
		return $bTest;
	}
	
	protected function _setContent($oContent, $oSimilarContentForCheck, $bSecurityChecks=true)
	{
		if (ANWIKI_DEVEL)
		{
			self::debug("_setContent: ".htmlentities($oContent->toXmlString()));
		}
		
		if (!(is_int($oSimilarContentForCheck) && $oSimilarContentForCheck == -1))
		{
			if (ANWIKI_DEVEL)
			{
				self::debug("_setContent: similar was: ".htmlentities($oSimilarContentForCheck->toXmlString()));
			}
			self::checkSimilarContents(array($oContent,$oSimilarContentForCheck));
		}
		
		if ($bSecurityChecks)
		{
			//check user permissions once again
			$sContentAsString = $oContent->toXmlString();
			//check PHP code edition
			if ( AnwUtils::contentHasPhpCode($sContentAsString) )
			{
				AnwCurrentSession::getUser()->checkPhpEditionAllowed();
			}
			
			//check unsafe code edition
			if ( AnwUtils::contentHasJsCode($sContentAsString) )
			{
				AnwCurrentSession::getUser()->checkJsEditionAllowed();
			}
		}
		
		$this->oPreviousContent = $this->oContent; //may be null		
		$this->oContent = $oContent;
		$this->touchInfosContent();
	}
	
	function checkPageGroupSynchronized()
	{
		$aoPageTranslations = $this->getPageGroup()->getPages($this);
		$oContent = $this->getContent();
		foreach ($aoPageTranslations as $oPageTranslation)
		{
			//AnwDebug::log("checkPageGroupSynchronized : ".$oPageTranslation->getName());
			self::checkSimilarContents(array($oContent,$oPageTranslation->getContent()));
			//AnwDebug::log("checkPageGroupSynchronized : ".$oPageTranslation->getName()." ok");
		}
	}
	
	static function checkSimilarContents($aoContents, $bTestAttributesValues=true)
	{
		$oContentReference = array_pop($aoContents);
		
		foreach ($aoContents as $oComparedContent)
		{
			self::doCheckSimilarContent($oContentReference, $oComparedContent, $bTestAttributesValues);
		}
	}
	
	private static function doCheckSimilarContent($oContent1, $oContent2, $bTestAttributesValues=true)
	{
		if (!$oContent1)
		{
			throw new AnwUnexpectedException("checkSimilarContent : oContent1 is null");
		}
		if (!$oContent2)
		{
			throw new AnwUnexpectedException("checkSimilarContent : oContent2 is null");
		}
		
		if ( !AnwXml::xmlSimilarStructure($oContent1->toXml(), $oContent2->toXml(), $bTestAttributesValues, true))
		{
			/*print '<hr/>';
			print 'Content1:'.htmlentities($oContent1->toXmlString());
			print '<br/>***<br/>';
			print 'Content2:'.htmlentities($oContent2->toXmlString());*/
			throw new AnwUnexpectedException("Content desynchronization detected on checkSimilarContent");
		}
	}
	
	
	//------------------------------------------------
	// STORAGE
	//------------------------------------------------
	
	static function rebuildPage($nPageId, $sPageName, $oPageGroup, $sPageLang, $nPageTime, $nChangeId, $sPageContent, $bPageTranslated, $nPageTranslatedPercent, $nCreationTime, $bArchive, $bActiveExists)
	{
		$oPage = new AnwPageById($nPageId);
		
		if (!self::isValidPageName($sPageName))
		{
			throw new AnwBadPageNameException();
		}
		$oPage->sName = $sPageName;
		$oPage->oPageGroup = $oPageGroup;
		
		if (!Anwi18n::isValidLang($sPageLang))
		{
			throw new AnwBadLangException();
		}
		$oPage->sLang = $sPageLang;
		
		$oPage->nTime = $nPageTime;
		$oPage->nChangeId = $nChangeId;
		$oPage->bIsArchive = $bArchive;
		
		/*
		 * TODO This check is commented out due to performances issues...
		 * 
		if (!self::isValidContent($sPageContent))
		{
			throw new AnwBadContentException();
		}
		*/
		
		$oPage->sContentFromRebuild = $sPageContent;
		
		$oPage->bTranslated = $bPageTranslated;
		$oPage->nTranslatedPercent = $nPageTranslatedPercent;
		$oPage->nCreationTime = $nCreationTime;
		$oPage->bExists = true;
		$oPage->bActiveExists = $bActiveExists;
		$oPage->bInfoLoaded = true;
		
		//do some initializations required for __sleep()
		$oPage->getCachedOutputKey();
		$oPage->getCachedOutputExpiry();
		
		return $oPage;
	}
	
	function rebuildPage_setGroup($oPageGroup)
	{
		$this->oPageGroup = $oPageGroup;
	}
	
	private function loadContentFromRebuild()
	{
		//if (!$this->sContentFromRebuild) throw new AnwUnexpectedException("sContentFromRebuild null");
		self::debug("loadContentFromRebuild");
		$this->oContent = $this->getPageGroup()->getContentClass()->rebuildContentFromXml($this->sContentFromRebuild);
	}
	
	protected function doLoadInfo($oPage)
	{
		//page exists, update it's attributes
		//do some tests to not override possibly updated attributes...
		$this->bExists = true;
		
		$this->oPageGroup = $oPage->getPageGroup();
		$this->sLang = $oPage->getLang();
		
		if (!$this->nTime)
		{
			$this->nTime = $oPage->getTime();
		}		
		if (!$this->nChangeId)
		{
			$this->nChangeId = $oPage->getChangeId();
		}
		
		if (!$this->oContent)
		{
			$this->sContentFromRebuild = $oPage->sContentFromRebuild;
			//$this->oContent = $oPage->getContent();
			$this->bTranslated = $oPage->isTranslated();
			$this->nTranslatedPercent = $oPage->getTranslatedPercent();
			$this->nCreationTime = $oPage->getCreationTime();
		}
		
		$this->sCachedOutputKey = $oPage->sCachedOutputKey;
		$this->nCachedOutputExpiry = $oPage->nCachedOutputExpiry;
	}
	
	static function isAvailablePageName($sPageName)
	{
		if (!self::isValidPageName($sPageName))
		{
			throw new AnwBadPageNameException();
		}
		return AnwStorage::isAvailablePageName($sPageName);
	}
	
	function createNewTranslation($sPageName, $sPageLang, $sChangeComment="", $oContent=null, $nTime=null)
	{
		if ($this->getPageGroup()->hasLang($sPageLang))
		{
			throw new AnwLangExistsForPageGroupException();
		}
		
		//create untranslated content if none
		if (!$oContent)
		{
			$oContent = clone $this->getContent();
			$oContent->setUntranslated();
		}
		
		
		$oContentSimilar = clone $this->getContent();
		// don't check for php/js edition permissions for creating a new translation from existing content
		$bSecurityChecks = false;
		
		if (!$nTime) $nTime = time();
		$oNewPage = self::doCreatePage($this->getPageGroup(), $sPageName, $sPageLang, $oContent, $oContentSimilar, $bSecurityChecks, AnwChange::TYPE_PAGE_CREATION_TRANSLATION, $nTime, $sChangeComment);
		
		return $oNewPage;
	}
	
	static function createNewPage($oContentClass, $sPageName, $sPageLang, $sChangeComment="", $oContent=null, $nTime=null)
	{
		AnwStorage::transactionStart();
		try
		{
			//create pagegroup
			$oPageGroup = new AnwPageGroup();
			$oPageGroup->create($oContentClass);
			
			//create empty content if none
			if (!$oContent)
			{
				$oContent = new AnwContentPage($oContentClass);
			}
			$bSecurityChecks = true;
			
			if (!$nTime) $nTime = time();
			$oNewPage = self::doCreatePage($oPageGroup, $sPageName, $sPageLang, $oContent, -1, $bSecurityChecks, AnwChange::TYPE_PAGE_CREATION, $nTime, $sChangeComment);
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		return $oNewPage;
	}
	
	protected static function doCreatePage($oPageGroup, $sPageName, $sPageLang, $oContent, $oContentSimilar, $bSecurityChecks, $nChangeType, $nTime, $sChangeComment)
	{
		if (!self::isAvailablePageName($sPageName))
		{
			throw new AnwPageAlreadyExistsException();
		}
		
		$oNewPage = new AnwPageByName($sPageName);
		$oNewPage->setLang($sPageLang);		
		$oNewPage->setPageGroupBeforePageCreation($oPageGroup);		
		$oNewPage->_setContent($oContent, $oContentSimilar, $bSecurityChecks);
		$oNewPage->nCreationTime = $nTime;
		
		//update some attributes
		$oNewPage->touchInfos($nTime);
		
		//get new change ID
		$oChange = new AnwChange($nChangeType, $oNewPage, $nTime, $sChangeComment);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			AnwStorage::createPage($oNewPage, $oChange, '_storage_cbk_create'); //set id
			
			//notify change
			$oNewPage->notifyChanged();
			
			AnwStorage::transactionCommit();
			
			// wait for commit success before notifying the pagegroup
			$oNewPage->setPageGroupAfterPageCreation($oPageGroup);
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
		return $oNewPage;
	}
	
	function restoreArchive($nTime=null, $sChangeComment="")
	{
		if (!$this->exists())
		{
			throw new AnwUnexpectedException("Can't restore an unexisting page");
		}
		if (!$this->isArchive())
		{
			throw new AnwUnexpectedException("Can't restore a non archived page");
		}
		
		if (!$nTime) $nTime = time();
		
		//get new change ID
		$nChangeType = AnwChange::TYPE_PAGE_REVERT;
		$sChangeInfo = $this->getTime(); //before touchInfos!
		$oChange = new AnwChange($nChangeType, $this, $nTime, $sChangeComment, $sChangeInfo);
		
		//update some attributes
		$this->touchInfos($nTime);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			AnwStorage::restoreArchive($this, $oChange, '_storage_cbk_restoreArchive'); //set non-archive status
			
			//notify change
			$this->notifyChanged();
				
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
	/**
	 * @throws AnwUnexpectedException
	 */
	protected function doUpdate($nChangeType, $sChangeComment="", $nTime=null, $sChangeInfo="", $bClearCachedOutputForPageGroupsLinking=true)
	{
		if ($this->isArchive())
		{
			throw new AnwUnexpectedException("An archive can't be updated");
		}
		if (!$this->exists())
		{
			throw new AnwUnexpectedException("Can't update an unexisting page");
		}
		
		if (!$nTime) $nTime = time();
		
		//update some attributes
		$this->touchInfos($nTime);
		
		//get new change ID
		$oChange = new AnwChange($nChangeType, $this, $nTime, $sChangeComment, $sChangeInfo);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			$this->getPageGroup()->update(); //update pagegroup
			AnwStorage::updatePage($this, $oChange);
			
			//notify change
			$this->notifyChanged($bClearCachedOutputForPageGroupsLinking);
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
	static function getLastPageRevision($nPageId)
	{
		return AnwStorage::getLastPageRevision($nPageId);
	}
	
	static function getPageByChangeId($nPageId, $nChangeId)
	{
		//is it active page?
		$oPage = new AnwPageById($nPageId);
		if ($oPage->exists() && $oPage->getChangeId()==$nChangeId)
		{
			return $oPage;
		}
		
		//is it an archive?
		$oPage = new AnwPageById($nPageId, $nChangeId);
		if ($oPage->exists() && $oPage->getChangeId()==$nChangeId)
		{
			return $oPage;
		}
		
		throw new AnwPageNotFoundException();
	}
	
	function rename($sName, $bUpdateLinks, $sComment="", $nTime=null)
	{
		if ($this->isArchive())
		{
			throw new AnwUnexpectedException("An archive can't be renamed");
		}
		if (!$this->exists())
		{
			throw new AnwUnexpectedException("A non existing page can't be renamed");
		}				
		if (!self::isValidPageName($sName))
		{
			throw new AnwBadPageNameException();
		}
		
		if (!$nTime) $nTime = time();
		
		//update some attributes
		$this->touchInfos($nTime);
		
		$sOldName = $this->getName();
		$this->sName = $sName;
		
		//get new change ID
		$nChangeType = AnwChange::TYPE_PAGE_RENAME;
		$sChangeInfo = $sOldName." -> ".$sName;
		$oChange = new AnwChange($nChangeType, $this, $nTime, $sComment, $sChangeInfo, $sOldName);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			AnwStorage::renamePage($this, $oChange, $sOldName);
			
			//notify change
			$this->notifyChanged();
			
			if ($bUpdateLinks)
			{
				//update links to this page
				$aoPageGroups = $this->getPageGroup()->getPageGroupsLinking();
				foreach ($aoPageGroups as $oPageGroup)
				{
					//edit the link from the preferred page, then deploy this change to other translations
					$oPage = $oPageGroup->getPreferedPage();
					
					$sNewContent = $oPage->getContent()->toXmlString();
					// don't generate hreflang attribute in the content now, it will be generated when rendering output
					$sNewContent = self::updateOutgoingLinkInContent($sOldName, $sName, $sNewContent, false);
					$oNewContent = $oPage->getPageGroup()->getContentClass()->rebuildContentFromXml($sNewContent);
					
					$sChangeInfos = $sOldName." -> ".$sName;
					$oPage->saveEditAndDeploy($oNewContent, AnwChange::TYPE_PAGE_UPDATELINKS, "", $sChangeInfos, $nTime);
				}
			}
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
/**
	 * If $sLinkLang is provided, "hreflang" attribute will be rendered.
	 */
	//used by class_output
	static function updateOutgoingLinkInContent($sOldLink, $sNewLinkBase, $sContent, $sLinkLang=false)
	{
		list($sOldLinkBase, $null) = self::explodeLinkPage($sOldLink);
		//TODO find another way to transmit this to the callback
		self::$sUpdateOutgoingLink_oldLinkBase = $sOldLinkBase;
		self::$sUpdateOutgoingLink_newLinkBase = $sNewLinkBase;
		self::$sUpdateOutgoingLink_linkLang = $sLinkLang;
		
		// TODO find an optimized approach to do this for all links at once
		foreach (self::$asREGEXP_LINKS as $sRegexp)
		{
			$sContent = preg_replace_callback($sRegexp, array('self','updateOutgoingLink_cbk'), $sContent);
		}
		
		self::$sUpdateOutgoingLink_oldLinkBase = null;
		self::$sUpdateOutgoingLink_newLinkBase = null;
		
		return $sContent;
	}
	
	private static function updateOutgoingLink_cbk($amMatches)
	{
		$sReturn = $amMatches[0];
		
		// is it the link we are looking for?
		list($sCandidateLinkBase, $sCandidateLinkArgs) = self::explodeLinkPage($amMatches[2]);
		if ($sCandidateLinkBase == self::$sUpdateOutgoingLink_oldLinkBase)
		{
			// ok, replace the link but keep current args
			$sNewLinkFull = AnwUtils::appendLinkArgs(self::$sUpdateOutgoingLink_newLinkBase, $sCandidateLinkArgs);
			$sReturn = $amMatches[1] . $sNewLinkFull . $amMatches[3];
			if (self::$sUpdateOutgoingLink_linkLang)
			{
				$sReturn .= ' hreflang="' . self::$sUpdateOutgoingLink_linkLang . '"';
			}
			self::debug("updateOutgoingLink_cbk: ".$amMatches[0]." -> ".$sReturn);
		}
		return $sReturn;
	}
	
	function changeLang($sLang, $sComment="", $nTime=null)
	{
		if ($this->isArchive())
		{
			throw new AnwUnexpectedException("Can't change the lang of an archive");
		}
		if (!$this->exists())
		{
			throw new AnwUnexpectedException("A non existing page can't have it's lang changed");
		}				
		if (!Anwi18n::isValidLang($sLang))
		{
			throw new AnwBadLangException();
		}
		
		if (!$nTime) $nTime = time();
		$sOldLang = $this->sLang;
		
		
		if ($sLang != $sOldLang && $this->getPageGroup()->hasLang($sLang))
		{
			throw new AnwLangExistsForPageGroupException();
		}
		
		
		//update some attributes
		$this->touchInfos($nTime);
		$this->sLang = $sLang;
		
		//get new change ID
		$nChangeType = AnwChange::TYPE_PAGE_CHANGELANG;
		$sChangeInfo = $sOldLang." -> ".$sLang;
		$oChange = new AnwChange($nChangeType, $this, $nTime, $sComment, $sChangeInfo, "", $sOldLang);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			AnwStorage::changeLangPage($this, $oChange);
			
			//notify change
			$this->notifyChanged();
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
	function delete($nTime=null, $sChangeComment="")
	{
		if (!$nTime) $nTime = time();
		
		//update some attributes
		$this->touchInfos($nTime);
		
		//get new change ID
		$nChangeType = AnwChange::TYPE_PAGE_DELETION;
		$oChange = new AnwChange($nChangeType, $this, $nTime, $sChangeComment);
		
		AnwStorage::transactionStart();
		try
		{
			//save
			AnwStorage::deletePage($this, $oChange);
			
			//notify change
			$this->notifyChanged();
			
			AnwStorage::transactionCommit();
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
	private function notifyChanged($bClearCachedOutputForPageGroupsLinking=true)
	{
		if ($bClearCachedOutputForPageGroupsLinking)
		{
			//clear cachedoutput for linking pagegroups
			$aoPageGroups = $this->getPageGroup()->getPageGroupsLinking();
			foreach ($aoPageGroups as $oPageGroup)
			{
				$aoPages = $oPageGroup->getPages();
				foreach ($aoPages as $oPage)
				{
					AnwCache::clearCachedOutputFromPage($oPage);
				}
			}
		}
		
		//notify contentclass
		$this->getPageGroup()->getContentClass()->onChange($this, $this->oPreviousContent);
		
		//notify plugins
		AnwPlugins::hook('page_onchange', $this, $this->oPreviousContent);
		$sContentClassName = $this->getPageGroup()->getContentClass()->getName();
		AnwPlugins::hook('page_onchange_byclassname_'.$sContentClassName, $this, $this->oPreviousContent);
	}
	
	protected static function debug($sMessage)
	{
		return AnwDebug::log("(AnwPage)".$sMessage);
	}
	
	function toHtml($bUseCache=true, $sCacheKey="")
	{
		if ($bUseCache && AnwComponent::globalCfgCacheOutputEnabled())
		{
			try
			{
				$oOutputHtml = $this->getCachedOutputHtml($sCacheKey);
			}
			catch(AnwCacheNotFoundException $e)
			{
				$oOutputHtml = $this->getContent()->toHtml($this);				
				$oOutputHtml->putCachedOutputHtml($sCacheKey);
			}
		}
		else
		{
			$oOutputHtml = $this->getContent()->toHtml($this);
		}
		return $oOutputHtml;
	}
	
	function getCachedOutputHtml($sCacheKey)
	{
		return AnwCache_outputHtml::getCachedOutputHtml($this, $sCacheKey);
	}
	
	function toFeedItem()
	{
		//return $this->getPageGroup()->getContentClass()->toFeedItem($this->getContent(), $this);
		return $this->getContent()->toFeedItem($this);
	}
	
		
	//----
	
	function link($action='view')
	{
		$sSrcFlag = Anwi18n::srcFlag($this->getLang());
		if ($action)
		{
			$sLink = AnwUtils::link($this, $action);
			$HTML = "<a href=\"".AnwUtils::xQuote($sLink)."\" style=\"background:url('$sSrcFlag') no-repeat left;padding-left:18px;\">".AnwUtils::xText($this->getName())."</a>";
		}
		else
		{
			$HTML = "<span style=\"background:url('$sSrcFlag') no-repeat left;padding-left:18px;\">".AnwUtils::xText($this->getName())."</span>";
		}
		return $HTML;
	}
	
	function redirectIfTranslationAvailable($sLang)
	{
		if ($this->getLang() != $sLang)
		{
			$aoTranslations = $this->getPageGroup()->getPages();
			if (isset($aoTranslations[$sLang]))
			{
				//translation found, let's redirect
				$oGoodPage = $aoTranslations[$sLang];
				
				if ($oGoodPage->isActionAllowed('view'))
				{
					self::debug("redirectIfTranslationAvailable(".$sLang."): translation found");
					
					//save _POST
					AnwUtils::httpPostToSession();
					
					//save _GET
					$asParams = $_GET;
					unset($asParams[AnwActionPage::GET_PAGENAME]); //avoid loop
					
					//redirect
					$sLink = AnwUtils::link($oGoodPage, "view", $asParams);
					AnwUtils::redirect($sLink);
				}
				else
				{
					self::debug("redirectIfTranslationAvailable(".$sLang."): translation found but no permission");
				}
			}
			else
			{
				self::debug("redirectIfTranslationAvailable(".$sLang."): translation NOT found");
			}
		}
	}
	
	private function touchInfos($nTime)
	{
		//update some attributes
		$this->nTime = $nTime;
		//$this->touchInfosContent();
	}
	
	private function touchInfosContent()
	{
		$this->bTranslated = $this->getContent()->isTranslated();
		$this->nTranslatedPercent = $this->getContent()->getTranslatedPercent();
	}
	
	function _storage_cbk_create($nId)
	{
		$this->nId = $nId;
		$this->bExists = true;
		$this->bActiveExists = true;
	}
	
	function _storage_cbk_restoreArchive()
	{
		$this->bIsArchive = false;
		$this->bActiveExists = true;
	}
	
	function _change_cbk_setchange($oChange)
	{
		$this->oChange = $oChange;
	}
	
	
	
	//we cache these two informations coming from the contentclass, to avoid loading it only for getting these informations
	function getCachedOutputKey()
	{
		if ($this->sCachedOutputKey === null)
		{
			if ($this->getPageGroup()->getContentClass() instanceof AnwCachedOutputKeyDynamic)
			{
				//this is a dynamic key, we can't cache it
				return $this->getPageGroup()->getContentClass()->getCachedOutputKeyDynamic();
			}
			else
			{
				//no key, we can cache this information
				$this->sCachedOutputKey = "";
			}
		}
		return $this->sCachedOutputKey;
	}	
	function getCachedOutputExpiry()
	{
		if ($this->nCachedOutputExpiry === null)
		{
			$this->nCachedOutputExpiry = $this->getPageGroup()->getContentClass()->getCachedOutputExpiry();
		}
		return $this->nCachedOutputExpiry;
	}
}


//------------------------------------------------------------------------
//------------------------------------------------------------------------


class AnwPageById extends AnwPage
{
	function __construct($nId, $nChangeIdArchive=false)
	{
		$this->nId = $nId;
		parent::__construct($nChangeIdArchive);
	}
	
	function getName()
	{
		$this->loadInfo();
		return parent::getName();
	}
	
	function loadInfo()
	{
		if ($this->bInfoLoaded) return;

		try
		{
			$this->debug("Loading page info...".($this->bSkipLoadingTranslationsContent ? "(boost)" : "").($this->bSkipLoadingContent ? "(superboost)" : ""));
			if (!$this->isArchive())
			{
				$oPage = AnwStorage::getPage($this->nId, $this->bSkipLoadingTranslationsContent, $this->bSkipLoadingContent );
			}
			else
			{
				$oPage = AnwStorage::getPageArchive( $this->nId, $this->nChangeId, $this->bSkipLoadingTranslationsContent, $this->bSkipLoadingContent );
			}
			$this->sName = $oPage->getName();
						
			parent::doLoadInfo($oPage);
		}
		catch(AnwPageNotFoundException $e)
		{
			//page doesn't exist
			$this->bExists = false;
		}
		$this->bInfoLoaded = true;
	}
	
}


//------------------------------------------------------------------------
//------------------------------------------------------------------------


class AnwPageByName extends AnwPage
{
	function __construct($sName, $nChangeIdArchive=false)
	{
		if (!self::isValidPageName($sName))
		{
			throw new AnwBadPageNameException();
		}
		
		$this->sName = $sName;
		parent::__construct($nChangeIdArchive);
	}
	
	function getId()
	{
		$this->loadInfo();
		return parent::getId();
	}
	
	function loadInfo()
	{
		if ($this->bInfoLoaded) return;
		
		try
		{
			$this->debug("Loading page info...".($this->bSkipLoadingTranslationsContent ? "(boost)" : "").($this->bSkipLoadingContent ? "(superboost)" : ""));
			if (!$this->isArchive())
			{
				$oPage = AnwStorage::getPageByName( $this->sName, $this->bSkipLoadingTranslationsContent, $this->bSkipLoadingContent );
			}
			else
			{
				$oPage = AnwStorage::getPageArchiveByName( $this->sName, $this->nChangeId, $this->bSkipLoadingTranslationsContent, $this->bSkipLoadingContent );
			}
			$this->nId = $oPage->getId();
			
			parent::doLoadInfo($oPage);
		}
		catch(AnwPageNotFoundException $e)
		{
			//page doesn't exist
			$this->bExists = false;
		}
		$this->bInfoLoaded = true;
	}
	
	static function getByNameOrSecondChance($sPageName) {
		//try to find page by name
		$oPageTest = new AnwPageByName($sPageName);
		if ($oPageTest->exists())
		{
			return $oPageTest;
		}
		
		//if name not found, try second chance
		$oPageTest = $oPageTest->getSecondChance();
		if ($oPageTest) {
			return $oPageTest;
		}
		
		//page not found
		return false;
	}
	
	/**
	 * When a pageByName is not found, some plugins may offer a second chance to find the desired page.
	 * In example, the plugin shortpagename allows to use shortcut pattern "[pagename]" to retrieve "[lang]/[pagename]"
	 */
	function getSecondChance() {
		if ($this->exists()) {
			throw new AnwUnexpectedException("Cannot call secondChance on an existing page!");
		}
		$oPageTest = AnwPlugins::vhook('page_notfound_secondchance', null, $this->getName());
		if ($oPageTest) {
			return $oPageTest;
		}
		return false;
	}
}
/*
class AnwPageDraft
{
	private $oPage;
	private $nTime;
	private $oUser;
	private $sContent;
	private $sComment;
	
	function __construct($oPage, $nTime, $oUser, $sContent, $sComment)
	{
		if (!$oPage->isValidContent($sContent))
		{
			throw new AnwInvalidContentException;
		}
		if (!AnwChange::isValidComment($sComment))
		{
			throw new AnwBadCommentException();
		}
		$this->oPage = $oPage;
		$this->nTime = $nTime;
		$this->oUser = $oUser;
		$this->sContent = $sContent;
		$this->sComment = $sComment;
	}
	
	function getPage()
	{
		return $this->oPage;
	}
	
	function getTime()
	{
		return $this->nTime;
	}
	
	function getUser()
	{
		return $this->oUser;
	}
	
	function getComment()
	{
		return $this->sComment;
	}
	
	//-----
	
	function save()
	{
		AnwStorage::savePageDraft($this);
	}
	
	function delete()
	{
		AnwStorage::deletePageDraft($this);
	}
}*/

/**
 * Cache manager for outputHtml.
 */
class AnwCache_outputHtml extends AnwCache
{
	private static function filenameCachedOutputHtml($oPage, $sCacheKey)
	{
		return self::cachedirOutput($oPage).'anwoutputhtml'.($sCacheKey?'-'.md5($sCacheKey):'').'.php';
	}
	
	static function putCachedOutputHtml($sCacheKey, $oOutputHtml)
	{
		// make sure caching is enabled
		if ($oOutputHtml->isOutputCachingEnabled())
		{
			$oPage = $oOutputHtml->getPage();
			$sCacheKey .= $oPage->getCachedOutputKey();
			
			$sCacheFile = self::filenameCachedOutputHtml($oPage, $sCacheKey);
			self::debug("putting cachedOutputHtml : ".$sCacheFile." (".$oPage->getName().") (key: ".$sCacheKey.")"); 
			self::putCachedObject($sCacheFile, $oOutputHtml);
		}
		else
		{
			self::debug("caching for this cachedOutputHtml is disabled");
		}
	}
	
	static function getCachedOutputHtml($oPage, $sCacheKey)
	{
		$sCacheKey .= $oPage->getCachedOutputKey();
		
		$sCacheFile = self::filenameCachedOutputHtml($oPage, $sCacheKey);
		$nDelayExpiry = $oPage->getCachedOutputExpiry();
		$oOutputHtml = self::getCachedObject($sCacheFile, $nDelayExpiry);
		
		if (!$oOutputHtml instanceof AnwOutputHtml)
	 	{
	 		self::debug("cachedOutputHtml invalid : ".$sCacheFile." (".$oPage->getName().") (key: ".$sCacheKey.")");
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedOutputHtml found : ".$sCacheFile." (".$oPage->getName().") (key: ".$sCacheKey.")");
	 		$oOutputHtml->rebuildPage($oPage); //dynamically rebuild oPage (performances improvement)
	 	}
		return $oOutputHtml;
	}
}


?>