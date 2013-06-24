<?php
/**
 * Anwiki is a multilingual content management system <http://www.anwiki.com>
 * Copyright (C) 2007-2009 Antoine Walter <http://www.anw.fr>
 * 
 * Anwiki is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 * 
 * Anwiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Anwiki.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Translating a Page.
 * @package Anwiki
 * @version $Id: action_translate.php 350 2010-12-12 22:12:07Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_translate extends AnwActionPage
{
	private $originalStrings;
	private $hiddenTags;
	private $genTranslateForm_html;
	private $genTranslatableField_html;
	private $saveTranslation_ContentFieldValues;
	
	const EMPTY_VALUE = '&nbsp;';
	const CSS_FILENAME = "action_translate.css";
	
	function getNavEntry()
	{
		return $this->createNavEntry();
	}
	
	function preinit()
	{
		//we need to load all contents to check pagegroup synchro
		$this->getoPage()->setSkipLoadingTranslationsContent(false);
		$this->checkPageExists();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		$this->lockPageForEdition(AnwLock::TYPE_PAGEONLY);
		$this->getoPage()->checkPageGroupSynchronized();
		
		//determine the Reference Page
		$oPageRef = $this->choosePageRef(urldecode(AnwEnv::_GET("ref")));
		
		if (AnwEnv::_POST("save") && AnwEnv::_POST("rev"))
		{
			$this->saveTranslation(AnwEnv::_POST("rev"));
		}
		else if(AnwEnv::_GET("abort"))
		{
			$this->abortTranslation();
		}
		else
		{
			$this->translateForm($oPageRef);
		}
	}
	
	private function choosePageRef($sLangRef)
	{
		if ($sLangRef)
		{
			$aoPagesFromGroup = $this->getoPage()->getPageGroup()->getPages();
			if (isset($aoPagesFromGroup[$sLangRef]))
			{
				$oPageRef = $aoPagesFromGroup[$sLangRef];
			}
			else
			{
				$oPageRef = $this->getoPage();
			}
		}
		else
		{
			$oPageRef = null;
			$aoPages = $this->getoPage()->getPageGroup()->getPages($this->getoPage());
			
			//prefer complete translation in default language
			$sDefaultLang = self::globalCfgLangDefault();
			if ($this->getoPage()->getLang() == $sDefaultLang && $this->getoPage()->isTranslated())
			{
				$oPageRef = $this->getoPage();
			}
			else
			{
				if (isset($aoPages[$sDefaultLang]) && $aoPages[$sDefaultLang]->isTranslated())
				{
					$oPageRef = $aoPages[$sDefaultLang];
				}
				else
				{
					//try to find a translation which has translation completed
					foreach ($aoPages as $oPageTranslation)
					{
						if ($oPageTranslation->isTranslated())
						{
							$oPageRef = $oPageTranslation;
							break;
						}
					}
				}
			}
			
			if (!$oPageRef)
			{
				$oPageRef = $this->getoPage();
			}
		}
		return $oPageRef;
	}
	
	
	//-------------------------------------------------------
	//                 TRANSLATION FORM
	//-------------------------------------------------------
	
	private function translateForm($oPageRef)
	{
		//preload original lang
		$fOnValue = "preloadOriginalLang_onContentFieldValue";
		AnwUtils::runCallbacksOnTranslatableField($this, $oPageRef->getContent(), $fOnValue);
		
		//now, generate translation form
		$fOnValue = "genTranslateForm_onContentFieldValue";
		$this->genTranslateForm_html = "";
		AnwUtils::runCallbacksOnTranslatableField($this, $this->getoPage()->getContent(), $fOnValue);
		$sTranslateForm = $this->genTranslateForm_html;
		
		
		//TODO : translate PHPVARS
		
		
		//PageRef list selection
		$aoTranslations = $this->getoPage()->getPageGroup()->getPages();
		$HTMLPageRef = $this->tpl()->selectPageRef_open();
		foreach ($aoTranslations as $oTranslation)
		{
			if ($oTranslation->getName() == $oPageRef->getName())
			{
				$HTMLPageRef .= $this->tpl()->selectPageRef_row_selected($oTranslation);
			}
			else
			{
				$HTMLPageRef .= $this->tpl()->selectPageRef_row($oTranslation);
			}
		}
		$HTMLPageRef .= $this->tpl()->selectPageRef_close();
		
		

		//output
		$sContentHtmlDir = self::g_("local_html_dir", array(), AnwAction::getActionLang());
		$sReferenceHtmlDir = self::g_("local_html_dir", array(), $oPageRef->getLang());
		$this->out .= $this->tpl()->translateform(
						$sContentHtmlDir,
						AnwUtils::cssViewContent($oPageRef),
						$sReferenceHtmlDir,
						AnwUtils::link($this->getoPage(),"translate"),
						$sTranslateForm, $this->getoPage()->getChangeId(),
						$oPageRef->getLang(),
						$this->getoPage()->getLang(),
						$HTMLPageRef);
						
		$this->head( $this->getJsSrcComponent("class_translator.js") );		
		$this->headJsOnload( $this->tpl()->jsOnload($this->hiddenTags, self::EMPTY_VALUE, $this->getEmptyValueSrc()) );
		
		//lock
		$aaObservers = array();
		$aaObservers[] = array('INPUT' => 'panel_translate', 'EVENT' => 'keypress');
		$this->lockObserver(AnwLock::TYPE_PAGEONLY, "translateform", $aaObservers);
	}
	
	private function getEmptyValueSrc()
	{
		$sEmptyValueSrc = '['.$this->t_("translate_item_empty").']';
		return $sEmptyValueSrc;
	}
	
	function genTranslateForm_onContentFieldValue($oContentField, $oXmlValue, $sInputName)
	{
		$nTranslatableFields = 0;
		
		//$this->generateTranslatableFields($oNodeRoot, $sFieldName, &$sTranslatableContent, &$nTranslatableFields);
		
		$fOnTextValue = "genTranslatableField_onTextValue";
		$fBeforeChilds = "genTranslatableField_beforeChilds";
		$fAfterChilds = "genTranslatableField_afterChilds";
		$fOnUntranslatableNode = "genTranslatableField_onUntranslatableNode";
		//&$sTranslatableContent, &$nTranslatableFields
		$this->genTranslatableField_html = "";
		AnwUtils::runCallbacksOnTranslatableValue($this, $oXmlValue, $sInputName, $fOnTextValue, $fBeforeChilds, $fAfterChilds, $fOnUntranslatableNode);
		//print htmlentities($sTranslatableContent);
		
		//only edit ContentFields which have translatable content
		if ($this->genTranslatableField_html != "")
		{
			$this->genTranslatableField_html = $this->simplifyHtmlForTranslation($this->genTranslatableField_html);
			$sContentHtmlDir = self::g_("local_html_dir", array(), AnwAction::getActionLang());
			$this->genTranslateForm_html .= $this->tpl()->translateContentField($sContentHtmlDir, $oContentField->getFieldLabel(), $this->genTranslatableField_html);
		}
	}
	
	protected function simplifyHtmlForTranslation($sHtml)
	{
		//disable custom javascript to avoid dynamic transformations while translating
		$sHtml = preg_replace('/<script([^>]*?)>(.*?)<\/script>/s', 
								'', $sHtml);
		$sHtml = preg_replace('/<script([^>]*?)\/>/s', 
								'', $sHtml);
								
		//disable javascript events
		$sHtml = preg_replace('/(onclick|onmouseover|onmouseout|onload|onunload)="([^"]*?)"/s', 
								'', $sHtml);
		$sHtml = preg_replace('/(onclick|onmouseover|onmouseout|onload|onunload)=\'([^\']*?)\'/s', 
								'', $sHtml);
		
		//disable links to make it editable
		$sHtml = preg_replace('/\<a ([^>]*?)href\="([^>]*?)"([^>]*?)\>/s', 
								'<a $1href="$2" onclick="return false"$3>', $sHtml);
		
		//disable lists to make it editable
		$sHtml = preg_replace('/\<select([^>]*?)\>(.*?)<\/select>/s', 
								'<div class="translate_select"><div class="translate_title">select</div>$2</div>', $sHtml);
		$sHtml = preg_replace('/\<option([^>]*?)\>(.*?)<\/option>/s', 
								'<span class="translate_select">$2</span>', $sHtml);
		
		//disable buttons
		$sHtml = preg_replace('/\<input([^>]*?)\>/s', 
								'<input$1 onclick="return false;">', $sHtml);
		
		//disable textareas to make it editable
		$sHtml = preg_replace('/<textarea([^>]*?)>(.*?)<\/textarea>/s', 
								'<div class="translate_select"><div class="translate_title">textarea</div>$2</div>', $sHtml);
		
		//disable flash objects and movies
		$sHtml = preg_replace('/<object([^>]*?)>(.*?)<\/object>/s', 
								'', $sHtml);
		
		//disable textareas to make it editable
		$sHtml = preg_replace('/<button([^>]*?)>(.*?)<\/button>/s', 
								'<div class="translate_button"><div class="translate_title">button</div>$2</div>', $sHtml);
		
		return $sHtml;
	}
	
	//-------------------------------------------------------
	//        TRANSLATION FORM -> translation fields
	//-------------------------------------------------------
	
	function genTranslatableField_onTextValue($oRootNode, $sInputName)
	{
		$sContent = AnwXml::xmlGetUntranslatedTxt($oRootNode->nodeValue, false); //strip out untranslated flags
		$sContentOriginal = $this->originalStrings[$sInputName] ;
		
		$this->genTranslatableField_html .= $this->tpl()->translationSpan($sInputName);
		
		$bTranslated = !AnwXml::xmlIsUntranslated($oRootNode/*->parentNode*/);
		
		if (trim($sContent) == "" || trim($sContent) == self::EMPTY_VALUE)
		{
			$sContent = ""; //hide ugly empty_value to translators
		}
		$sSafeContent = AnwXml::trimOneSpaceMax( $sContent );
		$sSafeContentOriginal = AnwXml::trimOneSpaceMax( $sContentOriginal );
		AnwDebug::log(" --> ".$sInputName." : ".($bTranslated?"TRANSLATED":"UNTRANSLATED")." : ".$sSafeContent." ( was ".$sSafeContentOriginal.")");
		
		$this->hiddenTags .= $this->tpl()->hiddenTags($sInputName, $sSafeContent, $sSafeContentOriginal, $bTranslated);
	}
	
	function genTranslatableField_beforeChilds($oRootNode)
	{
		$this->genTranslatableField_html .= AnwUtils::xmlDumpNodeOpen($oRootNode);
	}
	
	function genTranslatableField_afterChilds($oRootNode)
	{
		$this->genTranslatableField_html .= AnwUtils::xmlDumpNodeClose($oRootNode);
	}
	
	function genTranslatableField_onUntranslatableNode($oRootNode)
	{
		$sHtml = AnwUtils::xmlDumpNode($oRootNode);
		$sHtml = AnwOutput::cleanFixTags($sHtml);
		$this->genTranslatableField_html .= '<span class="fixeditem">'.$sHtml.'</span>';
	}
	
	
	//-------------------------------------------------------
	//        PRELOAD ORIGINAL LANG
	//-------------------------------------------------------
	
	function preloadOriginalLang_onContentFieldValue($oContentField, $oXmlValue, $sInputName)
	{
		$nTranslatableFields = 0;
		
		$fOnTextValue = "preloadOriginalLang_onTextValue";
		AnwUtils::runCallbacksOnTranslatableValue($this, $oXmlValue, $sInputName, $fOnTextValue, null, null);
	}
	
	function preloadOriginalLang_onTextValue($oRootNode, $sInputName)
	{
		$sContent = AnwXml::xmlGetUntranslatedTxt($oRootNode->nodeValue, false); //strip out untranslated flags
		//$sContent = AnwUtils::xmlDumpNode($oRootNode);
		$this->originalStrings[$sInputName] = $sContent;
	}
	
	
		
	
	//-------------------------------------------------------
	//                  SAVE TRANSLATION
	//-------------------------------------------------------
	
	private function saveTranslation($nRevChangeId)
	{
		$bPageWasEditedDuringTranslation = ($this->getoPage()->getChangeId() > $nRevChangeId ? true : false);
		
		if ($bPageWasEditedDuringTranslation)
		{
			//someone has edited the page since user was translating it, let's get the old revision from which we started the translation
			AnwDebug::log("Someone edited the page since we were translating it");
			$oPageRev = new AnwPageByName($this->getoPage()->getName(), $nRevChangeId);
			
			$oContentBeforeEdit = $oPageRev->getContent();
			$oContentAfterEdit = $this->getoPage()->getContent();
		}
		else
		{
			$oPageRev = $this->getoPage();
		}
		
		$oContent = clone $oPageRev->getContent();
		
		//preload original lang
		$fOnValue = "preloadOriginalLang_onContentFieldValue";
		AnwUtils::runCallbacksOnTranslatableField($this, $oPageRev->getContent(), $fOnValue);
		
		//update content from post
		$fOnValue = "saveTranslation_cbkOnValue"; //$fBeforeContentField
		$fBeforeContentField = "saveTranslation_cbkBeforeField";
		$fAfterContentField = "saveTranslation_cbkAfterField";
		AnwUtils::runCallbacksOnTranslatableField($this, $oContent, $fOnValue, $fBeforeContentField, $fAfterContentField);
		
		//if needed, apply again the edit which was done since the translation
		if ($bPageWasEditedDuringTranslation)
		{
			AnwDebug::log("Someone edited the page since we were translating it --> reapplying changes");
			$oContent = AnwAutoSync::propagateContentEdition($oContentBeforeEdit, $oContentAfterEdit, $oContent);
		}
		
		//save changes to the current page
		$this->debug("Updating current page...");
		
		$this->getoPage()->saveTranslation($oContent);
		
		//unlock
		$this->unlockPageForEdition();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getPageName()));
	}
	
	function saveTranslation_cbkBeforeField($oContentField, $oContent)
	{
		//$this->saveCbk_values = array();
		$this->saveTranslation_ContentFieldValues = array();
	}
	
	function saveTranslation_cbkOnValue($oContentField, $oXmlValue, $sInputName)
	{
		$fOnTextValue = "saveTranslation_onTextValue";
		AnwUtils::runCallbacksOnTranslatableValue($this, $oXmlValue, $sInputName, $fOnTextValue, null, null);
		//$oXmlValue has been modified by callback
		
		$sValue = AnwUtils::xmlDumpNodeChilds($oXmlValue);
		AnwDebug::log(" => value[] for $sInputName : ".htmlentities($sValue));
		$this->saveTranslation_ContentFieldValues[] = $sValue;
	}
	
	function saveTranslation_cbkAfterField($oContentField, $oContent)
	{
		//$oContent->setContentFieldValues($oContentField->getName(), $this->saveCbk_values);
		$oContent->setContentFieldValues($oContentField->getName(), $this->saveTranslation_ContentFieldValues);
	}
	
	//-------------
	
	private function getTranslatedValueFromPost($sFieldInput)
	{
		$sPostName = "translation-".$sFieldInput;
		$sTranslation = AnwEnv::_POST($sPostName, "", true); //skip trim
		$sTranslation = AnwUtils::escapeTags($sTranslation); //deny tags injection
		
		$sOriginal = AnwXml::xmlGetUntranslatedTxt($this->originalStrings[$sFieldInput], false);
		$sTranslation = AnwXml::xmlPreserveTextLayout($sTranslation, $sOriginal);
		
		return $sTranslation;
	}
	
	function saveTranslation_onTextValue($oRootNode, $sFieldInput)
	{
		//escape < and >
		$sTranslatedValue = $this->getTranslatedValueFromPost($sFieldInput);
		$bTranslated = ((int)AnwEnv::_POST("done-".$sFieldInput)) == 1 ? true : false;
		
		//deny empty values to avoid unsync
		if (trim($sTranslatedValue) == "")
		{
			$sTranslatedValue = self::EMPTY_VALUE;
		}
		
		AnwDebug::log(" --> $sFieldInput : ".($bTranslated?"TRANSLATED":"UNTRANSLATED")." : ".$sTranslatedValue." ( was ".$oRootNode->nodeValue.")");
		//$oRootNode->nodeValue = $sTranslatedValue;
		$oRootNode = AnwXml::xmlReplaceTextNodeValue($oRootNode, $sTranslatedValue);
		$oRootNode = AnwXml::xmlSetTextUntranslated($oRootNode, !$bTranslated);
		//xml structure is now modified
	}
	
	private function abortTranslation()
	{
		//unlock
		$this->unlockPageForEdition();
		
		//redirect
		AnwUtils::redirect(AnwUtils::link($this->getoPage()));
	}
}
?>