<?php

class AnwTemplateDefault_stepinstall_ping extends AnwTemplateDefaultStepInstall
{
	function pingInfo($sTarget)
	{
		$sPingYesLabel = AnwUtils::xQuote($this->t_("ping_yes_go"));
		$sPingNoLabel = AnwUtils::xQuote($this->t_("ping_no_go"));
		
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("ping_explain")}<br/>
</div>

<table style="width:100%">
<tr>
<td style="width:50%; padding:2em; vertical-align:top;">
<h2>{$this->t_("ping_yes_t")}</h2>
	<form method="post" action="$sTarget">
		<div style="text-align:center">
			<input type="hidden" name="ping" value="yes"/>
			<input type="checkbox" name="countincr" value="1" id="countincr" checked="checked" disabled="disabled"/><label for="countincr">{$this->t_("ping_yes_countincr_checkbox")} <span style="font-size:0.8em">({$this->t_("ping_yes_countincr_checkbox_info")})</span></label><br/>
			<input type="checkbox" name="addindirectory" value="1" id="addindirectory"/><label for="addindirectory">{$this->t_("ping_yes_directory_checkbox")} <span style="font-size:0.8em">({$this->t_("ping_yes_directory_checkbox_info")})</span></label><br/>
			<input type="submit" class="insubmit inbuttonbig" value="{$sPingYesLabel} &gt;&gt;"/>
		</div>
	</form>
</td>

<td style="width:50%; padding:1em; vertical-align:top;">
<h2>{$this->t_("ping_no_t")}</h2>
	<form method="post" action="$sTarget">
		<div style="text-align:center">
			<input type="hidden" name="ping" value="no"/>
			<input type="submit" class="inbutton inbuttonbig" value="{$sPingNoLabel} &gt;&gt;"/>
		</div>
	</form>
</td>
</tr>
</table>
EOF;
		return $HTML;
	}
	
	function doPing($sPingTarget, $sUrlContinue)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("ping_done_explain")}<br/>
	<input type="button" class="insubmit inbuttonbig" value="{$this->t_install("nextstep_go")} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sUrlContinue)}'"/>
</div>
<div style="text-align:center">
	<iframe src="{$this->xQuote($sPingTarget)}" style="border:1px solid #000; width:600px; height:100px; margin:0px auto;"></iframe>
</div>
EOF;
		return $HTML;
	}
}

?>