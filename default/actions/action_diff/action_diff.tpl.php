<?php

class AnwTemplateDefault_action_diff extends AnwTemplateOverride_global
{
	function beforeDiffs($sFormAction, $oPageRevFrom, $oPageRevTo, $oPageCurrent=null)
	{
		$oPageReference = ( $oPageCurrent ? $oPageCurrent : $oPageRevTo );
		
		$aoPageRevisions = $oPageReference->getPageRevisions();
		$sSelectFrom = $this->getOptions($aoPageRevisions, $oPageRevFrom, $oPageReference);
		$sSelectTo = $this->getOptions($aoPageRevisions, $oPageRevTo, $oPageReference);
		$sTitle = $this->t_("title", array("pagename"=>$oPageReference->getName()));
		$sGetActionName = AnwAction::GET_ACTIONNAME;
		$nGetPageId = $oPageReference->getId();
		
		$HTML = <<<EOF

<h1>$sTitle</h1>

<div class="anwfilter">
<fieldset id="diff">
<form action="{$this->xQuote($sFormAction)}" method="get">
<label id="diff_lbl_from">{$this->t_('select_from')}</label> 
<select name="revfrom" class="inselect" id="diff_sel_from">$sSelectFrom</select>

<label id="diff_lbl_to">{$this->t_('select_to')}</label> 
<select name="revto" class="inselect" id="diff_sel_to">$sSelectTo</select>
<input type="hidden" name="{$this->xQuote($sGetActionName)}" value="diff"/>
<input type="hidden" name="page" value="{$this->xQuote($nGetPageId)}"/>
<input type="submit" class="insubmit" value="{$this->xQuote($this->t_("submit"))}"/>
</form>
</fieldset>
</div>
EOF;
		return $HTML;
	}
	
	private function getOptions($aoPageRevisions, $oPageSelected, $oPageReference)
	{
		$HTML = "";
		foreach ($aoPageRevisions as $oPageRevision)
		{
			$nRevChangeId = $oPageRevision->getChangeId();
			
			$sSelected = '';
			if ( $nRevChangeId == $oPageSelected->getChangeId())
			{
				$sSelected = ' selected';
			}
			$sTimeRev = Anwi18n::dateTime($oPageRevision->getTime());
			
			$sChangeInfo = "";
			if ($oPageRevision->getChange())
			{
				$sChangeInfo = '- '.AnwChange::changeTypei18n($oPageRevision->getChange()->getType());
			}
			
			if (!$oPageReference->isArchive() && $nRevChangeId == $oPageReference->getChangeId())
			{
				$sChangeInfo .= " ".$this->t_("rev_current");
			}
			
			$HTML .= <<<EOF

<option value="{$this->xQuote($nRevChangeId)}"{$sSelected}>$sTimeRev $sChangeInfo</option>
EOF;
		}
		return $HTML;
	}
	
	function diffAdded($oDiff)
	{
		$sContent = AnwUtils::xmlDumpNode($oDiff->getNode(), $this);
		$HTML = <<<EOF

	<span style="color:green">$sContent</span>
EOF;
		return $HTML;
	}
	
	function diffMoved($oDiff)
	{
		$sContent = AnwUtils::xmlDumpNode($oDiff->getDiffDeleted()->getNode(), $this);
		$HTML = <<<EOF

	<span style="color:blue">$sContent</span>
EOF;
		return $HTML;
	}
	
	//TODO:render subdiffs added/deleted ?
	function diffDeleted($oDiff)
	{
		$sContent = AnwUtils::xmlDumpNode($oDiff->getNode());
		$HTML = <<<EOF

	<span style="text-decoration:line-through; color:red; font-size:11px;">$sContent</span>
EOF;
		return $HTML;
	}
	
	function diffKept($oDiff)
	{
		$sContent = AnwUtils::xmlDumpNode($oDiff->getNode());
		$HTML = <<<EOF

	<span>$sContent</span>
EOF;
		return $HTML;
	}
	
	function diffEdited($oDiff)
	{
		$HTML = <<<EOF

	<div style="border:1px solid orange">
		{$this->diffDeleted($oDiff->getDiffDeleted())}
		{$this->diffAdded($oDiff->getDiffAdded())}
	</div>
EOF;
		return $HTML;
	}
	
	function diffContainerOpen($sContentHtmlDir, $oDiff)
	{
		$sTagOpen = $oDiff->getTagOpen();
		$sTagClose = $oDiff->getTagOpen();
		$sTagOpenDisplay = htmlentities($sTagOpen);
		 
		$HTML = <<<EOF

	<div style="clear:both; border:1px solid grey; margin:5px 0px;">
		<div style="color:white; background-color:grey; font-size:10px; text-align:left;">{$sTagOpenDisplay}</div>
		{$sTagClose}
		<div style="padding:5px;" dir="{$this->xQuote($sContentHtmlDir)}">
EOF;
		return $HTML;
	}
	
	function diffContainerClose($oDiff)
	{
		$HTML = <<<EOF

		</div>
		{$oDiff->getTagClose()}
		<div style="clear:both"></div>
	</div>
EOF;
		return $HTML;
	}
	
	function diffFooter()
	{
		$HTML = <<<EOF

	</table>
EOF;
		return $HTML;
	}
}

?>