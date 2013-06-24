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
 * Main Diff algorithm for comparing two XML documents and finding precisely what changed:
 * It finds added nodes, deleted nodes, kept nodes, edited nodes, moved nodes...
 * Algorithm created from scratch by Antoine Walter.
 * @package Anwiki
 * @version $Id: class_diffs.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwDiff
{
	const TYPE_ADDED=1;
	const TYPE_DELETED=2;
	const TYPE_KEPT=3;
	const TYPE_EDITED=4;
	const TYPE_DIFFS=5;
	const TYPE_MOVED=6;
		
	function getDiffType()
	{
		$sClassName = get_class($this);
		switch($sClassName)
		{
			case "AnwDiffAdded": $sReturn = self::TYPE_ADDED; break;
			case "AnwDiffDeleted": $sReturn = self::TYPE_DELETED; break;
			case "AnwDiffKept": $sReturn = self::TYPE_KEPT; break;
			case "AnwDiffEdited": $sReturn = self::TYPE_EDITED; break;
			case "AnwDiffs": $sReturn = self::TYPE_DIFFS; break;
			case "AnwDiffMoved": $sReturn = self::TYPE_MOVED; break;
		}
		return $sReturn;
	}
	
	abstract function hidePhpCode();
	abstract function showPhpCode();
	function clearMovedNodes() {}
	abstract function debugHtml();
}

class AnwDiffs extends AnwDiff
{
	private $oNodeRootOlder;
	private $oNodeRootNewer;
	private $oNodeParent;
	private $aoNewDiffs;
	
	function __construct($oNodeRootOlder, $oNodeRootNewer, $oNodeParent=false)
	{
		$this->oNodeRootOlder = $oNodeRootOlder;
		$this->oNodeRootNewer = $oNodeRootNewer;
		$this->oNodeParent = $oNodeParent;
	}
	
	function hasChanges()
	{
		$aoDiffs = $this->getAllDiffs();
		if (count($aoDiffs) == 0)
		{
			return false;
		}
		foreach ($aoDiffs as $oDiff)
		{
			if ($oDiff->getDiffType() != AnwDiff::TYPE_KEPT)
			{
				//change found!
				return true;
			}
		}
		return false;
	}
	
	function hidePhpCode()
	{
		$aoDiffs = $this->getAllDiffs();
		foreach ($aoDiffs as $oDiff)
		{
			$oDiff->hidePhpCode();
		}
	}
	
	function showPhpCode()
	{
		$aoDiffs = $this->getAllDiffs();
		foreach ($aoDiffs as $oDiff)
		{
			$oDiff->showPhpCode();
		}
	}
	
	function clearMovedNodes()
	{
		$aoDiffs = $this->getAllDiffs();
		foreach ($aoDiffs as $oDiff)
		{
			$oDiff->clearMovedNodes();
		}
	}
	
	function getNodeRootNew()
	{
		return $this->oNodeRootNewer;
	}
	
	function getNodeParent()
	{
		return $this->oNodeParent;
	}
	
	function setDiffs($aoDiffs)
	{
		$this->aoNewDiffs = $aoDiffs;
	}
	
	function getAllDiffs()
	{
		if (!$this->aoNewDiffs)
		{
			//AnwDebug::logdetail("---------- begin getAllDiffs() ----------");
			$bFindMovedEdited = ($this->oNodeParent ? false : true); //only search on first call, when all tree has been built
			$this->aoNewDiffs = self::xmlDiff($this->oNodeRootOlder, $this->oNodeRootNewer, $bFindMovedEdited);
			//AnwDebug::logdetail("---------- end getAllDiffs() ----------");
		}
		else
		{
			//AnwDebug::logdetail("getAllDiffs() -- returning cached newDiffs");
		}
		return $this->aoNewDiffs;
	}
	
	function getTagOpen()
	{
		$sTagOpen = "";
		if ($this->getNodeParent())
		{
			$sTagOpen = AnwUtils::xmlDumpNodeOpen($this->getNodeParent());
		}
		return $sTagOpen;
	}
	
