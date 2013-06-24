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
 * An action is a feature of Anwiki.
 * @package Anwiki
 * @version $Id: class_action.php 360 2011-02-20 21:58:26Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwAction extends AnwComponent
{
	private static $oInstance;
	private static $sActionLang;	//TODO
	private static $aaActionsMapping = null;
	
	private $head = "";   //use head() to write
	private $headCss = "";   //use headCss() to write
	private $headJs = "";   //use headJs() to write
	private $headJsOnload = "";   //use headJsOnload() to write
	protected $out = "";
	protected $title = "";
	
	const GET_ACTIONNAME = 'a';
	
	final function __construct($sActionName, $bIsAddon)
	{
		$this->initComponent($sActionName, $bIsAddon);
	}
	
	function setAsCurrentAction()
	{
		if (self::$oInstance)
		{
			throw new AnwUnexpectedException("instance of AnwAction already created");
		}
		self::$oInstance = $this;
		self::$sActionLang = AnwCurrentSession::getLang();
		
		//export global JS variables
		$this->headJs( $this->getJsConfig() );
		
		//import some JS files
		$this->head( $this->tpl()->headJsSrc(self::getGlobalUrlStaticDefault()."lib/prototype.js") );
		$this->head( $this->tpl()->headJsSrc(self::getGlobalUrlStaticDefault()."lib/shortcut.js") );
		$this->head( $this->tpl()->headJsSrc(self::getGlobalUrlStaticDefault()."lib/scriptaculous/scriptaculous.js") );
		$this->head( $this->tpl()->headJsSrc(self::getGlobalUrlStaticDefault()."class_utils.js") );
		$this->head( $this->getJsSrcGlobal("global.js") );
		
		//import some CSS files
		$this->head($this->getCssSrcGlobal("styles.css"));
	}
		
	private static function getInstance()
	{
		return self::$oInstance;
	}
	
	// get parameter to request a captcha
	const GET_CAPTCHA = "captcha";
	
	function runAndOutput()
	{
		try
		{
			AnwDebug::startbench("action runAndOutput",true);
			
			//captcha request?
			if (AnwEnv::_GET(self::GET_CAPTCHA))
			{
				$this->doCaptcha();
				exit;
			}
			
			//make sure this action is enabled in configuration
			if (!in_array($this->getName(), AnwComponent::getEnabledComponents(AnwComponent::TYPE_ACTION)))
			{
				throw new AnwAclException("Trying to execute an action which is not enabled");
			}
			
			//is it an admin action?
			if ($this instanceof AnwAdminAction)
			{
				if (!AnwCurrentSession::getUser()->isAdminAllowed())
				{
					throw new AnwAclException("Admin is not allowed");
				}
			}
			
			//does action require https if available?
			if (self::isHttpsAction($this->getName()))
			{
				//do we need to redirect to https?
				if (self::globalCfgHttpsEnabled() && !AnwEnv::isHttps())
				{
					//redirect to https
					self::debug("Redirecting to https...");
					AnwUtils::httpPostToSession();					
					$asParams = $_GET;
					$sLink = AnwUtils::alink($this->getName(),$asParams);
					AnwUtils::redirect($sLink); //should automatically use https
				}
			}

			
			if (AnwCurrentSession::needsReauth())
			{
				//reauth processing
				if (AnwEnv::_POST("reauth"))
				{
					self::debug("Processing reauth request...");
					try{
						//check password and reset reauth
						$this->doReauth( AnwEnv::_POST("reauth") );
						self::debug("Reauth request success!");
					}
					catch(AnwException $e){
						//reauth failed, show reauth form again
						self::debug("Reauth success failed.");
						$this->doReauthForm(); //post data is already in session
						exit;
					}
				}
				
				//must the user reauth for this action? - do this after reauth processing
				if ($this instanceof AnwHarmlessAction)
				{
					//ok, user is authorized to run action without reauthenticating
				}
				else
				{
					//user needs to reauthenticate
					$this->checkReauth();
				}
			}
			
			//restore POST if any in session
			AnwUtils::restoreHttpPostFromSession();
			
			$this->initializeAction();
						
			//run the action
			$this->init();
			$this->run();
			AnwDebug::stopbench("action runAndOutput");
			$this->output();
		}
		catch(AnwLockException $e)
		{
			$aoLocks = $e->getLocks();
			$asLockInfos = array();
			foreach ($aoLocks as $oLock)
			{
				$nLockType = $oLock->getLockType();
				switch($nLockType)
				{
					case AnwLock::TYPE_PAGEONLY: $sTranslation = "err_ex_lock_details_pageonly"; break;
					case AnwLock::TYPE_PAGEGROUP: $sTranslation = "err_ex_lock_details_pagegroup"; break;
					default: throw new AnwUnexpectedException("lock type unknown"); break;
				}
				$asLockInfos[] = self::g_($sTranslation,
							array(
								"user"=>'<b>'.AnwUtils::xText($oLock->getLockUser()->getDisplayName()).'</b>',
								"pagename"=>'<i>'.AnwUtils::xText($oLock->getLockPage()->getName()).'</i>',
								"timestart"=>Anwi18n::dateTime($oLock->getLockTime()),
								"timeseen"=>Anwi18n::dateTime($oLock->getLockTimeLast()),
								"timeexpire"=>Anwi18n::dateTime($oLock->getLockTimeLast()+self::globalCfgLocksExpiry())
							)
				);
			}
			$this->headJs($this->tpl()->errorLock_js());
			$this->out = $this->tpl()->errorLock($asLockInfos);
			$this->output();
		}
		catch(AnwException $e)
		{
			$nErrorNumber = false;
			
			if ($e instanceof AnwAclPhpEditionException)
			{
				$sTitle = self::g_("err_ex_acl_t");
				$sExplain = self::g_("err_ex_acl_php_p");
				$sImageSrc = AnwUtils::pathImg("warning.gif");
			}
			else if ($e instanceof AnwAclJsEditionException)
			{
				$sTitle = self::g_("err_ex_acl_t");
				$sExplain = self::g_("err_ex_acl_js_p");
				$sImageSrc = AnwUtils::pathImg("warning.gif");
			}
			else if ($e instanceof AnwAclMinTranslatedPercentException)
			{
				$sTitle = self::g_("err_ex_acl_t");
				$sExplain = self::g_("err_ex_acl_mintranslatedpercent_p", array('percent'=>$e->getTranslatedPercent()));
				$sImageSrc = AnwUtils::pathImg("warning.gif");
			}
			else if ($e instanceof AnwAclException)
			{
				$sTitle = self::g_("err_ex_acl_t");
				if (AnwCurrentSession::isLoggedIn()) $sExplain = self::g_("err_ex_acl_loggedin_p");
				else $sExplain = self::g_("err_ex_acl_loggedout_p");
				$sImageSrc = AnwUtils::pathImg("warning.gif");
			}
			else if ($e instanceof AnwBadCallException)
			{
				$sTitle = self::g_("err_ex_badcall_t");
				$sExplain = self::g_("err_ex_badcall_p");
				$sImageSrc = AnwUtils::pathImg("warning.gif");
			}
			else if ($e instanceof AnwDbConnectException)
			{
				$sTitle = self::g_("err_ex_dbconnect_t");
				$sExplain = self::g_("err_ex_dbconnect_p");
				$sImageSrc = AnwUtils::pathImg("error.gif");
				$nErrorNumber = AnwDebug::reportError($e);
			}
			else
			{
				$sTitle = self::g_("err_ex_unexpected_t");
				$sExplain = self::g_("err_ex_unexpected_p");
				$sImageSrc = AnwUtils::pathImg("error.gif");
				$nErrorNumber = AnwDebug::reportError($e);
			}
			$this->out = $this->tpl()->errorException($sTitle, $sExplain, $sImageSrc, $nErrorNumber);
			//self::output(); //not use $this to avoid potential errors if it's an ActionPage
			$this->output();
		}
	}
	
	protected static function setActionLang($sLang)
	{
		self::$sActionLang = $sLang;
	}
	
	static function getActionLang()
	{
		return self::$sActionLang;
	}
	
	protected function error($msg, $sTitle="", $sImgSrc="")
	{
		self::debug("error() : ".$msg);
		$this->out = $this->tpl()->error($msg, $sTitle="", $sImgSrc="");
		$this->output(/*true*/); //true is important here, so that we display error page as soon as possible, without loading new stuff (such as globalnav/sessionnav)
	}
	
	protected static function error404()
	{
		self::getInstance()->error(self::g_("err_404"));
	}
	
	protected function setTitle($title)
	{
		$this->title = $title;
	}	
	
	function redirectInfo($url, $title, $info="")
	{
		if (!$url || !AnwUtils::isSafeUrl($url)) $url=AnwUtils::link(self::globalCfgHomePage());
		
		$this->title = $title;		
		$this->headJsOnload("setTimeout(function(){window.location.href='".AnwUtils::escapeApostrophe($url)."';},2000);");
		
		//render head
		$this->renderHeadForOutput();
		
		$this->out = $this->tpl()->globalBodyRedirectInfo($url, $title, $info);
		
		$this->out = $this->tpl()->globalHtml(
			self::g_("local_html_lang", array(), self::getActionLang()),		//content lang
			self::g_("local_html_dir", array(), self::getActionLang()),
			$this->title, 
			$this->head,
			$this->out
		);
		
		$this->printOutput();
	}
	
	protected function renderHeadForOutput()
	{
		//set final page title
		$sTitleBefore = self::g_("local_title_before");
		if ($sTitleBefore) $sTitleBefore .= ' ';
		
		$sTitleAfter = self::g_("local_title_after");
		if ($sTitleAfter) $sTitleAfter = ' '.$sTitleAfter;
		
		$this->title = $sTitleBefore.$this->title.$sTitleAfter;
		
		//set some global JS
		$this->headJs( 'var g_actionurl="'.str_replace('&amp;', '&', AnwUtils::escapeQuote(AnwUtils::aLink($this->getName()))).'";' );
		
		//render CSS & Javascript
		if ($this->headCss) $this->headCss = $this->tpl()->headCss($this->headCss);
		if ($this->headJsOnload) $this->headJs .= $this->tpl()->headJsOnload($this->headJsOnload); //append to headJs
		if ($this->headJs) $this->headJs = $this->tpl()->headJs($this->headJs);
		
		//render head
		$this->head = $this->tpl()->globalHead($this->head,
			$this->headCss, 
			$this->headJs);	
	}
	
	function output($bEmergencyError=false)
	{
		AnwDebug::startbench("output",true);
		
		//render head
		$this->renderHeadForOutput();
		
		//global actions
		/*$asAllGlobalActions = array("lastchanges", "sitemap", "untranslated", "management");
		$asAvailableGlobalActions = array();
		foreach ($asAllGlobalActions as $sAction)
		{
			if (AnwCurrentSession::isActionGlobalAllowed($sAction))
			{
				$asAvailableGlobalActions[] = array(
					'action' => $sAction,
					'link' => AnwUtils::alink($sAction),
					'translation' => self::g_("action_".$sAction)
				);
			}
		}*/
		
		if (!$bEmergencyError)
		{
			//session nav
			if (AnwCurrentSession::isLoggedIn())
			{
				$sLinkProfile = AnwUsers::isDriverInternal() ? AnwUtils::alink("profile") : AnwUsers::getLinkProfile(AnwCurrentSession::getUser());
				$sLinkSettings = AnwUtils::aLink("settings");
				$sLinkLogout = AnwSessions::isDriverInternal() ? AnwUtils::alink("logout") : AnwSessions::getLogoutLink();
				
				$sessionnav = $this->tpl()->sessionNavLoggedin(
					AnwCurrentSession::getUser()->getDisplayName(),
					$sLinkProfile,
					$sLinkSettings,
					$sLinkLogout
				);
			}
			else
			{
				$sLinkSettings = AnwUtils::aLink("settings");
				$sLinkLogin = AnwSessions::isDriverInternal() ? AnwUtils::alink("login") : AnwSessions::getLoginLink();
				
				if (self::globalCfgUsersRegisterEnabled())
				{
					$sLinkRegister = AnwUsers::isDriverInternal() ? AnwUtils::alink("register") : AnwUsers::getRegisterLink();
				}
				else
				{
					$sLinkRegister = false;
				}
				
				$sessionnav = $this->tpl()->sessionNavGuest(
					$sLinkSettings,
					$sLinkLogin,
					$sLinkRegister
				);
			}
			
			$aoAllowedGlobalNavEntries = $this->getGlobalNavEntriesAllowed();
			if (count($aoAllowedGlobalNavEntries) > 0)
			{
				$globalnav = $this->tpl()->globalNav($aoAllowedGlobalNavEntries);
			}
			else
			{
				$globalnav = "";
			}
		}
		else
		{
			$sessionnav = "";
			$globalnav = "";
		}
		$this->out = $this->tpl()->globalBody(
			$sessionnav, 
			$globalnav, 
			$this->out
		);
		
		$this->out = $this->tpl()->globalHtml(
			self::g_("local_html_lang", array(), self::getActionLang()),		//content lang
			self::g_("local_html_dir", array(), self::getActionLang()),
			$this->title, 
			$this->head,
			$this->out
		);
		AnwDebug::stopbench("output");
		$this->printOutput();
	}
	
	static function getCurrentActionName()
	{
		return self::$oInstance->getName();
	}
	
	protected function printOutput()
	{
		header("Content-type: text/html; charset=UTF-8");
		
		/*AnwDebug::startbench("parseTranslations",true);
		$sOutput = Anwi18n::parseTranslations($this->out);
		AnwDebug::stopBench("parseTranslations");*/
		$sOutput = $this->out;
		
		//full execution time benchmark
		$sElapsedTime = AnwDebug::stopBench("GLOBAL");
		
		//show execution time ?
		if (self::globalCfgShowExecTime())
		{
			$fMemoryUsage = AnwDebug::getMemoryUsage();
			$sOutput = str_replace('%ANWEXECTIME%', ' • '.$sElapsedTime.' sec • '.$fMemoryUsage.' MB', $sOutput);
		}
		else
		{
			$sOutput = str_replace('%ANWEXECTIME%', '', $sOutput);
		}
		
		//show full debug ?
		if (AnwUtils::isViewDebugAuthorized())
		{
			$sLog = AnwDebug::getLog();
			if ($sLog) $sOutput = str_replace('</body>', '<div id="debug">'.$sLog.'</div></body>', $sOutput);
		}
		print $sOutput;
		exit();
	}
	
	protected function printOutputRaw()
	{
		header("Content-type: text/html; charset=UTF-8");
		//print Anwi18n::parseTranslations($this->out);
		print $this->out;
		exit();
	}
	
	protected function printOutputAjax()
	{
		header('Content-Type: application/xml');
		print '<?xml version="1.0" ?>';
		print '<anwajax>';
		//print Anwi18n::parseTranslations($this->out);
		print $this->out;
		print '</anwajax>';
		exit();
	}
	
	/*
	protected function printOutputXml($bPrintXmlHead=true)
	{
		header('Content-Type: application/xml');
		if ($bPrintXmlHead) print '<?xml version="1.0" ?>';
		print Anwi18n::parseTranslations($this->out);
		exit();
	}*/
	
	protected function printOutputDownload($sFileName)
	{
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=".$sFileName);
		//print Anwi18n::parseTranslations($this->out);
		print $this->out;
		exit();
	}
	
	protected function head($sHtml)
	{
		$this->head .= $sHtml;
	}
	
	protected function getHead()
	{
		return $this->head;
	}
	
	static function headCss($sStyles)
	{
		self::$oInstance->headCss .= "\n".$sStyles;
	}
	
	static function headJs($sJavaScript)
	{
		self::$oInstance->headJs .= "\n".$sJavaScript;
	}
	/*
	protected function headJs($sJavaScript)
	{
		$this->headJs .= "\n".$sJavaScript;
	}*/
	
	static function headJsOnload($sJavaScript)
	{
		self::$oInstance->headJsOnload .= "\n".$sJavaScript;
	}
	
	static function headEditContent()
	{
		self::$oInstance->head( self::$oInstance->getJsSrcGlobal("editcontent.js") );
		self::$oInstance->head( self::$oInstance->getCssSrcGlobal("editcontent.css") );
	}
	
	/*
	protected function headJsOnload($sJavaScript)
	{
		$this->headJsOnload .= "\n".$sJavaScript;
	}*/
	
	private function getJsConfig()
	{
		//TODO - better adaptation with the new config system
		//config items exported to JavaScript
		//*WARNING* these config parameters will be viewable by everyone!
		$asExportVars = array();
		$asExportVars["lock_renewrate"] = self::globalCfgLocksRenewRate();
		$asExportVars["lock_expirealert"] = self::globalCfgLocksAlert();
		$asExportVars["lock_countdowninterval"] = self::globalCfgLocksRefreshRate();
		
		$sReturn = "var g_anwcfg = new Array();\n";
		foreach ($asExportVars as $sExportVar => $mValue)
		{
			$sReturn .= "g_anwcfg[\"".$sExportVar."\"] = \"".addslashes($mValue)."\";\n";
		}
		return $sReturn;
	}
	
	//filter utils
	
	protected function filterLangs($asRequiredActionsAcls=array(), $bCheckAllByDefault=false)
	{
		$asAllLangs = self::globalCfgLangs();
		$asDisplayLangs = array();
		foreach ($asAllLangs as $i => $sLang)
		{
			//check ACLs
			$bAuthorized = true;			
			foreach ($asRequiredActionsAcls as $sAction)
			{
				if (!AnwCurrentSession::isActionAllowed(-1, $sAction, $sLang))
				{
					$bAuthorized = false; break;
				}
			}
			if (!$bAuthorized)
			{
				unset($asAllLangs[$i]);
			}
			else
			{
				if (AnwEnv::_GET("lg_".$sLang))
				{
					$asDisplayLangs[] = $sLang;
				}
			}
		}
		//always check at least the default language
		if (!$bCheckAllByDefault && count($asDisplayLangs) == 0) 
		{
			$asDisplayLangs[] = self::globalCfgLangDefault();
			//when sDisplayLangs is empty, all checkbox are be checked
		}
		return array($asAllLangs, $asDisplayLangs);
	}
	
	protected function filterContentClasses($asRequiredActionsAcls=array())
	{
		$asAllClasses = self::globalCfgModulesContentClasses();
		$asDisplayClasses = array();
		foreach ($asAllClasses as $sClass)
		{
			if (AnwEnv::_GET("cc_".$sClass))
			{
				$asDisplayClasses[] = $sClass;
			}
		}
		return array($asAllClasses, $asDisplayClasses);
	}
	
	
	
	// ---------- reauthenticate management ----------
	
	protected function checkReauth()
	{
		if (AnwCurrentSession::needsReauth())
		{
			AnwUtils::httpPostToSession();
			$this->doReauthForm();
			exit;
		}
	}
	
	private function doReauthForm()
	{
		self::debug("Showing reauth form.");
		$asParams = $_GET;
		$sFormAction = AnwUtils::alink($this->getName(),$asParams);
		
		$sDisplayName = AnwCurrentSession::getSession()->getUser()->getDisplayName();
		$this->out = $this->tpl()->reauthForm($sFormAction, $sDisplayName);
		$this->headJsOnload( $this->tpl()->reauthFormJs() );
		$this->output();
	}
	
	private function doReauth( $sReauthPassword )
	{
		self::debug("Captured reauth request, processing...");
		AnwCurrentSession::getUser()->authenticate($sReauthPassword); //throws exception
		
		//password is correct, reset the reauth timer
		AnwCurrentSession::resetReauth();
		
		self::debug("Reauth success.");
	}
	
	// ---------- captcha management ----------
	
	protected function doCaptcha()
	{
		//Generate a number and save it in session
		$sCaptcha = (string)rand(1000,9999);
		
		//replace "1" or "7" as it's difficult to make the difference
		for($i=0; $i<strlen($sCaptcha); $i++){
			if($sCaptcha[$i]=="1" || $sCaptcha[$i]=="7"){
				$sCaptcha[$i] = (string)rand(2,6);
			}
		}
		
		AnwCurrentSession::setCaptcha($sCaptcha);
		
		
		//captcha settings
		$nMaxAngle = 20;
		$nFontSizeMin = 13;
		$nFontSizeMax = 14;
		$height = 20;
		$width = 80;
		$nCharSpace = 17;
		$font = ANWPATH_LIB.'fonts/lazy_dog.ttf';
		$nNoisePxMin = 20;
		$nNoisePxMax=40;
		
		header("Content-type: image/png");
		
		//Create image
		$im = imagecreate ($width, $height);
		imagecolorallocate($im,255,255,255); //white background
		
		//write numbers
		$len=strlen($sCaptcha);
		$x=10;
		for($i=0;$i<$len;$i++){
			$nTmpAngle = (rand(0,1)==1)?rand(0,$nMaxAngle):rand(360-$nMaxAngle,360);	//random angle
			$nTmpSize = rand($nFontSizeMin,$nFontSizeMax);	//random size
			$nTmpTop = ($height*0.8)+rand(0,($height/8));	//random position from top
			$nTmpColor =  imagecolorallocate($im,rand(0,210),rand(0,210),rand(0,210)); //random color
			imagettftext($im,$nTmpSize,$nTmpAngle,$x,$nTmpTop,$nTmpColor,$font,$sCaptcha[$i]);
			$x += $nCharSpace;
		}
		
		//add noise
		$nbpx = rand($nNoisePxMin,$nNoisePxMax);
		for ($i=1;$i<$nbpx;$i++){
			$color = imagecolorallocate($im,rand(0,255),rand(0,255),rand(0,255));
			imagesetpixel($im,rand(0,$width-1),rand(0,$height-1),$color);
		}
		
		//Output image
		imagepng ($im); 
		imagedestroy($im);
		exit;
	}
	
	protected function checkCaptcha()
	{
		if (!AnwCurrentSession::testCaptcha())
		{
			throw new AnwBadCaptchaException();
		}
	}
	
	//overrided by ActionGlobal and ActionPage
	protected abstract function initializeAction();
	public function getNavEntry(){ return array(); }
	
	//overridable by each action
	function init(){}
	abstract function run();
	
	//------------- mapping management -------------
	
	protected static function getActionsMapping()
	{
		if (self::$aaActionsMapping===null)
		{
			$bCacheActionsEnabled = self::globalCfgCacheActionsEnabled();
			try
			{
				//load actions mapping
				if (!$bCacheActionsEnabled) throw new AnwCacheNotFoundException();
				self::$aaActionsMapping = AnwCache_actionsMapping::getCachedActionsMapping();
				self::debug("Loading actions mapping from cache");
			}
			catch(AnwException $e)
			{
				//generate new mapping
				self::debug("Generating actions mapping");
				self::$aaActionsMapping = self::generateActionsMapping();
				if ($bCacheActionsEnabled) AnwCache_actionsMapping::putCachedActionsMapping(self::$aaActionsMapping);
			}
		}
		//print_r(self::$aaActionsMapping);
		return self::$aaActionsMapping;	
	}
	
	const MAPPING_GLOBALNAV = "GLOBALNAV";
	const MAPPING_PAGENAV = "PAGENAV";
	const MAPPING_MANAGEMENTNAV = "MANAGEMENTNAV";
	
	const HTTPS_ACTIONS = "HTTPS_ACTIONS";
	const PUBLIC_ACTIONS = "PUBLIC_ACTIONS";
	const ADMIN_ACTIONS = "ADMIN_ACTIONS";
	const ALWAYS_ENABLED_ACTIONS = "ALWAYS_ENABLED_ACTIONS";
	const GRANT_ALL_USERS_BY_DEFAULT_ACTIONS = "GRANT_ALL_USERS_BY_DEFAULT_ACTIONS";
	
	const ACTIONS_PAGE = "ACTIONS_PAGE";
	
	private static function generateActionsMapping()
	{
		$aaActionsMapping = array(
			self::MAPPING_GLOBALNAV => array(), 
			self::MAPPING_PAGENAV => array(), 
			self::MAPPING_MANAGEMENTNAV => array(), 
			
			self::HTTPS_ACTIONS => array(), 
			self::PUBLIC_ACTIONS => array(), 
			self::ADMIN_ACTIONS => array(),
			self::ALWAYS_ENABLED_ACTIONS => array(),
			self::GRANT_ALL_USERS_BY_DEFAULT_ACTIONS => array(),
			
			self::ACTIONS_PAGE => array()
		);
		
		$asEnabledActions = AnwComponent::getAvailableComponents(AnwComponent::TYPE_ACTION);
		foreach ($asEnabledActions as $sEnabledAction)
		{
			try
			{
				//load action
				$sEnabledAction = strtolower($sEnabledAction);
				$oAction = AnwAction::loadComponent($sEnabledAction);
				
				//is it an AnwHttpsAction?
				if ($oAction instanceof AnwHttpsAction)
				{
					$aaActionsMapping[self::HTTPS_ACTIONS][] = $oAction->getName();
				}
				
				//is it an AnwPublicAction?
				if ($oAction instanceof AnwPublicAction)
				{
					$aaActionsMapping[self::PUBLIC_ACTIONS][] = $oAction->getName();
				}
				
				//is it an AnwAdminAction?
				if ($oAction instanceof AnwAdminAction)
				{
					$aaActionsMapping[self::ADMIN_ACTIONS][] = $oAction->getName();
				}				
				
				//is it an AnwAlwaysEnabledAction?
				if ($oAction instanceof AnwAlwaysEnabledAction)
				{
					$aaActionsMapping[self::ALWAYS_ENABLED_ACTIONS][] = $oAction->getName();
				}
				
				//is it an AnwGrantAllUsersByDefaultAction?
				if ($oAction instanceof AnwGrantAllUsersByDefaultAction)
				{
					$aaActionsMapping[self::GRANT_ALL_USERS_BY_DEFAULT_ACTIONS][] = $oAction->getName();
				}
				
				//is it an AnwActionPage?
				if ($oAction instanceof AnwActionPage)
				{
					$aaActionsMapping[self::ACTIONS_PAGE][] = $oAction->getName();
				}
				
				$mGlobalNavEntries = $oAction->getNavEntry();
				$aaActionsMapping = self::updateNavMapping($mGlobalNavEntries, $aaActionsMapping);
			}
			catch(AnwException $e)
			{
				AnwDebug::reportError($e);
			}
		}
		return $aaActionsMapping;
	}
	
	private static function updateNavMapping($aoEntries, $aaActionsMapping)
	{
		if (!is_array($aoEntries)) $aoEntries = array($aoEntries);
		foreach ($aoEntries as $oEntry)
		{
			if ($oEntry instanceof AnwPageNavEntry)
			{
				$aaActionsMapping[self::MAPPING_PAGENAV][] = $oEntry;
			}
			else if ($oEntry instanceof AnwManagementGlobalNavEntry) //test before globalNav! (inherited)
			{
				$aaActionsMapping[self::MAPPING_MANAGEMENTNAV][] = $oEntry;
			}
			else if ($oEntry instanceof AnwGlobalNavEntry)
			{
				$aaActionsMapping[self::MAPPING_GLOBALNAV][] = $oEntry;
			}
		}
		return $aaActionsMapping;
	}
	
	protected static function getManagementNavEntriesAllowed()
	{
		$aaMapping = self::getActionsMapping();
		
		$aoAllowedEntries = array();
		$aoManagementNavMapping = $aaMapping[self::MAPPING_MANAGEMENTNAV];
		foreach ($aoManagementNavMapping as $oManagementNavEntry)
		{
			if ( $oManagementNavEntry->isActionAllowed() )
			{
				$aoAllowedEntries[] = $oManagementNavEntry;
			}
		}
		return $aoAllowedEntries;
	}
	
	protected static function getGlobalNavEntriesAllowed()
	{
		$aaMapping = self::getActionsMapping();
		
		$aoAllowedEntries = array();
		$aoGlobalNavMapping = $aaMapping[self::MAPPING_GLOBALNAV];
		foreach ($aoGlobalNavMapping as $oGlobalNavEntry)
		{
			if ( $oGlobalNavEntry->isActionAllowed() )
			{
				$aoAllowedEntries[] = $oGlobalNavEntry;
			}
		}
		return $aoAllowedEntries;
	}
	
	protected static function getPageNavEntriesAllowed($oPage)
	{
		$aaMapping = self::getActionsMapping();
		
		$aoAllowedEntries = array();
		$aoPageNavMapping = $aaMapping[self::MAPPING_PAGENAV];
		foreach ($aoPageNavMapping as $oPageNavEntry)
		{
			if ( $oPageNavEntry->isActionPageAllowed($oPage) )
			{
				$aoAllowedEntries[] = $oPageNavEntry;
			}
		}
		return $aoAllowedEntries;
	}
	
	/**
	 * These actions requires special ACLs tests.
	 */
	public static function isMagicAclAction($sActionName)
	{
		return ( self::isAdminAction($sActionName) || self::isPublicAction($sActionName) );
	}
	
	public static function isHttpsAction($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::HTTPS_ACTIONS]));	
	}
	
	public static function isPublicAction($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::PUBLIC_ACTIONS]));
	}
	
	public static function isAdminAction($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::ADMIN_ACTIONS]));
	}
	
	public static function isAlwaysEnabledAction($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::ALWAYS_ENABLED_ACTIONS]));
	}
	
	public static function isGrantAllUsersByDefaultAction($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::GRANT_ALL_USERS_BY_DEFAULT_ACTIONS]));
	}
	
	public static function isActionPage($sActionName)
	{
		$aaMapping = self::getActionsMapping();
		return (in_array($sActionName, $aaMapping[self::ACTIONS_PAGE]));
	}
	
	
	public static function getAlwaysEnabledActions()
	{
		$aaMapping = self::getActionsMapping();
		return $aaMapping[self::ALWAYS_ENABLED_ACTIONS];
	}
	
	public static function getGrantAllUsersByDefaultActions()
	{
		$aaMapping = self::getActionsMapping();
		return $aaMapping[self::GRANT_ALL_USERS_BY_DEFAULT_ACTIONS];
	}
	
	
	protected static function debug($sMsg)
	{
		AnwDebug::log("(AnwAction)"."(".get_class(self::getInstance()).")".$sMsg);
	}
	
	function getComponentName()
	{
		return 'action_'.$this->getName();
	}
		
	static function discoverEnabledComponents()
	{
		$asUnionArray = array_merge(self::globalCfgModulesActions(), self::getAlwaysEnabledActions());
		return $asUnionArray;
	}
	
	static function getComponentsRootDir()
	{
		return ANWDIR_ACTIONS;
	}
	static function getComponentsDirsBegin()
	{
		return 'action_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return AnwComponent::TYPE_ACTION;
	}
	
	static function loadComponent($sName)
	{
		try
		{
			$oComponentAlreadyLoaded = self::getComponentGenericIfLoaded($sName, 'action');
			return $oComponentAlreadyLoaded;
		}
		catch(AnwUnexpectedException $e){}
		
		$sFile = 'action_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwAction%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oAction = new $classname($sName, $bIsAddon);
		self::registerComponentGenericLoaded($sName, 'action', $oAction);
		
		return $oAction;
	}
}

