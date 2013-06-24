<?php

class AnwTemplateDefault_contentclass_trfile extends AnwTemplateOverride_global
{
	function startTrfile($sName, $sLang)
	{
		$HTML = <<<EOF

<h1>{$this->t_('local_title', array('name'=>$this->xText($sName)), $sLang)}</h1>

<div class="explain">
	{$this->t_('local_explain', array(), $sLang)}
</div>

<table>
	<tr>
		<th>{$this->t_('local_item_id', array(), $sLang)}</th>
		<th>{$this->t_('local_item_value', array(), $sLang)}</th>
	</tr>
EOF;
		return $HTML;
	}
		
	
	function translationItem($sTranslationId, $sTranslationValue)
	{
		static $i=0;
		$sCssClass = 'line'.($i++%2);
		
		$HTML = <<<EOF

		<tr class="$sCssClass">
			<th style="text-align:left">{$this->xText($sTranslationId)}</th>
			<td>{$this->xText($sTranslationValue)}</td>
		</tr>
EOF;
		return $HTML;
	}
	
	function stopTrfile()
	{
		$HTML = <<<EOF

</table>
EOF;
		return $HTML;
	}
}

?>