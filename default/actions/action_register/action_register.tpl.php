<?php

class AnwTemplateDefault_action_register extends AnwTemplateOverride_global
{
	function registerForm($formaction, $login, $displayname, $email, $error)
	{
		if ($error) $error = "<div class=\"error\">$error</div>";
		$sRegisterSubmitLabel = $this->t_("register_submit");
		
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	{$this->errorList($error)}
	<form action="{$this->xQuote($formaction)}" method="post">
		<label>{$this->g_('user_login')} : <input type="text" name="login" value="{$this->xQuote($login)}" class="intext"/></label><br/>
		<label>{$this->g_('user_displayname')} : <input type="text" name="displayname" value="{$this->xQuote($displayname)}" class="intext"/></label><br/>
		<label>{$this->g_('user_email')} : <input type="text" name="email" value="{$this->xQuote($email)}" class="intext"/></label><br/>
		<label>{$this->g_('user_password')} : <input type="password" name="password" value="" class="intext"/></label><br/>
		{$this->showCaptcha()}<br/>
		<input type="submit" name="submit" value="{$this->xQuote($sRegisterSubmitLabel)}"/><br/>
	</form>
EOF;
		return $HTML;
	}
}

?>