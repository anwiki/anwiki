<?php

class AnwTemplateDefault_action_create extends AnwTemplateOverride_global
{
	function startForm($pageid, $sFormAction, $sLang, $asLangs, $aoContentClasses)
	{
		$langOptions = $this->selectLang($asLangs, $sLang);
		$sContentClassOptions = $this->selectContentClass($aoContentClasses);
		
		$HTML = <<<EOF

	<h1>{$this->t_('title', array('pagename'=>'<i>'.$this->xText($pageid).'</i>'))}</h1>
	
	<form action="{$this->xQuote($sFormAction)}" method="post">
		{$this->g_('in_pagename')} {$this->xText($pageid)}<br/>
		{$this->g_('in_pagelang')} <select name="lang" class="languages inselect" id="selectpagelang" onchange="window.location.href='{$this->escapeQuoteApostrophe($sFormAction)}&amp;lang='+this.options[this.selectedIndex].value">$langOptions</select><br/> 
		{$this->g_('in_contentclass')} <select name="contentclass" class="contentclasses inselect">$sContentClassOptions</select><br/> 
		
		<div style="width:600px" id="create_translations">
		<fieldset>
			<legend>{$this->t_('translations')}
				<a href="#" onclick="AnwUtils.chkall('create_lang',$('create_translations')); return false;">{$this->g_('in_chkall')}</a> 
				<a href="#" onclick="AnwUtils.chknone('create_lang',$('create_translations')); return false;">{$this->g_('in_chknone')}</a>
			</legend>
EOF;
		return $HTML;
	}
	
	function translationRow($sLang, $sPageNameDefault, $sCheckBoxName, $sInputName)
	{
		$sImgFlag = Anwi18n::imgFlag($sLang);
		$sSrcFlag = Anwi18n::srcFlag($sLang);
		$HTML = <<<EOF

		<div class="div_translation">
			<input type="checkbox" name="{$this->xQuote($sCheckBoxName)}" id="{$this->xQuote($sCheckBoxName)}" value="1" class="create_lang" style="float:left"/>
			<label for="{$this->xQuote($sCheckBoxName)}" style="width:100px; float:left;">
				$sImgFlag $sLang :
			</label>
			<input type="text" name="{$this->xQuote($sInputName)}" value="{$this->xQuote($sPageNameDefault)}" class="intext inpagename" style="background-image:url('$sSrcFlag')"/><br/>
		</div>
EOF;
		return $HTML;
	}
	
	function translationRowCurrent($sLang, $sPageName)
	{
		$sImgFlag = Anwi18n::imgFlag($sLang);
		$sSrcFlag = Anwi18n::srcFlag($sLang);
		$HTML = <<<EOF

		<div class="div_translation">
			<input type="checkbox" name="lang_current" id="lang_current" value="null" style="float:left" checked="checked" disabled="disabled"/>
			<label for="lang_current" style="width:100px; float:left;">
				$sImgFlag $sLang :
			</label>
			<input type="text" name="pagename_current" value="{$this->xQuote($sPageName)}" class="intext inpagename" style="background-image:url('$sSrcFlag')" disabled="disabled"/><br/>
		</div>
EOF;
		return $HTML;
	}
	
	function endForm($lnkhome)
	{
		$sSubmitLabel = self::g_("in_submit");
		$sAbortLabel = self::g_("in_abort");
		$HTML = <<<EOF
		</fieldset>
		</div>
		<input type="button" value="{$this->xQuote($sAbortLabel)}" class="inabort" onclick="document.location='{$this->escapeQuoteApostrophe($lnkhome)}'"/> 
		<input type="submit" value="{$this->xQuote($sSubmitLabel)}" class="insubmit"/>
	</form>

EOF;
		return $HTML;
	}
}

?>