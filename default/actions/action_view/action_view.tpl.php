<?php

class AnwTemplateDefault_action_view extends AnwTemplateOverride_global
{
	function viewPage($sContentHtmlDir, $sCss, $sHtmlTitle, $sContent, $nEditionTime, $aoTranslations)
	{
		$HTML = <<<EOF

	<div class="$sCss" dir="$sContentHtmlDir">
	$sContent
	</div>
	
	<div id="pageinfo">
		{$this->pageTranslations($aoTranslations)}
		<div id="pagerevision">{$this->t_("local_version",array("time"=>$nEditionTime))}</div>
		<div class="break"></div>
	</div>
EOF;
		return $HTML;
	}
	
	function pageTranslations($aoTranslations)
	{
		$HTML = <<<EOF
		<div id="pagetranslations">
			<ul>
EOF;

		foreach ($aoTranslations as $amInfo)
		{
			$oPage = $amInfo['page'];
			$bCurrent = $amInfo['current'];
			$sLink = $oPage->getLang();
			$sFlag = Anwi18n::srcFlag($oPage->getLang());
			$sLinkStyle = $amInfo['online'] ? '' : ' text-decoration:line-through;';
			
			if ($bCurrent)
			{
				$HTML .= <<<EOF

			<li class="current" style="background-image:url('$sFlag');$sLinkStyle">{$this->xText($sLink)}</li>
EOF;
			}
			else
			{
				$asParams = $_GET;
				unset($asParams[AnwActionPage::GET_PAGENAME]); //avoid loop
				$sUrl = AnwUtils::link($oPage, "view", $asParams);
				$HTML .= <<<EOF

			<li><a href="{$this->xQuote($sUrl)}" style="background-image:url('$sFlag');$sLinkStyle">{$this->xText($sLink)}</a></li>
EOF;
			}
		}

		$HTML .= <<<EOF

			</ul>
		</div>
EOF;
		return $HTML;
	}
	
	// Available translations
	
	function availableTranslationsOpen($oPage)
	{
		$HTML = <<<EOF
		
EOF;
		return $HTML;
	}
	
	function availableTranslationsRowCurrent($oPage)
	{
		$sName = $oPage->getName();
		$sUrl = AnwUtils::link($oPage);
		$sStyle = "background:url('".Anwi18n::srcFlag($oPage->getLang())."') no-repeat left;";
		$HTML = "<span style=\"font-weight:bold;$sStyle\">".$this->xText($sName)."</span>";
		return $HTML;
	}
	
	function availableTranslationsRow($oPage)
	{
		$sName = $oPage->getName();
		$sUrl = AnwUtils::link($oPage);
		$sStyle = "background:url('".Anwi18n::srcFlag($oPage->getLang())."') no-repeat left;";
		$HTML = "<a href=\"".$this->xQuote($sUrl)."\" style=\"$sStyle\">".$this->xText($sName)."</a>";
		return $HTML;
	}
	
	function availableTranslationsClose()
	{
		$HTML = <<<EOF
		
EOF;
		return $HTML;
	}
}

?>