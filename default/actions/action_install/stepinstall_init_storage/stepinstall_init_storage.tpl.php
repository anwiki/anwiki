<?php

class AnwTemplateDefault_stepinstall_init_storage extends AnwTemplateDefaultStepInstallInitializeComponent
{
	function homePageBody($sPageLang, $sWebsite)
	{
		$sWebsite = '<a href="'.$sWebsite.'">'.$sWebsite.'</a>';
		$HTML = <<<EOF

<h1>{$this->t_("local_homepage_title", array(), $sPageLang)}</h1>
<p>{$this->t_("local_homepage_body_p1", array(), $sPageLang)}<br/>
{$this->t_("local_homepage_body_p2", array(), $sPageLang)}</p>
<p>{$this->t_("local_homepage_body_p_website", array('website'=>$sWebsite), $sPageLang)}</p>
EOF;
		return $HTML;
	}
	
	function homePageHead($sPageLang)
	{
		$HTML = <<<EOF

EOF;
		return $HTML;
	}
}

?>