	function getTagClose()
	{
		$sTagClose = "";
		if ($this->getNodeParent())
		{
			$sTagClose = '</'.$this->getNodeParent()->nodeName.'>';
		}
		return $sTagClose;
	}
	
	private function xmlDiff($node1, $node2, $bFindMovedEdited)
	{
		$aoDiffs = self::xmlDiffBasic($node1, $node2);
		
		if ($bFindMovedEdited) //original call
		{
			
			/*print "************1. BEFORE MOVED**************".'<ul style="font-size:12px; font-family:verdana">';
			foreach ($aoDiffs as $oDiff){
				print $oDiff->debugHtml();
			}
			print '</ul>';*/
			
			//must wait to have the whole diffs tree to search for moved nodes
			AnwDebug::startBench("findMovedNodes", true);
			$aoDiffs = self::findMovedNodes($aoDiffs); //find moved nodes
			AnwDebug::stopBench("findMovedNodes");
			
			/*print "************2. BEFORE EDITED**************".'<ul style="font-size:12px; font-family:verdana">';
			foreach ($aoDiffs as $oDiff){
				print $oDiff->debugHtml();
			}
			print '</ul>';*/
			
			AnwDebug::startBench("findEditedNodes", true);
			$aoDiffs = self::findEditedNodes($aoDiffs); //now find edited nodes
			AnwDebug::stopBench("findEditedNodes");
			
			/*print "************3. AFTER EDITED**************".'<ul style="font-size:12px; font-family:verdana">';
			foreach ($aoDiffs as $oDiff){
				print $oDiff->debugHtml();
			}
			print '</ul>';*/
		}
		return $aoDiffs;
	}
	
