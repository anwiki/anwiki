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
 * Install step : ping.
 * @package Anwiki
 * @version $Id: stepinstall_ping.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwStepInstallDefault_ping extends AnwStepInstall
{
	function run()
	{
		if (AnwEnv::_POST("ping"))
		{
			if (AnwEnv::_POST("ping")=="yes")
			{
				$bAddInDirectory = (AnwEnv::_POST("addindirectory") ? true : false);
				$this->doPing($bAddInDirectory);
			}
			else
			{
				$this->skipPing();
			}
		}
		else if (AnwEnv::_GET("pingdone"))
		{
			$this->pingDone();
		}
		else
		{
			$this->showPingInfo();
		}
	}
	
	protected function showPingInfo()
	{
		$this->out .= $this->tpl()->pingInfo($this->linkMe());
	}
	
	protected function skipPing()
	{
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
		
		AnwUtils::redirect($this->linkStepNext());
	}
	
	protected function doPing($bAddInDirectory)
	{
		//here, url is passed in any case for verification purpose
		//but don't worry, it's stored on server side only when 'addindirectory' is true
		$sPingTarget = ANWIKI_WEBPING.'newinstall?'
									.'siteurl='.urlencode(AnwComponent::globalCfgUrlRoot())
									.'&sitelang='.urlencode(AnwComponent::globalCfgLangDefault())
									.'&lang='.urlencode(AnwCurrentSession::getLang())
									.'&addindirectory='.($bAddInDirectory?'1':'0')
									.'&versionid='.urlencode(ANWIKI_VERSION_ID)
									.'&nocache='.time();
		
		$this->out .= $this->tpl()->doPing($sPingTarget, $this->linkMe().'&pingdone=1');
	}
	
	protected function pingDone()
	{
		//update step status
		$this->getActionInstall()->updateStepStatusNext();
		
		AnwUtils::redirect($this->linkStepNext());
	}
}

?>