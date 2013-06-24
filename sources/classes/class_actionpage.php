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
 * An ActionPage is an action dealing with a specific page.
 * @package Anwiki
 * @version $Id: class_actionpage.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
abstract class AnwActionPage extends AnwAction
{
	private static $oPage;
	
	const GET_PAGENAME = 'p';	//$_GET[] parameter for pagename
	
	function preinit(){}
	protected final function initializeAction()
	{
		//boost performances/memory by default
		$this->getoPage()->setSkipLoadingTranslationsContent(true);
		
		//boost options can be overriden here
		$this->preinit();
		
		//check permissions
		if ($this->pageExists())
		{
			//always check 'view' permission
			try
			{
				$this->getoPage()->checkActionAllowed('view');
			}
			catch(AnwAclMinTranslatedPercentException $e)
			{
				//if page is available in session langage, redirect
				$this->getoPage()->redirectIfTranslationAvailable(AnwCurrentSession::getLang());
				
				//if page is available in default langage, redirect
				$this->getoPage()->redirectIfTranslationAvailable(self::globalCfgLangDefault());
				
				//else show error msg
				throw $e;
			}
		}
		if ($this->getName() != "view")
		{
			$this->checkActionAllowed();
		}
		
		if ($this->pageExists())
		{
			self::setActionLang($this->getoPage()->getLang());
		}
		
		//AJAX locks management
		if (AnwEnv::_GET("locktype"))
		{
			//respond to the lock request and die
			$this->doLockReply( AnwEnv::_GET("locktype") );
			exit;
		}
	}
	
	//overridable
	protected function checkActionAllowed()
	{
		$this->getoPage()->checkActionAllowed($this->getName());
	}
	
	static function getCurrentPageName()
	{
		if (!self::$oPage)
		{
			$sPageName = AnwEnv::_GET(self::GET_PAGENAME, self::globalCfgHomePage());
			
			//ignore ending slashes, to avoid confusion in pagenames
			if (substr($sPageName,-1,1) == '/')
			{
				$sPageName = substr($sPageName, 0, (strlen($sPageName)-1));
			}
		}
		else
		{
			$sPageName = self::$oPage->getName();
		}
		return $sPageName;
	}
	
	protected function getoPage()
	{
		if (!self::$oPage)
		{
			if (AnwEnv::_GET(self::GET_PAGENAME))
			{
				//read pagename from env
				$sPageName = self::getCurrentPageName();
				if ( AnwPage::isValidPageName(self::getCurrentPageName()) )
				{
					self::$oPage = new AnwPageByName($sPageName);
				}
				else
				{
					//warning, doing error404() here may lead to infinite recursion in some very special cases... that's why $oPage is set to the homepage here, to prevent loop.
					self::$oPage = new AnwPageByName(self::globalCfgHomePage());
					self::error404();
				}
			}
			else
			{
				//load homepage
				$sPageName = self::globalCfgHomePage();
				self::$oPage = new AnwPageByName($sPageName);
				
				//make sure to load homepage in the session language, if available
				try
				{
					$sWantedLang = AnwCurrentSession::getLang();
					if (self::$oPage->exists() && self::$oPage->getLang() != $sWantedLang)
					{
						$aoPages = self::$oPage->getPageGroup()->getPages();
						if (isset($aoPages[$sWantedLang]))
						{
							self::$oPage = $aoPages[$sWantedLang];
							self::debug("Homepage found in current session lang");
						}
						else
						{
							self::debug("Homepage NOT found in current session lang");
						}
					}
				}
				catch(AnwException $e){}
			}
		}
		return self::$oPage;
	}
		
	/**
	 * Get pageid from $_GET["p"].
	 * @throws AnwUnexpectedException
	 */
	
	/**
	 * @throws AnwBadPageNameException
	 */
	protected function getPageName()
	{
		return $this->getoPage()->getName();
	}
	
	
	protected function pageExists()
	{
		return $this->getoPage()->exists();
	}
	
	
	protected function checkPageExists()
	{
		if (!$this->pageExists())
		{
			AnwUtils::redirect(AnwUtils::link($this->getoPage()));
		}
	}
	