	private static function xmlDiffBasic($node1, $node2)
	{
		$childsOld = $node1->childNodes;
		$childsNew = $node2->childNodes;
		$aoDiffs = array();
		
		$iStartNewChild = 0; // next childNew offset
		
		foreach ($childsOld as $oChildOld)
		{
			$bMatch = false;
			
			//AnwDebug::logdetail(" * Now, searching : ".htmlentities(AnwUtils::xmlDumpNode($oChildOld)));
			
			// search a childNew similar to $oChildOld, starting at offset $iStartNewChild
			
			//$nblinesdiff=0;
			
			for ($iNewChild = $iStartNewChild; $iNewChild < $childsNew->length; $iNewChild++) 
			{
				$oChildNew = $childsNew->item($iNewChild);
				
				//AnwDebug::logdetail("   -> Comparing with ".htmlentities(AnwUtils::xmlDumpNode($oChildNew)));
				
				/*
				if ( $iNewChild==($childsNew->length) || /*$nblinesdiff > 0 &&*//* AnwXml::xmlAreSimilarNodes($oChildOld, $oChildNew) )
				{
					//---------------------------------------------------------
					//   ADDED NODES
					//---------------------------------------------------------
					for($i = $iStartNewChild; $i < $iNewChild; $i++)
					{
						AnwDebug::log(" -> added : ".htmlentities(AnwUtils::xmlDumpNode($childsNew->item($i))));
						$oDiff = new AnwDiffAdded($childsNew->item($i));
						$aoDiffs[] = $oDiff;
					}
				}*/
			
				$bTestSimilar = AnwXml::xmlAreSimilarNodes($oChildOld, $oChildNew);
				if ($bTestSimilar)
				{
					//---------------------------------------------------------
					//   ADDED NODES
					//---------------------------------------------------------
					for($i = $iStartNewChild; $i < $iNewChild; $i++)
					{
						$oChildNewAdded = $childsNew->item($i);
						//AnwDebug::logdetail("[]added (before kept) : ".htmlentities(AnwUtils::xmlDumpNode($oChildNewAdded)));
						$aoDiffs[] = new AnwDiffAdded($oChildNewAdded);
						$iStartNewChild++;
					}
					
					//---------------------------------------------------------
					//   KEPT NODE
					//---------------------------------------------------------
					if ($iStartNewChild != $iNewChild) throw new AnwUnexpectedException("$iStartNewChild should equal to $iNewChild");
					
					//AnwDebug::logdetail("[]kept : ".htmlentities(AnwUtils::xmlDumpNode($oChildOld)));
					$aoDiffs[] = new AnwDiffKept($oChildOld);
					$iStartNewChild++;
					//$iStartNewChild = $iNewChild+1;
					
					$bMatch = true;
					break;
				}
				else
				{
					//no match at this line, try next one
					//$nblinesdiff++;
				}
			}
			//---------------------------------------------------------
			//   ADDED NODES
			//---------------------------------------------------------
			for($i = $iStartNewChild; $i < $iNewChild; $i++)
			{
				$oChildNewAdded = $childsNew->item($i);
				//AnwDebug::logdetail("[]added : ".htmlentities(AnwUtils::xmlDumpNode($oChildNewAdded)));
				$aoDiffs[] = new AnwDiffAdded($oChildNewAdded);
				$iStartNewChild++;
			}
			
			if ($bMatch == false)
			{
				//---------------------------------------------------------
				//   DELETED NODE
				//---------------------------------------------------------
				//no match found -> the line has been deleted
				//AnwDebug::logdetail("[]deleted : ".htmlentities(AnwUtils::xmlDumpNode($oChildOld)));
				$oDiff = new AnwDiffDeleted($oChildOld);
				$aoDiffs[] = $oDiff;
			}
		}
		
		//check that nothing was added at bottom of the file
		for($i = $iStartNewChild; $i < $childsNew->length; $i++)
		{
			//---------------------------------------------------------
			//   ADDED NODES
			//---------------------------------------------------------
			$oChildNewAdded = $childsNew->item($i);
			//AnwDebug::logdetail("[]added (at end) : ".htmlentities(AnwUtils::xmlDumpNode($oChildNewAdded)));
			$oDiff = new AnwDiffAdded($oChildNewAdded);
			$aoDiffs[] = $oDiff;
		}
		
		/*print "************BEFOREDIFFS**************".'<ul style="font-size:12px; font-family:verdana">';
		foreach ($aoDiffs as $oDiff){
			print $oDiff->debugHtml();
		}
		print '</ul>';*/
		
		////$aoDiffs = self::findMovedNodes($aoDiffs); //find moved nodes - first pass
		$aoDiffs = self::findDiffsNodes($aoDiffs); //find diffs only
		return $aoDiffs;
	}
	
	
	private static function findDiffsNodes($aoDiffs)
	{
		//AnwDebug::logdetail(" ------ findDiffsNodes ------ ");
		$aoNewDiffs = array();
		
		//find edited nodes (= near deletion + addition with the same tag)
		$nCountDiffs = count($aoDiffs);
		for($i=0; $i<$nCountDiffs; $i++) // don't use foreach here !!!
		{
			//$bKeepDiffUnchanged = true;
			if (isset($aoDiffs[$i])) // test needed as $aoDiffs is modified in the loop
			{
				$oDiff = $aoDiffs[$i];
				
				if ($oDiff->getDiffType() == AnwDiff::TYPE_ADDED || $oDiff->getDiffType() == AnwDiff::TYPE_DELETED)
				{
					$j = self::findEditedNodes_findNext($aoDiffs,$i);
					if ($j)
					{
						if ($oDiff->getDiffType() == AnwDiff::TYPE_ADDED)
						{
							$iDiffAdded = $i;
							$iDiffDeleted = $j;
						}
						else
						{
							$iDiffAdded = $j;
							$iDiffDeleted = $i;
						}
						$oDiffAdded = $aoDiffs[$iDiffAdded];
						$oDiffDeleted = $aoDiffs[$iDiffDeleted];
						//AnwDebug::logdetail("?!? ".htmlentities(AnwUtils::xmlDumpNode($oDiffAdded->getNode()))." (".$oDiffAdded->getNode()->nodeName.")");
						if ( AnwXml::xmlIsTextNode($oDiffAdded->getNode()) || AnwXml::xmlIsUnmodifiableBlockNode($oDiffAdded->getNode()) )
						{
							//---------------------------------------------------------
							//   EDITED NODE
							//---------------------------------------------------------
						}
						else
						{
							//---------------------------------------------------------
							//   DIFFS NODE
							//---------------------------------------------------------
							$oDiffReplacingAdded = new AnwDiffs($oDiffDeleted->getNode(), $oDiffAdded->getNode(), $oDiffAdded->getNode());
							//AnwDebug::logdetail("findEditedNodes : AnwDiffs found".htmlentities(AnwUtils::xmlDumpNode($oDiffDeleted->getNode()))." --> ".htmlentities(AnwUtils::xmlDumpNode($oDiffAdded->getNode())));
							
							//pre-calculate diffs - recursive call to xmlDiff()
							$oDiffReplacingAdded->getAllDiffs();
							
							//replace AnwDiffAdded with AnwDiffEdited, remove AnwDiffDeleted
							//$bKeepDiffUnchanged = false;
							//$aoNewDiffs[] = $oDiffReplacingAdded; //replace AnwDiffAdded
							$oDiff = $oDiffReplacingAdded; //replace AnwDiffAdded
							unset($aoDiffs[$iDiffDeleted]); //remove AnwDiffDeleted
						}
					}
				}
				//if ($bKeepDiffUnchanged)
				//{
				$aoNewDiffs[] = $oDiff;
				//}
			}
		}
		
		return $aoNewDiffs;
	}
	
	
	private static function findEditedNodes($aoDiffs)
	{
		//AnwDebug::logdetail(" ------ findEditedNodes ------ ");
		$aoNewDiffs = array();
		
		//find edited nodes (= near deletion + addition with the same tag)
		$nCountDiffs = count($aoDiffs);
		for($i=0; $i<$nCountDiffs; $i++) // don't use foreach here !!!
		{
			//$bKeepDiffUnchanged = true;
			if (isset($aoDiffs[$i])) // test needed as $aoDiffs is modified in the loop
			{
				$oDiff = $aoDiffs[$i];
				
				if ($oDiff->getDiffType() == AnwDiff::TYPE_ADDED 
					|| ($oDiff->getDiffType() == AnwDiff::TYPE_DELETED && !$oDiff->hasMovedDiff()) )
				{
					$j = self::findEditedNodes_findNext($aoDiffs,$i);
					if ($j)
					{
						if ($oDiff->getDiffType() == AnwDiff::TYPE_ADDED)
						{
							$iDiffAdded = $i;
							$iDiffDeleted = $j;
						}
						else
						{
							$iDiffAdded = $j;
							$iDiffDeleted = $i;
						}
						$oDiffAdded = $aoDiffs[$iDiffAdded];
						$oDiffDeleted = $aoDiffs[$iDiffDeleted];
						//AnwDebug::logdetail("?!? ".htmlentities(AnwUtils::xmlDumpNode($oDiffAdded->getNode()))." (".$oDiffAdded->getNode()->nodeName.")");
						if ( AnwXml::xmlIsTextNode($oDiffAdded->getNode()) || AnwXml::xmlIsPhpNode($oDiffAdded->getNode()) )
						{
							//---------------------------------------------------------
							//   EDITED NODE
							//---------------------------------------------------------
							//AnwDebug::logdetail("?!?---> ".$oDiffAdded->getNode()->nodeValue);
							$oDiffReplacingAdded = new AnwDiffEdited($oDiffAdded, $oDiffDeleted);
							//AnwDebug::logdetail("findEditedNodes : AnwDiffEdited found : ".htmlentities(AnwUtils::xmlDumpNode($oDiffDeleted->getNode()))." --> ".htmlentities(AnwUtils::xmlDumpNode($oDiffAdded->getNode())));
							
							//replace AnwDiffAdded with AnwDiffEdited, remove AnwDiffDeleted
							//$bKeepDiffUnchanged = false;
							//$aoNewDiffs[] = $oDiffReplacingAdded; //replace AnwDiffAdded
							$oDiff = $oDiffReplacingAdded; //replace AnwDiffAdded
							unset($aoDiffs[$iDiffDeleted]); //remove AnwDiffDeleted
						}
						else
						{
							//throw new AnwUnexpectedException("We shouldn't find anymore diffs here");
							//---------------------------------------------------------
							//   DIFFS NODE
							//---------------------------------------------------------
						}
					}
				}
				else if($oDiff->getDiffType() == AnwDiff::TYPE_DIFFS)
				{
					//continue search on childs
					$aoSubDiffs = self::findEditedNodes($oDiff->getAllDiffs());
					$oDiff->setDiffs($aoSubDiffs);
				}
				//if ($bKeepDiffUnchanged)
				//{
				$aoNewDiffs[] = $oDiff;
				//}
			}
		}
		
		return $aoNewDiffs;
	}
	