/**
 * Indicates that action needs to be accessed throught HTTPS when available.
 */
interface AnwHttpsAction
{
	
}

/**
 * Indicates that action can be accessed without having to reauthenticate even if needed.
 * Useful for read-only public actions (such as view, sitemap...).
 * Actions which doesn't implement it are considered as potentially harmful so we ask users to reauthenticate if they are logged in for long time
 */
interface AnwHarmlessAction
{
	
}

/**
 * Indicates that action can be accessed by everybody without checking any permission.
 * Useful for system actions (such as login, logout, register, settings...)
 */
interface AnwPublicAction extends AnwHarmlessAction
{
	
}

/**
 * Indicates that, when creating default ACLs (on fresh install or update process), all users will get ACL for executing this action.
 * Default ACL can be modified at any time.
 */
interface AnwGrantAllUsersByDefaultAction
{
	
}

/**
 * Indicates that action is only runnable by admins.
 */
interface AnwAdminAction
{
	
}

/**
 * Indicates that action can't be disabled.
 */
interface AnwAlwaysEnabledAction
{
	
}

class AnwCache_actionsMapping extends AnwCache
{
	
	private static function filenameCachedActionsMapping()
	{
		return ANWPATH_CACHESYSTEM.'actions_mapping.php';
	} 
	
