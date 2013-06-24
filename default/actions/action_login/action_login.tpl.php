<?php

class AnwTemplateDefault_action_login extends AnwTemplateOverride_global
{
	function loginForm($formaction, $login, $sUrlRedirect, $bRememberMe, $bShowRememberMe, $error)
	{
		if ($error) $error = "<div class=\"error\">$error</div>";
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	$error
	<form action="{$this->xQuote($formaction)}" method="post">
		<label>{$this->g_('user_login')} : <input type="text" name="login" id="login" value="{$this->xQuote($login)}" class="intext"/></label>
		<label>{$this->g_('user_password')} : <input type="password" name="password" value="" class="intext"/></label>
EOF;
		if ($bShowRememberMe)
		{
			$HTML .= <<<EOF

		<input type="checkbox" id="remember" name="remember" value="1"/><label for="remember">{$this->t_('login_remember')}</label><br/>
EOF;
		}
		$HTML .= <<<EOF

		<input type="submit" name="submit" value="{$this->xQuote($this->t_("login_submit"))}"/>
		<input type="hidden" name="redirect" value="{$this->xQuote($sUrlRedirect)}"/>
	</form>
EOF;
		return $HTML;
	}
	
	function loginFormJs()
	{
		$JS = <<<EOF
$('login').focus();
EOF;
		return $JS;
	}
}

?>