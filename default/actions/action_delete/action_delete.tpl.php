<?php

class AnwTemplateDefault_action_delete extends AnwTemplateOverride_global
{
	function selectDeletionType($linkDeletePage, $linkDeleteGroup=false)
	{
		$HTML = <<<EOF

		<h1>{$this->t_('title')}</h1>
		<ul>
		<li><a href="{$this->xQuote($linkDeletePage)}">{$this->t_('p_delete_page_only')}</a></li>
EOF;
		if ($linkDeleteGroup)
		{
			$HTML .= <<<EOF

		<li><a href="{$this->xQuote($linkDeleteGroup)}">{$this->t_('p_delete_page_group')}</a></li>
EOF;
		}
		$HTML .= <<<EOF

		</ul>
EOF;
		return $HTML;
	}
}

?>