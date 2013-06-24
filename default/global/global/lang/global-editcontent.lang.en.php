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
 * Global english translations for content edition.
 * @package Anwiki
 * @version $Id: global-editcontent.lang.en.php 333 2010-09-29 20:28:05Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

$lang = array (
	"err_contentfield_xml_invalid"	=>	"Malformed XML : %xmlerrors%",
	"err_contentfield_date_format"	=>	"Invalid date. Expected format : %dateformat%",
	"err_contentfield_system_directory_notfound"	=>	"Directory not found",
	"err_contentfield_string_tags" => "Characters '&gt;' and '&lt;' are forbidden, please use &amp;lt; and &amp;gt;",
	"err_contentfield_string_nomatch_pattern"	=>	"The value must match the following pattern: %allowedpattern%",
	"err_contentfield_string_nomatch_patterns"	=>	"The value must match one of the following patterns: %allowedpatterns%",
	"err_contentfield_string_forbidden_pattern"	=>	"The value must not match the following pattern: %forbiddenpattern%",
	"err_contentfield_url_typeforbidden" => "You can't use %typeforbidden% here, you should use %typesallowed%",
	"err_contentfield_system_directory_testfilenotfound"	=>	"The required file %file% was not found in this directory",
	"err_contentfield_user_notfound"	=>	"User not found",
	"err_contentfield_integer_numeric" => "An integer is expected",
	"err_contentfield_number_min" => "Minimum value : %minval%",
	"err_contentfield_number_max" => "Maximum value : %maxval%",
	"err_contentfield_enum_values" => "One of the selectable values is expected",
	"err_contentfield_dynamic_parsing_disabled"	=>	"Dynamic parsing tags are forbidden for this field : %tags%",
	"err_contentfield_dynamic_php_disabled"	=>	"PHP edition is forbidden for this field",
	"err_contentfield_acl_php"	=>	"You don't have the permission to edit contents having PHP code.",
	"err_contentfield_acl_js"	=>	"You don't have the permission to edit potentially unsafe contents, such as javascript and iframes.",
	"err_contentfield_mysqlconnexion_dbconnect"	=>	"Connexion failed : %details%",
	"err_contentmultiplicity_single_notfound" => "No value found",
	"err_contentmultiplicity_single_unexpected" => "More than one value found (bug?)",
	"err_contentmultiplicity_multiple_min" => "Minimum number of %elementname% : %minval%",
	"err_enabling_unconfigured_component"	=>	"The component '%componentname%' can't be enabled untill it is configured. Please configure it before enabling it.",
	"contentfieldcollapsed_edit"	=>	"Edit %fieldname%",
	
	"contentfield_ip_address_label"	=>	"IP address",
	"contentfield_ip_address_single"	=>	"IP address",
	"contentfield_ip_address_plural"	=>	"IP addresses",
	
	"contentfield_email_address_label"	=>	"E-mail address",
	"contentfield_email_address_single"	=>	"e-mail address",
	"contentfield_email_address_plural"	=>	"e-mail addresses",
	
	"contentfield_url_label"	=>	"URL",
	"contentfield_url_single"	=>	"URL",
	"contentfield_url_plural"	=>	"URLs",
	"contentfield_url_buttonopen"	=>	"Go!",
	
	"contentfield_link_label"	=>	"Hypertext link",
	"contentfield_link_single"	=>	"link",
	"contentfield_link_plural"	=>	"links",
	"contentfield_link_title_label"	=>	"Link title",
	"contentfield_link_url_label"	=>	"Link URL",
	"contentfield_link_target_label"	=>	"Target",
	"contentfield_link_target_self"	=>	"Same window",
	"contentfield_link_target_blank"	=>	"New window",
	"contentfield_link_collapsed"	=>	"%link% (%url%, %target%)",
	
	"contentfield_daterange_label"	=>	"Date range",
	"contentfield_daterange_single"	=>	"date range",
	"contentfield_daterange_plural"	=>	"date ranges",
	"contentfield_daterange_begin_label"	=>	"Begin",
	"contentfield_daterange_end_label"	=>	"End",
	"contentfield_daterange_collapsed"	=>	"Date from %begin% to %end%",
	
	"contentfield_mysqlconnexion_label"	=>	"MySQL connexion",
	"contentfield_mysqlconnexion_single"	=>	"MySQL connexion",
	"contentfield_mysqlconnexion_plural"	=>	"MySQL connexions",
	"contentfield_mysqlconnexion_collapsed"	=>	"Connecting as user %user% at host %host%. Accessing database %database%, tables prefixed with %prefix%",
	"contentfield_mysqlconnexion_user_label"	=>	"MySQL user",
	"contentfield_mysqlconnexion_password_label"	=>	"MySQL password",
	"contentfield_mysqlconnexion_host_label"	=>	"MySQL server",
	"contentfield_mysqlconnexion_host_explain"	=>	"Enter a hostname or an IP address.<br/>You can specifying a port, using the syntax: mysqlserver:3306",
	"contentfield_mysqlconnexion_database_label"	=>	"MySQL database",
	"contentfield_mysqlconnexion_prefix_label"	=>	"Tables prefix",
	"contentfield_mysqlconnexion_prefix_explain"	=>	"This optional prefix will be used for all tables",
	
	
	"datatype_xml_tip" => "Any valid XML is allowed",
	"datatype_xhtml_tip" => "Any valid XHTML is allowed",
	"datatype_date_tip"	=>	"Format : %dateformat%",
	"datatype_string_tip"	=>	"Any text excepted the following characters : %forbiddenchars%",
	"datatype_url_tip_any"	=>	"Any valid URL",
	"datatype_url_tip_typeallowed"	=>	"Any %allowedtype%",
	"datatype_url_tip_listallowed"	=>	"Any valid URL of the following types: %allowedtypes%",
	"datatype_url_typelabel_absolute"	=>	"absolute URL",
	"datatype_url_typelabel_relative"	=>	"relative URL",
	"datatype_url_typelabel_full"	=>	"full URL",
	"datatype_ip_address_tip"	=>	"A valid IP address",
	"datatype_email_address_tip"	=>	"A valid e-mail address",
	"datatype_system_directory_tip"	=>	"An existing directory, ending with a slash (/)",
	"datatype_userlogin_tip"	=>	"An existing user's login",
	"datatype_boolean_tip" =>	"A checkbox",
	"datatype_boolean_legend" =>	"Yes",
	"datatype_boolean_collapsed_true"	=>	"Yes",
	"datatype_boolean_collapsed_false"	=>	"No",
	"datatype_number_tip_between"	=>	"Any %numbertype% between %minval% and %maxval%",
	"datatype_number_tip_greater"	=>	"Any %numbertype% greater than %minval%",
	"datatype_number_tip_lower"	=>	"Any %numbertype% lower than %maxval%",
	"datatype_number_tip_any"	=>	"Any %numbertype%",
	"datatype_number_integer"	=>	"integer",
	"datatype_delay_tip"	=>	"A delay in hours, minutes, seconds",
	"datatype_delay_days"	=>	"days",
	"datatype_delay_hours"	=>	"hours",
	"datatype_delay_minutes"	=>	"minutes",
	"datatype_delay_seconds"	=>	"seconds",
	"datatype_number_float"	=>	"float",
	"datatype_enum_tip"	=>	"One of the selectable values",
	"datatype_geoposition_tip" => "Any geographic position",
	
	
	"contentmultiplicity_multiple_tip_between"	=>	"Between %mincount% and %maxcount% %contentfield%",
	"contentmultiplicity_multiple_tip_greater"	=>	"At least %mincount% %contentfield%",
	"contentmultiplicity_multiple_tip_lower"		=>	"At most %maxcount% %contentfield%",
	"contentmultiplicity_multiple_tip_any"		=>	"Any count of %contentfield%",
	"contentmultiplicity_multiple_tip_fixed"		=>	"Exactly %count% %contentfield%",
	
	"contentmultiplicity_multiple_contentfield_add"	=>	"Add %fieldname%",
	"contentmultiplicity_multiple_contentfield_del"	=>	"Remove %fieldname%",
	
	"editsettings_info"	=>	"These settings are stored in the file %filename%.<br/>Feel free to edit this file directly from file system.",
	
	"dependancy_unsolved"	=>	"The component '%component1%' has unsolved dependancy problems with '%component2%', sorry.",
	"dependancy_requirement"	=>	"The component '%component1%' requires the component '%component2%' to be enabled.",
	"dependancy_conflict"	=>	"The component '%component1%' is conflicting with '%component2%'. These two components are not compatible, sorry.",
	"dependancy_conflict_exceed"	=>	"The component '%component1%' has dependancies conflicts with '%component2%', which are too complex for me to solve, sorry.",
	"dependancy_conflict_unsolved"	=>	"The component '%component1%' has unsolved dependancies conflicts with '%component2%', sorry."

);
?>