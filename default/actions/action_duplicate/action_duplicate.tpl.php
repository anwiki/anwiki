<?php

class AnwTemplateDefault_action_duplicate extends AnwTemplateOverride_global
{
	function duplicateStart($sFormAction, $sCurrentPageName, $sCurrentPageLang, $error=false)
	{
		$sFlag = Anwi18n::imgFlag($sCurrentPageLang);
		
		$HTML = <<<EOF

	{$this->errorList($error)}
	<h1>{$this->t_('title', array('pagename'=>'<i>'.$this->xText($sCurrentPageName).'</i>'))} $sFlag</h1>
	<form action="{$this->xQuote($sFormAction)}" method="post">
		<a href="#" onclick="AnwUtils.chkall('duplicate_lang');">{$this->g_('in_chkall')}</a> 
		<a href="#" onclick="AnwUtils.chknone('duplicate_lang');">{$this->g_('in_chknone')}</a><br/>
EOF;
		return $HTML;
	}
	
	function duplicateStop()
	{
		$sSubmitLabel = self::g_("in_submit");
		$sAbortLabel = self::g_("in_abort");
		$HTML = <<<EOF

	
		<input type="submit" name="submit" class="insubmit" value="{$this->xQuote($sSubmitLabel)}"/>
		<input type="submit" name="abort" class="inabort" value="{$this->xQuote($sAbortLabel)}"/>
	</form>
EOF;
		return $HTML;
	}
	
	function duplicateRowCurrent($sTranslationLang, $sTranslationPageName, $sInputName)
	{
		$sImgFlag = Anwi18n::imgFlag($sTranslationLang);
		$sSrcFlag = Anwi18n::srcFlag($sTranslationLang);
		$HTML = <<<EOF

		<input type="checkbox" name="current" value="1" class="duplicate_lang" style="float:left" checked="checked" disabled="disabled"/>
		<label style="width:100px; float:left;">
			$sImgFlag $sTranslationLang :
		</label>
		<input type="text" name="{$this->xQuote($sInputName)}" value="{$this->xQuote($sTranslationPageName)}" class="intext inpagename" style="background-image:url('$sSrcFlag')"/><br/>
EOF;
		return $HTML;		
	}
	
	function duplicateRow($sTranslationLang, $sTranslationPageName, $sInputName, $sCheckBoxName)
	{
		$sImgFlag = Anwi18n::imgFlag($sTranslationLang);
		$sSrcFlag = Anwi18n::srcFlag($sTranslationLang);
		$HTML = <<<EOF

		<input type="checkbox" name="{$this->xQuote($sCheckBoxName)}" value="1" id="{$this->xQuote($sCheckBoxName)}" class="duplicate_lang" style="float:left"/>
		<label for="{$this->xQuote($sCheckBoxName)}" style="width:100px; float:left;">
			$sImgFlag $sTranslationLang :
		</label>
		<input type="text" name="{$this->xQuote($sInputName)}" value="{$this->xQuote($sTranslationPageName)}" class="intext inpagename" style="background-image:url('$sSrcFlag')"/><br/>
EOF;
		return $HTML;		
	}
	
	
	//------------
	
	function beginProcessDuplication($sCurrentPageName, $sCurrentPageLang)
	{
		$sFlag = Anwi18n::imgFlag($sCurrentPageLang);
		$HTML = <<<EOF

	<h1>{$this->t_('title', array('pagename'=>'<i>'.$this->xText($sCurrentPageName).'</i>'))} $sFlag</h1>
	<div class="explain">{$this->t_('process_explain')}</div>
	<ul>
EOF;
		return $HTML;
	}
	
	function rowProcessDuplication_failed($sPageName, $sPageLang, $asNotices)
	{
		$sNotices = "";
		foreach ($asNotices as $sNotice)
		{
			$sNotices .= $this->drawNoticeIcon($sNotice).' ';
		}
		$sImgFlag = Anwi18n::imgFlag($sPageLang);
		
		$HTML = <<<EOF

		<li>$sImgFlag{$this->xText($sPageName)} : <span style="color:red">{$this->t_('process_fail')}</span> $sNotices</li>
EOF;
		return $HTML;
	}
	
	function rowProcessDuplication_success($sPageLink)
	{
		$HTML = <<<EOF

		<li>$sPageLink : <span style="color:green">{$this->t_('process_success')}</span></li>
EOF;
		return $HTML;
	}
	
	function endProcessDuplication()
	{
		$HTML = <<<EOF

	</ul>
EOF;
		return $HTML;
	}
}

?>