<?php

class AnwTemplateDefault_action_editconfig extends AnwTemplateOverride_global
{
	
	function editConfigLayout($sLinkRefresh, $aaConfigurableComponents, $sHtmlContent)
	{
		$HTML = <<<EOF

	<div id="listconfigurables">
		<div id="refreshcomponentslist"><a href="{$this->xQuote($sLinkRefresh)}">{$this->t_("refreshcomponents_redirect_link")}</a></div>
		
		<ul>
EOF;
		foreach ($aaConfigurableComponents as $mComponentType => $asComponents)
		{
			$HTML .= <<<EOF
		<li><label>{$this->xText($mComponentType)}</label>
			<ul>
EOF;
			$asComponentsEnabled = array();
			$asComponentsDisabled = array();
			
			foreach ($asComponents as $sComponent)
			{
				if (AnwComponent::isComponentEnabled($sComponent, $mComponentType))
				{
					$asComponentsEnabled[] = $sComponent;
				}
				else
				{
					$asComponentsDisabled[] = $sComponent;
				}
			}
			
			foreach ($asComponentsEnabled as $sComponent)
			{
				$sLink = AnwUtils::aLink("editconfig", array("c"=>$sComponent, "t"=>$mComponentType));
				$HTML .= <<<EOF
			<li class="active"><a href="{$this->xQuote($sLink)}">{$this->xText($sComponent)}</a></li>
EOF;
			}
			
			foreach ($asComponentsDisabled as $sComponent)
			{
				$sLink = AnwUtils::aLink("editconfig", array("c"=>$sComponent, "t"=>$mComponentType));
				$HTML .= <<<EOF
			<li class="inactive"><a href="{$this->xQuote($sLink)}">{$this->xText($sComponent)}</a></li>
EOF;
			}
			
			$HTML .= <<<EOF
			</ul>
EOF;
		}
		$HTML .= <<<EOF
	
		</ul>
	</div>
	<div id="editconfigurable">
		$sHtmlContent
	</div>
EOF;
		return $HTML;
	}
	
	
	function editConfigForm($formaction, $sContentHtmlDir, $sComponentShortName, $sComponentType, $sComponentName, $sContentFieldsHtml, $error)
	{
		$HTML = <<<EOF

	<h1>{$this->t_('title',array('componentname'=>'<i>'.$this->xText($sComponentName).'</i>'))}</h1>
	{$this->errorList($error)}
	<form action="{$this->xQuote($formaction)}" method="post" id="edit_form" class="editcontent">

$sContentFieldsHtml

		<input type="hidden" name="posted" value="1" /> 
		<input type="hidden" name="c" value="{$this->xQuote($sComponentShortName)}" />
		<input type="hidden" name="t" value="{$this->xQuote($sComponentType)}" />
		<input style="margin-top:30px" type="submit" name="publish" class="insubmit" value="{$this->xQuote($this->t_("submit_save"))}" />
	</form>

EOF;
		return $HTML;
	}
}

?>