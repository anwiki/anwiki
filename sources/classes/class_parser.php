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
 * Anwiki's logic tags parser.
 * @package Anwiki
 * @version $Id: class_parser.php 333 2010-09-29 20:28:05Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwParser
{
	protected $oPage;
	private $bCachingEnabled; //enable anwloop and cacheblocks caching?
	private $nTmpCacheBlockId = 0; //value must be kept when parsing multiple contentfield from the same page
	
	//temporary variables used for parsing
	private $asTmpLoopItemsInUse = array();
	private $aoLoopsItems = array();
	private $sLoopItem; //temporary variable for AnwLoops execution
	
	const ANWIF_GT = '>';
	const ANWIF_LT = '<';//TODO: not allowed in XML attribute
	const ANWIF_EQ = '==';
	const ANWIF_NEQ = '!=';
	
	const TAG_ANWCACHE = "anwcache";
	const TAG_ANWLOOP = "anwloop";
	const TAG_ANWIF = "anwif";
	const TAG_ANWELSE = "anwelse";
	
	
	function __construct($oPage, $bCachingEnabled)
	{
		$this->oPage = $oPage;
		$this->bCachingEnabled = $bCachingEnabled;
		self::debug("Parser: caching=".$bCachingEnabled);
	}
	
	static function getDynamicParsingTags()
	{
		$asTags = array();
		$asTags[] = self::TAG_ANWCACHE;
		$asTags[] = self::TAG_ANWLOOP;
		$asTags[] = self::TAG_ANWIF;
		$asTags[] = self::TAG_ANWELSE;
		return $asTags;
	}
	
	/**
	 * Does the content use the dynamic parsing?
	 */
	static function contentHasDynamicParsing($sHtmlAndPhpContent)
	{
		$asParsingTags = self::getDynamicParsingTags();
		
		foreach ($asParsingTags as $sTag)
		{
			if (strstr($sHtmlAndPhpContent, '<'.$sTag))
			{
				return true;
			}
		}
		return false;
	}
	
	function parse($sReturn)
	{
		//reset temporary variables but keep $nTmpCacheBlockId as it is!
		$this->asTmpLoopItemsInUse = array();
		$this->aoLoopsItems = array();
		$this->sLoopItem = null;
		
		return $this->runDynamicTransformations($sReturn);
	}
	
	//recursive function
	private function runDynamicTransformations($sReturn)
	{
		//replace <anwcache> tags (with recursive calls to runDynamicTransformations)
		$sReturn = $this->runAnwcache($sReturn);
		
		//evaluate context items - before loop tags, as context items may be used in loop filters!
		$sReturn = $this->runContextItems($sReturn);
		
		//now, execute <anwloop> tags (with recursive calls to runAnwloops)
		$sReturn = $this->runAnwloops($sReturn);
		
		//clear remaining AnwIf tags (which were delayed because not in context while parsing, but never found in context later)
		$sRegexp = self::regexpAnwIf();
		$sReturn = preg_replace($sRegexp, '['.AnwComponent::g_("local_exec_condition_error").';$1]', $sReturn);
		
		return $sReturn;
	}
	
	private function runAnwcache($sReturn)
	{
		//warning, this (recursive) regexp is dangerous for human brains :D
		$sPattern = '#<'.self::TAG_ANWCACHE.'([^<]*)>((?:[^<]|<(?!/?'.self::TAG_ANWCACHE.')|(?R))+)</'.self::TAG_ANWCACHE.'>#si';
		
		//are cache-blocks enabled ?
		if ($this->bCachingEnabled && AnwComponent::globalCfgCacheBlocksEnabled())
		{
			//execute cache-blocks:
			//if cache is found, it will just return the cached value
			//if not, it will recursively call runDynamicTransformations() on content to be cached, and then cache it
			$sReturn = preg_replace_callback($sPattern, array($this, 'cbk_anwcache'), $sReturn);
		}
		else
		{
			//strip out cache-blocks tags
			$sReturn = preg_replace($sPattern, '$2', $sReturn);
		}
		return $sReturn;
	}
	
	//does recursive calls to runDynamicTransformations()
	private function cbk_anwcache($asMatches)
	{
		
		$sCacheParams = $asMatches[1];
		$sCacheContent = $asMatches[2];
		
		//read cache parameters
		
		//cachetime - OPTIONAL
		try {
			$nCacheTime = (int)self::getTagSetting($sCacheParams, "cachetime", '!^([0-9]*?)$!si');
		}
		catch(AnwUnexpectedException $e) {
			$nCacheTime = -1;
		}
		
		$this->nTmpCacheBlockId++;
		try
		{
			$sReturn = AnwCache_cacheBlock::getCacheBlock($this->oPage, $this->nTmpCacheBlockId, $nCacheTime);
		}
		catch(AnwCacheNotFoundException $e)
		{
			//cache the result of the dynamic transformations
			$sReturn = $this->runDynamicTransformations($sCacheContent); //recursive call, cache childs first
			AnwCache_cacheBlock::putCacheBlock($this->oPage, $this->nTmpCacheBlockId, $sReturn);
			//Example:
			//<anwcache>AAA<anwloop...>{$item.title} <anwcache>BBB</anwcache></anwloop></anwcache>
			//--> execute&cache first <anwcache>BBB</anwcache>
			//--> then execute&cache <anwcache>AAA<anwloop...>{$item.title} [cached result]</anwloop></anwcache>
		}
		return $sReturn;
	}
		
	private static function getTagSetting($sLoopParams, $sAttribute, $sRegexp=false)
	{
		if (!preg_match('!'.$sAttribute.'="(.*?)"!si', $sLoopParams, $asMatches))
		{
			//AnwDebug::log("loop attribute ".$sAttribute." not found");
			throw new AnwUnexpectedException("loop attribute ".$sAttribute." not found"); 
		}
		$sValue = $asMatches[1];
		if ($sRegexp && !preg_match($sRegexp, $sValue))
		{
			AnwDebug::log("WARNING : loop attribute invalid value : ".$sAttribute." - ".$sValue);
			throw new AnwUnexpectedException("loop attribute invalid value : ".$sAttribute." - ".$sValue);
		}
		//AnwDebug::log("loop attribute found : ".$sAttribute." - ".$sValue);
		return $sValue;
	}
	
	private function runContextItems($sReturn)
	{
		$sPattern = self::regexpContext();
		$sReturn = preg_replace_callback($sPattern, array($this, 'cbk_replaceContextItem'), $sReturn);
		return $sReturn;
	}
	
	/**
	 * Evaluate a context item such as : {#now|date}
	 */
	private function cbk_replaceContextItem($asMatches)
	{
		//{#now}
		//{#now|date}
		//{#foo.bar|count}
		
		//print '<br/>';print_r($asMatches).'<hr/>';
	
		//{#page.title} [1] => page [2] => .title
		//{#page.test.body|firstwords} [1] => page [2] => .test.body [3] => |firstwords [4] => firstwords
		$sItem = $asMatches[1];
		$sInstructions = @$asMatches[2];
		$sOperator = @$asMatches[4];
		
		//retrieve context item
		$oCallItem = $this->pubcallContext($sItem);
		if (!$oCallItem)
		{
			self::debug("ERROR - parseContextItem, item not found : ".$sString);
			return $sString;
		}
		
		//execute all chained instructions
		$sResult = $this->parseChainedInstructions($oCallItem, $sInstructions, $sOperator); //result of the last call of the chain
		
		self::debug("parseContextItem, result : ".$sResult);
		return $sResult;
	}
	
	//recursive
	private function runAnwloops($sReturn)
	{
		//warning, this (recursive) regexp is dangerous for human brains :D
		//this regexp finds matchings for multiple imbricated tags <anwloop>parent<anwloop>child1</anwloop><anwloop>child2</anwloop></anwloop>
		//starting from the parent tags.
		$sPattern = '#<'.self::TAG_ANWLOOP.'([^<]*)>((?:[^<]|<(?!/?'.self::TAG_ANWLOOP.')|(?R))+)</'.self::TAG_ANWLOOP.'>#si';
		$sReturn = preg_replace_callback($sPattern, array($this, 'cbk_anwloop'), $sReturn);
		return $sReturn;
	}
	
	private function cbk_anwloop($asMatches)
	{
		$sLoopParams = $asMatches[1];
		$sLoopContent = $asMatches[2];
		
		self::debug("Starting loop: ".$sLoopParams);
		//read loop parameters
		
				
		//loop/type - REQUIRED
		try {
			$sLoopLoop = self::getTagSetting($sLoopParams, "loop", $this->regexpVariable());
			$bIsFetchLoop = false;
			// it's a normal loop : <anwloop item="$tag" loop="$item.tags" limit="5">
		}
		catch(AnwUnexpectedException $e) {
			$bIsFetchLoop = true;
			// it's a fetch loop : <anwloop item="$menu" class="menu" match="*" limit="5" sort="myname" order="asc">
		}
		
		
		//only Fetching loop can be cached (to avoid unsync problems between parent/childs loops)
		$nLoopCacheTime = -1;
		$bCacheEnabled = false;
		$bCacheBlockEnabled = false;
		$bDoCaching = false;
		
		if ($bIsFetchLoop)
		{
		
			//cachetime - OPTIONAL
			try {
				$nLoopCacheTime = (int)self::getTagSetting($sLoopParams, "cachetime", '!^([0-9]*?)$!si');
			}
			catch(AnwUnexpectedException $e) {
				//cachetime setting not found, keep default values
				$nLoopCacheTime = AnwComponent::globalCfgLoopsAutoCacheTime();
			}
			
			if ($nLoopCacheTime > 0)
			{
				$bCacheEnabled = true;
				
				//if we have specified a cachetime, look for cacheblock setting
				try {
					$sTmpCacheBlock = self::getTagSetting($sLoopParams, "cacheblock", '!^(true|false|yes|no|0|1)$!si');
					$bCacheBlockEnabled = (in_array($sTmpCacheBlock, array('true','yes','1')));
					unset($sTmpCacheBlock);
				}
				catch(AnwUnexpectedException $e) {
					//should we implicitely enable cacheblock by default?
					$bCacheBlockEnabled = AnwComponent::globalCfgLoopsAutoCacheblock();
				}
				self::debug("cacheBlockEnabled: ".$bCacheBlockEnabled);
			}
			
			
			
			//can we really use cache for this loop?
			$bDoCaching = ($bCacheEnabled && $this->bCachingEnabled && AnwComponent::globalCfgCacheLoopsEnabled());
			
			//simulate a cacheblock if enabled
			if ($bDoCaching && $bCacheBlockEnabled)
			{
				self::debug("cacheblock enabled for loop");
				$this->nTmpCacheBlockId++;
				$nCurrentCacheBlockId = $this->nTmpCacheBlockId; //we need to keep this value unchanged for caching under the same key, at the end of the function
				try
				{
					$sReturn = AnwCache_cacheBlock::getCacheBlock($this->oPage, $nCurrentCacheBlockId, $nLoopCacheTime);
					self::debug("AnwLoop found in cacheblock, returning cached result.");
					return $sReturn; //return directly
				}
				catch(AnwCacheNotFoundException $e){}
			}
			else
			{
				self::debug("cacheblock disabled for loop");
			}
		
		}
		
				
		//item - REQUIRED
		$sLoopItem = self::getTagSetting($sLoopParams, "item", '!^\$([a-z]*?)$!si'); //required
		$sLoopItem = substr($sLoopItem,1); //remove starting '$'
		if (isset($this->asTmpLoopItemsInUse[$sLoopItem]))
		{
			throw new AnwUnexpectedException("Loop item already used : ".$sLoopItem);
		}
		$this->asTmpLoopItemsInUse[$sLoopItem] = 1;
		
		
		//limit - OPTIONAL
		try {
			$nLoopLimit = (int)self::getTagSetting($sLoopParams, "limit", '!^([0-9]*?)$!si');
		}
		catch(AnwUnexpectedException $e) {
			$nLoopLimit = 999999; //TODO
		}
		
		if ($bIsFetchLoop)
		{
			//class - REQUIRED
			$sLoopClass = self::getTagSetting($sLoopParams, "class", '!^([a-z]*?)$!si');
			
			//match - OPTIONAL
			try {
				$sLoopMatch = self::getTagSetting($sLoopParams, "match", '!^([^"]*?)$!si');
			}
			catch(AnwUnexpectedException $e) {
				$sLoopMatch = '*';
			}
			
			//morelangs - OPTIONAL
			$asLoopLangs = array($this->oPage->getLang());
			try {
				$sTmp = self::getTagSetting($sLoopParams, "morelangs", '!^([a-z,]*?)$!si');
				
				$asTmpLangs = explode(',',$sTmp);
				foreach ($asTmpLangs as $sTmpLang)
				{
					$sTmpLang = trim($sTmpLang);
					if (Anwi18n::langExists($sTmpLang) && !in_array($sTmpLang, $asLoopLangs))
					{
						$asLoopLangs[] = $sTmpLang;
					}
				}
			}
			catch(AnwUnexpectedException $e) {}
			
			//sort - OPTIONAL
			try {
				$sLoopSort = self::getTagSetting($sLoopParams, "sort", '!^([a-z]*?)$!si');
			}
			catch(AnwUnexpectedException $e) {
				$sLoopSort = AnwUtils::SORT_BY_NAME; //TODO secure pattern
			}
			
			//order - OPTIONAL
			try {
				$sLoopOrder = self::getTagSetting($sLoopParams, "order", '!^('.AnwUtils::SORTORDER_ASC.'|'.AnwUtils::SORTORDER_DESC.')$!si');
			}
			catch(AnwUnexpectedException $e) {
				$sLoopOrder = AnwUtils::SORTORDER_ASC;
			}
			
			//filter - OPTIONAL
			//filter="required=true,name:test*"
			$asLoopFilters = array();
			try {
				$asFILTERS_OPERATORS = array(
					AnwUtils::FILTER_OP_EQUALS,
					AnwUtils::FILTER_OP_LIKE,
					AnwUtils::FILTER_OP_LT,
					AnwUtils::FILTER_OP_GT,
					AnwUtils::FILTER_OP_LE,
					AnwUtils::FILTER_OP_GE
				);
				$sTmp = self::getTagSetting($sLoopParams, "filter", '!^([a-z0-9_\-,#|'.implode('',$asFILTERS_OPERATORS).'\*]*?)$!si');
				
				$asTmpFilters = explode(',',$sTmp);
				foreach ($asTmpFilters as $sTmpFilter)
				{
					$sTmpFilter = trim($sTmpFilter);
					
					try{
						list($sFilterOp1, $sFilterOperator, $sFilterOp2) = self::parseOperator($asFILTERS_OPERATORS, $sTmpFilter);
						$sFilterOp1 = $this->getOperandValue($sFilterOp1);
						$sFilterOp2 = $this->getOperandValue($sFilterOp2);
						
						$asLoopFilters[] = array(
							'FIELD' => $sFilterOp1,
							'OPERATOR' => $sFilterOperator,
							'VALUE' => $sFilterOp2
						);
					}
					catch(AnwUnexpectedException $e){}
				}
			}
			catch(AnwUnexpectedException $e) {}
		}
		
		//iterate over the loop
		$sReturn = "";
		$aoLoopsItems = array();
		try {
			if ($bIsFetchLoop)
			{
				self::debug("anwloop/fetch found");
				$aoLoopsItems = $this->getAnwloopFetchItems($nLoopLimit, $sLoopClass, $sLoopMatch, $asLoopLangs, $sLoopSort, $sLoopOrder, $asLoopFilters, $bDoCaching, $nLoopCacheTime);
			}
			else
			{
				self::debug("anwloop/loop found");				
				$aoLoopsItems = $this->parseLoopVariable('{'.$sLoopLoop.'}'); //throws an exception if error
			}
			
			//run the loop!
			foreach ($aoLoopsItems as $oLoopItem)
			{
				self::debug("anwloop iteration");
				$this->aoLoopsItems[$sLoopItem] = $oLoopItem; //we may need it during recursive calls
				$sReturn .= $this->runAnwloopIteration($sLoopContent, $sLoopItem);
				unset($this->aoLoopsItems[$sLoopItem]);
			}
		}
		catch(Exception $e) {
			self::debug("! LOOP ERROR!");
			$sReturn = AnwComponent::g_("local_exec_loop_error");
		}
		
		unset($this->asTmpLoopItemsInUse[$sLoopItem]);
		
		//put whole content in cache if cacheblock enabled
		if ($bDoCaching && $bCacheBlockEnabled)
		{
			AnwCache_cacheBlock::putCacheBlock($this->oPage, $nCurrentCacheBlockId, $sReturn);
		}
		
		return $sReturn;
	}
	
	
	private function getAnwloopFetchItems($nLoopLimit, $sLoopClass, $sLoopMatch, $asLoopLangs, $sLoopSort, $sLoopOrder, $asLoopFilters, $bDoCaching, $nLoopCacheTime)
	{
		$aoLoopsItems = array();
		try {
			//try to get it from cache
			if (!$bDoCaching) throw new AnwCacheNotFoundException();
			$sCacheKey = implode('-', array($nLoopLimit, $sLoopClass, $sLoopMatch, implode('.',$asLoopLangs), $sLoopSort, $sLoopOrder, md5(serialize($asLoopFilters)) ));
			$aoLoopsItems = AnwCache_loops::getCachedLoop($this->oPage->getId(), $sCacheKey, $nLoopCacheTime);
		}
		catch(AnwCacheNotFoundException $e) {
			//cache not found or disabled
			
			//fetch loop datas from database
			$aoLoopsItems = AnWiki::tag_anwloop($sLoopMatch, $sLoopClass, $asLoopLangs, $nLoopLimit, $sLoopSort, $sLoopOrder, $asLoopFilters);
			
			if ($bDoCaching)
			{
				//put result in cache for next time
				AnwCache_loops::putCachedLoop($this->oPage->getId(), $sCacheKey, $aoLoopsItems);
			}
		}
		return $aoLoopsItems;
	}
	
	private function runAnwloopIteration($sLoopContent, $sLoopItem)
	{
		if ($this->sLoopItem!=null) throw new AnwUnexpectedException("sLoopItem not null");
		$this->sLoopItem = $sLoopItem; //TODO crappy - pass current item to the callback
		
		//evaluate <anwif> related to current loop in order to only keep <anwif> content or <anwelse> content
		$sPattern = self::regexpAnwIf();
		$sLoopContent = preg_replace_callback($sPattern, array($this, 'anwloop_cbk_anwif'), $sLoopContent);
		
		//print the value of variables related to the current loop : {$var.aaa}
		$sPatternItem = self::regexpVariableItem($sLoopItem);
		$sLoopContent = preg_replace_callback($sPatternItem, array($this, 'anwloop_cbk_variable'), $sLoopContent);
		$this->sLoopItem = null;
		
		//recursive call for imbricated loops
		$sLoopContent = $this->runAnwloops($sLoopContent);
		
		return $sLoopContent;
	}
	
	private function anwloop_cbk_variable($asMatches)
	{
		return $this->parseLoopVariable($asMatches[0]);
	}
	
	
	//<anwif cond="{$release.roadmap.bugfixes|count}>0">...[<anwelse/>...]</anwif>
	private function anwloop_cbk_anwif($asMatches)
	{
		$sCondInstructions = $asMatches[1];
		$sIfContents = $asMatches[2];
		
		$asKnownOperators = array(
			self::ANWIF_GT, self::ANWIF_LT, 
			self::ANWIF_EQ, self::ANWIF_NEQ
		);
		
		$sReturn = '';
		try
		{
			list($sOp1, $sOperator, $sOp2) = self::parseOperator($asKnownOperators, $sCondInstructions);
		
			//evaluate operands - throws AnwUnexpectedException if variable is not in current context
			$sOp1Value = $this->getOperandValue($sOp1);
			$sOp2Value = $this->getOperandValue($sOp2);
			
			//evaluate the test
			$bTestResult = false;
			switch($sOperator)
			{
				case self::ANWIF_GT:
					$bTestResult = ($sOp1Value > $sOp2Value);
					break;
				case self::ANWIF_LT:
					$bTestResult = ($sOp1Value < $sOp2Value);
					break;
				case self::ANWIF_EQ:
					$bTestResult = ($sOp1Value == $sOp2Value);
					break;
				case self::ANWIF_NEQ:
					$bTestResult = ($sOp1Value != $sOp2Value);
					break;
				default:
					self::debug("ERROR anwif : Unrecognized operator : ".$sOperator);
					throw new AnwUnexpectedException("Unrecognized operator : ".$sOperator);
			}
			self::debug("anwif: $sOp1 ($sOp1Value) ; $sOperator ; $sOp2 ($sOp2Value) :: ".($bTestResult?'TRUE':'FALSE'));
			
			//return the appropriate block
			$sRegexpElse = '!(.*?)<'.self::TAG_ANWELSE.'/>(.*?)$!si';
			
			$sTestTrueContents = $sIfContents;
			$sTestFalseContents = "";
			if ( preg_match($sRegexpElse, $sIfContents, $asMatchesElse) )
			{
				$sTestTrueContents = $asMatchesElse[1];
				$sTestFalseContents = $asMatchesElse[2];
			}
			
			if ($bTestResult)
			{
				//test is verified, let's add <anwif> contents but not <anwelse>
				$sReturn = $sTestTrueContents;
			}
			else
			{
				//test is not verified, let's add only <anwelse> contents (if any)
				$sReturn = $sTestFalseContents;
			}
		}
		catch(AnwParserException $e){
			//variable was not in current context, parse it later.
			self::debug("anwif : parse it later");
			return $asMatches[0]; //just return original value
		}
		catch(AnwUnexpectedException $e)
		{
			self::debug("anwif: ERROR, condition not understood : ".$sCondInstructions);
			$sReturn = '['.AnwComponent::g_("local_exec_condition_error").';'.$sCondInstructions.']';
		}
		return $sReturn;
	}
	
	//throws AnwUnexpectedException if gets a dynamic variable not in current context
	private function getOperandValue($sReturn)
	{
		//$oLoopItem = $this->aoLoopsItems[$sLoopItem];
				
		//evaluate items if they are into the condition
		//such as: if({$item.name} == ...)
		self::debug("getOperandValue: getting value of: ".$sReturn);//." with:".self::regexpVariableItem($sLoopItem));
		
		//is it a dynamic variable?
		$sPatternItem = self::regexpVariable();
		if (preg_match($sPatternItem, '{'.$sReturn.'}', $asMatchesItem))
		{
			//is this dynamic variable in the context of the current loop?
			$sLoopItem = $this->sLoopItem;
			$sPatternItem = self::regexpVariableItem($sLoopItem);
			if (preg_match($sPatternItem, '{'.$sReturn.'}', $asMatchesItem))
			{
				self::debug("getOperandValue: dynamic variable in context, evaluating...");
				$sReturn = $this->parseLoopVariable($asMatchesItem[0]);
			}
			else
			{
				//this dynamic variable is not in current context, we will eval it later...
				self::debug("getOperandValue: dynamic variable is not in current context, parse it later!");
				throw new AnwParserException("variable not in context, parse it later");
			}
		}
		else
		{
			self::debug("getOperandValue: evaluating constant... ".$sReturn);
			//ignore surrounding apostrophes/quotes
			//such as: if('...' == ...)
			$sPatternItem = '![\'|"](.*?)[\'|"]!si';//TODO: handle pubcall args
			if (preg_match($sPatternItem, $sReturn, $asMatchesItem))
			{
				$sReturn = $asMatchesItem[1];
			}
		}
		return $sReturn;
	}
	
	private static function parseOperator($asOperators, $sCondition)
	{
		//search for operator
		foreach ($asOperators as $sOperator)
		{
			$asInstructions = explode($sOperator, $sCondition);
			if (count($asInstructions) == 2)
			{
				return array($asInstructions[0], $sOperator, $asInstructions[1]);
			}
		}
		throw new AnwUnexpectedException("parseOperator : no matching operator found");
	}
		
	private static function regexpVariableItem($sItem)
	{
		$sPattern = '!{\$'.$sItem.'\.(['.self::expVariableSimple().'\.\|'.self::expVariableParameters().']*?)}!si';
		return $sPattern;
	}
	
	private static function regexpVariable()
	{
		$sPattern = '!\$(['.self::expVariableSimple().'\.\|'.self::expVariableParameters().']*?)!si';
		return $sPattern;
	}
	
	private static function regexpContext()
	{
		$sPattern = self::doRegexpChained('#');
		return $sPattern;
	}
	
	private static function doRegexpChained($sPrefixCharacter)
	{
		$sPattern = '!{'.$sPrefixCharacter.'(['.self::expVariableSimple().']*)(['.self::expVariableSimple().'\.]*)(\|(['.self::expVariableParameters().']*)){0,1}}!si';
		return $sPattern;
	}
	
	private static function regexpLoopVariable()
	{
		$sPattern = self::doRegexpChained('\\$');
		return $sPattern;
	}
	
	private static function regexpAnwIf()
	{
		$sPattern = '!<'.self::TAG_ANWIF.' cond="(.*?)">(.*?)</'.self::TAG_ANWIF.'>!si';
		return $sPattern;
	}
	
	private static function expVariableSimple()
	{
		$sPattern = 'a-z0-9\[\]';
		return $sPattern;
	}
	
	private static function expVariableParameters()
	{
		$sPattern = 'a-z,0-9';
		return $sPattern;
	}
	
	/**
	 * Evaluate a variable such as : {$release.changelog.bugs|count}
	 */
	private function parseLoopVariable($sString)
	{
		//{$page.title}
		//{$page.body|firstwords,20}
		//{$release.changelog.info}
		//{$release.changelog.bugs|count}
		
		$sResult = $sString;
		
		//self::debug("parsing variable: ".$sString);
		$sPattern = self::regexpLoopVariable();
		if (preg_match($sPattern, $sString, $asMatches))
		{
			//print '<br/>';print_r($asMatches).'<hr/>';
		
			//{$page.title} [1] => page [2] => .title
			//{$page.test.body|firstwords} [1] => page [2] => .test.body [3] => |firstwords [4] => firstwords
		
			$sItem = $asMatches[1];
			$sInstructions = @$asMatches[2];
			$sOperator = @$asMatches[4];
			
			//retrieve item
			if (!isset($this->aoLoopsItems[$sItem]))
			{
				self::debug("ERROR - parseLoopVariable, item not found : ".$sString);
				return $sString;
			}
			
			//execute all chained instructions
			$oCallItem = $this->aoLoopsItems[$sItem];
			$sResult = $this->parseChainedInstructions($oCallItem, $sInstructions, $sOperator); //result of the last call of the chain
		}
		else
		{
			self::debug("ERROR - parseLoopVariable, no match : ".$sString);
		}
		self::debug("parsing variable: ".$sString." ; result: ".$sResult);
		return $sResult;
	}
	
	// TODO make this overridable by contentclass?
	// TODO allow args for contexts such as strtotime(arg1)?
	private function pubcallContext($sArg)
	{
		switch($sArg)
		{
			case "time":
				// timestamp for exact time
				return AnwUtils::time();
				break;
				
			case "today":
				// timestamp for midnight today
				return AnwUtils::timeToday();
				break;
			
			default:
				$sReturn = AnwPlugins::vhook('parser_pubcallcontext_default', $sArg);
				return $sReturn;
				break;
		}
	}
	
	private function parseChainedInstructions($oCallItem, $sInstructions, $sOperator)
	{
		//execute all chained instructions
		$asInstructions = explode('.', $sInstructions);
		
		/*if (!($oCallItem instanceof AnwContentPage)) $mLastValue = $oCallItem->getContent(); //we are in a fetching loop, we have a Page
		else */$mLastValue = $oCallItem; //we are in a loop-loop, we already have a SubContent
		
		while (count($asInstructions)>0)
		{
			$sCallInstruction = array_shift($asInstructions);
			if (trim($sCallInstruction)!="") //avoid empty strings from explode()
			{
				self::debug("pubcall chain: ".get_class($oCallItem)."->pubcall(".$sCallInstruction.",".get_class($mLastValue).")");
				if (!$oCallItem instanceof AnwPubcallCapability)
				{
					self::debug("ERROR - parseLoopVariable, looping on instructions, callItem is not pubcallable");
					return $sString;
				}
				$mLastValue = $oCallItem->pubcall($sCallInstruction, $mLastValue);
				$oCallItem = $mLastValue;
			}				
		}
		
		//execute operator if any
		if ($sOperator)
		{
			//do we have args for operator?
			$asOperatorArgs = array();
			$asTmpOperatorArgs = explode(',', $sOperator);
			if (count($asTmpOperatorArgs)>1)
			{
				$sOperator = array_shift($asTmpOperatorArgs);
				$asOperatorArgs = $asTmpOperatorArgs;
			}
			unset($asTmpOperatorArgs);
			//self::debug("pubcall chain: pubcallOperator(".$sOperator.",".$mLastValue.")");
			$mLastValue = AnwContentClassPage::pubcallOperator($sOperator, $mLastValue, $this->oPage->getLang(),/*AnwAction::getActionLang()*/ $asOperatorArgs);
		}
		
		$sResult = $mLastValue; //result of the last call of the chain
		return $sResult;
	}
	
	private function debug($sMsg)
	{
		AnwDebug::log("(AnwParser) ".$sMsg);
	}
}

