<?php

class AnwTemplateDefault_stepinstall_start extends AnwTemplateDefaultStepInstall
{
	function showStart($sLinkContinue, $sWebsite)
	{
		$sStartLabel = AnwUtils::xQuote($this->t_("start_go"));
		
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("start_explain_p1")}<br/>
	{$this->t_("start_explain_p2")}<br/><br/>
	{$this->t_("start_explain_p3", array('website'=>$sWebsite))}<br/><br/>
	<input type="button" class="insubmit inbuttonbig" value="{$sStartLabel} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkContinue)}'"/>  
</div>
EOF;
		return $HTML;
	}
}

?>