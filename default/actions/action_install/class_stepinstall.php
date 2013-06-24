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
 * Step of Anwiki installation.
 * @package Anwiki
 * @version $Id: class_stepinstall.php 258 2010-03-10 20:55:59Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */


abstract class AnwStepInstall extends AnwComponent
{
	protected $out;
	private static $oActionInstall;
	
	const STEP_START = 'start';
	const STEP_CFG_GLOBAL = 'cfg_global';
	
	const STEP_CFG_STORAGE = 'cfg_storage';
	const STEP_CFG_USERS = 'cfg_users';
	const STEP_CFG_SESSIONS = 'cfg_sessions';
	const STEP_CFG_ACLS = 'cfg_acls';
	
	const STEP_INIT_STORAGE = 'init_storage';
	const STEP_INIT_USERS = 'init_users';
	const STEP_INIT_SESSIONS = 'init_sessions';
	const STEP_INIT_ACLS = 'init_acls';
	
	const STEP_ORDER_DIFFERENCE = 4; //count of steps between cfg and init (for links back)
	
	const STEP_GRANT_ADMIN = 'grant_admin';
	const STEP_PING = 'ping';
	const STEP_FINISHED = 'finished';
	
	function __construct($sName, $bIsAddon)
	{
		$this->initComponent($sName, $bIsAddon);
	}
	
	abstract function run();
	
	function getStepTitle()
	{
		return $this->t_("step_t");
	}
	
	//------------------------------
	
	static function getAllSteps()
	{
		$asSteps = array();
		$asSteps[] = self::STEP_START;		
		$asSteps[] = self::STEP_CFG_GLOBAL;
		
		$asSteps[] = self::STEP_CFG_STORAGE;
		$asSteps[] = self::STEP_CFG_USERS;
		$asSteps[] = self::STEP_CFG_SESSIONS;
		$asSteps[] = self::STEP_CFG_ACLS;
		
		$asSteps[] = self::STEP_INIT_STORAGE;
		$asSteps[] = self::STEP_INIT_USERS;
		$asSteps[] = self::STEP_INIT_SESSIONS;
		$asSteps[] = self::STEP_INIT_ACLS;
		
		$asSteps[] = self::STEP_GRANT_ADMIN;
		$asSteps[] = self::STEP_PING;
		$asSteps[] = self::STEP_FINISHED;
		return $asSteps;
	}
	
	static function getStepOrder($sStepName)
	{
		$asSteps = self::getAllSteps();		
		foreach ($asSteps as $i => $sStep)
		{
			if ($sStep == $sStepName)
			{
				return $i+1;
			}
		}
		throw new AnwUnexpectedException("step not found: ".$sStep);
	}
	
	function getMyStepOrder()
	{
		$nStep = self::getStepOrder($this->getName());
		return $nStep;
	}
	
	static function getStepForOrder($nOrder)
	{
		$asSteps = self::getAllSteps();		
		if (!isset($asSteps[$nOrder-1])) throw new AnwUnexpectedException("step not found for order: ".$nOrder);
		return $asSteps[$nOrder-1];
	}
	
	static function getStepDefault()
	{
		return self::getStepForOrder(self::getStepOrderDefault());
	}
	
	static function getStepOrderDefault()
	{
		return 1;
	}
	
	//------------------------------
	
	static function setActionInstall($oActionInstall)
	{
		self::$oActionInstall = $oActionInstall;
	}
	
	static function getActionInstall()
	{
		return self::$oActionInstall;
	}
	protected static function t_install($id, $asParams=array(), $sLangIfLocal=false, $sValueIfNotFound=false)
	{
		return self::getActionInstall()->t_($id, $asParams, $sLangIfLocal, $sValueIfNotFound);
	}
	
	function runStepInstall()
	{
		$this->run();
		return $this->out;
	}
	
	//-------------------------------
	
	function linkMe()
	{
		$nStep = $this->getMyStepOrder();
		return '?a=install&step='.$nStep;
	}
	
	static function linkStep($sStep)
	{
		$nStep = self::getStepOrder($sStep);
		return '?a=install&step='.$nStep;
	}
	function linkStepNext()
	{
		$nStep = $this->getMyStepOrder()+1;
		//check step exists
		try
		{
			$null = self::getStepForOrder($nStep);
			return '?a=install&step='.$nStep;
		}
		catch(AnwException $e)
		{
			return false;
		}
	}
	function linkStepPrevious()
	{
		$nStep = $this->getMyStepOrder()-1;
		//check step exists
		try
		{
			$null = self::getStepForOrder($nStep);
			return '?a=install&step='.$nStep;
		}
		catch(AnwException $e)
		{
			return false;
		}
	}
	
	//---------------------
	
	
	function getComponentName()
	{
		return 'stepinstall_'.$this->getName();
	}
	
	static function getComponentsRootDir()
	{
		return AnwAction::getComponentDir('install');
	}
	static function getComponentsDirsBegin()
	{
		return 'stepinstall_';
	}
	//due to "stricts oo standards", have to duplicate this function in all components class...
	static function getComponentDir($sName)
	{
		return self::getComponentsRootDir().self::getComponentsDirsBegin().$sName.'/';
	}
	
	static function getMyComponentType()
	{
		return 'stepinstall';
	}
	
	static function discoverEnabledComponents()
	{
		return self::getAllSteps();
	}
		
	static function loadComponent($sName)
	{
		$sFile = 'stepinstall_'.$sName.'.php';
		$sDir = self::getComponentDir($sName);
		$sClassNamePrefix = 'AnwStepInstall%%_'.$sName;
		list($classname, $bIsAddon) = self::requireCustomOrDefault($sFile, $sDir, $sClassNamePrefix);
		
		$oComponent = new $classname($sName, $bIsAddon);
		return $oComponent;
	}
}