/**
 * Cache manager for cacheBlock.
 */
class AnwCache_cacheBlock extends AnwCache
{
	private static function filenameCacheBlock($oPage, $sCacheKey)
	{
		return self::cachedirOutput($oPage).'anwcacheblock-'.md5($sCacheKey).'.php';
	}
	
	static function putCacheBlock($oPage, $sCacheKey, $sStr)
	{
		$sCacheFile = self::filenameCacheBlock($oPage, $sCacheKey);
		self::debug("putting cacheBlock : ".$sCacheFile." (".$oPage->getName().") (key: ".$sCacheKey.")"); 
		self::putCachedObject($sCacheFile, $sStr);
	}
	
	static function getCacheBlock($oPage, $sCacheKey, $nDelayExpiry)
	{
		$sCacheFile = self::filenameCacheBlock($oPage, $sCacheKey);
		$oObject = (string)self::getCachedObject($sCacheFile, $nDelayExpiry);
		self::debug("cacheBlock found : ".$sCacheFile." (".$oPage->getName().") (key: ".$sCacheKey.")");
	 	return $oObject;
	}
}

/**
 * Cache manager for loops.
 */
class AnwCache_loops extends AnwCache
{
	private static function filenameCachedLoop($nPageId, $sCacheKey)
	{
		self::debug("loop key: ".$sCacheKey);
		return self::symlinkPageById($nPageId).'/anwloop-'.md5($sCacheKey).'.php';
	}
	
	static function putCachedLoop($nPageId, $sCacheKey, $amLoopResults)
	{
		$sCacheFile = self::filenameCachedLoop($nPageId, $sCacheKey);
		self::debug("putting cachedLoop : ".$sCacheKey); 
		self::putCachedObject($sCacheFile, $amLoopResults);
	}
	
	static function getCachedLoop($nPageId, $sCacheKey, $nDelayExpiry)
	{
		$sCacheFile = self::filenameCachedLoop($nPageId, $sCacheKey);
		$oObject = self::getCachedObject($sCacheFile, $nDelayExpiry);
		if (!is_array($oObject))
	 	{
	 		self::debug("cachedLoop invalid : ".$sCacheFile);
	 		throw new AnwCacheNotFoundException();
	 	}
	 	else
	 	{
	 		self::debug("cachedLoop found : ".$sCacheFile);
	 	}
		return $oObject;
	}
}

?>