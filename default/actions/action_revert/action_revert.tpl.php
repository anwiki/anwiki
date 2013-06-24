<?php

class AnwTemplateDefault_action_revert extends AnwTemplateOverride_global
{
	function formRevert($sFormAction, $aoPageGroupChanges, $nRevToChangeId, $sHistoryPageGroupLink=false)
	{
		$sSelectTo = $this->getOptions($aoPageGroupChanges, $nRevToChangeId);
		$sTitle = $this->t_("title");
		
		$HTML = <<<EOF

<h1>{$this->xText($sTitle)}</h1>

<form action="{$this->xQuote($sFormAction)}" method="post">
<div class="explain">
	<label id="revert_to">{$this->t_('revert_select_to')}</label> <br/>
	<select name="revto" class="inselect" id="revert_sel_to" onchange="window.location.href='{$this->escapeQuoteApostrophe($sFormAction)}&amp;revto='+this.options[this.selectedIndex].value">$sSelectTo</select>
EOF;
		if ($sHistoryPageGroupLink)
		{
			$HTML .= <<<EOF

	<br/>
	<a href="{$this->xQuote($sHistoryPageGroupLink)}" target="_blank" style="color:#000; font-weight:bold">{$this->t_('view_pagegroup_history')}</a>
EOF;
		}
		$HTML .= <<<EOF
</div>

EOF;
		return $HTML;
	}
	
	private function getOptions($aoPageGroupChanges, $nRevToChangeId)
	{
		$HTML = "";
		foreach ($aoPageGroupChanges as $oChange)
		{
			$sSelected = '';
			$nRevChangeId = $oChange->getChangeId();
			if ( $oChange->getChangeId() == $nRevToChangeId)
			{
				$sSelected = ' selected';
			}
			$sTimeRev = Anwi18n::dateTime($oChange->getTime());
			
			$sChangePage = "";
			$sChangeInfo = "";
			$sChangePage = ' - '.$oChange->getPageName();
			$sChangeInfo = ' - '.AnwChange::changeTypei18n($oChange->getType()).' - '.$oChange->getComment().' '.$oChange->getInfo();
			
			$HTML .= <<<EOF

<option value="{$this->xQuote($nRevChangeId)}"{$sSelected}>{$this->xText("$sTimeRev $sChangePage $sChangeInfo")}</option>
EOF;
		}
		return $HTML;
	}
	
	function simulateRevert($sLang, $sName, $oPageRev, $sLnkDiff)
	{
		$nRevTime = Anwi18n::dateTime($oPageRev->getTime());
		$sRevName = $oPageRev->getName();
		$sRevLang = $oPageRev->getLang();
		$sRevUser = ($oPageRev->getChange() ? $oPageRev->getChange()->getUser()->getDisplayName() : "");
		$HTML = <<<EOF

<span style="">{$this->t_('revert_sim_revert',array('pagename'=>'<b>'.$this->xText($sName).'</b>','pagelang'=>$sLang,'revtime'=>$nRevTime,'revname'=>$sRevName,'revlang'=>$sRevLang,'revuser'=>$this->xText($sRevUser)))} $sLnkDiff</span>
<br/>
EOF;
		return $HTML;
	}
	
	
	
	function simulateKeep($sLang, $sName)
	{
		$HTML = <<<EOF

<span style="color:grey;">{$this->t_('revert_sim_keep', array('pagename'=>'<b>'.$this->xText($sName).'</b>','pagelang'=>$sLang))}</span>
<br/>
EOF;
		return $HTML;
	}
	
	function simulateDelete($sLang, $sName)
	{
		$HTML = <<<EOF

<span style="color:red;">{$this->t_('revert_sim_delete', array('pagename'=>'<b>'.$this->xText($sName).'</b>','pagelang'=>$sLang))}</span>
<br/>
EOF;
		return $HTML;
	}
	
	function simulateCreate($oPageRev)
	{
		$nRevTime = Anwi18n::dateTime($oPageRev->getTime());
		$sRevName = $oPageRev->getName();
		$sRevLang = $oPageRev->getLang();
		$sRevUser = ($oPageRev->getChange() ? $oPageRev->getChange()->getUser()->getDisplayName() : "");
		$HTML = <<<EOF

<span style="color:green;">{$this->t_('revert_sim_create', array('revtime'=>$nRevTime,'revname'=>'<b>'.$sRevName.'</b>','revlang'=>$sRevLang,'revuser'=>$this->xText($sRevUser)))}</span>
<br/>
EOF;
		return $HTML;
	}
	
	function end()
	{
		$HTML = <<<EOF

	<input type="submit" class="insubmit" name="submit" value="{$this->xQuote($this->t_("revert_submit"))}"/>
</form>
EOF;
		return $HTML;
	}
}

?>