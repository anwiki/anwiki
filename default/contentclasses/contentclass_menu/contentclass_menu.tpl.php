<?php

class AnwTemplateDefault_contentclass_menu extends AnwTemplateOverride_global
{
	function openMenu()
	{
		$HTML = <<<EOF

<div class="menu-container">
<span class="menu-open1"></span>
<span class="menu-open2"></span>
<div class="menu">
	<ul>
EOF;
		return $HTML;
	}
	
	function mainItem($sTitle, $sUrl, $sTarget, $sRenderedSubItems=false)
	{
		$sTargetHtml = ($sTarget==AnwIPage_link::TARGET_SELF ? '' : ' target="'.$sTarget.'"');
		$HTML = <<<EOF

		<li>
			<a href="{$this->xQuote($sUrl)}"{$sTargetHtml}>
				<span class="menu-mainitem-open1">
				<span class="menu-mainitem-open2">
					<span class="menu-mainitem-title">{$this->xText($sTitle)}</span>
				</span>
				</span>
			</a>
EOF;
		if ($sRenderedSubItems)
		{
			$HTML .= <<<EOF

			<ul>$sRenderedSubItems</ul>
EOF;
		}
		$HTML .= <<<EOF

		</li>
EOF;
		return $HTML;
	}
	
	function mainItemCurrent($sTitle, $sUrl, $sTarget, $sRenderedSubItems=false)
	{
		$sTargetHtml = ($sTarget==AnwIPage_link::TARGET_SELF ? '' : ' target="'.$sTarget.'"');
		$HTML = <<<EOF

		<li class="menu-active">
			<a href="{$this->xQuote($sUrl)}"{$sTargetHtml}>
				<span class="menu-mainitem-open1">
				<span class="menu-mainitem-open2">
					<span class="menu-mainitem-title">{$this->xText($sTitle)}</span>
				</span>
				</span>
			</a>
EOF;
		if ($sRenderedSubItems)
		{
			$HTML .= <<<EOF

			<ul>$sRenderedSubItems</ul>
EOF;
		}
		$HTML .= <<<EOF

		</li>
EOF;
		return $HTML;
	}
	
	function subItem($sTitle, $sUrl, $sTarget)
	{
		$sTargetHtml = ($sTarget==AnwIPage_link::TARGET_SELF ? '' : ' target="'.$sTarget.'"');
		$HTML = <<<EOF

		<li>
			<a href="{$this->xQuote($sUrl)}"{$sTargetHtml}>
			<span class="menu-subitem-open1">
			<span class="menu-subitem-open2">
				<span class="menu-subitem-title">{$this->xText($sTitle)}</span>
			</span>
			</span>
			</a>
		</li>
EOF;
		return $HTML;
	}
	
	function subItemCurrent($sTitle, $sUrl, $sTarget)
	{
		$sTargetHtml = ($sTarget==AnwIPage_link::TARGET_SELF ? '' : ' target="'.$sTarget.'"');
		$HTML = <<<EOF

		<li class="submenu-active">
			<a href="{$this->xQuote($sUrl)}"{$sTargetHtml}>
			<span class="menu-subitem-open1">
			<span class="menu-subitem-open2">
				<span class="menu-subitem-title">{$this->xText($sTitle)}</span>
			</span>
			</span>
			</a>
		</li>
EOF;
		return $HTML;
	}
	
	function closeMenu()
	{
		$HTML = <<<EOF

	</ul>
</div><!-- end menu -->
<span class="menu-close1"></span>
<span class="menu-close2"></span>
</div><!-- end menu-container -->
EOF;
		return $HTML;
	}
}

?>