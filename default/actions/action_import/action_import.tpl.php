<?php

class AnwTemplateDefault_action_import extends AnwTemplateOverride_global
{
	function uploadForm($sLinkAction, $sInputNameFile, $sInputNamePrefix)
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	<div class="explain">{$this->t_('upload_explain')}</div>
	<form action="{$this->xQuote($sLinkAction)}" method="post" enctype="multipart/form-data">
	
	<label for="importfile">{$this->t_('upload_file')}</label>
	<input type="file" name="{$this->xQuote($sInputNameFile)}" id="importfile"/><br/>
	
	<label for="importprefix">{$this->t_('upload_prefix')}</label>
	<input type="text" name="{$this->xQuote($sInputNamePrefix)}" value="" id="importprefix"/>
	<div class="importprefixtip">{$this->t_('upload_prefix_tip')}</div>
	
	<input type="submit" value="{$this->xQuote($this->t_("upload_submit"))}"/>
	</form>
EOF;
		return $HTML;
	}
	
	function beginSelection($sLinkAction, $sUploadedFileName, $sInputNameFile, $sInputContinueOnErrors, $sExportTime, $sExportFrom, $sExportVersion)
	{
		$sSubmit = $this->t_("select_submit");
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
	<div class="explain">{$this->t_('select_explain')}</div>
	
	<fieldset>
		<legend>{$this->t_('filedetails_t')}</legend>
		<p><label>{$this->t_('filedetails_time')}</label>$sExportTime</p>
		<p><label>{$this->t_('filedetails_from')}</label><a href="{$this->xQuote($sExportFrom)}">{$this->xText($sExportFrom)}</a></p>
		<p><label>{$this->t_('filedetails_version')}</label>$sExportVersion</p>
	</fieldset>
	
	<form action="{$this->xQuote($sLinkAction)}" method="post">
	<input type="hidden" name="{$this->xQuote($sInputNameFile)}" value="{$this->xQuote($sUploadedFileName)}"/>
	
	<input type="submit" value="{$this->xQuote($sSubmit)}"/> 
	<input type="radio" name="{$this->xQuote($sInputContinueOnErrors)}" value="false" id="continueOnErrorsNo" checked="checked"/><label for="continueOnErrorsNo">{$this->t_('in_continue_on_errors_no')}</label>
	<input type="radio" name="{$this->xQuote($sInputContinueOnErrors)}" value="true" id="continueOnErrorsYes"/><label for="continueOnErrorsYes">{$this->t_('in_continue_on_errors_yes')}</label>
	
	<br/>
	<br/>
	<a href="#" onclick="AnwUtils.chkall('chkimport',$('importpages')); return false;">{$this->g_('in_chkall')}</a> 
	<a href="#" onclick="AnwUtils.chknone('chkimport',$('importpages')); return false;">{$this->g_('in_chknone')}</a><br/>
	<ul id="importpages">
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
	
	function rowTranslation($sChkName, $sInputPageName, $sInputPageLang, $sOriginalPageName, $sPageName, $sPageLang, $nPageTime, $bImportDisabled, $asNotices)
	{
		$sDisabled = $bImportDisabled ? ' disabled="disabled"' : "";
		$sNotices = "";
		foreach ($asNotices as $sNotice)
		{
			$sNotices .= $this->drawNoticeIcon($sNotice).' ';
		}
				
		$sPageTime = Anwi18n::dateTime($nPageTime);
		$sImgFlag = Anwi18n::imgFlag($sPageLang);
		
		$sSrcFlag = Anwi18n::srcFlag($sPageLang);
		
		$HTML = <<<EOF

		<li>
			<input type="checkbox" name="{$this->xQuote($sChkName)}[]" class="chkimport" id="{$this->xQuote($sOriginalPageName)}" value="{$this->xQuote($sOriginalPageName)}"{$sDisabled}/> 
			<label for="{$this->xQuote($sOriginalPageName)}">$sImgFlag{$this->xText($sPageName)} ($sPageTime) $sNotices</label>
EOF;
		if (!$bImportDisabled)
		{
			$sSelectLang = $this->selectLang(null, $sPageLang);
			$HTML .= <<<EOF
		<input type="text" class="intext inpagename" style="background-image:url('$sSrcFlag')" name="{$this->xQuote($sInputPageName)}" value="{$this->xQuote($sPageName)}"/>
		<select class="languages" name="{$this->xQuote($sInputPageLang)}">$sSelectLang</select> 
EOF;
		}
		$HTML .= <<<EOF
		</li>
EOF;
		return $HTML;
	}
	
	function endSelection()
	{
		$HTML = <<<EOF

	</ul>
	</form>
EOF;
		return $HTML;
	}
	
	//------------
	
	function beginProcess()
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title')}</h1>
EOF;
		return $HTML;
	}
	
	function importResultSuccess($nCountImportSuccess)
	{
		$HTML = <<<EOF

	<div class="explain"><b>{$this->t_('process_success_explain')}</b><br/>{$this->t_('process_success_stats',array('countSuccess'=>$nCountImportSuccess))}</div>
EOF;
		return $HTML;
	}
	
	function importResultFailed($nCountImportErrors)
	{
		$HTML = <<<EOF

	<div class="explain"><b>{$this->t_('process_failed_explain')}</b><br/>{$this->t_('process_failed_stats',array('countErrors'=>$nCountImportErrors))}</div>
EOF;
		return $HTML;
	}
	
	function importResultErrorsContinued($nCountImportSuccess, $nCountImportErrors)
	{
		$HTML = <<<EOF

	<div class="explain"><b>{$this->t_('process_continued_explain')}</b><br/>{$this->t_('process_continued_stats',array('countSuccess'=>$nCountImportSuccess, 'countErrors'=>$nCountImportErrors))}</div>
EOF;
		return $HTML;
	}
	
	function importResultErrorsCancelled($nCountImportSuccess, $nCountImportErrors)
	{
		$HTML = <<<EOF

	<div class="explain"><b>{$this->t_('process_cancelled_explain')}</b><br/>{$this->t_('process_cancelled_stats',array('countSuccess'=>$nCountImportSuccess, 'countErrors'=>$nCountImportErrors))}</div>
EOF;
		return $HTML;
	}
	
	function importDetails($sHtmlImportDetails)
	{
		$HTML = <<<EOF

	<ul>
	$sHtmlImportDetails
	</ul>
EOF;
		return $HTML;
	}
	
	function rowTranslationProcess_failed($sPageName, $sPageLang, $asNotices)
	{
		$sNotices = "";
		foreach ($asNotices as $sNotice)
		{
			$sNotices .= $this->drawNoticeIcon($sNotice).' ';
		}
		$sImgFlag = Anwi18n::imgFlag($sPageLang);
		
		$HTML = <<<EOF

		<li>$sImgFlag{$sPageName} : <span style="color:red">{$this->t_('process_fail')}</span> $sNotices</li>
EOF;
		return $HTML;
	}
	
	function rowTranslationProcess_success($sPageLink)
	{
		$HTML = <<<EOF

		<li>$sPageLink : <span style="color:green">{$this->t_('process_success')}</span></li>
EOF;
		return $HTML;
	}
	
	function rowTranslationProcess_skipped($sPageName, $sPageLang)
	{
		$sImgFlag = Anwi18n::imgFlag($sPageLang);
		$HTML = <<<EOF

		<li>$sImgFlag{this->xText($sPageName)} : <span style="color:#CCC">{$this->t_('process_skipped')}</span></li>
EOF;
		return $HTML;
	}
}

?>