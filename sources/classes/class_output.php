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
 * Anwiki output possibilities.
 * @package Anwiki
 * @version $Id: class_output.php 362 2011-07-19 21:49:28Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
/**
 * This method can be called in an XML field to exit PHP interpretation, without killing Anwiki. 
 */
function anwexit()
{
	throw new AnwRunInterruptionException();
}

abstract class AnwOutput
{
	protected $oPage;
	private $bCachingEnabled = true; //enable anwloop and cacheblocks caching?
	private $bOutputCachingEnabled = true; //enable outputhtml caching? (flag set/read from outside)
	private $oParser;
	
	
	function __construct($oPage)
	{
		$this->oPage = $oPage;
	}
	
	function disableCaching()
	{
		$this->bCachingEnabled = false;
	}
	
	function disableOutputCaching()
	{
		$this->bOutputCachingEnabled = false;
	}
	
	function isOutputCachingEnabled()
	{
		return $this->bOutputCachingEnabled;
	}
	
	function getPage()
	{
		return $this->oPage;
	}
	
	//--------------------------------------------------
	/**
	 * Clean output before caching and running.
	 */
	protected function clean( $sContentHtmlAndPhp )
	{
		try
		{
			if (trim($sContentHtmlAndPhp) == "") return $sContentHtmlAndPhp;
			
			$sReturn = $sContentHtmlAndPhp;
			
			//strip out <fix> tags
			$sReturn = self::cleanFixTags($sReturn);
			
			$sReturn = $this->doBindLinksToPreferedLang($sReturn);
			
			$sReturn = AnwPlugins::vhook('output_clean', $sReturn, $this->oPage);
			
			//print nl2br(htmlentities($sReturn)).'<hr/>';
			return $sReturn;
		}
		catch(AnwException $e)
		{
			self::debug("local_exec_dynamic_error: ".$e);
			return AnwComponent::g_("local_exec_dynamic_error");
		}
	}
	
	public static function cleanFixTags($sReturn) //used by action_translate
	{
		$sReturn = preg_replace('!<'.AnwUtils::XML_NODENAME_UNTRANSLATABLE.'>(.*?)</'.AnwUtils::XML_NODENAME_UNTRANSLATABLE.'>!si', '$1', $sReturn);
		$sReturn = str_replace('<'.AnwUtils::XML_NODENAME_UNTRANSLATABLE.'/>', '', $sReturn);
		return $sReturn;
	}
	
	private function doBindLinksToPreferedLang($sContentHtmlAndPhp)
	{
		$aoOutgoingLinks = AnwPage::getOutgoingLinksFromContent($sContentHtmlAndPhp);
		foreach ($aoOutgoingLinks as $sOutgoingLink => $oPageTarget)
		{
			//get target page in the lang we want
			$sPreferedLang = $this->oPage->getLang();
			$oPageTarget = $oPageTarget->getPageGroup()->getPreferedPage($sPreferedLang);
			
			//update the link in content
			$sLinkTarget = AnwUtils::link( $oPageTarget->getName() );
			$sLinkLang = $oPageTarget->getLang();
			$sContentHtmlAndPhp = AnwPage::updateOutgoingLinkInContent($sOutgoingLink, $sLinkTarget, $sContentHtmlAndPhp, $sLinkLang);
		}
		return $sContentHtmlAndPhp;
	}
	
	/**
	 * Run output (which has been cleaned and maybe cached) on each hit. 
	 * Result will be immediately displayed and not reused, so we don't need to keep track of special tags for next execution.
	 */
	protected function run( $sReturn, $bRunDynamicParsing, $bRunDynamicPhp )
	{		
		$sReturn = AnwPlugins::vhook('output_run_before', $sReturn, $this->oPage);
		
		self::debug("run : dynamicParsing=".$bRunDynamicParsing.", dynamicPhp=".$bRunDynamicPhp);
		
		//execute user's php code (if any)
		//it seems safer to execute PHP code before that any dynamic transformation to the content is applied (cache, loops...)
		//so that we are *sure* that the PHP code being executed comes directly from the edited content,
		//and not generated from a compromised dynamic transformation.
		if ($bRunDynamicPhp)
		{
			AnwDebug::startbench("runPhp", true);
			$sReturn = AnwUtils::evalMixedPhpCode($sReturn);
			AnwDebug::stopbench("runPhp");
		}
		
		//PHP code has been executed, we should never go back in this function!
		if ($bRunDynamicParsing)
		{
			$sReturn = $this->getParser()->parse($sReturn);
		}
		
		$sReturn = AnwPlugins::vhook('output_run', $sReturn, $this->oPage);
		return $sReturn;
	}
	
