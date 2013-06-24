<?php

class AnwTemplateDefault_stepinstall_finished extends AnwTemplateDefaultStepInstall
{
	function showFinished($sDirToDelete, $sLinkHome, $sLinkEditConfig, $sWebsite)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("finished_explain_p1")}<br/>
	{$this->t_("finished_explain_p2",array('website'=>$sWebsite))}<br/><br/>
	{$this->t_("finished_explain_locked_p1")}<br/>
	<span style="color:#FF0000; font-weight:bold">{$this->t_("finished_explain_locked_p2",array('dirtodelete'=>'<br/>'.$sDirToDelete))}</span><br/><br/>
	<input type="button" class="inbutton inbuttonbig" value="{$this->xQuote($this->t_("finished_go_editconfig"))} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkEditConfig)}'"/>
	<input type="button" class="insubmit inbuttonbig" value="{$this->xQuote($this->t_("finished_go_home"))} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkHome)}'"/>  
</div>
EOF;
		return $HTML;
	}
}

?>