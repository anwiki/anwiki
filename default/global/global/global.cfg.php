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
 * Anwiki default configuration settings.
 * @package Anwiki
 * @version $Id: global.cfg.php 274 2010-09-06 21:12:04Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
/*
 * ANWIKI DEFAULT CONFIGURATION
 * DO NOT EDIT THIS CONFIGURATION FILE !
 * Edit _override/global/global.cfg.php instead !
 */
$cfg = array (

	"setup"	=>	array(
		"location" => array(		
			"urlroot"	=>	"",
			"homepage"	=>	"en/home",
			"friendlyurl_enabled"	=>	false,
			"noindexurl_enabled"	=>	false,
			"website_name"	=>	"Anwiki"
		),
		"i18n" => array(
			"lang_default" => "en",
			"langs" => array("en", "fr", "ar", "de", "it", "pl", "zh_CN"),
			"timezone_default" => 2
		),
		"cookies" => array(
			"path"	=>	"/",
			"domain"	=>	".example.com",
		),
		"prefixes" => array(
			"session" => "anwiki_",
			"cookies" => "anwiki_",	
		)
	),
	
	"components" => array(
		"drivers" => array(
			"storage" => "mysql",
			"sessions" => "mysql",
			"users" => "mysql",
			"acls" => "configrules",
		),
		"modules" => array(
			"plugins" => array(),
			"contentclasses" => array("page", "news", "newscategory", "feed", "menu", "trfile"),
			"actions" => array("edit", "translate", "create", "diff", "export", "import", "lastchanges", 
								"newtranslation", "newtranslations", "output", "register", "rename", "changelang", "revert", "revertpage", "sitemap",
								"untranslated", "history", "delete", "duplicate")
		)
	),
	
	"prefs" => array(
		"locks" => array(
			"expiry" => 600,
			"renewrate" => 20,
			"alert" => 180,
			"refreshrate" => 5,
		),
		"users" => array(
			"register_enabled" => true,
			"unique_email" => true,
			"unique_displayname" => true,
			"change_displayname" => true
		),
		"misc" => array(
			"history_expiration" => 2592000,
			"history_min_revisions" => 1,
			"view_untranslated_minpercent" => 15,
			"show_exectime" => true
		)
	
	),
	
	"security" => array(
		"https" => array(
			"enabled" => false,
			"url" => "https://"
		),
		"reauth" => array(
			"enabled" => true,
			"delay" => 5400
		),
		"session" => array(
			"resume_enabled" => true,
			"checkip" => true,
			"checkclient" => true,
			"checkserver" => false
		),
		
		"misc" => array(
			"phpeval_enabled" => true
		)
	),
	
	"system" => array(
		"trace" => array(
			"enabled" => false,
			"view_ips" => array(),
		),
		"report" => array(
			"file_enabled" => true,
			"mail_enabled" => false,
			"mail_addresses" => array()
		),
		"cache" => array(
			"cacheplugins_enabled"		=>	true,
			"cachecomponents_enabled"	=>	true,
			"cacheactions_enabled"		=>	true,
			"cacheoutput_enabled"		=>	true,
			"cacheblocks_enabled"		=>	true,
			"cacheloops_enabled"		=>	true,
			"cachepages_enabled"		=>	true,
			"loops_auto_cachetime"		=>	30,
			"loops_auto_cacheblock"		=>	true,
			"symlinks_relative" => true,
		),
		"misc" => array(
			"keepalive_delay"	=>	300,
		)
	),
	
	
	"advanced" => array(
		"statics" => array(
			"shared" => array(
				"enabled" => false,
				"url" => "http://"
			),
			"setup" => array(
				"enabled" => false,
				"url" => "http://"
			)
		),
	),	
	
);
?>