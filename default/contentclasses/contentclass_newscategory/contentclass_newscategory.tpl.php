<?php

class AnwTemplateDefault_contentclass_newscategory extends AnwTemplateOverride_global
{
	function showNewscategory($sNewsCategoryTitle, $sNewsCategoryIntro, $sNewsListHtml, $sLang)
	{
		$HTML = <<<EOF

<h1>{$this->t_('local_newscategory_title', array('categorytitle'=>$this->xText($sNewsCategoryTitle)), $sLang)}</h1>

<div class="newscategory_intro">
	$sNewsCategoryIntro
</div>

$sNewsListHtml
EOF;
		return $HTML;
	}
	
		
	function newsListStart()
	{
		$HTML = <<<EOF
	<div class="newscategory_newslist">
EOF;
		return $HTML;
	}
	
	function newsListItem($sNewsTitle, $sNewsIntro, $sNewsDate, $sNewsUrl, $sNewsLang)
	{
		$HTML = <<<EOF
	<div class="news">
		<h2><span class="news_date">$sNewsDate</span> $sNewsTitle</h2>
		<p>$sNewsIntro</p>
		<a href="{$this->xQuote($sNewsUrl)}">{$this->t_('local_news_readmore', array(), $sNewsLang)}</a>
	</div>
EOF;
		return $HTML;
	}
	
	function newsListEnd()
	{
		$HTML = <<<EOF
	</div>
EOF;
		return $HTML;
	}
}

?>