	private static function findEditedNodes_findNext($aoDiffs, $iRef)
	{
		$oDiffRef = $aoDiffs[$iRef];
		
		$sTargetType = ( $oDiffRef->getDiffType() == AnwDiff::TYPE_ADDED ? AnwDiff::TYPE_DELETED : AnwDiff::TYPE_ADDED );
		$sNodename = $oDiffRef->getNode()->nodeName;
		$iStart = $iRef+1;
		
		foreach ($aoDiffs as $i => $oDiff)
		{
			//AnwDebug::log("--findEditedNodes_findNext : <ul>".$oDiff->debugHtml()."</ul>");
			if ($i >= $iStart)
			{
				if ($oDiff->getDiffType() == $sTargetType && $oDiff->getNode()->nodeName == $sNodename)
				{
					if ($oDiff->getDiffType() == AnwDiff::TYPE_DELETED && $oDiff->hasMovedDiff())
					{
						//we don't associate deleted nodes having moved diffs for edited nodes
						//continue the search...
					}
					else
					{
						return $i;
					}
				}
				else if ($oDiff->getDiffType() == AnwDiff::TYPE_KEPT)
				{
					return false;
				}
				//below : very unsure????????
				else if ($oDiff->getDiffType() == $sTargetType/* || $oDiff->getDiffType() == AnwDiff::TYPE_DIFFS*/)
				{
					//there can be <deleted1><deleted2><added1><added2> ?????TODO
					return false;
				}
				else if ($oDiff->getDiffType() == AnwDiff::TYPE_DIFFS)
				{
					return false; //testApplyDiffsMoved19
				}
			}
		}
		return false;
	}
	
	
	private static function findMovedNodes($aoDiffs, $aoRootDiffs=false)
	{
		//AnwDebug::logdetail(" ------ findMovedNodes ------ ");
		
		if (!$aoRootDiffs)
		{
			$aoRootDiffs = $aoDiffs;
		}
		
		//find moved nodes = deletion + addition with the same nodeName and nodeValue
		
		//2 passes are necessary, otherwise we could get sub-level movednodes included in main-level movednodes...
		//first pass
		$aoNewDiffs = array();
		$nCountDiffs = count($aoDiffs);
		for ($i=0; $i<$nCountDiffs; $i++)
		{
			$oDiff = $aoDiffs[$i];
			
			//don't move blank lines... just delete it and add it again. avoids lots of problems.
			if ( $oDiff->getDiffType() == AnwDiff::TYPE_ADDED && !AnwXml::xmlIsEmptyNode($oDiff->getNode()) )
			{
				//AnwDebug::logdetail("findMovedNodesStep:".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode())));
				//important : don't move nodes into untranslatable parent nodes ! (testApplyDiffsMoved20)
				if ( AnwXml::xmlIsTranslatableParent($oDiff->getNode()) )
				{
					
					$oDiffDeleted = self::findMovedNodes_findDeleted($aoRootDiffs, $oDiff);
					if ($oDiffDeleted)
					{
						//AnwDebug::logdetail(" * MOVED NODE FOUND : ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode()))." <-- ".htmlentities(AnwUtils::xmlDumpNode($oDiffDeleted->getNode()))." ---parentDeleted---".htmlentities(AnwUtils::xmlDumpNode($oDiffDeleted->getNode()->parentNode)));
						
						// warning, here there is 2 possible cases:
						// 1. Nodes are 100% equals
						// 2. Values are not equals, but values are the same but have a different textLayout.
						// We do the test here, for better performances, so we don't test it more than 1 time even when applying diffs to several documents
						
						$oDiffMoved = new AnwDiffMoved();
						
						// did the textlayout changed?
						$sNewNodeValue = $oDiff->getNode()->nodeValue;
						if ($oDiffDeleted->getNode()->nodeValue != $sNewNodeValue)
						{
							// mark the textlayout changed (ie: "\n" added/removed before/after text value), so that we will apply the new textlayout when applying the diffs later
							$oDiffMoved->setTextLayoutReferenceValue($sNewNodeValue);
						}
						else
						{
							// textlayout didn't change, the moved nodes are 100% the same
						}						
						
						
						if ($oDiff->getDiffAddedParent()) //are we called recursively on a subAdded diff?
						{
							//$oDiff->getNode()->set = "***moved-node-from-added***";
							//AnwDebug::logdetail("findMovedNodes : !special !!! moved node from SubAdded");
							$oOldDiffSubAdded = $oDiff;
							$oDiffDeleted->setMovedDiff($oDiffMoved);
							$oOldDiffSubAdded->replaceBy($oDiffMoved); //notify ancestors from change
						}
						else
						{
							//replace AnwDiffAdded by AnwDiffMoved
							$oDiffDeleted->setMovedDiff($oDiffMoved);
						}
						
						$oDiff = $oDiffMoved;
					}/*
					else
					{
						//this DiffAdded has no move found. continue search into its subdiffsadded
						if ($oDiff->hasSubAddedDiffs())
						{
							AnwDebug::log("findMovedNodes : subAddedDiffs --->");
							$aoSubAddedDiffs = $oDiff->getSubAddedDiffs();
							$aoSubAddedDiffs = self::findMovedNodes($aoSubAddedDiffs, $aoRootDiffs, true);
							AnwDebug::log("findMovedNodes : subAddedDiffs <---");
						}
					}*/
				}
			}
			/*else if ($oDiff->getDiffType() == AnwDiff::TYPE_DIFFS)
			{
				//continue into sub diffs
				AnwDebug::log("findMovedNodes : diffs --->");
				$aoSubDiffs = self::findMovedNodes($oDiff->getAllDiffs(), $aoRootDiffs);
				AnwDebug::log("findMovedNodes : diffs <---");
				$oDiff->setDiffs($aoSubDiffs);
			}*/
			$aoNewDiffs[] = $oDiff;
		}
		
		//second pass
		$aoNewDiffs2 = array();
		$nCountDiffs = count($aoNewDiffs);
		for ($i=0; $i<$nCountDiffs; $i++)
		{
			$oDiff = $aoNewDiffs[$i];
			
			if ($oDiff->getDiffType() == AnwDiff::TYPE_DIFFS)
			{
				//continue into sub diffs
				//AnwDebug::logdetail("findMovedNodes : diffs --->");
				$aoSubDiffs = self::findMovedNodes($oDiff->getAllDiffs(), $aoRootDiffs);
				//AnwDebug::logdetail("findMovedNodes : diffs <---");
				$oDiff->setDiffs($aoSubDiffs);
			}
			else if ($oDiff->getDiffType() == AnwDiff::TYPE_ADDED)
			{
				if ( AnwXml::xmlIsTranslatableParent($oDiff->getNode()) )
				{
					//continue search into its subdiffsadded
					if ($oDiff->hasSubAddedDiffs())
					{
						//AnwDebug::logdetail("findMovedNodes : subAddedDiffs --->");
						$aoSubAddedDiffs = $oDiff->getSubAddedDiffs();
						$aoSubAddedDiffs = self::findMovedNodes($aoSubAddedDiffs, $aoRootDiffs);
						//AnwDebug::logdetail("findMovedNodes : subAddedDiffs <---");
					}
				}				
			}
			$aoNewDiffs2[] = $oDiff;
		}
		
		return $aoNewDiffs2;
	}
	
