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
 * Dependancy management between components.
 * @package Anwiki
 * @version $Id: class_dependancy.php 147 2009-02-08 23:28:55Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

abstract class AnwDependancy
{
	private $oSourceComponent;
	private $mComponentType;
	private $sComponentName;
	
	function __construct($oSourceComponent, $mTargetComponentType, $sTargetComponentName)
	{
		$this->oSourceComponent = $oSourceComponent;
		$this->mTargetComponentType = $mTargetComponentType;
		$this->sTargetComponentName = $sTargetComponentName;
	}
	
	protected abstract function doCheckDependancies($aoComponents);
	// overridable
	protected function doSolveDependancies($aoComponents, $amSolvedValues)
	{
		throw new AnwDependancyException(AnwComponent::g_editcontent("dependancy_unsolved",array('component1'=>$this->getSourceComponent()->getComponentName(),'component2'=>$this->getTargetComponentName())));
	}
	
	public function solveDependancies($aoComponents, $amSolvedValues)
	{
		return $this->doSolveDependancies($aoComponents, $amSolvedValues);
	}
	public function checkDependancies($aoComponents)
	{
		$this->doCheckDependancies($aoComponents);
	}
	
	protected function sourceComponentMatches($oComponent)
	{
		return ($this->oSourceComponent->getMyComponentType()==$oComponent->getMyComponentType() 
			&& $this->oSourceComponent->getName()==$oComponent->getName());
	}
	
	protected function targetComponentMatches($oComponent)
	{
		return ($this->mTargetComponentType==$oComponent->getMyComponentType() 
			&& $this->sTargetComponentName==$oComponent->getName());
	}
	
	protected function getSourceComponent()
	{
		return $this->oSourceComponent;
	}
	
	protected function getTargetComponentName()
	{
		return $this->sTargetComponentName;
	}
	
	protected function debug($sMsg)
	{
		AnwDebug::log("(AnwDependancy)".$sMsg);
	}
}

class AnwDependancyRequirement extends AnwDependancy
{
	protected function doCheckDependancies($aoComponents)
	{
		// make sure that required component is in the list
		foreach ($aoComponents as $i => $oComponent)
		{
			if ($this->targetComponentMatches($oComponent))
			{
				// ok, required component present
				return;
			}
		}
		
		// required component not found
		throw new AnwDependancyException(AnwComponent::g_editcontent("dependancy_requirement",array('component1'=>$this->getSourceComponent()->getComponentName(),'component2'=>$this->getTargetComponentName())));
	}
}

class AnwDependancyConflict extends AnwDependancy
{
	const SOLUTION_NONE="NONE";
	const SOLUTION_LOAD_BEFORE="LOAD_BEFORE";
	const SOLUTION_LOAD_AFTER="LOAD_AFTER";
	private $mSolution;
	
	function __construct($oSourceComponent, $mTargetComponentType, $sTargetComponentName, $mSolution=self::SOLUTION_NONE)
	{
		parent::__construct($oSourceComponent, $mTargetComponentType, $sTargetComponentName);
		$this->mSolution = $mSolution;
	}
	
	protected function doSolveDependancies($aoComponents, $amSolvedValues)
	{
		return $this->doCheckOrSolveDependancies($aoComponents, $amSolvedValues);
	}
	
	protected function doCheckDependancies($aoComponents)
	{
		$this->doCheckOrSolveDependancies($aoComponents, null);
	}
	
	/**
	 * Conflicts checker/solver.
	 * When $amSolvedValues is null, it will only check for dependancies problems and throw exceptions.
	 * When $amSolvedValues is not null, it must be an array of misc values, at the same indexes than $aoComponents.
	 * If a solution is found, $amSolvedValues will be reorganized in the correct order.
	 */
	private function doCheckOrSolveDependancies($aoComponents, $amSolvedValues=null, $nDepth=0)
	{
		self::debug("solveDependancies() for ".$this->getSourceComponent()->getComponentName());
		
		// search for source/target indices...
		$nSourceComponentIndice=null;
		$nTargetComponentIndice=null;
		foreach ($aoComponents as $i => $oComponent)
		{
			if ($nSourceComponentIndice===null && $this->sourceComponentMatches($oComponent))
			{
				$nSourceComponentIndice = $i;
			}
			else if ($nTargetComponentIndice===null && $this->targetComponentMatches($oComponent))
			{
				$nTargetComponentIndice = $i;
			}
			if ($nSourceComponentIndice!==null && $nTargetComponentIndice!==null)
			{
				break;
			}
		}
		if ($nSourceComponentIndice===null)
		{
			throw new AnwUnexpectedException("Source component not found"); //should never happend
		}
		
		// begin dependancies resolution
		if ($nTargetComponentIndice!==null)
		{
			//the conflicting component has been found...
			
			if ($this->mSolution==self::SOLUTION_NONE)
			{
				// there is no solution
				self::debug("conflict between ".$this->getSourceComponent()->getComponentName()." and ".$this->getTargetComponentName()." : no solution");
				throw new AnwDependancyException(AnwComponent::g_editcontent("dependancy_conflict",array('component1'=>$this->getSourceComponent()->getComponentName(),'component2'=>$this->getTargetComponentName())));
			}
			else
			{
				// there is a solution, we will try to apply it...
				$bSolvedValuesWereChanged = false;
				if (($this->mSolution==self::SOLUTION_LOAD_BEFORE && $nSourceComponentIndice < $nTargetComponentIndice)
					|| ($this->mSolution==self::SOLUTION_LOAD_AFTER && $nSourceComponentIndice > $nTargetComponentIndice))
				{
					// already solved
					self::debug("conflict between ".$this->getSourceComponent()->getComponentName()." and ".$this->getTargetComponentName()." : already solved");
				}
				else
				{
					self::debug("conflict between ".$this->getSourceComponent()->getComponentName()." and ".$this->getTargetComponentName()." : solvable");
					if ($amSolvedValues!==null)
					{
						// conflict can be solved by permuting the two components (must permute it in $aoComponents AND $amSolvedValues)
						list($aoComponents, $amSolvedValues) = AnwUtils::permuteMultipleArrays(array($aoComponents,$amSolvedValues), $nSourceComponentIndice, $nTargetComponentIndice);
						$bSolvedValuesWereChanged = true;
					}
					else
					{
						throw new AnwDependancyException(AnwComponent::g_editcontent("dependancy_conflict_unsolved",array('component1'=>$this->getSourceComponent()->getComponentName(),'component2'=>$this->getTargetComponentName())));
					}
				}
								
				if ($amSolvedValues!==null && $bSolvedValuesWereChanged)
				{
					// we have to check again the whole values, to be sure that we didn't break a dependancy while reorganizing $amSolvedValues
					$nDepth++;
					if ($nDepth>3)
					{
						// we may be in an infinite dependancies problem... give up!
						self::debug("amSolvedValues have changed, but nDepth exceeded");
						throw new AnwDependancyException(AnwComponent::g_editcontent("dependancy_conflict_exceed",array('component1'=>$this->getSourceComponent()->getComponentName(),'component2'=>$this->getTargetComponentName())));
					}
					else
					{
						self::debug("amSolvedValues have changed, checking again whole values");
						$aoComponents = $this->doCheckOrSolveDependancies($aoComponents, $amSolvedValues, $nDepth); //recursive call
					}
				}
			}
		}
		else
		{
			//no dependancy problem
			self::debug("conflict between ".$this->getSourceComponent()->getComponentName()." and ".$this->getTargetComponentName()." : no dependancy problem");
		}
		return $amSolvedValues;
	}
}

?>