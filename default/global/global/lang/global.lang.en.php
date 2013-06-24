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
 * Global english translations.
 * @package Anwiki
 * @version $Id: global.lang.en.php 249 2010-02-23 20:27:50Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

$lang = array (
	"in_pagename"			=>	"Page name :",
	"in_pagelang"			=>	"Page lang :",
	"in_contentclass"		=>	"Page class :",
	"in_comment"			=>	"Comment :",
	"in_submit"				=>	"Ok",
	"in_abort"				=>	"Abort",
	"in_chkall"				=>	"all",
	"in_chknone"			=>	"none",
	"in_filter" 			=>	"Filter",
	
	"topnav_settings"		=>	"settings",
	"topnav_login"			=>	"log in",
	"topnav_logout"			=>	"logout",
	"topnav_register"		=>	"register",
	
	"err_404"				=>	"404 - The requested URL was not found.",
	"err_occurred"			=>	"An error occurred",
	"err_badpagename"		=>	"Illegal page name : no page can have a such name",
	"err_badlang"			=>	"Illegal lang : the language code is not valid",
	"err_badtimezone"		=>	"Illegal time zone : the time zone is not valid",
	"err_auth"				=>	"No user with such username and password",
	"err_emptyfields"		=>	"You must fill all required fields",
	"err_pwdlength"			=>	"Password length must be at least %minlength% chars",
	"err_unkn"				=>	"An unknown error occurred, try again later",
	"err_badcall"			=>	"Bad page call, the link you clicked seems broken",
	"err_pagealreadyexists"	=>	"There is already a page with this page name",
	"err_badlogin"			=>	"Illegal login",
	"err_loginalreadytaken"	=>	"Login already taken by another user",
	"err_baddisplayname"	=>	"Illegal display name",
	"err_displaynamealreadytaken"	=>	"Display name already taken by another user",
	"err_bademail"			=>	"Illegal email",
	"err_emailalreadytaken"	=>	"Email address already taken by another user",
	"err_unknownlogin"		=>	"Unknown login",
	"err_badpassword"		=>	"Illegal password",
	"err_incorrectpassword"	=>	"Provided password is incorrect",
	"err_passwordsmatch"	=>	"The repeted password doesn't match",
	"err_badcaptcha"		=>	"Wrong anti-spam code",
	"err_nopermission"		=>	"You are not allowed to perform this action",
	"err_rendercontent"		=>	"Unable to render content. You may need to edit and update this content.",
	"err_langexistsforpagegroup" => "There is already a translation with this lang",
	"err_contentinvalid"	=>	"Unable to save your changes : edited content has errors",
	"err_ex_report"			=>	"Error has been logged. Please retry in a few minutes, or contact the administrator providing the following error number : %errornumber%",
	"err_ex_sorry"			=>	"Sorry for the inconvenience",
	"err_ex_badcall_t"		=>	"400 - Bad request",
	"err_ex_badcall_p"		=>	"The system is unable to complete your request, due to incomplete parameters given in request.<br/>This may happen when you type an erroneous URL, when you refresh an expired page or when you go back in your navigation history.",
	"err_ex_unexpected_t"	=>	"Unexpected error occurred",
	"err_ex_unexpected_p"	=>	"The system is unable to complete your request, due to an internal error. This may be a bug.",
	"err_ex_acl_t"			=>	"Permission denied",
	"err_ex_acl_loggedin_p"	=>	"You don't have the permission to perform this action. You may contact the administrator to get more permissions.",
	"err_ex_acl_loggedout_p"=>	"You don't have the permission to perform this action. Try to log-in if you have an account, or register.",
	"err_ex_acl_mintranslatedpercent_p"	=>	"This content is being translated (%percent%% done). You will be able to view it as soon as it'll be translated.",
	"err_ex_acl_php_p"		=>	"You don't have the permission to edit contents having PHP code.",
	"err_ex_acl_js_p"		=>	"You don't have the permission to edit contents having JavaScript code.",
	"err_ex_lock_t"			=>	"Content temporarily unavailable",
	"err_ex_lock_p1"		=>	"The content you are trying to edit is currently used by another user. In order to avoid conflicts, it has been temporarily locked.",
	"err_ex_lock_p2"		=>	"You will be automatically redirected as soon as the content will be available.",
	"err_ex_lock_details_pageonly"	=>	"%user% has exclusive lock on %pagename% since %timestart%, last seen on %timeseen%. Lock will expire on %timeexpire% if user is idle.",
	"err_ex_lock_details_pagegroup"	=>	"%user% has exclusive lock on %pagename% and its translations since %timestart%, last seen on %timeseen%. Lock will expire on %timeexpire% if user is idle.",
	"err_ex_dbconnect_t"		=>	"Database connexion error",
	"err_ex_dbconnect_p"		=>	"The system is unable to complete your request, as it was unable to connect to the database. The database may be temporarily down, please try again later.",
	"err_need_write_file"	=>	"The file '%filename%' is not writable. The web server is running as user: %processuser%, group: %processgroup%.",
	
	"err_mailreport_subject"=>	"Report for error #%errornumber%",
	"err_mailreport_body"	=>	"An error occurred on your website %website%. Here is the complete log :",
	
	"err_phpeval_disabled"	=>	"Error: PHP eval is disabled in configuration.",
	
	"lockinfo_pagegroup"	=>	"You have exclusive rights on %pagename% and all its translations since %locktime%. Your lock will expire in %remainingtime%",
	"lockinfo_pageonly"		=>	"You have exclusive rights on %pagename% since %locktime%. Your lock will expire in %remainingtime%",
	"lockinfo_expired"		=>	"Your lock time is over ! Try to get another lock by clicking on Renew.",
	"lockinfo_renew"		=>	"Renew",
	"lockinfo_alert_expired"=>	"Your lock time is over ! Without a valid lock, your changes will be lost. Try to get another lock by clicking on Renew.",
	"lockinfo_alert_expiresoon"=>	"Your lock time is about to expire ! Update your lock by clicking on Renew",
	
	"changes_creation"		=>	"Creation",
	"changes_newtranslation"=>	"New translation",
	"changes_edition"		=>	"Edition",
	"changes_edition_deploy" => "Auto/edit",
	"changes_edition_deploy_info"	=>	"Deploy from %pagename%",
	"changes_translation"	=>	"Translation",
	"changes_deletion"		=>	"Deletion",
	"changes_rename"		=>	"Rename",
	"changes_changelang"	=>	"Lang change",
	"changes_updatelinks"	=>	"Auto/link",
	"changes_revert"		=>	"Revert",
	"changes_revert_info"	=>	"Back to %datetime%",
	
	"change_time"			=>	"Time",
	"change_type"			=>	"Type",
	"change_page"			=>	"Current page",
	"change_pageoriginal"	=>	"Original page",
	"change_comment"		=>	"Comment",
	"change_user"			=>	"User",
	"change_diff"			=>	"Diff",
	"change_diff_link"		=>	"View differences",
	"change_diff_current"	=>	"current",
	"change_similars"		=>	"%count% similars",
	
	"page_name"				=>	"Page",
	"page_time"				=>	"Last modification time",
	"page_contentclass"		=>	"Content",
	
	"user_login"			=>	"Login",
	"user_displayname"		=>	"Display name",
	"user_email"			=>	"Email",
	"user_password"			=>	"Password",
	
	"redir_link"			=>	"Click here if you are not automatically redirected",
	
	"user_displayname_anonymous"	=>	"Anonymous",
	"user_displayname_installassistant"	=>	"Install assistant",
	
	"rss_export"			=>	"Export to RSS",
	
	
	"translation_progress"	=>	"%percent%% translated",
	"translation_complete"	=>	"translation complete",
	
	"captcha_copy"		=>	"Copy the anti-spam code %code% here : %input%",
	
	
	"lang_ar"				=>	"Arabic",
	"lang_de"				=>	"German",
	"lang_en"				=>	"English",
	"lang_es"				=>	"Spanish",
	"lang_fr"				=>	"French",
	"lang_fi"				=>	"Finnish",
	"lang_gr"				=>	"Greek",
	"lang_hi"				=>	"Hindi",
	"lang_it"				=>	"Italian",
	"lang_ja"				=>	"Japanese",
	"lang_lt"				=>	"Lithuanian",
	"lang_nl"				=>	"Dutch",
	"lang_pl"				=>	"Polish",
	"lang_pt_br"			=>	"Portuguese BR",
	"lang_ro"				=>	"Romanian",
	"lang_ru"				=>	"Russian",
	"lang_sl"				=>	"Slovenian",
	"lang_uk"				=>	"Ukrainian",
	"lang_zh_CN"			=>	"Chinese (simp)",
	
	"timezone_gmt_name"		=>	"GMT%offset%",
	
	"page_progress"			=>	"Progress",
	
	"reauth_t"				=>	"Reauthentication required",
	"reauth_password"		=>	"Your password, %displayname%:",
	"reauth_explain"		=>	"You are logged in for a long time. For security reasons, please reauthenticate in order to continue your action.",
	
	"local_datetime"		=>	"m/d/Y H:i:s",
	"local_date"			=>	"m/d/Y",
	"local_html_lang"		=>	"en",
	"local_html_dir"		=>	"ltr",
	
	"local_header_backhome"		=>	"Back to home",
	
	"local_title_before" => "",
	"local_title_after" => "",
	
	"local_exec_dynamic_error"	=>	"Dynamic content error",
	"local_exec_loop_error"	=>	"Dynamic loop error",
	"local_exec_condition_error"	=>	"Dynamic condition error",
	
	"filter_langs" => "Langs filter :",
	"filter_classes" => "Content filter :",
	
	"footer"				=>	"",
	
	"local_poweredby"				=>	"Powered by %link%",
	"local_poweredby_legend"		=>	"%name% is a multilingual wiki / CMS",
	
);
?>