	private static function findMovedNodes_findDeleted($aoDiffs, $oDiffAdded)
	{
		$oNodeRef = $oDiffAdded->getNode();
		
		foreach ($aoDiffs as $i => $oDiff)
		{
			if ($oDiff->getDiffType() == AnwDiff::TYPE_DIFFS)
			{
				$oDiffDeleted = self::findMovedNodes_findDeleted($oDiff->getAllDiffs(), $oDiffAdded);
				if ($oDiffDeleted)
				{
					return $oDiffDeleted;
				}
			}
			else if ( ($oDiff->getDiffType() == AnwDiff::TYPE_DELETED && !$oDiff->getMovedDiff()) 
					|| ($oDiff->getDiffType() == AnwDiff::TYPE_EDITED && !$oDiff->getDiffDeleted()->getMovedDiff()) ) //!!!skip already moved diffs !
			{
				if ($oDiff->getDiffType() == AnwDiff::TYPE_EDITED)
				{
					$oDiff = $oDiff->getDiffDeleted(); //...
					//AnwDebug::logdetail("findMovedNodes : !special EDITED !!!");
				}
				
				//AnwDebug::log("findMovedNodes : compare ".htmlentities(AnwUtils::xmlDumpNode($oNodeRef))." <---> ".htmlentities(AnwUtils::xmlDumpNode($oDiff->getNode())));
				
				//warning: do the test hasSubMovedDiff() here, not before, as there could be a valid subdeleted node child of a parent deletednode having already another subdeletednode 
				if (!$oDiff->getMovedDiff() && !$oDiff->hasSubMovedDiff() && AnwXml::xmlAreSimilarNodesAllowTextLayoutChange($oDiff->getNode(), $oNodeRef))
				{
					return $oDiff;
				}
				else if ($oDiff->hasSubDeletedDiffs())
				{
					//continue search into subdeleted diffs
					$aoSubDiffs = $oDiff->getSubDeletedDiffs();
					$oDiffDeleted = self::findMovedNodes_findDeleted($aoSubDiffs, $oDiffAdded);
					if ($oDiffDeleted)
					{
						return $oDiffDeleted;
					}
				}
			}
		}
		return false;
	}
	
	
	private static function cleanupSubDeletedDiffs($aoDiffs)
	{
		foreach ($aoDiffs as $oDiff)
		{
			if ($oDiff->getDiffType() == AnwDiff::TYPE_DELETED)
			{
				self::cleanupSubDeletedDiffs_do($oDiff);
			}
		}
	}
	
