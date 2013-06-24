<?php

class AnwTemplateDefault_contentclass_news extends AnwTemplateOverride_global
{
	function showNews($sNewsTitle, $sNewsIntro, $sNewsBody, $sNewsDate, $sCategoriesHtml, $sNewsLang)
	{
		$HTML = <<<EOF

<h1>{$this->xText($sNewsTitle)}</h1>

<div class="news_date">{$this->t_('local_news_date',array('date'=>$sNewsDate),$sNewsLang)}</div>
$sCategoriesHtml

<div class="news_intro">
	$sNewsIntro
</div>

<div class="news_body">
	$sNewsBody
</div>
EOF;
		return $HTML;
	}
	
	function categoriesStart()
	{
		$HTML = <<<EOF
	<div class="news_categories">
EOF;
		return $HTML;
	}
	
	function categoriesItem($sCategoryTitle, $sCategoryUrl)
	{
		$HTML = <<<EOF
		<span><a href="{$this->xQuote($sCategoryUrl)}">{$this->xText($sCategoryTitle)}</a></span>
EOF;
		return $HTML;
	}
	
	function categoriesEnd()
	{
		$HTML = <<<EOF
	</div>
EOF;
		return $HTML;
	}
}

?>