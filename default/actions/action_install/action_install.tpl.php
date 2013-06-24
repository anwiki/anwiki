<?php

class AnwTemplateDefault_action_install extends AnwTemplateOverride_global
{
	function alreadyInstalled($sLinkHome, $sLinkForce, $sFileLock, $sFileStatus)
	{
		$sGoHomeLabel = AnwUtils::xQuote($this->t_("alreadyinstalled_go_home"));
		$sGoForceLabel = AnwUtils::xQuote($this->t_("alreadyinstalled_go_force"));
		
		$HTML = <<<EOF

<h1>{$this->t_('alreadyinstalled_t')}</h1>
<div class="explain">
	{$this->t_('alreadyinstalled_explain_p1')}<br/><br/>
	{$this->t_('alreadyinstalled_explain_p2')}<br/>
	<ul>
		<li>$sFileLock</li>
	</ul>
	<br/>
	{$this->t_('alreadyinstalled_explain_p3')}<br/><br/>
	<input type="button" class="inabort inbuttonbig" value="&lt;&lt; {$sGoHomeLabel}" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkHome)}'"/>
	<input type="button" class="insubmit inbuttonbig" value="{$sGoForceLabel} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkForce)}'"/>
</div>
EOF;
		return $HTML;
	}
	
	function headerInstall($sTitleStep, $nStepOrder, $nCountSteps, $sLinkPrevious, $sLinkNext)
	{
		$HTML = <<<EOF

<div style="padding:1em; text-align:center;">
	<span style="font-weight:bold; font-size:1.4em">{$this->t_("install_assistant_t", array('currentstep'=>$nStepOrder,'countsteps'=>$nCountSteps))}</span><br/>
	<span style="font-weight:bold">{$this->t_("install_progress", array('currentstep'=>$nStepOrder,'countsteps'=>$nCountSteps,'titlestep'=>$sTitleStep))}</span>
</div>
<h1>$sTitleStep</h1>
EOF;
		return $HTML;
	}
	
	function footerInstall($sTitleStep, $nStepOrder, $nCountSteps, $sLinkPrevious, $sLinkNext)
	{
		$HTML = <<<EOF

<div style="margin-top:2em; text-align:center;">
EOF;
		if ($sLinkPrevious)
		{
			$HTML .= <<<EOF

	<a href="{$this->xQuote($sLinkPrevious)}" style="float:left; display:block;padding:0.3em 0.8em; color:#000; background-color:#F5E1CD; -moz-border-radius: 0.5em; border-radius: 0.5em; -khtml-border-radius: 0.5em;">&lt;&lt; {$this->t_("install_link_previous")}</a>
EOF;
		}
		if ($sLinkNext)
		{
			$HTML .= <<<EOF

	<a href="{$this->xQuote($sLinkNext)}" style="float:right; display:block;padding:0.3em 0.8em; color:#000; background-color:#F5E1CD; -moz-border-radius: 0.5em; border-radius: 0.5em; -khtml-border-radius: 0.5em;">{$this->t_("install_link_next")} &gt;&gt;</a>
EOF;
		}
		$HTML .= <<<EOF

</div>
EOF;
		return $HTML;
	}
}

class AnwTemplateDefaultStepInstall extends AnwTemplateOverride_global
{
	protected function getActionInstall()
	{
		return $this->getComponent()->getActionInstall();
	}
	
	protected function t_install($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		return $this->getActionInstall()->t_($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
}

class AnwTemplateDefaultStepInstallConfigureComponent extends AnwTemplateDefaultStepInstall
{
	
	function editConfigForm($sFormaction, $sRenderForm, $sComponentName, $sError)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_install("configcomponent_explain",array('componentname'=>'<b>'.$sComponentName.'</b>'))}<br/>
</div>
{$this->errorList($sError)}
<form action="{$this->xQuote($sFormaction)}" method="post" id="edit_form" class="editcontent">

$sRenderForm

		<div style="height:30px"></div>
	<input type="submit" class="insubmit inbuttonbig" value="{$this->t_install('configcomponent_submit')} &gt;&gt;" />
</form>

EOF;
		return $HTML;
	}
	
	function noConfigurationNeeded($sLinkNextStep)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_install("configcomponent_notneeded_explain")}<br/>
	<input type="button" class="insubmit inbuttonbig" value="{$this->t_install('nextstep_go')} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkNextStep)}'"/>
</div>
EOF;
		return $HTML;
	}
}

class AnwTemplateDefaultStepInstallInitializeComponent extends AnwTemplateDefaultStepInstall
{
function showInitConfirm($sLinkContinue, $sInitSettings, $sComponentName, $sError)
	{
		$HTML = <<<EOF

{$this->errorList($sError)}
<div class="explain">
	{$this->t_install("initcomponent_confirm_explain", array('componentname'=>'<b>'.$sComponentName.'</b>'))}
	<div style="margin:0px auto; padding:1em; width:40em; border:1px dashed #000; background-color:#FFF;">$sInitSettings</div>
	<input type="button" class="insubmit inbuttonbig" value="{$this->t_install('initcomponent_confirm_go')} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkContinue)}'"/>
</div>
EOF;
		return $HTML;
	}
	
	function showInitResult($sLinkContinue, $sInitLog)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_install("initcomponent_result_explain")}<br/>
	<input type="button" class="insubmit inbuttonbig" value="{$this->t_install('nextstep_go')} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkContinue)}'"/>
	<div style="margin:1em auto; padding:1em; font-size:0.8em; width:40em; border:1px dashed #000; background-color:#FFF;">$sInitLog</div>
</div>
EOF;
		return $HTML;
	}
	
	function noInitializationNeeded($sLinkNextStep)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_install("initcomponent_notneeded_explain")}<br/>
	<input type="button" class="insubmit inbuttonbig" value="{$this->t_install('nextstep_go')} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkNextStep)}'"/>
</div>
EOF;
		return $HTML;
	}
	
	function showAlreadyInit($sLinkNextStep, $sLinkEditSettings, $sInitSettings)
	{
		$HTML = <<<EOF

<div class="explain">
	{$this->t_install("initcomponent_already_explain")}
	<div style="margin:1em auto; padding:1em; width:40em; border:1px dashed #000; background-color:#FFF;">$sInitSettings</div>
	<input type="button" class="inabort inbuttonbig" value="&lt;&lt; {$this->t_install('already_back')}" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkEditSettings)}'"/>
	<input type="button" class="inbutton inbuttonbig" value="{$this->t_install('already_go')} &gt;&gt;" onclick="window.location.href='{$this->escapeQuoteApostrophe($sLinkNextStep)}'"/>
</div>
EOF;
		return $HTML;
	}
}

?>