	private static function cleanupSubDeletedDiffs_do($oDiff)
	{
		if ($oDiff->hasSubDeletedDiffs())
		{
			$bHasSubDeletedItems = false;
			$aoSubDeletedDiffs = $oDiff->getSubDeletedDiffs();
			foreach ($aoSubDeletedDiffs as $aoSubDeletedDiff)
			{
				self::cleanupSubDeletedDiffs_do($aoSubDeletedDiff);
				if ($aoSubDeletedDiff->hasSubDeletedDiffs())
				{
					$bHasSubDeletedItems = true;
				}
			}
			if (!$bHasSubDeletedItems)
			{
				$oDiff->setSubDeletedDiffs(false);
			}
		}
	}
	
	function __clone() //needed to restore movednodes when running applyDiffs() to apply the same diffs to different pages...
	{
		$this->aoNewDiffs = @clone $this->aoNewDiffs; //TODO:souci clone array?
	}
	
	function debugHtml()
	{
		$sHtml = "<li>AnwDiffs : &lt;".$this->getNodeRootNew()->nodeName."&gt;<br/>";
		$sHtml .= "from : ".htmlentities(AnwUtils::xmlDumpNode($this->oNodeRootOlder))."<br/>";
		$sHtml .= "to : ".htmlentities(AnwUtils::xmlDumpNode($this->getNodeRootNew()))."<br/>";
		$sHtml .= "<ul>";
		$aoDiffs = $this->getAllDiffs();
		foreach ($aoDiffs as $oDiff)
		{
			$sHtml .= $oDiff->debugHtml();
		}
		$sHtml .= "</ul></li>";
		return $sHtml;
	}
	
}
?>