<?php

class AnwTemplateDefault_action_rename extends AnwTemplateOverride_global
{
	function renameForm($sNewPageName, $sLang, $sComment, 
						$sPagename, $formaction, $aoPageGroupsLinked, 
						$sError)
	{
		$sSrcFlagTr = Anwi18n::srcFlag($sLang);
		$sSubmitLabel = self::g_("in_submit");
		$sAbortLabel = self::g_("in_abort");
		$HTML = <<<EOF

	<h1>{$this->t_('title', array('pagename'=>'<i>'.$this->xText($sPagename).'</i>'))}</h1>
	{$this->errorList($sError)}
	<form action="{$this->xQuote($formaction)}" method="post" id="rename_form">
		{$this->g_('in_pagename')} <input type="text" name="newname" class="intext inpagename" id="newname" style="background-image:url('$sSrcFlagTr')" value="{$this->xQuote($sNewPageName)}"/><br/> 
		{$this->g_('in_comment')} <input type="text" name="comment" class="intext incomment" id="comment" value="{$this->xQuote($sComment)}"/><br/>
		
		
		<input type="submit" name="rename" class="insubmit" value="{$this->xQuote($sSubmitLabel)}" />
		<input type="submit" name="abort" class="inabort" value="{$this->xQuote($sAbortLabel)}" />
EOF;
		if(count($aoPageGroupsLinked)>0)
		{
			$HTML .= <<<EOF

		<br/><input type="checkbox" name="updatelinks" value="1" checked="checked" id="updatelinks"/><label for="updatelinks">{$this->t_('rename_updatelinks')}</label><br/>
EOF;
		}
		$HTML .= <<<EOF
	</form>
EOF;
		if(count($aoPageGroupsLinked)>0)
		{
			$HTML .= <<<EOF

		<p>{$this->t_('rename_links')}</p>
		<ul>
EOF;
			foreach ($aoPageGroupsLinked as $oPageGroupLinked)
			{
				$aoPagesLinked = $oPageGroupLinked->getPages();
				foreach ($aoPagesLinked as $oPageLinked)
				{
					$HTML .= <<<EOF

			<li>{$oPageLinked->link()}</li>
EOF;
				}
			}
			$HTML .= <<<EOF

		</ul>
EOF;
		}
		
		return $HTML;
	}
	
	function beginLinks()
	{
		$HTML = <<<EOF

	
	<ul>
EOF;
		return $HTML;
	}
	
	function showLink($oPage)
	{
		$HTML = <<<EOF

	<li>{$oPage->link()}</li>
EOF;
		return $HTML;
	}
	
	function endLinks()
	{
		$HTML = <<<EOF

	</ul>
EOF;
		return $HTML;
	}
}

?>