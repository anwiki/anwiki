<?php

class AnwTemplateDefault_action_newtranslation extends AnwTemplateOverride_global
{
	function newTranslationStart($formaction, $originalpagename, $sOriginalPageLang, $error=false)
	{
		$sFlag = Anwi18n::imgFlag($sOriginalPageLang);
		
		$HTML = <<<EOF

	{$this->errorList($error)}
	<h1>{$this->t_('title', array('pagename'=>'<i>'.$this->xText($originalpagename).'</i>'))} $sFlag</h1>
	<form action="{$this->xQuote($formaction)}" method="post">
		<a href="#" onclick="AnwUtils.chkall('newtranslation_lang');">{$this->g_('in_chkall')}</a> 
		<a href="#" onclick="AnwUtils.chknone('newtranslation_lang');">{$this->g_('in_chknone')}</a><br/>
EOF;
		return $HTML;
	}
	
	function newTranslationStop()
	{
		$sSubmitLabel = self::g_("in_submit");
		$sAbortLabel = self::g_("in_abort");
		$HTML = <<<EOF

	
		<input type="submit" name="submit" class="insubmit" value="{$this->xQuote($sSubmitLabel)}"/>
		<input type="submit" name="abort" class="inabort" value="{$this->xQuote($sAbortLabel)}"/>
	</form>
EOF;
		return $HTML;
	}
	
	function newTranslationRow($sLang, $sDefaultPageNameValue, $sInputName, $sCheckBoxName)
	{
		$sImgFlag = Anwi18n::imgFlag($sLang);
		$sSrcFlag = Anwi18n::srcFlag($sLang);
		$HTML = <<<EOF

		<input type="checkbox" name="{$this->xQuote($sCheckBoxName)}" value="1" id="{$this->xQuote($sCheckBoxName)}" class="newtranslation_lang" style="float:left"/>
		<label for="{$this->xQuote($sCheckBoxName)}" style="width:100px; float:left;">
			$sImgFlag $sLang :
		</label>
		<input type="text" name="{$this->xQuote($sInputName)}" value="{$this->xQuote($sDefaultPageNameValue)}" class="intext inpagename" style="background-image:url('$sSrcFlag')"/><br/>
EOF;
		return $HTML;		
	}
	
}

?>