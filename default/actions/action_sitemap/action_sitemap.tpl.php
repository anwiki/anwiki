<?php

class AnwTemplateDefault_action_sitemap extends AnwTemplateOverride_global
{
	function begin($sFilters)
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	$sFilters
	<ul>
EOF;
		return $HTML;
	}
	
	function rowGroupOpen()
	{
		$HTML = <<<EOF

	<li style="margin:10px 0px">
		<ul>
EOF;
		return $HTML;
	}
	
	function rowGroupClose()
	{
		$HTML = <<<EOF

		</ul>
	</li>
EOF;
		return $HTML;
	}
	
	function rowTranslation($oPage, $aoPageNavEntries)
	{	
		if ($oPage->isTranslated())
		{
			$sUntranslated = $this->g_("translation_complete");
			$sColor = 'green';
		}
		else
		{
			$nTranslatedPercent = $oPage->getTranslatedPercent();
			$sUntranslated = $this->g_("translation_progress",array("percent"=>$nTranslatedPercent));
			if ($nTranslatedPercent < 25)
			{
				$sColor = 'red';
			}
			else
			{
				$sColor = 'orange';
			}
		}
		$HTML = <<<EOF

		<li>
			<div class="pageactions">
EOF;

		foreach ($aoPageNavEntries as $oEntry)
		{
			$sLink = $oEntry->getPageLink($oPage);
			$sTitle = $oEntry->getTitle();
			$sImg = $oEntry->getImg();
			$HTML .= <<<EOF

				<a href="{$this->xQuote($sLink)}" class="pageaction" title="{$this->xQuote($sTitle)}" style="background-image:url('$sImg')"></a>
EOF;
		}

		$HTML .= <<<EOF
			</div>
			{$oPage->link()} (<span style="color:$sColor">$sUntranslated</span>)
		</li>
EOF;
		return $HTML;
	}
	
	function end()
	{
		$HTML = <<<EOF

	</ul>
EOF;
		return $HTML;
	}
}

?>