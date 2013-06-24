<?php

class AnwTemplateDefault_action_newtranslations extends AnwTemplateOverride_global
{
	function startForm($sFormAction, $asLangs, $sSelectedLang, $error=false)
	{
		$sSelectLangs = $this->selectLang($asLangs, $sSelectedLang);
		$sLangName = Anwi18n::langName($sSelectedLang);
		$sErrorsHtml = $this->errorList($error);
		
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	{$sErrorsHtml}
	<form action="{$this->xQuote($sFormAction)}" method="post">
	
		<div class="explain">
			{$this->t_('addlang')} :
			<select name="addlang" class="languages" onchange="window.location.href='{$this->escapeQuoteApostrophe($sFormAction)}&amp;addlang='+this.options[this.selectedIndex].value">
			$sSelectLangs
			</select>
		</div>
	
		{$this->t_('explain',array('lang'=>$sLangName))} :<br/>
	
		<a href="#" onclick="AnwUtils.chkall('newtranslation_lang'); return false;">{$this->g_('in_chkall')}</a> 
		<a href="#" onclick="AnwUtils.chknone('newtranslation_lang'); return false;">{$this->g_('in_chknone')}</a><br/>
EOF;
		return $HTML;
	}
	
	function submitButton()
	{
		$sSubmitLabel = self::g_("in_submit");
		$HTML = <<<EOF
	
		<input type="submit" name="submit" class="insubmit" value="{$this->xQuote($sSubmitLabel)}"/>
EOF;
		return $HTML;
	}
	
	function noTranslation()
	{
		$HTML = <<<EOF
	
		<p style="font-style:italic">{$this->t_('notranslation')}</p>
EOF;
		return $HTML;
	}
	
	function endForm()
	{
		$HTML = <<<EOF

	</form>
EOF;
		return $HTML;
	}
	
	function newTranslationRow($sRefLang, $sRefName, $nPageRefId, $sTrLang, $sTrNameDefault, $sInputRef, $sInputName, $sCheckBoxName)
	{
		$sImgFlagRef = Anwi18n::imgFlag($sRefLang);
		$sSrcFlagTr = Anwi18n::srcFlag($sTrLang);
		$HTML = <<<EOF

	<input type="checkbox" name="{$this->xQuote($sCheckBoxName)}" value="1" id="{$this->xQuote($sCheckBoxName)}" class="newtranslation_lang" style="float:left"/>
	<input type="hidden" name="{$this->xQuote($sInputRef)}" value="{$this->xQuote($nPageRefId)}"/>
	<label for="{$this->xQuote($sCheckBoxName)}" style="width:500px; float:left; border-bottom:1px dotted #CCCCCC;">
		$sImgFlagRef $sRefName :
	</label>
	<input type="text" name="{$this->xQuote($sInputName)}" id="pagename" value="{$this->xQuote($sTrNameDefault)}" class="intext inpagename" style="background-image:url('$sSrcFlagTr')"/><br/>
	<div style="clear:both"></div>
EOF;
		return $HTML;		
	}
	
	
	
	//---------------
	
	
	
	function startProcess()
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	
	{$this->t_('created')} :
	<ul>
EOF;
		return $HTML;
	}
	
	function newTranslationCreated($sTranslationLang, $sTranslationLink)
	{
		$sImgFlag = Anwi18n::imgFlag($sTranslationLang);
		$HTML = <<<EOF

	<li>$sTranslationLink</li>
EOF;
		return $HTML;
	}
	
	function endProcess($sUrlContinue)
	{
		$sSubmitLabel = self::g_("in_submit");
		$HTML = <<<EOF

	</ul>
	<input type="button" value="{$this->xQuote($sSubmitLabel)}" onclick="document.location.href='{$this->escapeQuoteApostrophe($sUrlContinue)}'" class="insubmit"/>
EOF;
		return $HTML;
	}
	
}

?>