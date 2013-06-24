<?php

class AnwTemplateDefault_action_export extends AnwTemplateOverride_global
{
	function begin($sFormAction, $sFilters)
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	
	$sFilters
	
	<div class="explain">{$this->t_('explain')}</div>
	
	<form action="{$this->xQuote($sFormAction)}" method="post">
	
	<input type="submit" value="{$this->xQuote($this->t_("export_submit"))}"/>
	
	<div style="display:inline">
		<a href="#" onclick="AnwUtils.chkall('chkexport',$('exportpages')); return false;">{$this->g_('in_chkall')}</a> 
		<a href="#" onclick="AnwUtils.chknone('chkexport',$('exportpages')); return false;">{$this->g_('in_chknone')}</a>
	</div>
	<div id="exportpages">
		<div class="header">
			<div class="colpagename">{$this->g_('page_name')}</div>
			<div class="colprogress">{$this->g_('page_progress')}</div>
			<div class="colcontentclass">{$this->g_('page_contentclass')}</div>
			<div class="coltime">{$this->g_('page_time')}</div>
		</div><br/>
		<ul>
EOF;
		return $HTML;
	}
	
	function rowGroupOpen()
	{
		$HTML = <<<EOF

	<li class="rowgroup">
		<ul>
			<li>
				<a href="#" onclick="AnwUtils.chkall('chkexport',$(this).up().up()); return false;">{$this->g_('in_chkall')}</a> 
				<a href="#" onclick="AnwUtils.chknone('chkexport',$(this).up().up()); return false;">{$this->g_('in_chknone')}</a>
			</li>
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
	
	function rowTranslation($oPage, $bExportDisabled, $bNoticePhp, $bNoticeAcl)
	{
		$sTime = Anwi18n::dateTime($oPage->getTime());
		$nPageId = $oPage->getId();
		$sContentClass = $oPage->getPageGroup()->getContentClass()->getLabel();
		
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
		
		$sDisabled = $bExportDisabled ? ' disabled="disabled"' : "";
		$sNoticePhp = $bNoticePhp ? $this->drawNoticeIcon($this->t_("notice_php")) : "";
		$sNoticeAcl = $bNoticeAcl ? $this->drawNoticeIcon($this->t_("notice_acl")) : "";
		
		$sImgFlag = Anwi18n::imgFlag($oPage->getLang());
		
		$HTML = <<<EOF

		<li>
			<div class="colpagename">
				<input type="checkbox" name="exportpages[]" class="chkexport" value="{$this->xQuote($nPageId)}" id="exportpages{$nPageId}"{$sDisabled}/>
				<label for="exportpages{$nPageId}">{$sImgFlag} {$this->xText($oPage->getName())}</label> {$sNoticePhp} {$sNoticeAcl}
			</div>
			<div class="colprogress"><span style="color:$sColor">$sUntranslated</span></div>
			<div class="colcontentclass">$sContentClass</div>
			<div class="coltime">$sTime</div>
		</li>
EOF;
		return $HTML;
	}
	
	function end()
	{
		$HTML = <<<EOF

		</ul>
	</div>
	</form>
EOF;
		return $HTML;
	}
}

?>