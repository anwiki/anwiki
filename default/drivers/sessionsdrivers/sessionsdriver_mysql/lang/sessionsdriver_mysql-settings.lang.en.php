<?php
$lang = array(
	'setting_sessions_label'				=>	"Session preferences",

	'setting_sessions_public_label'	=>	"Public sessions",
	'setting_sessions_public_explain'	=>	"The following settings apply to any public session which don't have the 'remember me' option",
	'setting_sessions_public_duration_idle_label'	=>	"Session expiry when IDLE",
	'setting_sessions_public_duration_idle_explain'	=>	"Inactive sessions will expire after this time, without closing browser (to limit damages from an opened session left on a public computer)",
	
	'setting_sessions_public_duration_max_label'	=>	"Maximum active session duration",
	'setting_sessions_public_duration_max_explain'	=>	"Maximum session duration time, without closing browser, even if user is still active (to limit damages from an opened session left on a public computer, and found by a bad guy starting using it)",
	
	'setting_sessions_resume_label'	=>	"Sessions with 'remember me' option",
	'setting_sessions_resume_delay_max_label'	=>	"Maximum resume delay",
	'setting_sessions_resume_delay_max_explain'	=>	"Maximum delay for resuming a session created with 'remember me' feature.",
	
	'err_setting_sessions_duration_idle_gt_max' =>	"Session expiry when IDLE can't be greater than maximum session duration.",
	
	
);
?>