	private function getParser()
	{
		//we need to use the same instance of the parser for each ContentFields of the page, 
		//for correct cacheBlocksId incrementation
		if (!$this->oParser)
		{
			$this->oParser = new AnwParser($this->oPage, $this->bCachingEnabled);
		}
		return $this->oParser;
	}
	
	protected function debug($sMsg)
	{
		AnwDebug::log("(AnwOutput) ".$sMsg);
	}
}


class AnwOutputHtml extends AnwOutput
{
	private $sTitle;
	private $sHead;
	private $sBody;
	
	private $bRunDynamicParsingTitle = false;
	private $bHasDynamicParsingTitle = false;
	private $bRunDynamicPhpTitle = false;
	private $bHasDynamicPhpTitle = false;
	
	
	private $bRunDynamicParsingHead = false;
	private $bHasDynamicParsingHead = false;
	private $bRunDynamicPhpHead = false;
	private $bHasDynamicPhpHead = false;
	
	private $bRunDynamicParsingBody = false;
	private $bHasDynamicParsingBody = false;	
	private $bRunDynamicPhpBody = false;
	private $bHasDynamicPhpBody = false;
	
	
	function __construct($oPage)
	{
		parent::__construct($oPage);
	}
	
	function __sleep()
	{
		//avoid saving temporary vars from AnwOutput: we don't need it once output is cached
		return array('sTitle', 'sHead', 'sBody', 
					'bRunDynamicParsingTitle', 
					'bHasDynamicParsingTitle', 
					'bRunDynamicPhpTitle', 
					'bHasDynamicPhpTitle', 
					
					'bRunDynamicParsingHead', 
					'bHasDynamicParsingHead', 
					'bRunDynamicPhpHead', 
					'bHasDynamicPhpHead',
					
					'bRunDynamicParsingBody', 
					'bHasDynamicParsingBody', 
					'bRunDynamicPhpBody', 
					'bHasDynamicPhpBody'
		);
		//oPage is dynamically rebuild with rebuildPage() when loading from cache (performances improvement)
	}
	
	function rebuildPage($oPage)
	{
		if ($this->oPage!=null)
		{
			throw new AnwUnexpectedException("rebuildPage but oPage is not null");
		}
		$this->oPage = $oPage;
	}
	
	
	function setTitle($sTitle)
	{
		$sHtmlAndPhp = $this->clean($sTitle);
		$sHtmlAndPhp = AnwUtils::stripUntr($sHtmlAndPhp);
		$sHtmlAndPhp = AnwPlugins::vhook('outputhtml_clean_title', $sHtmlAndPhp);
		$sHtmlAndPhp = self::do_on_html($sHtmlAndPhp, 'cbk_vhook_outputhtml_clean_title_html');
		//$sHtmlAndPhp = Anwi18n::parseTranslations($sHtmlAndPhp); //apply translations from plugins
		$this->sTitle = $sHtmlAndPhp;
		
		$this->bHasDynamicPhpTitle = AnwUtils::contentHasPhpCode($this->sTitle);
		$this->bHasDynamicParsingTitle = AnwParser::contentHasDynamicParsing($this->sTitle);
	}
	private static function cbk_vhook_outputhtml_clean_title_html($asMatches)
	{
		$sHtmlOnly = $asMatches[1];
		$sHtmlOnly = AnwPlugins::vhook('outputhtml_clean_title_html', $sHtmlOnly);
		$sHtmlOnly = '?>'.$sHtmlOnly.'<?';
		return $sHtmlOnly;
	}
	
	
	function setHead($sHead)
	{
		$sHtmlAndPhp = $this->clean($sHead);
		$sHtmlAndPhp = AnwUtils::stripUntr($sHtmlAndPhp);
		$sHtmlAndPhp = AnwPlugins::vhook('outputhtml_clean_head', $sHtmlAndPhp);
		$sHtmlAndPhp = self::do_on_html($sHtmlAndPhp, 'cbk_vhook_outputhtml_clean_head_html');
		//$sHtmlAndPhp = Anwi18n::parseTranslations($sHtmlAndPhp); //apply translations from plugins
		$this->sHead = $sHtmlAndPhp;
		
		$this->bHasDynamicPhpHead = AnwUtils::contentHasPhpCode($this->sHead);
		$this->bHasDynamicParsingHead = AnwParser::contentHasDynamicParsing($this->sHead);
	}
	private static function cbk_vhook_outputhtml_clean_head_html($asMatches)
	{
		$sHtmlOnly = $asMatches[1];
		$sHtmlOnly = self::cleanHtmlCloseEndTags($sHtmlOnly);
		$sHtmlOnly = AnwPlugins::vhook('outputhtml_clean_head_html', $sHtmlOnly);
		$sHtmlOnly = '?>'.$sHtmlOnly.'<?';
		return $sHtmlOnly;
	}
	
	
	function setBody($sBody)
	{
		$sHtmlAndPhp = $this->clean($sBody);
		$sHtmlAndPhp = AnwPlugins::vhook('outputhtml_clean_body', $sHtmlAndPhp);
		$sHtmlAndPhp = self::do_on_html($sHtmlAndPhp, 'cbk_vhook_outputhtml_clean_body_html');
		//$sHtmlAndPhp = Anwi18n::parseTranslations($sHtmlAndPhp); //apply translations from plugins
		
		$this->sBody = $sHtmlAndPhp;
		
		$this->bHasDynamicPhpBody = AnwUtils::contentHasPhpCode($this->sBody);
		$this->bHasDynamicParsingBody = AnwParser::contentHasDynamicParsing($this->sBody);
	}
	
