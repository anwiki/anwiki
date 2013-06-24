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
 * Global english translations for settings.
 * @package Anwiki
 * @version $Id: global-settings.lang.en.php 255 2010-03-10 20:41:37Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

$lang = array (

	//global setup	
	"setting_setup_label"		=>	"Setup",
	"setting_setup_explain"		=>	"Settings related to this Anwiki setup.",
	
	"setting_setup_location_label"		=>	"Location",
	"setting_setup_location_explain"		=>	"",
	"setting_setup_location_urlroot_label"	=>	"Root URL",
	"setting_setup_location_urlroot_explain"	=>	"URL where Anwiki has been installed",
	"setting_setup_location_homepage_label"		=>	"Home (content name)",
	"setting_setup_location_homepage_explain"		=>	"The home is the content which is displayed by default, when accessing the root URL.",
	"setting_setup_location_friendlyurl_enabled_label"	=>	"Friendly URLs",
	"setting_setup_location_friendlyurl_enabled_explain"	=>	"Friendly URLs are easier to manipulate for humans and give better ranking in search engines.<br/>Before enabling this option, make sure to have mod_rewrite enabled on your web server and check that the .htaccess file is present in Anwiki directory.",
	"setting_setup_location_friendlyurl_enabled_checkbox"	=>	"Enable friendly URLs",
	"setting_setup_location_noindexurl_enabled_label"	=>	"Remove 'index.php' from URLs",
	"setting_setup_location_noindexurl_enabled_explain"	=>	"When enabled, internal links will not target explicitely 'index.php' but the parent directory or domain.<br/>This way, URLs will be shorter and won't reveal PHP usage.<br/>Before enabling this option, make sure to have mod_dir enabled on your web server and check that 'index.php' is configured as DirectoryIndex.",
	"setting_setup_location_noindexurl_enabled_checkbox"	=>	"Remove 'index.php' from URLs",
	"setting_setup_location_website_name_label"	=>	"Website name",
	"setting_setup_location_website_name_explain"	=>	"This name identifies your website. It appears on various public locations such as RSS feeds, e-mails...",
	
	"setting_setup_i18n_label"		=>	"Internationalization",
	"setting_setup_i18n_explain"		=>	"",
	"setting_setup_i18n_lang_default_label"	=>	"Default language",
	"setting_setup_i18n_lang_default_explain"	=>	"Default language for this Anwiki setup.",
	"setting_setup_i18n_langs_label"	=>	"Languages for contents, in order of preference",
	"setting_setup_i18n_langs_explain"	=>	"You will be able to translate contents in these languages.<br/>Warning, don't remove a language if you already have contents in this language.",
	"setting_setup_i18n_langs_single"	=>	"language",
	"setting_setup_i18n_langs_plural"	=>	"languages",
	"setting_setup_i18n_timezone_default_label"	=>	"Default timezone",
	"setting_setup_i18n_timezone_default_explain"	=>	"Default timezone for new users.",
	
	"setting_setup_cookies_label"		=>	"Cookies",
	"setting_setup_cookies_path_label"		=>	"Cookies path",
	"setting_setup_cookies_path_explain"		=>	"Standard value is '/'",
	"setting_setup_cookies_domain_label"		=>	"Cookies domain",
	"setting_setup_cookies_domain_explain"		=>	"Standard value is '.yourdomain.com' by replacing this with your own domain (don't forget the first dot at beginning!). For a local setup, let this empty.",
	
	"setting_setup_prefixes_label"		=>	"Multiple setups",
	"setting_setup_prefixes_explain"		=>	"If you are running multiple instances of Anwiki on the same host, set a different prefix for each instance to avoid conflicts.",
	"setting_setup_prefixes_session_label"		=>	"Session prefix",
	"setting_setup_prefixes_cookies_label"		=>	"Cookies prefix",
	
	
	//components	
	"setting_components_label"		=>	"Components",
	"setting_components_explain"		=>	"Select your favorite components for this Anwiki setup.",
	
	"setting_components_drivers_label"		=>	"Drivers",
	"setting_components_drivers_explain"		=>	"",
	"setting_components_drivers_storage_label"		=>	"Storage driver",
	"setting_components_drivers_storage_explain"		=>	"Storage driver stores and retrieves Anwiki contents.",
	"setting_components_drivers_sessions_label"		=>	"Sessions driver",
	"setting_components_drivers_sessions_explain"		=>	"Sessions driver manages users sessions.",
	"setting_components_drivers_users_label"		=>	"Users driver",
	"setting_components_drivers_users_explain"		=>	"Users driver handles users accounts.",
	"setting_components_drivers_acls_label"		=>	"ACLs driver",
	"setting_components_drivers_acls_explain"		=>	"ACLs driver manages users permissions.",
	"setting_components_modules_label"		=>	"Optional modules",
	"setting_components_modules_explain"		=>	"These modules can be enabled or disabled. You can download and install more modules from Anwiki website.",
	"setting_components_modules_plugins_label"		=>	"Plugins",
	"setting_components_modules_plugins_explain"		=>	"",
	"setting_components_modules_plugins_single"		=>	"plugin",
	"setting_components_modules_plugins_plural"		=>	"plugins",
	"setting_components_modules_contentclasses_label"		=>	"Content classes",
	"setting_components_modules_contentclasses_explain"		=>	"",
	"setting_components_modules_contentclasses_single"		=>	"content class",
	"setting_components_modules_contentclasses_plural"		=>	"content classes",
	"setting_components_modules_actions_label"		=>	"Actions",
	"setting_components_modules_actions_explain"		=>	"",
	"setting_components_modules_actions_single"		=>	"action",
	"setting_components_modules_actions_plural"		=>	"actions",
	
	
	//prefs	
	"setting_prefs_label"		=>	"Preferences",
	"setting_prefs_explain"		=>	"Settings for adjusting Anwiki features.",
	
	"setting_prefs_locks_label"		=>	"Edition locks",
	"setting_prefs_locks_explain"		=>	"Edition locks prevents that two users edit the same content at the same time.",
	"setting_prefs_locks_expiry_label"		=>	"Expiry",
	"setting_prefs_locks_expiry_explain"		=>	"Edition locks automatically expire if user is IDLE.",
	"setting_prefs_locks_renewrate_label"		=>	"Renew rate",
	"setting_prefs_locks_renewrate_explain"		=>	"Edition locks can be renewed when user is active. This maximum rate prevents flooding with too much renew requests.",
	"setting_prefs_locks_alert_label"		=>	"Expiry alert",
	"setting_prefs_locks_alert_explain"		=>	"Inactive users are warned when the lock is about to expire.",
	"setting_prefs_locks_refreshrate_label"		=>	"Refresh rate",
	"setting_prefs_locks_refreshrate_explain"		=>	"A countdown displays remaining time before the lock expires. This time is not refreshed in real time for saving client cpu time.",
	
	"setting_prefs_users_label"		=>	"User accounts",
	"setting_prefs_users_explain"		=>	"Preferences related to user accounts.",
	"setting_prefs_users_register_enabled_label"		=>	"Allow new registrations",
	"setting_prefs_users_register_enabled_explain"		=>	"You can temporarily disable new registrations.",
	"setting_prefs_users_register_enabled_checkbox"		=>	"Enable user account creation",
	"setting_prefs_users_unique_email_label"		=>	"Unique e-mail address",
	"setting_prefs_users_unique_email_explain"		=>	"If enabled, users can't be able to register with an e-mail address already in use.",
	"setting_prefs_users_unique_email_checkbox"		=>	"Require unique e-mail addresses",
	"setting_prefs_users_unique_displayname_label"		=>	"Unique display name",
	"setting_prefs_users_unique_displayname_explain"		=>	"If enabled, users can't choose a display name already in use.",
	"setting_prefs_users_unique_displayname_checkbox"		=>	"Require unique display names",
	"setting_prefs_users_change_displayname_label"		=>	"Change display name",
	"setting_prefs_users_change_displayname_explain"		=>	"If enabled, users can change their display name.",
	"setting_prefs_users_change_displayname_checkbox"		=>	"Allow changing display names",
	
	"setting_prefs_misc_label"		=>	"Miscellaneous",
	"setting_prefs_misc_explain"		=>	"Other settings related to preferences.",
	"setting_prefs_misc_history_expiration_label"		=>	"History expiration",
	"setting_prefs_misc_history_expiration_explain"		=>	"Pages history is automatically purged after this time.",
	"setting_prefs_misc_history_min_revisions_label"	=>	"When purging page history, always keep at least the",
	"setting_prefs_misc_history_min_revisions_number"	=>	"latest revisions",
	"setting_prefs_misc_history_min_revisions_explain"		=>	"It's a good idea to keep a few revisions of each page, so that page history will never be empty.",
	"setting_prefs_misc_view_untranslated_minpercent_label"		=>	"Hide contents to public when not translated at more than ",
	"setting_prefs_misc_view_untranslated_minpercent_explain"		=>	"You may want to hide untranslated content to public untill they are enough translated.<br/>If this value is set to 0%, anyone will see any contents, translated or not.<br/>If it's set to 100%, only translators will see untranslated contents.",
	"setting_prefs_misc_view_untranslated_minpercent_number"		=>	"%",
	"setting_prefs_misc_show_exectime_label"		=>	"Execution statistics",
	"setting_prefs_misc_show_exectime_explain"		=>	"Execution statistics (such as execution time, memory used...) can be displayed in footer in footer.",
	"setting_prefs_misc_show_exectime_checkbox"		=>	"Show execution statistics",
	
	
	
	//security	
	"setting_security_label"	=>	"Security",
	"setting_security_explain"	=>	"Adjust your security level by editing these settings.",
	
	"setting_security_https_label"	=>	"HTTPS",
	"setting_security_https_enabled_label"	=>	"HTTPS",
	"setting_security_https_enabled_explain"	=>	"Anwiki can use HTTPS for actions requiring privacy (such as register, login, edit...).<br/>Thought, it won't be used on public actions for better performances.",
	"setting_security_https_enabled_checkbox"	=>	"Enable HTTPS for sensitive actions",
	"setting_security_https_url_label"	=>	"HTTPS URL",
	"setting_security_https_url_explain"	=>	"URL with HTTPS enabled",
	
	"setting_security_reauth_label"	=>	"Reauth",
	"setting_security_reauth_explain"	=>	"If enabled, users will be regularly asked to re-authenticate when they execute sensitive actions (such as edit, delete...).<br/>If the delay is enough short, this limits potential damages caused by session stealing.",
	"setting_security_reauth_enabled_label"	=>	"Reauth",
	"setting_security_reauth_enabled_explain"	=>	"This feature may be not supported by your sessions driver.",
	"setting_security_reauth_enabled_checkbox"	=>	"Enable reauth",
	"setting_security_reauth_delay_label"	=>	"Delay",
	"setting_security_reauth_delay_explain"	=>	"Users won't be asked again to re-authenticate before this time.",
	
	"setting_security_session_label"	=>	"Session",
	"setting_security_session_explain"	=>	"These settings may help to protect against session stealing attacks.",
	"setting_security_session_resume_enabled_label"	=>	"Session remember",
	"setting_security_session_resume_enabled_explain"	=>	"If enabled, users will be able to choose the 'remember me' option when logging in.<br/>This option allows users to resume their sessions when they close their browser and come back later. Otherwise, session expires as soon as user closes his browser.",
	"setting_security_session_resume_enabled_checkbox"	=>	"Allow session resume",
	"setting_security_session_checkip_label"	=>	"IP",
	"setting_security_session_checkip_explain"	=>	"If enabled, session will be destroyed if the first three digit groups of user's IP address change, to prevent session hijacking.<br/>Warning, a few ISPs provide highly dynamic IPs changing range very often. Some users may have session problems with this feature enabled.",
	"setting_security_session_checkip_checkbox"	=>	"Enable IP tracing (first three digit groups)",
	"setting_security_session_checkclient_label"	=>	"Client",
	"setting_security_session_checkclient_explain"	=>	"If enabled, session will be destroyed if client informations change (such as HTTP_USER_AGENT).",
	"setting_security_session_checkclient_checkbox"	=>	"Enable client tracing",
	"setting_security_session_checkserver_label"	=>	"Server",
	"setting_security_session_checkserver_explain"	=>	"If enabled, session will be destroyed if server informations change (such as SERVER_SOFTWARE).<br/>You may need to disable it if doing load-balancing between multiple web servers.",
	"setting_security_session_checkserver_checkbox"	=>	"Enable server tracing",
	
	"setting_security_misc_label"	=>	"Miscellaneous",
	"setting_security_misc_explain"	=>	"Other settings related to security.",
	"setting_security_misc_phpeval_enabled_label"	=>	"PHP eval()",
	"setting_security_misc_phpeval_enabled_explain"	=>	"You may want to disable usage of the PHP function eval() for security reasons. Anwiki uses it for evaluating dynamic contents containing PHP code.<br/>If disabled, Anwiki won't be able to render these dynamic contents.",
	"setting_security_misc_phpeval_enabled_checkbox"	=>	"Enable dynamic contents evaluation",
	
	
	//system
	"setting_system_label"	=>	"System",
	"setting_system_explain"	=>	"System settings for developers.<br/>These settings may affect performances.",
	
	"setting_system_trace_label"	=>	"Execution trace",
	"setting_system_trace_explain"	=>	"Anwiki can generate an execution trace for debugging purpose.",
	"setting_system_trace_enabled_label"	=>	"Execution trace",
	"setting_system_trace_enabled_explain"	=>	"This option may affect execution performances.",
	"setting_system_trace_enabled_checkbox"	=>	"Enable execution trace",
	"setting_system_trace_view_ips_label"	=>	"Allowed IP addresses",
	"setting_system_trace_view_ips_explain"	=>	"Show execution trace for the following IP addresses",
	
	"setting_system_report_label"	=>	"Errors report",
	"setting_system_report_explain"	=>	"Anwiki can log and e-mail errors if anyone occurs.",
	"setting_system_report_file_enabled_label"	=>	"Errors log",
	"setting_system_report_file_enabled_explain"	=>	"If enabled, errors will be logged in a file.",
	"setting_system_report_file_enabled_checkbox"	=>	"Enable errors logging",
	"setting_system_report_mail_enabled_label"	=>	"Notification",
	"setting_system_report_mail_enabled_explain"	=>	"If enabled, an e-mail will be send when an error occurs.",
	"setting_system_report_mail_enabled_checkbox"	=>	"Enable e-mail notification",
	"setting_system_report_mail_addresses_label"	=>	"Recipients for errors notification",
	"setting_system_report_mail_addresses_explain"	=>	"Error notifications will be sent to these e-mail addresses.",
	
	"setting_system_cache_label"	=>	"Cache",
	"setting_system_cache_explain"	=>	"Multiple caching levels can be disabled for debugging purpose.<br/>Disabling caching options will drastically affect performances.",
	"setting_system_cache_cacheplugins_enabled_label"	=>	"Plugins mapping",
	"setting_system_cache_cacheplugins_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cachecomponents_enabled_label"	=>	"Components mapping",
	"setting_system_cache_cachecomponents_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cacheactions_enabled_label"	=>	"Actions mapping",
	"setting_system_cache_cacheactions_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cacheoutput_enabled_label"	=>	"Rendered output",
	"setting_system_cache_cacheoutput_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cacheblocks_enabled_label"	=>	"Cache blocks",
	"setting_system_cache_cacheblocks_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cacheloops_enabled_label"	=>	"Dynamic loops",
	"setting_system_cache_cacheloops_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_cachepages_enabled_label"	=>	"Page objects",
	"setting_system_cache_cachepages_enabled_checkbox"	=>	"Enable cache",
	"setting_system_cache_loops_auto_cachetime_label"	=>	"Default cache time for dynamic loops",
	"setting_system_cache_loops_auto_cachetime_explain"	=>	"If greater to 0, this default cache time will be used for dynamic loops which don't have set the 'cachetime' parameter.",
	"setting_system_cache_loops_auto_cacheblock_label"	=>	"Cache-blocks",
	"setting_system_cache_loops_auto_cacheblock_explain"	=>	"If enabled, cache-blocks will be enabled for dynamic loops which don't have set the 'cacheblock' parameter.",
	"setting_system_cache_loops_auto_cacheblock_checkbox"	=>	"Implicitely enable cache-blocks for dynamic loops",
	"setting_system_cache_symlinks_relative_label"	=>	"Symlinks",
	"setting_system_cache_symlinks_relative_explain"	=>	"Caching system uses symlinks which can be relative or absolute. If this option is enabled, relative symlinks will be used.<br/>Relative symlinks may not work on some hosts. If you encounter caching problems, try to disable this option.",
	"setting_system_cache_symlinks_relative_checkbox"	=>	"Use relative symlinks",
	
	"setting_system_misc_label"	=>	"Miscellaneous",
	"setting_system_misc_explain"	=>	"Other system settings.",
	"setting_system_misc_keepalive_delay_label"	=>	"Keepalive frequency",
	"setting_system_misc_keepalive_delay_explain"	=>	"Keepalive is a procedure which is executed at the given frequency. This procedure depends on session driver and plugins installed, but it's often used to keep user session alive and synchronized with external apps.<br/>If the frequency is too high, this may affect performances. If the frequency is too low, keepalive procedures may fail.",
	
	
	//advanced
	"setting_advanced_label"	=>	"Advanced settings",
	"setting_advanced_explain"	=>	"Advanced settings for experimented users.",
	"setting_advanced_statics_label"	=>	"Static files",
	"setting_advanced_statics_explain"	=>	"Static files (such as CSS, Javascript, icons...) can be accessed from a specific URL.<br/>Configuring a server dedicated to static files helps to reduce your main server load, if it has much traffic.<br/>Before enabling any option, make sure to replicate all static files to your alternative server, as explained in INSTALL-ADVANCED documentation.",
	"setting_advanced_statics_shared_label"	=>	"Default static files",
	"setting_advanced_statics_shared_explain"	=>	"",
	"setting_advanced_statics_shared_enabled_label"	=>	"Default static files access",
	"setting_advanced_statics_shared_enabled_explain"	=>	"",
	"setting_advanced_statics_shared_enabled_checkbox"	=>	"Use an alternative server",
	"setting_advanced_statics_shared_url_label"	=>	"URL for default static files",
	"setting_advanced_statics_shared_url_explain"	=>	"",
	"setting_advanced_statics_setup_label"	=>	"Setup's static files URL",
	"setting_advanced_statics_setup_explain"	=>	"",	
	"setting_advanced_statics_setup_enabled_label"	=>	"Setup's static files access",
	"setting_advanced_statics_setup_enabled_explain"	=>	"",
	"setting_advanced_statics_setup_enabled_checkbox"	=>	"Use an alternative server",
	"setting_advanced_statics_setup_url_label"	=>	"URL for Setup's static files URL",
	"setting_advanced_statics_setup_url_explain"	=>	"",
	
);
?>