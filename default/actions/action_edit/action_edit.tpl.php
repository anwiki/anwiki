<?php

class AnwTemplateDefault_action_edit extends AnwTemplateOverride_global
{
	function editForm($formaction, $sContentHtmlDir, $sPagename, $sContentFieldsHtml, $sLang, $sContentClassName, $htmldrafts, $nDraftTime=false, $summary, $bShowCaptcha, $error)
	{
		$sFlag = Anwi18n::imgFlag($sLang);
		$HTML = <<<EOF

	<h1>{$this->t_('title',array('pagename'=>'<i>'.$this->xText($sPagename).'</i>'))} $sFlag <span style="font-size:0.7em">{$this->xText($sContentClassName)}</span></h1>
	{$this->errorList($error)}
	<form action="{$this->xQuote($formaction)}" method="post" id="edit_form" class="editcontent">

$sContentFieldsHtml


		<div style="height:30px"></div>
		<input type="hidden" name="posted" value="1" /> 
EOF;
//		<input type="hidden" name="draft" value="{$this->xQuote($nDraftTime)}" />
		if ($bShowCaptcha)
		{
			$HTML .= <<<EOF

{$this->showCaptcha()}<br/>
EOF;
		}
		$sAbortLabel = self::g_("in_abort");
		$HTML .= <<<EOF

		{$this->g_('in_comment')} <input type="text" name="comment" class="intext incomment" id="comment" size="50" value="{$this->xQuote($summary)}" />
		<input type="submit" name="publish" class="insubmit" value="{$this->xQuote($this->t_("publish"))}" />
		<input type="submit" name="preview" class="inbutton" value="{$this->xQuote($this->t_("preview"))}" />
		<input type="submit" name="abort" value="{$this->xQuote($sAbortLabel)}" class="inabort"/>
		<br/>
EOF;
/*		{$this->t_('drafts')}
		<input type="submit" name="savenewdraft" class="insubmit" value="{$this->t_('savenewdraft')}" />

		if ($nDraftTime)
		{
			$HTML .= <<<EOF
		<input type="submit" name="updatedraft" class="insubmit" value="{$this->t_('updatedraft')}" />
		<input type="submit" name="discarddraft" class="inabort" value="{$this->t_('discarddraft')}" onclick="return confirm('{$this->t_('discarddraft_alert')}');" />
EOF;
		}*/
		$HTML .= <<<EOF
	</form>
	
	$htmldrafts

EOF;
		return $HTML;
	}
	
	function draftsOpen()
	{
		$HTML = <<<EOF

	<div style="border:1px solid grey">
	<p style="margin:5px">{$this->t_('draftlist')}</p>
	<ul>
EOF;
		return $HTML;
	}
	
	function draftsLine($sDraftTime, $sDraftComment, $sDraftUser, $sLink)
	{
		$HTML = <<<EOF

	<li><a href="{$this->xQuote($sLink)}">$sDraftTime</a> - $sDraftUser : $sDraftComment</li>
EOF;
		return $HTML;
	}
	
	function draftsLineCurrent($sDraftTime, $sDraftComment, $sDraftUser)
	{
		$HTML = <<<EOF

	<li style="font-weight:bold">$sDraftTime - $sDraftUser : $sDraftComment</li>
EOF;
		return $HTML;
	}
	
	function draftsClose()
	{
		$HTML = <<<EOF

	</ul>
	</div>
EOF;
		return $HTML;
	}
	
	function draftsNone()
	{
		$HTML = <<<EOF

	<div style="border:1px solid grey">
		{$this->t_('nodrafts')}
	</div>
EOF;
		return $HTML;
	}
	
	function preview($sHtmlPreview, $sCss)
	{
		$HTML = <<<EOF

<div class="edit_preview $sCss">
<a name="preview"></a>
<h2 class="preview_title">{$this->t_('preview_title')}</h2>
$sHtmlPreview
</div><!-- end edit_preview -->
EOF;
		return $HTML;
	}
	
	function preview_jsOnload()
	{
		$JS = <<<EOF

setTimeout(function(){window.location.href=window.location.href+"#preview";},100);
EOF;
		return $JS;		
	}
}

?>