	private static function cbk_vhook_outputhtml_clean_body_html($asMatches)
	{
		$sHtmlOnly = $asMatches[1];
		
		$sHtmlOnly = self::cleanBodyHtmlRenderTranslatableAttributes($sHtmlOnly);
		$sHtmlOnly = self::cleanHtmlCloseEndTags($sHtmlOnly);
		$sHtmlOnly = AnwUtils::renderUntr($sHtmlOnly);
		
		$sHtmlOnly = AnwPlugins::vhook('outputhtml_clean_body_html', $sHtmlOnly);
		$sHtmlOnly = '?>'.$sHtmlOnly.'<?';
		return $sHtmlOnly;
	}
	
	function setTitleDependancy($oContentField)
	{
		$this->bRunDynamicParsingTitle = $this->getDependancyParsing($oContentField, $this->bRunDynamicParsingTitle);
		$this->bRunDynamicPhpTitle = $this->getDependancyPhp($oContentField, $this->bRunDynamicPhpTitle);
	}
	
	private static function cleanBodyHtmlRenderTranslatableAttributes_callback($matches)
	{
		$matches[2] = str_replace(array(AnwUtils::FLAG_UNTRANSLATED_OPEN, AnwUtils::FLAG_UNTRANSLATED_CLOSE), array('', ''), $matches[2]);
		return " $matches[1]=\"$matches[2]\"";
	}
	
	/**
	 * FS#58 - Allow translation of external links.
	 * Example: <img src="test.png"><attr name="alt">Alternative text here</attr><attr name="title">Title here</attr></img>
	 * becomes: <img src="test.png" alt="Alternative text here" title="Title here"/>
	 */
	private static function cleanBodyHtmlRenderTranslatableAttributes($sHtmlOnly) {
		// Use callback to skip untranslated flags, avoid things such as: <img alt="<span class="untr">blah</span>"/>
		$sHtmlOnly = preg_replace_callback('/>\s*<attr\s+name="(\w+)">([^"<>]*)<\/attr\b/', array('self', 'cleanBodyHtmlRenderTranslatableAttributes_callback'), $sHtmlOnly);
		return $sHtmlOnly;
	}
	
