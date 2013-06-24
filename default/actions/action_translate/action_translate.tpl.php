<?php

class AnwTemplateDefault_action_translate extends AnwTemplateOverride_global
{
	function translateform($sContentHtmlDir, $sCss, $sReferenceHtmlDir, $formaction,$content,$nRev,$langfrom,$langto,$selectPageRef)
	{
		$sImgFlagFrom = Anwi18n::imgFlag($langfrom);
		$sImgFlagTo = Anwi18n::imgFlag($langto);
		$sAbortLabel = self::g_("in_abort");
		$sTranslateSaveLabel = $this->t_("translate_button_save");
		$sTranslatePreviousLabel = $this->t_("translate_button_previous");
		$sTranslateNextLabel = $this->t_("translate_button_next");
		$sTranslateRevertLabel = $this->t_("translate_button_revert");
		$sTranslateCloseLabel = $this->t_("translate_button_closepanel");
		$sTranslateUndoAllLabel = $this->t_("translate_button_undoneall");
		$sTranslateDoneAllLabel = $this->t_("translate_button_doneall");
		$sTranslateRefreshLabel = $this->t_("translate_button_refresh");
		
		$HTML = <<<EOF

	<div class="explain">
		{$this->t_('translate_explain')}
		<ol>
			<li>{$this->t_('translate_explain_1', array('selectref'=>$selectPageRef))}</li>
			<li>{$this->t_('translate_explain_2')}</li>
			<li>{$this->t_('translate_explain_3')}</li>
			<li>{$this->t_('translate_explain_4')}</li>
		</ol>
		<input type="button" onclick="oTranslator.save();return false;" class="insubmit" value="{$this->xQuote($sTranslateSaveLabel)}"/>  
		<input type="button" onclick="window.location=window.location+'&amp;abort=1'" class="inabort" value="{$this->xQuote($sAbortLabel)}"/> 
		
	</div>
	
	<form action="{$this->xQuote($formaction)}" method="post" id="translateform">
		<input type="hidden" name="save" value="1"/>
		<input type="hidden" name="rev" value="{$this->xQuote($nRev)}"/>
		<div id="panel">
			<fieldset id="panel_current">
				<legend>{$this->t_('translate_panel_currentarea')}</legend>
				<div style="text-align:center">
					<input type="button" onclick="oTranslator.translatePrevious();return false;" class="inbutton" value="{$this->xQuote($sTranslatePreviousLabel)}"/> 
					<input type="button" onclick="oTranslator.translateNext();return false;" class="inbutton" value="{$this->xQuote($sTranslateNextLabel)}"/><br/>
					<input type="checkbox" id="panel_skiptranslated" checked="checked"/><label for="panel_skiptranslated" title="will move to the next untranslated string">{$this->t_('translate_chk_skiptranslated')}</label>
				</div>
				
				$sImgFlagFrom {$this->t_('translate_original_value')}<br/>
				<div id="panel_original" dir="{$this->xQuote($sReferenceHtmlDir)}"></div><br/>
				$sImgFlagTo {$this->t_('translate_translated_value')}<br/>
				<textarea id="panel_translate" rows="2" dir="{$this->xQuote($sContentHtmlDir)}"></textarea><br/>
				<input type="checkbox" id="panel_done"/><label for="panel_done">{$this->t_('translate_chk_translated')}</label><br/>
				<div style="display:none"><input type="checkbox" id="panel_apply"/><label for="panel_apply">{$this->t_('translate_chk_apply')}</label><br/></div>
				<input type="checkbox" id="panel_autotranslate"/><label for="panel_autotranslate">{$this->t_('translate_chk_autotranslate')}</label>
			
				<div class="panel_buttons">
					<input type="button" onclick="oTranslator.revert();return false;" class="inabort" value="{$this->xQuote($sTranslateRevertLabel)}"/> 
					<input type="button" onclick="oTranslator.save();return false;" class="insubmit" value="{$this->xQuote($sTranslateSaveLabel)}"/> 
					<input type="button" onclick="window.location=window.location+'&amp;abort=1'" class="inabort" value="{$this->xQuote($sAbortLabel)}"/> 
					<input type="button" onclick="oTranslator.hidePanel();return false;" class="inbutton" value="{$this->xQuote($sTranslateCloseLabel)}"/>
				</div>
			</fieldset> 
			<fieldset id="panel_global">
				<legend>{$this->t_('translate_panel_globalarea')}</legend>
				<div class="panel_buttons">
					<input type="button" onclick="oTranslator.undoneall();return false;" class="inabort" value="{$this->xQuote($sTranslateUndoAllLabel)}"/>
					<input type="button" onclick="oTranslator.doneall();return false;" class="insubmit" value="{$this->xQuote($sTranslateDoneAllLabel)}"/> 
					<input type="button" onclick="window.location=window.location" class="inabort" value="{$this->xQuote($sTranslateRefreshLabel)}"/>
				</div>
			</fieldset> 
			<fieldset id="panel_shortcuts">
				<legend>{$this->t_('translate_panel_shortcuts')}</legend>
				<label>CTRL+Enter</label>{$this->t_('translate_shortcut_next')}<br/>
				<label>CTRL+Shift+Enter</label>{$this->t_('translate_shortcut_nextandmark')}<br/>
			</fieldset>
		</div>
		<div id="translator"></div>
	</form>
	
	<div class="{$this->xQuote($sCss)}">
	$content
	</div>
EOF;
		return $HTML;
	}
	
