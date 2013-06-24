<?php
/**
 * Anwiki is a multilingual content management system <http://www.anwiki.com>
 * Copyright (C) 2007-2009 Antoine Walter <http://www.anw.fr>
 * 
 * Anwiki is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 * 
 * Anwiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Anwiki.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * User preferences.
 * @package Anwiki
 * @version $Id: action_settings.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_settings extends AnwActionGlobal implements AnwHttpsAction, AnwPublicAction, AnwAlwaysEnabledAction
{
	function run()
	{
		$this->setTitle( $this->t_("title") );
		if (AnwEnv::_POST("submit"))
		{
			$this->updateSettings();
		}
		else
		{
			$this->formSettings(AnwEnv::_GET("done"));
		}
	}
	
	private function formSettings($bUpdateDone=false, $asErrorsPrefs=array(), $asErrorsAccount=array())
	{
		$this->out .= $this->tpl()->openSettings($this->linkMe(), $bUpdateDone);
		
		//prefs
		$nTimezone = AnwCurrentSession::getTimezone();
		$sLang = AnwCurrentSession::getLang();
		$this->out .= $this->tpl()->showSettingsPrefs($nTimezone, $sLang, $asErrorsPrefs);
		
		if (AnwCurrentSession::isLoggedIn())
		{
			//account settings
			if (AnwUsers::isDriverInternal())
			{
				//editable
				$sLogin = AnwCurrentSession::getUser()->getLogin();
				$sEmail = AnwCurrentSession::getUser()->getEmail();
				
				if ( self::globalCfgUsersChangeDisplayname() )
				{
					$bChangeDisplaynameAllowed = true;
					$sDisplayname = AnwCurrentSession::getUser()->getDisplayName();
				}
				else
				{
					$bChangeDisplaynameAllowed = false;
					$sDisplayname = AnwCurrentSession::getUser()->getDisplayName();
				}
				
				$this->out .= $this->tpl()->showSettingsAccountInternal($sLogin, $sDisplayname, $sEmail, $bChangeDisplaynameAllowed, $asErrorsAccount);
			}
			else
			{
				//read only
				$sLogin = AnwCurrentSession::getUser()->getLogin();
				$sDisplayname = AnwCurrentSession::getUser()->getDisplayName();
				$sEmail = AnwCurrentSession::getUser()->getEmail();
				$sEditLink = AnwUsers::getEditLink();
				$this->out .= $this->tpl()->showSettingsAccountExternal($sLogin, $sDisplayname, $sEmail, $sEditLink, $asErrorsAccount);
			}
		}
		
		$this->out .= $this->tpl()->closeSettings();
		
	}
	
	private function updateSettings()
	{
		//update prefs
		$asErrorsPrefs = array();
		
		try
		{
			$sLang = AnwEnv::_POST("lang", "");
			AnwCurrentSession::setLang($sLang);
			
			$nTimezone = AnwEnv::_POST("timezone", 0);
			AnwCurrentSession::setTimezone($nTimezone);
		}
		catch (AnwBadLangException $e)
		{
			$asErrorsPrefs[] = $this->g_("err_badlang");
		}
		catch(AnwBadTimezoneException $e)
		{
			$asErrorsPrefs[] = $this->g_("err_badtimezone");
		}
		catch(AnwException $e)
		{
			$asErrorsPrefs[] = $this->g_("err_unkn");
		}
		
		$asErrorsAccount = array();
		if (AnwCurrentSession::isLoggedIn() && AnwUsers::isDriverInternal())
		{
			//update account
			
			try
			{
				//displayname change requested ?
				if (self::globalCfgUsersChangeDisplayname())
				{
					$sDisplayname = AnwEnv::_POST("displayname", "");
					if (AnwCurrentSession::getUser()->getDisplayName() != $sDisplayname)
					{
						AnwCurrentSession::getUser()->changeDisplayName($sDisplayname);
					}
				}
				
				//email change requested ?
				$sEmail = AnwEnv::_POST("email", "");
				if (AnwCurrentSession::getUser()->getEmail() != $sEmail)
				{
					AnwCurrentSession::getUser()->changeEmail($sEmail);
				}
				
				//password change requested ?
				$sNewPassword = AnwEnv::_POST("newpassword");
				$sNewPasswordRepeat = AnwEnv::_POST("newpassword_repeat");
				$sCurrentPassword = AnwEnv::_POST("currentpassword", "");
				if ($sNewPassword)
				{
					if ($sNewPassword == $sNewPasswordRepeat)
					{
						try
						{
							//authenticate with current password
							AnwCurrentSession::getUser()->authenticate($sCurrentPassword);
							
							//authentication ok, change the password
							try
							{
								AnwCurrentSession::getUser()->changePassword($sNewPassword);
							}
							catch(AnwBadPasswordException $e)
							{
								$asErrorsAccount[] = $this->t_("err_badnewpassword");
							}
						}
						catch(AnwBadPasswordException $e)
						{
							$asErrorsAccount[] = $this->g_("err_incorrectpassword");
						}
						catch(AnwAuthException $e)
						{
							$asErrorsAccount[] = $this->g_("err_incorrectpassword");
						}
					}
					else
					{
						$asErrorsAccount[] = $this->g_("err_passwordsmatch");
					}
				}
			}
			catch (AnwDisplayNameAlreadyTakenException $e)
			{
				$asErrorsAccount[] = $this->g_("err_displaynamealreadytaken");
			}
			catch (AnwBadDisplayNameException $e)
			{
				$asErrorsAccount[] = $this->g_("err_baddisplayname");
			}
			catch(AnwEmailAlreadyTakenException $e)
			{
				$asErrorsAccount[] = $this->g_("err_emailalreadytaken");
			}
			catch(AnwBadEmailException $e)
			{
				$asErrorsAccount[] = $this->g_("err_bademail");
			}
			catch(AnwException $e)
			{
				$asErrorsAccount[] = $this->g_("err_unkn");
			}
		}
		if (count($asErrorsPrefs) > 0 || count($asErrorsAccount) > 0)
		{
			$this->formSettings(false, $asErrorsPrefs, $asErrorsAccount);
		}
		else
		{
			AnwUtils::redirect($this->linkMe(array("done"=>1)));
		}		
	}
}

?>