	/**
	 * FS#97 - Correctly close empty tags for HTML-compatibility.
	 * Example: <script src=""/> becomes <script src=""></script>
	 *          <img src="foo"/> becomes <img src="foo" />
	 *          <img src="foo"></img> becomes <img src="foo" />
	 */
	private static function cleanHtmlCloseEndTags($sHtmlOnly) {
		// close empty tags correctly, except those marked with "end tag: forbidden" in HTML compatibility guidelines - thx Trev!
		// Example: <script src=""/> becomes <script src=""></script> 
		$sHtmlOnly = preg_replace('/<((?!link\b|meta\b|br\b|col\b|base\b|img\b|param\b|area\b|hr\b|input\b)([\w:]+)\b[^<>]*)\/>/', '<$1></$2>', $sHtmlOnly);
		
		// make sure that tags marked with "end tag: forbidden" are closed with the minimized form
		// Example: <img src="foo"/> becomes <img src="foo" />
		$sHtmlOnly = preg_replace('/(\S)\/>/', '$1 />', $sHtmlOnly);
		
		// Example: <img src="foo"></img> becomes <img src="foo" />
		// Example: <img src="foo">dummy</img> becomes <img src="foo" />
		$sHtmlOnly = preg_replace('/<((link|meta|br|col|base|img|param|area|hr|input)\b[^<>]*)>([^<>]*)<\/\2>/', '<$1 />', $sHtmlOnly);
		
		return $sHtmlOnly;
	}
	
	function setHeadDependancy($oContentField)
	{
		$this->bRunDynamicParsingHead = $this->getDependancyParsing($oContentField, $this->bRunDynamicParsingHead);
		$this->bRunDynamicPhpHead = $this->getDependancyPhp($oContentField, $this->bRunDynamicPhpHead);
	}
	
	function setBodyDependancy($oContentField)
	{
		$this->bRunDynamicParsingBody = $this->getDependancyParsing($oContentField, $this->bRunDynamicParsingBody);
		$this->bRunDynamicPhpBody = $this->getDependancyPhp($oContentField, $this->bRunDynamicPhpBody);
	}
	
	private function getDependancyParsing($oContentField, $mCurrentValue)
	{
		if (!$mCurrentValue && $oContentField->runDynamicParsing())
		{
			return true;
		}
		return $mCurrentValue;
	}
	
	private function getDependancyPhp($oContentField, $mCurrentValue)
	{
		if (!$mCurrentValue && $oContentField->runDynamicPhp())
		{
			return true;
		}
		return $mCurrentValue;
	}
	
	
	function runTitle()
	{
		$bRunDynamicParsing = ( $this->bRunDynamicParsingTitle && $this->bHasDynamicParsingTitle );
		$bRunDynamicPhp = ( $this->bRunDynamicPhpTitle && $this->bHasDynamicPhpTitle );
		
		$sHtml = $this->run($this->sTitle, $bRunDynamicParsing, $bRunDynamicPhp);
		$sHtml = AnwPlugins::vhook('outputhtml_run_title', $sHtml);
		return $sHtml;
	}
	
	function runHead()
	{
		$bRunDynamicParsing = ( $this->bRunDynamicParsingHead && $this->bHasDynamicParsingHead );
		$bRunDynamicPhp = ( $this->bRunDynamicPhpHead && $this->bHasDynamicPhpHead );
		
		$sHtml = $this->run($this->sHead, $bRunDynamicParsing, $bRunDynamicPhp);
		$sHtml = AnwPlugins::vhook('outputhtml_run_head', $sHtml);
		return $sHtml;
	}
	
	function runBody()
	{
		$bRunDynamicParsing = ( $this->bRunDynamicParsingBody && $this->bHasDynamicParsingBody );
		$bRunDynamicPhp = ( $this->bRunDynamicPhpBody && $this->bHasDynamicPhpBody );
		
		$sHtml = $this->run($this->sBody, $bRunDynamicParsing, $bRunDynamicPhp);
		$sHtml = AnwPlugins::vhook('outputhtml_run_body', $sHtml);
		return $sHtml;
	}
	
	//-------------
	
	function putCachedOutputHtml($sCacheKey)
	{
		AnwCache_outputHtml::putCachedOutputHtml($sCacheKey, $this);
	}
	
	//-------------
	
	private static function do_on_html($sContent, $sMethodName)
	{
		//clean only html, not php!
		$sContent = '?>'.$sContent.'<?'; //temporary add these tags to match only html
		$sPattern = '!\?>(.*?)<\?!si';
		$sContent = preg_replace_callback($sPattern, array('self', $sMethodName), $sContent);
		$sContent = substr($sContent, 2, -2); //remove added tags
		//print '===>'.htmlentities($sContent).'<hr/>';
		return $sContent;
	}
}

?>