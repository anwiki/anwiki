<?php

class AnwTemplateDefault_action_management extends AnwTemplateOverride_global
{
	function managementHome($aoManagementNavEntries)
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	<div class="explain">
		{$this->t_('explain')}
	</div>
EOF;
		foreach ($aoManagementNavEntries as $oEntry)
		{
			$sLink = $oEntry->getLink();
			$sTitle = $oEntry->getTitle();
			$sDescription = $oEntry->getDescription();
			$sImg = ($oEntry->getImg() ? '<img src="'.AnwUtils::xQuote($sImg).'"/>' : '' );
			$HTML .= <<<EOF

	<div style="float:left;width:45%; margin:2%;">
		<h2><a href="{$this->xQuote($sLink)}" style="color:#000; text-decoration:none;">$sImg{$this->xText($sTitle)}</a></h2>
		<p>{$this->xText($sDescription)}</p>
	</div>
EOF;
		}
		
		return $HTML;
	}
}

?>