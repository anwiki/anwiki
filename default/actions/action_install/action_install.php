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
 * Editing Anwiki configuration.
 * @package Anwiki
 * @version $Id: action_install.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_install extends AnwActionMinimal implements AnwHttpsAction, AnwAdminAction, AnwAlwaysEnabledAction
{
	private $asInstallStatus;
	private $oStepInstall;
	
	function run()
	{
		loadApp ($this->getMyComponentPathDefault().'class_stepinstall.php');
		
		//make sure it's not already installed
		if (file_exists(ANWIKI_INSTALL_LOCK))
		{
			$sLinkHome = AnwUtils::link( AnwComponent::globalCfgHomePage() );
			$sLinkForce = AnwStepInstall::linkStep( AnwStepInstall::getStepDefault() );
			$this->out .= $this->tpl()->alreadyInstalled($sLinkHome, $sLinkForce, ANWIKI_INSTALL_LOCK, ANWIKI_INSTALL_STATUS);
			return;
		}
		
		//make sure writable dir is writable
		if (!file_exists(ANWIKI_INSTALL_STATUS))
		{
			@touch(ANWIKI_INSTALL_STATUS);
		}
		if (!is_writable(ANWPATH_WRITABLE)||!is_writable(ANWIKI_INSTALL_STATUS))
		{
			$this->out .= $this->tpl()->error($this->t_("err_notwritable_directory_explain", array('directory'=>'<br/>'.ANWPATH_WRITABLE)), $this->t_("err_notwritable_t"));
			return;
		}
		
		//security
		define('ANWIKI_IN_INSTALL', true);		
		
		
		
		//read next step		
		$nCurrentStepStatus = self::getCurrentStepStatus();
		self::debug("current step status: ".$nCurrentStepStatus);
				
		
		//find step to execute
		$nStepOrder = (int)AnwEnv::_GET("step", AnwEnv::_POST("step", $nCurrentStepStatus));
		
		//do not go to next steps if previous steps were not completed
		if ($nStepOrder > $nCurrentStepStatus)
		{
			$nStepOrder = $nCurrentStepStatus;
		}
		
		//make sure step exists
		try
		{
			$sStepName = AnwStepInstall::getStepForOrder($nStepOrder);
		}
		catch(AnwException $e)
		{
			$nStepOrder = $nCurrentStepStatus;
			$sStepName = AnwStepInstall::getStepForOrder($nStepOrder);
		}
		
		//load the step
		$this->oStepInstall = AnwStepInstall::loadComponent($sStepName);
		AnwStepInstall::setActionInstall($this);
				
		$nCountSteps = count(AnwStepInstall::getAllSteps());		
		$sLinkPrevious = $this->oStepInstall->linkStepPrevious();
		$sLinkNext = $this->oStepInstall->linkStepNext();
		$sTitleStep = $this->oStepInstall->getStepTitle();
		
		$this->out .= $this->tpl()->headerInstall($sTitleStep, $nStepOrder, $nCountSteps, $sLinkPrevious, $sLinkNext);

		
		//run the step
		$this->out .= $this->oStepInstall->runStepInstall();
		
		$this->out .= $this->tpl()->footerInstall($sTitleStep, $nStepOrder, $nCountSteps, $sLinkPrevious, $sLinkNext);

	}
	
	//---------------------------------------
	
	private function getInstallStatus()
	{
		if (!$this->asInstallStatus)
		{
			$asStatus = AnwUtils::getFileSerializedObject(ANWIKI_INSTALL_STATUS);
			if (!is_array($asStatus)) throw new AnwUnexpectedException();
			$this->asInstallStatus = $asStatus;
		}
		return $this->asInstallStatus;
	}
	
	private function saveInstallStatus($asInstallStatus)
	{
		AnwUtils::putFileSerializedObject(ANWIKI_INSTALL_STATUS, $asInstallStatus);
		$this->asInstallStatus = null;
	}
	
	private function getCurrentStepStatus()
	{
		try
		{
			$asInstallStatus = self::getInstallStatus();
			$nCurrentStepStatus = $asInstallStatus['currentstep'];
		}
		catch(AnwException $e)
		{
			$nCurrentStepStatus = AnwStepInstall::getStepOrderDefault();
		}
		return $nCurrentStepStatus;
	}
	
	function updateStepStatusNext()
	{
		$nFutureStatus = $this->oStepInstall->getMyStepOrder()+1;
		if (self::getCurrentStepStatus() < $nFutureStatus)
		{
			try
			{
				$asInstallStatus = self::getInstallStatus();
			}
			catch(AnwException $e)
			{
				$asInstallStatus = array();
			}
			$asInstallStatus['currentstep'] = $nFutureStatus;
			self::saveInstallStatus($asInstallStatus);
		}
	}
}


?>