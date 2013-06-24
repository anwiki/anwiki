<?php

class AnwTemplateDefault_action_settings extends AnwTemplateOverride_global
{
	function openSettings($sFormAction, $bUpdateDone)
	{
		$sDoneMsg = $bUpdateDone ? '<div class="done">'.$this->t_("settings_updated").'</div>' : '';
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	$sDoneMsg
	<form action="{$this->xQuote($sFormAction)}" method="post">
EOF;
		return $HTML;
	}
	
	function showSettingsPrefs($nTimezone, $sLang, $asErrors)
	{
		$sSelectLang = $this->selectLang();
		$sSelectTimezone = $this->selectTimezone($nTimezone);
		$sErrors = $this->errorList($asErrors);
		$HTML = <<<EOF

		<h2>{$this->t_('settings_prefs_t')}</h2>
		$sErrors
		<p>
			<label>{$this->t_('settings_lang')} :</label>
			<select name="lang" class="languages">$sSelectLang</select>
		</p>
		<p>
			<label>{$this->t_('settings_timezone')} :</label>
			<select name="timezone" class="timezones">
				$sSelectTimezone
			</select>
		</p>
EOF;
		return $HTML;
	}
	
	function showSettingsAccountInternal($sLogin, $sDisplayname, $sEmail, $bChangeDisplaynameAllowed, $asErrors)
	{
		$sErrors = $this->errorList($asErrors);
		$HTML = <<<EOF

		<h2>{$this->t_('settings_account_t')}</h2>
		$sErrors
		<p>
			<label>{$this->g_('user_login')} :</label>
			$sLogin</select>
		</p>
EOF;
		if ($bChangeDisplaynameAllowed)
		{
			$HTML .= <<<EOF
		<p>
			<label>{$this->g_('user_displayname')} :</label>
			<input type="text" name="displayname" value="{$this->xQuote($sDisplayname)}"/>
		</p>
EOF;
		}
		else
		{
			$HTML .= <<<EOF
		<p>
			<label>{$this->g_('user_displayname')} :</label>
			$sDisplayname
		</p>
EOF;
		}
		$HTML .= <<<EOF
		<p>
			<label>{$this->g_('user_email')} :</label>
			<input type="text" name="email" value="{$this->xQuote($sEmail)}"/>
		</p>
		<p class="submit" id="show_changepassword">
			<a href="#" onclick="$('changepassword').style.display='block'; $('show_changepassword').style.display='none'; return false;">{$this->t_('settings_changepassword')}</a>
		</p>
		<div id="changepassword" style="display:none;">
		<fieldset>
			<legend>{$this->t_('settings_changepassword')}</legend>
			<p>
				<label>{$this->t_('settings_currentpassword')} :</label>
				<input type="password" name="currentpassword" value="" id="currentpassword"/> <a href="#" onclick="$('newpassword').value=''; $('newpassword_repeat').value=''; $('changepassword').style.display='none'; $('show_changepassword').style.display='block'; return false;">{$this->t_('settings_cancelpassword')}</a>
			</p>
			<p>
				<label>{$this->t_('settings_newpassword')} :</label>
				<input type="password" name="newpassword" value="" id="newpassword"/>
			</p>
			<p>
				<label>{$this->t_('settings_newpassword_repeat')} :</label>
				<input type="password" name="newpassword_repeat" value="" id="newpassword_repeat"/>
			</p>
		</fieldset>
		</div>
EOF;
		return $HTML;
	}
	
	function showSettingsAccountExternal($sLogin, $sDisplayname, $sEmail, $sEditLink, $asErrors)
	{
		$sErrors = $this->errorList($asErrors);
		$HTML = <<<EOF

		<h2>{$this->t_('settings_account_t')}</h2>
		$sErrors
		<div class="explain">
			{$this->t_('settings_account_edit', array('startlink'=>'<a href="'.$this->xQuote($sEditLink).'">','endlink'=>'</a>'))}
		</div>
		<p>
			<label>{$this->g_('user_login')} :</label>
			$sLogin</select>
		</p>
		<p>
			<label>{$this->g_('user_displayname')} :</label>
			$sDisplayname
		</p>
		<p>
			<label>{$this->g_('user_email')} :</label>
			$sEmail
		</p>
EOF;
		return $HTML;
	}
	
	function closeSettings()
	{
		$HTML = <<<EOF

		<p class="submit"><input type="submit" name="submit" value="{$this->xQuote($this->t_("settings_submit"))}"/></p>
	</form>
EOF;
		return $HTML;
	}
	
	private function selectTimezone($nSelectedTimezone)
	{
		$HTML = "";
		$anTimezones = AnwUsers::getTimezones();
		foreach ($anTimezones as $nTimezone)
		{
			$sSelected = ($nSelectedTimezone == $nTimezone ? ' selected="selected"':'');
			$sTimezoneName = Anwi18n::timezoneName($nTimezone);
			$sTimezoneTime = Anwi18n::dateTime(time(), false, $nTimezone);
			$HTML .= <<<EOF

				<option value="$nTimezone"{$sSelected}>($sTimezoneName) $sTimezoneTime</option>
EOF;
		}
		return $HTML;
	}
}

?>