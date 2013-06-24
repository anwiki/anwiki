<?php

class AnwTemplateDefault_action_changelang extends AnwTemplateOverride_global
{
	function changeLangForm($sLang, $sComment, $selectlang,
						$sPagename, $formaction, 
						$sError)
	{
		$sFlag = Anwi18n::imgFlag($sLang);
		$sSubmitLabel = self::g_("in_submit");
		$sAbortLabel = self::g_("in_abort");
		$HTML = <<<EOF

	<h1>{$this->t_('title',array("pagename"=>"<i>".$this->xText($sPagename)."</i>"))} $sFlag</h1>
	{$this->errorList($sError)}
	<form action="{$this->xQuote($formaction)}" method="post" id="changelang_form">
		{$this->g_('in_pagelang')} $selectlang<br/>
		{$this->g_('in_comment')} <input type="text" name="comment" id="comment" class="intext incomment" value="{$this->xQuote($sComment)}"/><br/> 
		<input type="submit" name="changelang" class="insubmit" value="{$this->xQuote($sSubmitLabel)}" />
		<input type="submit" name="abort" class="inabort" value="{$this->xQuote($sAbortLabel)}"/>
	</form>
EOF;
		return $HTML;
	}
}

?>