	static function putCachedActionsMapping($aaActionsMapping)
	{
		$sCacheFile = self::filenameCachedActionsMapping();
		self::debug("putting cachedActionsMapping : ".$sCacheFile); 
		self::putCachedObject($sCacheFile, $aaActionsMapping);
	}
	
	static function getCachedActionsMapping()
	{
		$sCacheFile = self::filenameCachedActionsMapping();
		if (!file_exists($sCacheFile))
		{
			throw new AnwCacheNotFoundException();
		}
		
		//mapping must be newer than enabled-plugins-settings
		try
		{
			if ( filemtime($sCacheFile) < filemtime(AnwUtils::getFileOverride("global.cfg.php", AnwComponent::getGlobalComponentFullDir())))
			{
				self::debug("cachedActionsMapping obsoleted by settings");
				throw new AnwCacheNotFoundException();
			}
		}
		catch(AnwFileNotFoundException $e){} //no override config
		
		//mapping must be newer than each available action
		$asEnabledActions = AnwComponent::getAvailableComponents(AnwComponent::TYPE_ACTION);
		foreach ($asEnabledActions as $sEnabledAction)
		{
			$asActionsFilesLocations = array();
			
			$sActionFile = 'action_'.$sEnabledAction.'.php';
			$sActionDir = AnwAction::getComponentDir($sEnabledAction);
			list($sFileActionDefault, $null) = AnwUtils::getFileDefault($sActionFile, $sActionDir);
			$asActionsFilesLocations[] = $sFileActionDefault;
			
			try
			{
				$sFileActionOverride = AnwUtils::getFileOverride($sActionDir, $sActionDir);
				$asActionsFilesLocations[] = $sFileActionOverride;
			}
			catch(AnwFileNotFoundException $e){} //no override config
			
			foreach ($asActionsFilesLocations as $sActionFileLocation)
			{
				if ( file_exists($sActionFileLocation) && filemtime($sCacheFile) < filemtime($sActionFileLocation) )
				{
					self::debug("cachedActionsMapping obsoleted by action : ".$sEnabledAction);
					throw new AnwCacheNotFoundException();
				}
			}
		}
		
		//load it from cache
		$oObject = (array)self::getCachedObject($sCacheFile);
		if (!is_array($oObject))
	 	{
	 		self::debug("cachedActionsMapping invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedActionsMapping found : ".$sCacheFile);
	 	}
		return $oObject;
	}
	
}


?>