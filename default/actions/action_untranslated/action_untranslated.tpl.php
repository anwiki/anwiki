<?php

class AnwTemplateDefault_action_untranslated extends AnwTemplateOverride_global
{
	function begin()
	{
		$HTML = <<<EOF

	<h1>{$this->xText($this->t_('title'))}</h1>
EOF;
		return $HTML;
	}
		
	function nav($sRssLink)
	{
		$HTML = <<<EOF

	<div class="untranslated_nav">
		<a class="rss" href="{$this->xQuote($sRssLink)}">{$this->xText($this->g_('rss_export'))}</a>
	</div>
EOF;
		return $HTML;
	}
	
	function openTable()
	{
		$HTML = <<<EOF

	<table class="untranslatedpages tablepages">
		<tr class="line0">
			<th>{$this->xText($this->g_('page_name'))}</th>
			<th>{$this->xText($this->g_('page_progress'))}</th>
			<th>{$this->xText($this->g_('page_contentclass'))}</th>
			<th>{$this->xText($this->g_('page_time'))}</th>
		</tr>
EOF;
		return $HTML;
	}
	
	function untranslatedPageLine($oPage)
	{
		static $n = 0;
		$n = ($n%2)+1;
		$sCss = 'line'.$n;
		
		$sTime = Anwi18n::dateTime($oPage->getTime());
		$sLnkPage = $oPage->link();
		
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
		
		$sContentClass = $oPage->getPageGroup()->getContentClass()->getLabel();
		
		$HTML = <<<EOF

		<tr class="$sCss">
			<td class="pagename">$sLnkPage</td>
			<td class="pageprogress"><span style="color:$sColor">$sUntranslated</span></td>
			<td class="pagecontentclass">$sContentClass</td>
			<td class="pagetime">$sTime</td>
		</tr>
EOF;
		return $HTML;
	}
	
	function closeTable()
	{
		$HTML = <<<EOF

	</table>
EOF;
		return $HTML;
	}
}

?>