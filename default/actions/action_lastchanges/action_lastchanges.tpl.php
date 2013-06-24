<?php

class AnwTemplateDefault_action_lastchanges extends AnwTemplateOverride_global
{
	function lastchangesHeader($sTitle)
	{
		$HTML = <<<EOF

	<h1>$sTitle</h1>
EOF;
		return $HTML;
	}
	
	function lastchangesLine($sTime, $sType, $sComment, $sInfo, $sUserLogin, $sLnkPage, $sLnkDiff, $sLnkHistory=false, $sLnkRevert, $sPageName, $sPageLang)
	{
		static $n = 0;
		$n = ($n%2)+1;
		$sCss = 'line'.$n;
		
		$sImgFlag = Anwi18n::imgFlag($sPageLang);
		$sInfo = ($sInfo == "" ? "" : "<div class=\"changeinfo\">".$sInfo."</div>");
		
		// info and comment are already escaped
		$HTML = <<<EOF

		<tr class="{$this->xQuote($sCss)}">
			<td class="changetime">$sTime</td>
			<td class="changepageoriginal">{$sImgFlag}{$this->xText($sPageName)}</td>
			<td class="changetype">{$sType}{$sInfo}</td>
			<td class="changecomment">{$sComment}</td>
			<td class="changelogin">{$this->xText($sUserLogin)}</td>
			<td class="changepage">$sLnkPage</td>
			<td class="changediff">$sLnkDiff</td>
EOF;
		if ($sLnkHistory)
		{
			$HTML .= <<<EOF

			<td class="changehistory">$sLnkHistory</td>
EOF;
		}
		$HTML .= <<<EOF

			<td class="changerevert">$sLnkRevert</td>
		</tr>
EOF;
		return $HTML;
	}
	
	function lastchangesFooter()
	{
		$HTML = <<<EOF

	</table>
EOF;
		return $HTML;
	}
	
	function filterBefore($sFormAction)
	{
		$HTML = <<<EOF

	<form action="{$this->xQuote($sFormAction)}" method="get">
EOF;
		return $HTML;
	}
	
	function filterAfter($bGrouped, $nPageId, $nPageGroupId, $sRssLink, $sHistoryPageGroupLink)
	{
		if ($bGrouped)
		{
			$sSelected1 = ' checked="checked"';
			$sSelected0 = '';
		}
		else
		{
			$sSelected1 = '';
			$sSelected0 = ' checked="checked"';
		}
		$sGetActionName = AnwAction::GET_ACTIONNAME;
		$sFilterLabel = $this->t_("filter_button");
		$sHistoryPageGroupLabel = $this->t_("history_pagegroup_link");
		$HTML = <<<EOF

	<div class="anwfilter">
		<fieldset><legend>{$this->t_('filter_group')}</legend>
			<input type="radio" id="fg_1" name="fg" value="1"$sSelected1/><label for="fg_1">{$this->t_('filter_group_yes')}</label> 
			<input type="radio" id="fg_0" name="fg" value="0"$sSelected0/><label for="fg_0">{$this->t_('filter_group_no')}</label> 
			<input type="hidden" name="{$this->xQuote($sGetActionName)}" value="lastchanges"/>
			<input type="hidden" name="page" value="{$this->xQuote($nPageId)}"/>
			<input type="hidden" name="pagegroup" value="{$this->xQuote($nPageGroupId)}"/>
			<input type="submit" class="insubmit" value="{$this->xQuote($sFilterLabel)}"/>
			<a class="rss" href="{$this->xQuote($sRssLink)}">{$this->g_('rss_export')}</a>
		</fieldset>
EOF;
		if ($sHistoryPageGroupLink)
		{
			$HTML .= <<<EOF
		<fieldset><legend>{$this->t_('filter_pagegroup_history')}</legend>
			<input type="button" class="insubmit" onclick="document.location.href='{$this->escapeQuoteApostrophe($sHistoryPageGroupLink)}'" value="{$this->xQuote($sHistoryPageGroupLabel)} &gt;&gt;"/>
		</fieldset>
EOF;
		}
		$HTML .= <<<EOF
		
		</form>
	</div>
EOF;
		return $HTML;
	}
	
	function filterChangeTypes($asAllChangeTypes=null, $asDisplayChangeTypes=null)
	{
		$HTML = <<<EOF

	<div class="anwfilter" id="filter_changetypes">
	<fieldset>
		<legend>{$this->t_('filter_types')}
			<a href="#" onclick="AnwUtils.chkall('chkfilterct',$('filter_changetypes')); return false;">{$this->g_('in_chkall')}</a> 
			<a href="#" onclick="AnwUtils.chknone('chkfilterct',$('filter_changetypes')); return false;">{$this->g_('in_chknone')}</a>
		</legend>
EOF;
		foreach ($asAllChangeTypes as $sChangeType)
		{
			$bSelected = (in_array($sChangeType, $asDisplayChangeTypes) || count($asDisplayChangeTypes) == 0);
			$sSelected = ( $bSelected ? ' checked="checked"' : '');
			$sClassName = AnwChange::changeTypei18n($sChangeType);
			$sInputName = "ct_".$sChangeType;
			$HTML .= <<<EOF

		<input type="checkbox" id="{$this->xQuote($sInputName)}" class="chkfilterct" name="{$this->xQuote($sInputName)}"$sSelected/><label for="{$this->xQuote($sInputName)}">$sClassName</label> 
EOF;
		}
		$HTML .= <<<EOF

	</fieldset>
	</div>
EOF;
		return $HTML;
	}
		
	function nav($sLatestLink="", $sPrevLink="", $sNextLink, $bShowHistoryColumn=false)
	{
		$HTML = <<<EOF

	<div class="lastchanges_nav">
EOF;
		if ($sLatestLink != "")
		{
			$HTML .= <<<EOF

		<a href="{$this->xQuote($sLatestLink)}">{$this->t_('nav_latest')}</a>
EOF;
		}
		if ($sPrevLink != "")
		{
			$HTML .= <<<EOF

		<a href="{$this->xQuote($sPrevLink)}">{$this->t_('nav_previous')}</a>
EOF;
		}
		$HTML .= <<<EOF

		<a href="{$this->xQuote($sNextLink)}">{$this->t_('nav_next')}</a> 
	</div>
	<table class="changes">
		<tr class="line0">
			<th>{$this->g_('change_time')}</th>
			<th>{$this->g_('change_pageoriginal')}</th>
			<th>{$this->g_('change_type')}</th>
			<th>{$this->g_('change_comment')}</th>
			<th>{$this->g_('change_user')}</th>
			<th>{$this->g_('change_page')}</th>
			<th>{$this->g_('change_diff')}</th>
EOF;
		if ($bShowHistoryColumn)
		{
			$HTML .= <<<EOF

			<th>{$this->t_('change_history')}</th>
EOF;
		}
		$HTML .= <<<EOF

			<th>{$this->t_('change_revert')}</th>
		</tr>
EOF;
		return $HTML;
	}
}

?>