abstract class AnwStepInstallDefaultConfigureComponent extends AnwStepInstall
{
	private $oEditionForm;
	private $oComponent;
	
	function getStepTitle()
	{
		return $this->t_install("step_cfgcomponent_t", array('componentname'=>$this->getComponentToConfigure()->getComponentName()));
	}
	
	abstract protected function loadComponentToConfigure();
	
	protected function getComponentToConfigure()
	{
		if (!$this->oComponent)
		{
			$this->oComponent = $this->loadComponentToConfigure();
		}
		return $this->oComponent;
	}
	
	function run()
	{
		AnwStructuredContentField::setDisplayOnlyMandatory(true);
		
		if ($this->getComponentToConfigure() instanceof AnwConfigurable && $this->getComponentToConfigure()->getContentClassSettings()->hasMandatorySettings())
		{
			if ($this->getEditionForm()->isPosted())
			{
				$this->saveEditionForm();
			}
			else
			{
				$this->showEditionForm();
			}
		}
		else
		{
			//no need to configure anything
			$this->showNoConfigurationNeeded();
		}
	}
	
	protected function showEditionForm($sError=false)
	{
		$sRenderForm = $this->getEditionForm()->getRender();
		$this->out .= $this->tpl()->editConfigForm($this->linkMe(), $sRenderForm, $this->getComponentToConfigure()->getComponentName(), $sError);
	}
	
	protected function saveEditionForm()
	{
		try
		{
			//save changes
			$this->getEditionForm()->saveEdition();
			
			//update step status
			$this->getActionInstall()->updateStepStatusNext();
			
			//redirect
			AnwUtils::redirect($this->linkStepNext());
		}
		catch (AnwStructuredContentEditionFormException $e)
		{
			$sError = $e->getMessage();
			$this->showEditionForm( $sError );
		}
	}
	
	protected function showNoConfigurationNeeded()
	{
		$this->out .= $this->tpl()->noConfigurationNeeded($this->linkStepNext());
		
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
	}
	
	protected function getEditionForm()
	{
		if (!$this->oEditionForm)
		{
			$oEditableComponent = $this->getComponentToConfigure();
			$this->oEditionForm = new AnwStructuredContentEditionFormSettings($oEditableComponent, $oEditableComponent->getConfigurableContent(), $this->linkMe());
		}
		return $this->oEditionForm;
	}
}



abstract class AnwStepInstallDefaultInitializeComponent extends AnwStepInstall
{
	private $oComponent;
	
	function getStepTitle()
	{
		return $this->t_install("step_initcomponent_t", array('componentname'=>$this->getComponentToInitialize()->getComponentName()));
	}
	
	abstract protected function loadComponentToInitialize();
	
	protected function getComponentToInitialize()
	{
		if (!$this->oComponent)
		{
			$this->oComponent = $this->loadComponentToInitialize();
		}
		return $this->oComponent;
	}
	
	function run()
	{
		if ($this->getComponentToInitialize() instanceof AnwInitializable)
		{
			if ($this->getComponentToInitialize()->isComponentInitialized())
			{
				$this->showAlreadyInitialized();
			}
			else
			{
				if (AnwEnv::_GET("runinit"))
				{
					$this->runInitialization();
				}
				else
				{
					$this->showInitializationConfirm();
				}
			}
		}
		else
		{
			//no need to configure anything
			$this->showNoInitializationNeeded();
		}
	}
	
	protected function showInitializationConfirm($sError=false)
	{
		$sLinkContinue = $this->linkMe().'&runinit=1';
		$sInitSettings = $this->getComponentToInitialize()->getSettingsForInitialization();
		$this->out .= $this->tpl()->showInitConfirm($sLinkContinue, $sInitSettings, $this->getComponentToInitialize()->getComponentName(), $sError);
	}
	
	//overridable
	protected function initializeAdditional()
	{
		return "";
	}
	
	protected function runInitialization()
	{
		AnwStorage::transactionStart();
		try
		{
			$sInitializationLog = $this->getComponentToInitialize()->initializeComponent();
			
			if (!$this->getComponentToInitialize()->isComponentInitialized())
			{
				throw new AnwUnexpectedException("Component is still not initialized!");
			}
			
			//run additional initialization
			$sInitializationLog .= $this->initializeAdditional();
			
			$this->out .= $this->tpl()->showInitResult($this->linkStepNext(), $sInitializationLog);
			
			AnwStorage::transactionCommit();
		
			//update step status
			$this->getActionInstall()->updateStepStatusNext();
		}
		catch(AnwComponentInitializeException $e)
		{
			$this->showInitializationConfirm($e->getMessage());
		}
		catch(AnwException $e)
		{
			AnwStorage::transactionRollback();
			throw $e;
		}
	}
	
	protected function showNoInitializationNeeded()
	{
		$this->out .= $this->tpl()->noInitializationNeeded($this->linkStepNext());
		
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
	}
	
	protected function showAlreadyInitialized()
	{
		$sInitSettings = $this->getComponentToInitialize()->getSettingsForInitialization();
		$sStepEditSettings = self::getStepForOrder($this->getMyStepOrder()-self::STEP_ORDER_DIFFERENCE);
		$sLinkEditSettings = $this->linkStep($sStepEditSettings);
		$this->out .= $this->tpl()->showAlreadyInit($this->linkStepNext(), $sLinkEditSettings, $sInitSettings);
		
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
	}
}


?>