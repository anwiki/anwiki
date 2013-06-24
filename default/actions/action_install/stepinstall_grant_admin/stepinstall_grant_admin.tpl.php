<?php

class AnwTemplateDefault_stepinstall_grant_admin extends AnwTemplateDefaultStepInstall
{
	function chooseGrantNotSupported($sLinkContinue, $sLinkDriver)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("grant_not_supported")}<br/>
	<input type="button" class="inabort inbuttonbig" value="&lt;&lt; {$this->xQuote($this->t_("grant_not_supported_godriver"))}" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkDriver)}'"/>
	<input type="button" class="inbutton inbuttonbig" value="{$this->xQuote($this->t_("grant_not_supported_go"))} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkContinue)}'" />
</div>
EOF;
		return $HTML;
	}
	
	function chooseGrant($sTarget, $bCanCreateUser, $sLogin, $sDisplayname, $sEmail, $sExistingLogin, $sErrorCreate, $sErrorExisting)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_("choose_explain")}
</div>

<table style="width:100%;">
<tr>
<td style="width:50%;vertical-align:top;padding:1em;">
	<h2>{$this->t_("choose_create_t")}</h2>
	{$this->errorList($sErrorCreate)}
	<fieldset>
		<legend>{$this->t_("choose_create_t")}</legend>
EOF;
		$sSubmitLabel = self::g_("in_submit");
		if ($bCanCreateUser)
		{
			$HTML .= <<<EOF

		<form action="$sTarget" method="post">
			<label>{$this->g_('user_login')} : <input type="text" name="login" value="{$this->xQuote($sLogin)}" class="intext"/></label><br/>
			<label>{$this->g_('user_displayname')} : <input type="text" name="displayname" value="{$this->xQuote($sDisplayname)}" class="intext"/></label><br/>
			<label>{$this->g_('user_email')} : <input type="text" name="email" value="{$this->xQuote($sEmail)}" class="intext"/></label><br/>
			<label>{$this->g_('user_password')} : <input type="password" name="password" value="" class="intext"/></label><br/>
			<input type="submit" name="submit_create" value="{$this->xQuote($sSubmitLabel)}"/><br/>
		</form>
EOF;
		}
		else
		{
			$HTML .= <<<EOF

		{$this->t_("choose_create_notsupported")}
EOF;
		}
		$HTML .= <<<EOF

	</fieldset>
</td>

<td style="vertical-align:top;padding:1em;">
	<h2>{$this->t_("choose_existing_t")}</h2>
	{$this->errorList($sErrorExisting)}
	<fieldset>
		<legend>{$this->t_("choose_existing_t")}</legend>
		<form action="$sTarget" method="post">
			<label>{$this->g_('user_login')} : <input type="text" name="login" value="{$this->xQuote($sExistingLogin)}" class="intext"/></label><br/>
			<label>{$this->g_('user_password')} : <input type="password" name="password" value="" class="intext"/></label>
			<input type="submit" name="submit_existing" value="{$this->xQuote($sSubmitLabel)}"/><br/>
		</form>
	</fieldset>
</td>
</tr>
</table>
EOF;
		return $HTML;
	}
	
	function formGrantExisting($sTarget)
	{
		$HTML = <<<EOF

<form action="{$this->xQuote($sTarget)}" method="POST">

</form>
EOF;
		return $HTML;
	}
}

?>