	function jsSrc()
	{
		
	}
	
	function jsOnload($hidden_tags, $sEmptyValue, $sEmptyValueSrc)
	{
		$sEmptyValue = AnwUtils::escapeQuote($sEmptyValue);
		$sEmptyValueSrc = AnwUtils::escapeQuote($sEmptyValueSrc);
		$JS = <<<EOF
	oTranslator = new AnwTranslator( $('translateform'), $('translator'), $('panel'), $('panel_original'), $('panel_translate'), $('panel_done'), $('panel_apply'), $('panel_autotranslate'), $('panel_skiptranslated'), "{$sEmptyValue}", "{$sEmptyValueSrc}" );
	$hidden_tags 
	oTranslator.start();
EOF;
		return $JS;
	}
	
	function hiddentags($id, $defaultcontent, $originalcontent, $translated)
	{
		$defaultcontent = AnwUtils::escapeQuote($defaultcontent);
		$originalcontent = AnwUtils::escapeQuote($originalcontent);
		
		$sTranslated = ($translated ? 'true' : 'false');
		$HTML = <<<EOF

	oTranslator.addItem( new TranslateItem("{$this->xQuote($id)}", "{$defaultcontent}", "{$originalcontent}", $sTranslated) );
EOF;
		return $HTML;
	}
	
	function translateContentField($sContentHtmlDir, $sTitle, $sValue)
	{
		$HTML = <<<EOF

<div class="contentfield">
	<div class="contentfield_label">$sTitle : </div>
	<div class="contentfield_content" dir="{$this->xQuote($sContentHtmlDir)}">$sValue
		<div style="clear:both"></div>
	</div>
</div>
EOF;
		return $HTML;
	}
	
	function translationSpan($id)
	{
		$HTML = <<<EOF
<span id="{$this->xQuote($id)}">&nbsp;</span>
EOF;
		return $HTML;
	}
	
	function selectPageRef_open()
	{
		$HTML = <<<EOF
<select onchange="document.location='?a=translate&ref='+this.options[this.selectedIndex].value">
EOF;
		return $HTML;
	}
	
	function selectPageRef_row($oPage)
	{
		if ($oPage->isTranslated())
		{
			$sTranslationProgress = $this->g_("translation_complete");
		}
		else
		{
			$sTranslationProgress = $this->g_("translation_progress",array("percent"=>$oPage->getTranslatedPercent()));
		}
		$sTitle = $oPage->getLang().' : '.$oPage->getName().' ('.$sTranslationProgress.')';
		$sValue = urlencode($oPage->getLang());
		$HTML = <<<EOF
<option value="{$this->xQuote($sValue)}">$sTitle</option>
EOF;
		return $HTML;
	}
	
	function selectPageRef_row_selected($oPage)
	{
		if ($oPage->isTranslated())
		{
			$sTranslationProgress = $this->g_("translation_complete");
		}
		else
		{
			$sTranslationProgress = $this->g_("translation_progress",array("percent"=>$oPage->getTranslatedPercent()));
		}
		$sTitle = $oPage->getLang().' : '.$oPage->getName().' ('.$sTranslationProgress.')';
		$sValue = urlencode($oPage->getName());
		$HTML = <<<EOF
<option value="{$this->xQuote($sValue)}" selected="selected">&gt;$sTitle</option>
EOF;
		return $HTML;
	}
	
	function selectPageRef_close()
	{
		$HTML = <<<EOF
</select>
EOF;
		return $HTML;
	}
}

?>