	/**
	 * Overloaded function to display a toolbar with page actions.
	 */
	public function output($bEmergencyError=false)
	{
		if (!$bEmergencyError)
		{
			if ($this->pageExists())
			{
				//action links
				$aoAllowedActions = self::getPageNavEntriesAllowed($this->getoPage());
				$nCountAllowedActions = count($aoAllowedActions);
				if ( $nCountAllowedActions > 1 || 
					($nCountAllowedActions == 1 && $aoAllowedActions[0]->getActionName() != 'view'))
				{
					$this->out = $this->tpl()->pageNav( $aoAllowedActions ).$this->out;
				}
			}
			$this->headJs( 'var g_pagename="'.str_replace('&amp;', '&', AnwUtils::escapeQuote($this->getoPage()->getName())).'";' );
			$this->headJs( 'var g_actionpageurl="'.str_replace('&amp;', '&', AnwUtils::escapeQuote(AnwUtils::link($this->getoPage(), $this->getName()))).'";' );
		}
		parent::output($bEmergencyError);
	}
	
	// ---------- locks management ----------
	
	protected function lockPageForEdition($nLockType)
	{
		return AnwStorage::lockPage($nLockType, $this->getoPage(), AnwCurrentSession::getSession());
	}
	
	protected function unlockPageForEdition()
	{
		AnwStorage::unlockPage($this->getoPage(), AnwCurrentSession::getSession());
	}
	
	protected function lockObserver($sLockType, $sLockForm, $aaObservers)
	{
		$this->head( $this->tpl()->headJsSrc(self::getGlobalUrlStaticDefault()."class_lock.js") );
		$this->headJsOnload( $this->tpl()->lockObserver_jsOnload($sLockType, $aaObservers) );
		$this->headJs( $this->tpl()->lockObserver_js($sLockForm) );
		$this->out .= $this->tpl()->lockObserver_body();
	}
	
	protected function doLockReply( $nLockType )
	{
		$sStatus = 'ERROR';
		$sMessage = '';
		$nRemainingTime = 0;
		
		try
		{
			switch($nLockType)
			{
				case AnwLock::TYPE_PAGEONLY:
					$sTranslation = "lockinfo_pageonly";
					break;
				case AnwLock::TYPE_PAGEGROUP:
					$sTranslation = "lockinfo_pagegroup";
					break;
				default:
					throw new AnwUnexpectedException("unknown lockactionid");
					break;
			}
			
			$oLock = $this->lockPageForEdition($nLockType);
			$nExpirationTime = ($oLock->getLockTimeLast() + self::globalCfgLocksExpiry());
			$nRemainingTime = $nExpirationTime - time();
			
			$sMessage = self::g_($sTranslation, array(
												"pagename" => '<i>'.$oLock->getLockPage()->getName().'</i>',
												"locktime" => Anwi18n::dateTime($oLock->getLockTime()),
												"remainingtime" => '<span id="lockinfo_remainingtime"></span>'
											)
			);
			
			if ($nRemainingTime <= 0)
			{
				throw new AnwLockException();
			}
			
			$sStatus = 'OK';
		}
		catch (AnwException $e)
		{
			$sStatus = 'ERROR';
		}
		
		$this->out = '<status>'.$sStatus.'</status>';
		$this->out .= '<message><![CDATA['.$sMessage.']]></message>';
		$this->out .= '<remainingtime>'.$nRemainingTime.'</remainingtime>';
		
		$this->printOutputAjax();
	}
	
	protected function createNavEntry($sTitle=null, $sImg=null)
	{
		if ($sTitle===null)
		{
			$sTitle = $this->t_("naventry");
		}
		if ($sImg===null)
		{
			$sImg = $this->getMyComponentUrlStaticDefault()."action_".$this->getName().".gif";
		}
		return new AnwPageNavEntry($this, $sTitle, $sImg);
	}
	
	protected function linkMe($asParams=array())
	{
		return AnwUtils::link($this->getPageName(), $this->getName(), $asParams=array());
	}
}
?>