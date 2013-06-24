<?php
$lang = array(

//--------------------------
// Rules tab
//--------------------------
	'setting_acls_label'				=>	"ACLs",
	'setting_acls_explain'				=>	"Defining users permissions...",
	
	'setting_acls_rules_label'				=>	"ACL rules",
	'setting_acls_rules_explain'			=>	"By default, user have no permission. Each rule is cumulative: if several rules applies to the same user, user will get permissions granted in both rules.",
	'setting_acls_rules_single'				=>	"ACL rule",
	'setting_acls_rules_plural'				=>	"ACL rules",
	
	'setting_acls_rules_permissionuser_label'	=>	"Targeted users",
	'setting_acls_rules_permissionuser_explain'	=>	"This rule will apply to users matching the following criteria.",
	
	'setting_acls_rules_permissionuser_policy_label'	=>	"Give permissions to",
	'setting_acls_rules_permissionuser_policy_explain'	=>	"",
	'policy_all_users'	=>	"Everyone",
	'policy_logged_users' => "Logged-in users",
	'policy_selected_users' => "Selected users",
	
	'setting_acls_rules_permissionuser_users_label'	=>	"Allowed users",
	'setting_acls_rules_permissionuser_users_explain'	=>	"",
	'setting_acls_rules_permissionuser_users_single'	=>	"allowed user",
	'setting_acls_rules_permissionuser_users_plural'	=>	"allowed users",
	
	'err_setting_policy_selected_users_nouser'	=>	"Select at least one user, or choose another policy.",
	'err_setting_policy_selected_users_notselected'	=>	"You can't select users with this policy.",
	
	'err_setting_policy_selected_actions_noaction'	=>	"Select at least one action, or choose another policy.",
	'err_setting_policy_selected_actions_notselected'	=>	"You can't select actions with this policy.",
	
	'err_setting_policy_selected_langs_nolang'	=>	"Select at least one lang, or choose another policy.",
	'err_setting_policy_selected_langs_notselected'	=>	"You can't select lang with this policy.",
	
	'setting_acls_rules_permissionactionglobal_label'		=>	"Allowed global actions",
	'setting_acls_rules_permissionactionglobal_explain'	=>	"",
	'setting_acls_rules_permissionactionglobal_single'	=>	"permission on content",
	'setting_acls_rules_permissionactionglobal_plural'	=>	"permissions on content",
	
	'setting_acls_rules_permissionactionglobal_policy_label'		=>	"Give permissions for running",
	'policy_all_actionsglobal'	=>	"All global actions",
	'policy_selected_actionsglobal'	=>	"Selected global actions",
	'policy_no_actionsglobal'	=>	"No global action",
	
	'setting_acls_rules_permissionactionglobal_actions_label'		=>	"Selected global actions",
	'setting_acls_rules_permissionactionglobal_actions_explain'	=>	"",
	'setting_acls_rules_permissionactionglobal_actions_single'	=>	"allowed global action",
	'setting_acls_rules_permissionactionglobal_actions_plural'	=>	"allowed global actions",
	
	'setting_acls_rules_permissioncontent_label'	=>	"Rules for contents",
	'setting_acls_rules_permissioncontent_explain'	=>	"By default, users have no permission.",
	'setting_acls_rules_permissioncontent_single'	=>	"Rule for contents",
	'setting_acls_rules_permissioncontent_plural'	=>	"Rules for contents",
	
	'setting_acls_rules_permissioncontent_permissionactionpage_policy_label'	=>	"Give permissions for running",
	'policy_all_actionspage'	=>	"All page actions",
	'policy_selected_actionspage'	=>	"Selected page actions",
	
	
	'setting_acls_rules_permissioncontent_permissionactionpage_label'	=>	"Allowed actions on contents",
	'setting_acls_rules_permissioncontent_permissionactionpage_explain'	=>	"",
	
	'setting_acls_rules_permissioncontent_permissionactionpage_actions_label'	=>	"Selected actions on contents",
	'setting_acls_rules_permissioncontent_permissionactionpage_actions_explain'	=>	"",
	'setting_acls_rules_permissioncontent_permissionactionpage_actions_single'	=>	"allowed action on contents",
	'setting_acls_rules_permissioncontent_permissionactionpage_actions_plural'	=>	"allowed actions on contents",
	
	'setting_acls_rules_permissioncontent_contentmatch_label'		=>	"Optional contents filter",
	'setting_acls_rules_permissioncontent_contentmatch_explain'		=>	"A content can be match by its name, its language and its content-class. If no filter is created, all contents will be matched.",
	'setting_acls_rules_permissioncontent_contentmatch_single'		=>	"matching content",
	'setting_acls_rules_permissioncontent_contentmatch_plural'		=>	"matching contents",
	
	'setting_acls_rules_permissioncontent_contentmatch_name_label'	=>	"Name",
	'setting_acls_rules_permissioncontent_contentmatch_name_explain'	=>	"You can match contents by name. Enter either a full name or a regexp. If no name is entered, all content names will match.",
	'setting_acls_rules_permissioncontent_contentmatch_name_single'	=>	"content name",
	'setting_acls_rules_permissioncontent_contentmatch_name_plural'	=>	"content names",
	
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_label'	=>	"Language",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_explain'	=>	"You can match contents by language.",
	
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_policy_label'	=>	"Match contents in",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_langs_explain'	=>	"",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_langs_label'	=>	"Selected languages",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_langs_explain'	=>	"",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_langs_single'	=>	"language",
	'setting_acls_rules_permissioncontent_contentmatch_permissionlang_langs_plural'	=>	"languages",
	'policy_all_langs'	=>	"All languages",
	'policy_selected_langs'	=>	"Selected languages",
	
	

//--------------------------
// Privileges tab
//--------------------------
	
	'setting_privileges_label'	=>	"Privileges",
	'setting_privileges_explain'	=>	"<b>!!! SECURITY WARNING !!! Do NOT MODIFY these settings unless you know exactly what you are doing. Only give privileges to absolutely trusted users.<br/>If you remove your own privilege, you won't be able to come back here!</b>",
	
	'setting_privileges_privilegerules_label'	=>	"Privileges rules",
	'setting_privileges_privilegerules_explain'	=>	"By default, users have no privilege.",
	'setting_privileges_privilegerules_single'	=>	"privileges rule",
	'setting_privileges_privilegerules_plural'	=>	"privileges rules",
	
	'setting_privileges_privilegerules_users_label'	=>	"Privileged users",
	'setting_privileges_privilegerules_users_explain'	=>	"Only give privileges to absolutely trusted users.",
	'setting_privileges_privilegerules_users_single'	=>	"privileged user",
	'setting_privileges_privilegerules_users_plural'	=>	"privileged users",
	
	'setting_privileges_privilegerules_php_edition_label'		=>	"PHP edition",
	'setting_privileges_privilegerules_php_edition_explain'	=>	"Allow PHP edition. User is potentially able to get configured drivers passwords and run destructive shell commands.",
	'setting_privileges_privilegerules_php_edition_checkbox'	=>	"Allow PHP edition",
	
	'setting_privileges_privilegerules_unsafe_edition_label'		=>	"Unsafe edition",
	'setting_privileges_privilegerules_unsafe_edition_explain'	=>	"Allow user to edit potentially unsafe code, such as JavaScript, iframes...",
	'setting_privileges_privilegerules_unsafe_edition_checkbox'	=>	"Allow potentially dangerous edition",
	
	'setting_privileges_privilegerules_is_admin_label'		=>	"Super administrator",
	'setting_privileges_privilegerules_is_admin_explain'	=>	"Super administrators can run admin actions (such as configuration and upgrade scripts). They also have full permissions everywhere, they can execute any action on any content.",
	'setting_privileges_privilegerules_is_admin_checkbox'	=>	"Consider as super administrator",
	
//--------------------------
// Others
//--------------------------

	'init_rules_intro_explain'	=>	"The system will set up a few permissions by default. If you wish, you can customize it later.",
	'init_rules_intro_list'	=>	"Anyone will have permission for executing:",
	'init_rules_actionglobal'	=>	"The following global actions: %actions%",
	'init_rules_actionpage'	=>	"The following actions on any content: %actions%",
	'init_rules_action_none'	=>	"(none)",
);
?>