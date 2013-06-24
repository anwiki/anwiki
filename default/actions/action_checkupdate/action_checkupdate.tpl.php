<?php

class AnwTemplateDefault_action_checkUpdate extends AnwTemplateOverride_global
{
	function showCheckUpdate($sFrameTarget,$sWebsite)
	{
		$sWebsite = '<a href="'.$sWebsite.'" target="_blank">'.$sWebsite.'</a>';
		$HTML = <<<EOF

	<div class="explain">
		{$this->t_('explain_p1')}<br/>
		{$this->t_('explain_p2',array('website'=>$sWebsite))}<br/>
	</div>
	
	<div style="text-align:center">
		<iframe src="{$this->xQuote($sFrameTarget)}" style="border:1px solid #000; width:90%; height:400px; margin:0px auto;"></iframe>
	</div>
EOF;
		return $HTML;
	}
}

?>