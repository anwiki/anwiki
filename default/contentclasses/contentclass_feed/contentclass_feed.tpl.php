<?php

class AnwTemplateDefault_contentclass_feed extends AnwTemplateOverride_global
{
	function feedInfo($sFeedTitle, $sFeedDescription, $sUrlRss, $sHtmlItems, $sPageLang)
	{
		$HTML = <<<EOF

<div class="feedinfo">
	<h1>{$this->t_('local_title',array(),$sPageLang)} {$this->xText($sFeedTitle)}</h1>
	<div class="feed_description">
		$sFeedDescription
	</div>
	<a href="{$this->xQuote($sUrlRss)}" class="rss">{$this->t_('local_go_rss',array(),$sPageLang)}</a>
	<ul>
$sHtmlItems
	</ul>
</div>
EOF;
		return $HTML;
	}
	
	function feedItem($sItemTitle, $sItemLink)
	{
		$HTML = <<<EOF

		<li><a href="{$this->xQuote($sItemLink)}">{$this->xText($sItemTitle)}</a></li>
EOF;
		return $HTML;
	}
}

?>