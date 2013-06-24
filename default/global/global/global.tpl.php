<?php

class AnwTemplateDefault_global
{
	private $oComponent;
	
	function __construct($oComponent)
	{
		$this->oComponent = $oComponent;
	}
	
	//-----------------------------------
	// output filtering
	//-----------------------------------
	
	protected function xText($sString)
	{
		return AnwUtils::xText($sString);
	}
	
	protected function xQuote($sString)
	{
		return AnwUtils::xQuote($sString);
	}
	
	protected function xTextareaValue($sString)
	{
		return AnwUtils::xTextareaValue($sString);
	}
	
	// useful for JS code inside attributes, such as onclick="window.location.href='..'"
	protected function escapeQuoteApostrophe($sString)
	{
		return AnwUtils::escapeQuoteApostrophe($sString);
	}
	
	// -----------------------------
	
	protected function t_($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		return $this->oComponent->t_($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	protected function g_($id, $asParams=array(), $sLangIfLocal=false)
	{
		return $this->oComponent->g_($id, $asParams, $sLangIfLocal);
	}
	
	protected function getComponent()
	{
		if (!$this->oComponent) throw new AnwUnexpectedException("no component for this tpl");
		return $this->oComponent;
	}
	
	function globalHtml($sHtmlLang, $sHtmlDir, $sTitle, $sHead, $sBody)
	{
		$HTML = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$this->xQuote($sHtmlLang)}" lang="{$this->xQuote($sHtmlLang)}" dir="{$this->xQuote($sHtmlDir)}">
<head>
	<title>{$this->xText($sTitle)}</title>
	$sHead
</head>

<body dir="{$this->xQuote($sHtmlDir)}">
	$sBody
</body>
</html>
EOF;
		return $HTML;
	}
	
	function globalHead($sHeadContent, $sHeadCss, $sHeadJs)
	{
		$HTML = <<<EOF

	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	$sHeadContent
	
	$sHeadCss
	
	$sHeadJs
EOF;
		return $HTML;
	}
	
	
	protected function getMenuPageName()
	{
		return "en/_include/menu"; //TODO?
	}
	protected function getMenuPage()
	{
		$oPage = null;
		try
		{
			$sMenuPageName = $this->getMenuPageName();
			$sActionLang = AnwAction::getActionLang();
			$oPage = AnwStorage::getPageByName($sMenuPageName, false, false, $sActionLang);
			
			// we don't check ACLs for better performances...
			
			// load translation if available
			if ($oPage->getLang() != $sActionLang)
			{
				$oPage = $oPage->getPageGroup()->getPreferedPage($sActionLang);
			}
		}
		catch (AnwException $e){}
		return $oPage;
	}
	protected function renderMenu($sRenderMode)
	{
		$HTML = "";
		
		$oPage = $this->getMenuPage();		
		if ($oPage)
		{
			//TODO quick fix with upper-level caching
			//for avoiding loading whole contentclasses framework when menu is already cached
			$sCacheKey = "globaltpl-".$sRenderMode."-currentpage-".AnwActionPage::getCurrentPageName();
			try
			{
				$oOutputHtml = $oPage->getCachedOutputHtml($sCacheKey);
			}
			catch(AnwCacheNotFoundException $e)
			{
				$oContentClass = $oPage->getPageGroup()->getContentClass();
				if ($oContentClass instanceof AnwIContentClassPageDefault_menu)
				{
					$oOutputHtml = $oContentClass->toHtmlCustom($oPage, $sRenderMode);
					
					// put in cache...
					$oOutputHtml->putCachedOutputHtml($sCacheKey);
				}
			}
			if ($oOutputHtml)
			{
				$HTML = $oOutputHtml->runBody();
			}
		}
		return $HTML;
	}
	
	function globalBody($sSessionNav, $sGlobalNav, $sContent)
	{
		$sAction = AnwAction::getCurrentActionName();
		
		$sHeader = $this->globalHeader( 
			$sSessionNav, 
			$sGlobalNav			
		);
		
		$sFooter = $this->globalFooter();
		$sBottom = $this->globalBottom();
		
		//
		// Global page layout
		//
		
		$HTML = <<<EOF

<div id="container" class="action-{$this->xQuote($sAction)}">
	
	
	<div id="header-container">
		<div class="header-open1">
		<div class="header-open2">
		<div class="header-open3">
		<div class="header-open4">
		<div class="header-open5">
		<div class="header-open6">
		<div class="header">

		$sHeader

		</div><!-- end header -->
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
	</div><!-- end header-container -->
	
	
	<div id="content-container">
		<div class="content-open1">
		<div class="content-open2">
		<div class="content-open3">
		<div class="content-open4">
		<div class="content-open5">
		<div class="content-open6">
		<div id="content">

		$sContent
		
		</div><!-- end content -->
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
	</div><!-- end content-container -->


	<div id="footer-container">
		<div class="footer-open1">
		<div class="footer-open2">
		<div class="footer-open3">
		<div class="footer-open4">
		<div class="footer">
		
			$sFooter
		
		</div><!-- end footer -->
		</div>
		</div>
		</div>
		</div>
	</div><!-- end footer-container -->
	
	
</div><!-- end container -->

<div id="bottom-container">
	<div class="bottom-open1">
	<div class="bottom-open2">
	<div class="bottom-open3">
	<div class="bottom-open4">
	<div class="bottom">
	
		$sBottom

	</div>
	</div>
	</div>
	</div>
	</div>
</div><!-- end bottom-container -->
EOF;
		return $HTML;
	}
	
	function globalBodyMinimal($sContent)
	{
		$sAction = AnwAction::getCurrentActionName();
		
		$sFooter = $this->globalFooter();
		$sBottom = $this->globalBottom();
		
		//
		// Global page layout
		//
		
		$HTML = <<<EOF

<div id="container" class="action-{$this->xQuote($sAction)}">
	
	
	<div id="content-container">
		<div class="content-open1">
		<div class="content-open2">
		<div class="content-open3">
		<div class="content-open4">
		<div class="content-open5">
		<div class="content-open6">
		<div id="content">

<div style="width:100%; height:50px"></div>
		$sContent
<div style="width:100%; height:50px"></div>
		
		</div><!-- end content -->
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
	</div><!-- end content-container -->

</div><!-- end container -->

<div id="bottom-container">
	<div class="bottom-open1">
	<div class="bottom-open2">
	<div class="bottom-open3">
	<div class="bottom-open4">
	<div class="bottom">
	
		$sBottom

	</div>
	</div>
	</div>
	</div>
	</div>
</div><!-- end bottom-container -->
EOF;
		return $HTML;
	}
	
	
	function globalBodyRedirectInfo($url, $title, $info)
	{
		$HTML = <<<EOF

<div class="redirection">
	<h1>{$this->xText($title)}</h1>
	{$this->xText($info)}<br/>
	<a href="{$this->xQuote($url)}">{$this->g_('redir_link')}</a>
</div>
EOF;
		return $HTML;
	}
	
	
	protected function globalHeader($sSessionNav, $sGlobalNav)
	{
		$sRenderMode = AnwIContentClassPageDefault_menu::RENDER_MODE_FULL_MENU;
		$sMenu = $this->renderMenu($sRenderMode);
		
		$sLinkHome = AnwUtils::alink("view");
		
		//
		// Header layout
		//
		
		$HTML = <<<EOF

		$sSessionNav<br/>
		$sGlobalNav
		
		<div id="logo">
		<h1><a href="{$this->xQuote($sLinkHome)}" title="{$this->g_('local_header_backhome')}">{$this->g_('local_header_backhome')}</a></h1>
		</div><!-- end logo -->
	
		<div id="topmenu">
		$sMenu
		</div><!-- topmenu -->
EOF;
		return $HTML;
	}
	
	
	function globalFooter()
	{
		$HTML = <<<EOF

		{$this->g_('footer')}
EOF;
		return $HTML;
	}
	
	function globalBottom()
	{
		$sLegend = self::g_("local_poweredby_legend",array('name'=>'Anwiki'));
		$HTML = <<<EOF

		{$this->g_('local_poweredby', array('link'=>'<a href="http://www.anwiki.com" title="'.$sLegend.'">Anwiki</a>'))} %ANWEXECTIME%
EOF;
		return $HTML;
	}
	
	
	function globalNav($aoGlobalNavEntries)
	{
		$HTML = <<<EOF

	<div id="globalnav" class="unexpanded">
		<ul>
EOF;

	foreach ($aoGlobalNavEntries as $oEntry)
	{
		$sLink = $oEntry->getLink();
		$sTitle = $oEntry->getTitle();
		//$sImg = $oEntry->getImg();
		$HTML .= <<<EOF

		<li><a href="{$this->xQuote($sLink)}" class="action">{$this->xText($sTitle)}</a></li>
EOF;
	}
		$HTML .= <<<EOF

		</ul>
	</div>
EOF;
		return $HTML;
	}
	
	function sessionNavLoggedin($displayname, $lnkprofile, $lnksettings, $lnklogout)
	{
		$sSessionFlag = Anwi18n::srcFlag(AnwCurrentSession::getLang());
		$HTML = <<<EOF

	<div id="sessionnav" class="sessionnav-loggedin">
		<a href="{$this->xQuote($lnkprofile)}">{$this->xText($displayname)}</a> 
		[<a href="{$this->xQuote($lnksettings)}" style="background-image:url('$sSessionFlag')" class="anwsettings">{$this->g_('topnav_settings')}</a> | 
		<a href="{$this->xQuote($lnklogout)}">{$this->g_('topnav_logout')}</a>]
	</div>
EOF;
		return $HTML;
	}
	
	function sessionNavGuest($lnksettings, $lnklogin, $lnkregister=false)
	{
		$sSessionFlag = Anwi18n::srcFlag(AnwCurrentSession::getLang());
		$HTML = <<<EOF

	<div id="sessionnav" class="sessionnav-guest">
		{$this->g_('user_displayname_anonymous')} 
		[ <a href="{$this->xQuote($lnksettings)}" style="background-image:url('$sSessionFlag')" class="anwsettings">{$this->g_('topnav_settings')}</a> | 
		<a href="{$this->xQuote($lnklogin)}">{$this->g_('topnav_login')}</a>
EOF;
		if ($lnkregister)
		{
			$HTML .= <<<EOF
			 | <a href="{$this->xQuote($lnkregister)}">{$this->g_('topnav_register')}</a>
EOF;
		}
		
		$HTML .= <<<EOF
		]
	</div>
EOF;
		return $HTML;
	}
	
	function pageNav($aoPageNavEntries)
	{
		$HTML = <<<EOF

	<div id="pageactions">
		<ul>
EOF;
		foreach ($aoPageNavEntries as $oEntry)
		{
			$sLink = $oEntry->getPageLink(AnwActionPage::getCurrentPageName());
			$sTitle = $oEntry->getTitle();
			$sImg = $oEntry->getImg();
			$HTML .= <<<EOF

		<li><a href="{$this->xQuote($sLink)}" class="pageaction" style="background-image:url('$sImg')">{$this->xText($sTitle)}</a></li>
EOF;
		}

		$HTML .= <<<EOF

		</ul>
	</div>

EOF;
		return $HTML;
	}
	
	function error($sInfo, $sTitle="", $sImgSrc="")
	{
		if (!$sImgSrc) $sImgSrc = AnwUtils::pathImg("warning.gif");
		if (!$sTitle) $sTitle = $this->g_('err_occurred');
		$HTML = <<<EOF

<div class="erroroccurred">
	<h1>{$this->xText($sTitle)}</h1>
	<img src="{$this->xQuote($sImgSrc)}" alt=""/>
	$sInfo
</div>
EOF;
		return $HTML;
	}
	
	function errorException($sTitle, $sExplain, $sImgSrc, $nErrorNumber=false)
	{
		$HTML = <<<EOF

<div class="errorexception">
	<h1>{$this->xText($sTitle)}</h1>
	<img src="{$this->xQuote($sImgSrc)}" alt=""/>
	<p>{$this->xText($sExplain)}</p>
EOF;
		if ($nErrorNumber)
		{
			$HTML .= <<<EOF

	<p>{$this->g_('err_ex_report', array('errornumber'=>"<b>$nErrorNumber</b>"))}</p>
EOF;
		}
		$HTML .= <<<EOF
	<p>{$this->g_('err_ex_sorry')}</p>
</div>
EOF;
		return $HTML;
	}
	
	function errorLock_js()
	{
		$JS = <<<EOF

setTimeout(function(){ location.reload(true);},60000);
EOF;
		return $JS;
	}
	
	function errorLock($asLockInfos)
	{
		$sImgSrc = AnwUtils::pathImg("clock.gif");
		$HTML = <<<EOF

<div class="errorlock">
	<h1>{$this->g_('err_ex_lock_t')}</h1>
	<img src="{$this->xQuote($sImgSrc)}" alt=""/>
	<p>{$this->g_('err_ex_lock_p1')}<br/><br/>{$this->g_('err_ex_lock_p2')}</p>
	<div class="errorlockdetails">
		<ul>
EOF;
		foreach ($asLockInfos as $sLockInfo)
		{
			$HTML .= <<<EOF

			<li>$sLockInfo</li>
EOF;
		}
		$HTML .= <<<EOF

		</ul>
	</div>
</div>
EOF;
		return $HTML;
	}
	
	function selectLang($langs=null, $selectedlang=null)
	{
		$HTML = '';
		if (!$langs) $langs=AnwComponent::globalCfgLangs();
		if (!$selectedlang) $selectedlang = AnwCurrentSession::getLang();
		foreach($langs as $lang)
		{
			$selected = $lang==$selectedlang ? ' selected="selected"' : "";
			$sSrcFlag = Anwi18n::srcFlag($lang);
			$HTML .= "<option value=\"".$this->xQuote($lang)."\"$selected style=\"background-image:url('$sSrcFlag');\">{$this->g_('lang_'.$lang)}</option>";
		}
		return $HTML;
	}
	
	function selectContentClass($aoContentClasses, $oSelectedContentClass=null)
	{
		$HTML = '';
		foreach($aoContentClasses as $oContentClass)
		{
			$selected = ($oSelectedContentClass && $oContentClass->getName()==$oSelectedContentClass->getName()) ? ' selected="selected"' : "";
			$sLabel = $oContentClass->getLabel();
			$sValue = $oContentClass->getName();
			$HTML .= "<option value=\"".$this->xQuote($sValue)."\"$selected>".$this->xText($sLabel)."</option>";
		}
		return $HTML;
	}
	
	function drawError($sMessage)
	{
		// no xText() for allowing html links
		$HTML = '<div class="error">'.$sMessage.'</div>';
		return $HTML;
	}
	
	function drawNotice($sMessage)
	{
		// no xText() for allowing html links
		$HTML = '<div class="notice">'.$sMessage.'</div>';
		return $HTML;
	}
	
	function drawNoticeIcon($sMessage)
	{
		$sSrcIcon = AnwUtils::pathImg("notice.gif");
		
		$HTML = <<<EOF
<a href="#" onclick="return false;" title="{$this->xQuote($sMessage)}"><img src="{$this->xQuote($sSrcIcon)}" alt="{$this->xQuote($sMessage)}" title="{$this->xQuote($sMessage)}" class="noticeicon"/></a>
EOF;
		return $HTML;
	}
	
	function headRss($sUrl, $sTitle="Feed")
	{
		$HTML = <<<EOF
		<link rel="alternate" type="application/rss+xml" title="{$this->xQuote($sTitle)}" href="{$this->xQuote($sUrl)}" />
EOF;
		return $HTML;
	}
	
	
	function filterStart($sFormAction)
	{
		$HTML = <<<EOF

	<form action="{$this->xQuote($sFormAction)}" method="get">
EOF;
		return $HTML;
	}
	
	function filterEnd()
	{
		$sInputName = AnwAction::GET_ACTIONNAME;
		$sAction = AnwAction::getCurrentActionName();
		$sFilterSubmitLabel = self::g_("in_filter");
		
		$HTML = <<<EOF

	<input type="hidden" name="{$this->xQuote($sInputName)}" value="{$this->xQuote($sAction)}"/>
	<input type="submit" value="{$this->xQuote($sFilterSubmitLabel)}"/>
	</form>
EOF;
		return $HTML;
	}
	
	function filterLangs($asAllLangs, $asDisplayLangs=array())
	{
		$HTML = <<<EOF

	<div class="anwfilter" id="filter_langs">
	<fieldset>
		<legend>{$this->g_('filter_langs')}
			<a href="#" onclick="AnwUtils.chkall('chkfilterlang',$('filter_langs')); return false;">{$this->g_('in_chkall')}</a> 
			<a href="#" onclick="AnwUtils.chknone('chkfilterlang',$('filter_langs')); return false;">{$this->g_('in_chknone')}</a>
		</legend>
EOF;
		foreach ($asAllLangs as $sLang)
		{
			$bSelected = (in_array($sLang, $asDisplayLangs) || count($asDisplayLangs) == 0);
			$sSelected = ( $bSelected ? ' checked="checked"' : '');
			$sLangName = Anwi18n::langName($sLang);
			$sInputName = "lg_".$sLang;
			$HTML .= <<<EOF

		<input type="checkbox" id="{$this->xQuote($sInputName)}" class="chkfilterlang" name="{$this->xQuote($sInputName)}" value="1"$sSelected/><label for="{$this->xQuote($sInputName)}" title="{$this->xQuote($sLangName)}">{$this->xText($sLang)}</label> 
EOF;
		}
		$HTML .= <<<EOF

	</fieldset>
	</div>
EOF;
		return $HTML;
	}
	
	function filterClass($asAllClasses, $asDisplayClasses=array())
	{
		$HTML = <<<EOF

	<div class="anwfilter" id="filter_classes">
	<fieldset>
		<legend>{$this->g_('filter_classes')}
			<a href="#" onclick="AnwUtils.chkall('chkfilterclass',$('filter_classes')); return false;">{$this->g_('in_chkall')}</a> 
			<a href="#" onclick="AnwUtils.chknone('chkfilterclass',$('filter_classes')); return false;">{$this->g_('in_chknone')}</a>
		</legend>
EOF;
		foreach ($asAllClasses as $sClass)
		{
			$bSelected = (in_array($sClass, $asDisplayClasses) || count($asDisplayClasses) == 0);
			$sSelected = ( $bSelected ? ' checked="checked"' : '');
			$sClassName = AnwContentClasses::getContentClass($sClass)->getLabel();
			$sInputName = "cc_".$sClass;
			$HTML .= <<<EOF

		<input type="checkbox" id="{$this->xQuote($sInputName)}" class="chkfilterclass" name="{$this->xQuote($sInputName)}"$sSelected/><label for="{$this->xQuote($sInputName)}">{$this->xText($sClassName)}</label> 
EOF;
		}
		$HTML .= <<<EOF

	</fieldset>
	</div>
EOF;
		return $HTML;
	}
		
	protected function errorList($error)
	{
		$HTML = "";
		if ($error)
		{
			$HTML .= '<div class="error">';
			if (is_array($error))
			{
				$HTML .= '<ul>';
				foreach ($error as $e)
				{
					$HTML .= '<li>'.$e.'</li>';
				}
				$HTML .= '</ul>';
			}
			else
			{
				$HTML .= $error;
			}
			$HTML .= '</div>';
		}
		return $HTML;
	}
	
	function showCaptcha()
	{
		$sActionName = AnwAction::getCurrentActionName();
		$asParams = array(AnwAction::GET_CAPTCHA => 1, "t" => time());
		$sActionUrl = AnwUtils::alink($sActionName, $asParams);
		$HTML = <<<EOF

{$this->g_('captcha_copy', array('code'=>'<img src="'.$sActionUrl.'" alt=""/>','input'=>'<input type="text" name="captcha" value="" class="intext captcha" maxlength="4"/>'))}
EOF;
		return $HTML;
	}
	
	//
	
	function renderTabField($sTabDivId, $sInputTitle, $sInputHtml, $sFieldExplain, $sFieldError=false)
	{
		$sHtmlFieldExplain = ($sFieldExplain ? '<div class="contentfield_tab_explain">'.$sFieldExplain.'</div>' : "");
		$sCssClass = ( $sFieldError ? ' contentfield_error' : '' );		
		
		$HTML = <<<EOF

<div class="contentfield contentfield_tab{$sCssClass}" id="{$sTabDivId}">
	<h2>$sInputTitle :</h2>
	$sHtmlFieldExplain
	$sInputHtml
EOF;
		if ($sFieldError) 
		{
			$HTML .= <<<EOF

	<div class="errordetails">{$sFieldError}</div>
EOF;
		}
		$HTML .= <<<EOF

</div>
EOF;
		return $HTML;
	}
	
	function renderInputField($sInputTitle, $sInputHtml, $sFieldTip, $sFieldExplain, $sMultiplicityTip, $bShowHasOverridingValues, $sFieldError=false)
	{
		$sHtmlFieldTip = ($sFieldTip ? '<div class="contentfield_tip">'.$sFieldTip.'</div>' : "");
		$sHtmlFieldExplain = ($sFieldExplain ? '<div class="contentfield_explain">'.$sFieldExplain.'</div>' : "");
		$sHtmlMultiplicityTip = ($sMultiplicityTip ? '<div class="contentmultiplicity_tip">'.$sMultiplicityTip.'</div>' : "");
		$sCssClass = ( $sFieldError ? ' contentfield_error' : '' );
		if ($bShowHasOverridingValues) $sCssClass .= ' contentfield_overriding';
		
		$HTML = <<<EOF

<div class="contentfield{$sCssClass}">
	$sHtmlMultiplicityTip $sHtmlFieldTip
	$sHtmlFieldExplain
	<label>$sInputTitle :</label>
	$sInputHtml
EOF;
		if ($sFieldError) 
		{
			$HTML .= <<<EOF

	<div class="errordetails">{$sFieldError}</div>
EOF;
		}
		
		$HTML .= <<<EOF

</div>
EOF;
		return $HTML;
	}
	
	function renderComposedField($sInputTitle, $sInputHtml, $sFieldTip, $sFieldExplain, $sMultiplicityTip, $bShowHasOverridingValues, $sFieldError=false)
	{
		$sHtmlFieldTip = ($sFieldTip ? '<div class="contentfield_tip">'.$sFieldTip.'</div>' : "");
		$sHtmlFieldExplain = ($sFieldExplain ? '<div class="contentfieldcomposed_explain">'.$sFieldExplain.'</div>' : "");
		$sHtmlMultiplicityTip = ($sMultiplicityTip ? '<div class="contentmultiplicity_tip">'.$sMultiplicityTip.'</div>' : "");
		$sCssClass = ( $sFieldError ? ' contentfield_error' : '' );		
		if ($bShowHasOverridingValues) $sCssClass .= ' contentfield_overriding';
		
		$HTML = <<<EOF

<div class="contentfield{$sCssClass}">
	$sHtmlMultiplicityTip $sHtmlFieldTip
	<label>$sInputTitle :</label>
	$sHtmlFieldExplain
	$sInputHtml
EOF;
		if ($sFieldError) 
		{
			$HTML .= <<<EOF

	<div class="errordetails">{$sFieldError}</div>
EOF;
		}
		$HTML .= <<<EOF

</div>
EOF;
		return $HTML;
	}
	
	//
	
	function renderCollapsedInput($sEditInputHtml, $sCollapsedHtml, $oContentField)
	{
		$sButtonEdit = AnwComponent::g_editcontent("contentfieldcollapsed_edit", array('fieldname'=>$oContentField->getFieldLabel()));
		$HTML = <<<EOF

<div class="contentfield_collapsed">
	<div class="contentfield_collapsed_collapsed contentfield_container">
		<a class="contentfield_collapsed_expand" onclick="anwExpandField(this.parentNode.parentNode);return false;">{$sButtonEdit}</a>
		$sCollapsedHtml 
		<div class="break"></div>
	</div>
	<div class="contentfield_collapsed_expanded">
		$sEditInputHtml
	</div>
</div>
EOF;
		return $HTML;
	}
	
	function renderMultipleInput()
	{
		
	}
	
	function renderMultipleInputInstance($sInstancesClass, $bIsSortable, $sRenderedInput, $sLabelSingle, $sFieldTip=false)
	{
		$sTranslationRemoveButton = AnwComponent::g_editcontent("contentmultiplicity_multiple_contentfield_del", array('fieldname'=>$sLabelSingle));
		if ($sFieldTip) $sFieldTip = '<div class="contentfield_tip">'.$sFieldTip.'</div>';
		
		$HTML = <<<EOF

<!-- Begin instance of $sInstancesClass -->
<div class="contentfield_multiple_instance $sInstancesClass">
	<div class="contentmultiplicity_tools">
		<a class="contentmultiplicity_remove" href="#" onclick="AnwContentFieldMultiple.get('$sInstancesClass').removeInstance(this.parentNode.parentNode); return false;">$sTranslationRemoveButton</a>
EOF;
		if ($bIsSortable)
		{
			//alternative sort to drag&drop
			$sSrcUp = AnwUtils::pathImg("up.gif");
			$sSrcDown = AnwUtils::pathImg("down.gif");
			$HTML .= <<<EOF

		<a class="contentmultiplicity_sort" href="#" onclick="AnwContentFieldMultiple.get('$sInstancesClass').moveUp(this.parentNode.parentNode); return false;"><img alt="up" src="{$this->xQuote($sSrcUp)}"/></a>
		<a class="contentmultiplicity_sort" href="#" onclick="AnwContentFieldMultiple.get('$sInstancesClass').moveDown(this.parentNode.parentNode); return false;"><img alt="down" src="{$this->xQuote($sSrcDown)}"/></a>
EOF;
		}
		$HTML .= <<<EOF

	</div>
{$sFieldTip}
{$sRenderedInput}
</div><!-- end contentfield_multiple_instance $sInstancesClass -->
<!-- End instance of $sInstancesClass -->
EOF;
		return $HTML;
	}
	
	function renderEditTab($sTabContent, $sDivId)
	{
		$HTML = <<<EOF

	<a href="#" class="{$this->xQuote($sDivId)}">$sTabContent</a>
EOF;
		return $HTML;
	}
	
	
	
	
	// ----------- locks management -----------
	
	function lockObserver_jsOnload($sLockType, $aaObservers)
	{
		$sMessageExpired = self::g_("lockinfo_expired");
		$sRenewLink = " - <a href=\\\"#\\\" onclick=\\\"AnwLock.getLock().renew();return false;\\\" class=\\\"refresh\\\">".$this->xQuote(self::g_("lockinfo_renew"))."</a>";
		
		$JS = <<<EOF

	var sMessageExpired = "{$this->xQuote($sMessageExpired)}";
	var sRenewLink = "{$sRenewLink}";
	new AnwLock( '$sLockType', $('lockinfo'), sMessageExpired, sRenewLink, lockObserver_expiresoon, lockObserver_expired, lockObserver_continue);
	
EOF;
		//update lock on some events
		foreach ($aaObservers as $asObserver)
		{
			$sInput = $asObserver['INPUT'];
			$sEvent = $asObserver['EVENT'];
			$JS .= <<<EOF

	if ($('$sInput')) $('$sInput').observe('$sEvent', AnwLock.getLock().renew.bind(AnwLock.getLock()));
EOF;
		}
		return $JS;
	}
	
	function lockObserver_js($sLockForm)
	{		
		$sMessageAlertExpireSoon = self::g_('lockinfo_alert_expiresoon');
		$sMessageAlertExpired = self::g_('lockinfo_alert_expired');
		$JS = <<<EOF

//lock observer

function lockObserver_expiresoon()
{
	var sMessageAlertExpireSoon = "{$this->xQuote($sMessageAlertExpireSoon)}";
	alert(sMessageAlertExpireSoon);
}

function lockObserver_expired()
{
	var sMessageAlertExpired = "{$this->xQuote($sMessageAlertExpired)}";
	alert(sMessageAlertExpired);
	$('$sLockForm').observe('submit', submit_disabled);
}

function submit_disabled(e)
{
	var sMessageAlertExpired = "{$this->xQuote($sMessageAlertExpired)}";
	alert(sMessageAlertExpired);
	if (e && e.preventDefault) e.preventDefault(); // DOM style
	return false; // IE style
}

function lockObserver_continue()
{
	$('$sLockForm').stopObserving('submit', submit_disabled);
}
EOF;
		return $JS;
	}
	
	function lockObserver_body()
	{
		$HTML = <<<EOF

<div id="lockinfo" class="lockinfo"></div>
EOF;
		return $HTML;
	}
	
	// ----------- reauthenticate management -----------
	
	function reauthFormJs()
	{
		$JS = <<<EOF

$("reauth").focus();
EOF;
		return $JS;
	}
	
	function reauthForm($sFormAction, $sDisplayName)
	{
		$sImgSrc = AnwUtils::pathImg("warning.gif");
		$sSubmitLabel = self::g_("in_submit");
		$HTML = <<<EOF

<div class="reauthinfo">
	<h1>{$this->g_('reauth_t')}</h1>
	<img src="{$this->xQuote($sImgSrc)}" alt=""/>
	<form action="{$sFormAction}" method="post">
	<p>{$this->g_('reauth_explain')}</p>
	<label for="reauth">{$this->g_('reauth_password', array('displayname'=>$this->xText($sDisplayName)))}</label> 
	<input type="password" name="reauth" class="intext" id="reauth"/>
	<input type="submit" value="{$this->xQuote($sSubmitLabel)}"/>
	</form>
	<div class="break"></div>
</div>
EOF;
		return $HTML;
	}
	
	//
	
	function headCss($sStyles)
	{
		$HTML = <<<EOF

	<style type="text/css">$sStyles</style>
EOF;
		return $HTML;
	}
	
	function headCssSrc($sCssFile)
	{
		$HTML = <<<EOF
		
	<link rel="StyleSheet" href="{$this->xQuote($sCssFile)}" type="text/css"/>
EOF;
		return $HTML;
	}
	
	function headJs($sJsCode)
	{
		$HTML = <<<EOF
		
	<script type="text/javascript">$sJsCode</script>
EOF;
		return $HTML;
	}
	
	function headJsOnload($sJsCode)
	{
		$HTML = <<<EOF


//initialize
document.observe("dom:loaded", function(){
$sJsCode
});
EOF;
		return $HTML;
	}
	
	function headJsSrc($sJsFile)
	{
		$HTML = <<<EOF
		
	<script type="text/javascript" src="{$this->xQuote($sJsFile)}"></script>
EOF;
		return $HTML;
	}
		
}

?>