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
 * @version $Id: action_editconfig.php 305 2010-09-12 15:21:16Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
 
class AnwActionDefault_editconfig extends AnwActionGlobal implements AnwHttpsAction, AnwAdminAction, AnwAlwaysEnabledAction
{
	private $oEditionForm;
	
	private $oComponent;
	private $sComponentName;
	private $sComponentType;
	
	const CSS_FILENAME = "action_editconfig.css";
	
	function getNavEntry()
	{
		return $this->createManagementGlobalNavEntry();
	}
	
	function run()
	{
		// load CSS
		$this->head( $this->getCssSrcComponent(self::CSS_FILENAME) );
		
		//decide what to do
		if (AnwEnv::_GET("refresh")) {
			$this->refreshComponentsList();
		}
		else {
			try
			{
				$this->oComponent = null;
				
				$this->sComponentName = strtolower(AnwEnv::_GET("c", AnwEnv::_POST("c")));
				$this->sComponentType = strtolower(AnwEnv::_GET("t", AnwEnv::_POST("t")));
				if ($this->sComponentName && $this->sComponentType)
				{
					try
					{
						$this->oComponent = AnwComponent::loadComponentGeneric($this->sComponentName, $this->sComponentType);
					}
					catch(AnwException $e){}
				}
				
				if (!$this->oComponent)
				{
					$this->oComponent = AnwComponent::loadComponentGeneric('global', 'global');
				}
				
				if (AnwEnv::_POST("publish"))
				{
					$this->saveEdition();
				}
				else if(AnwEnv::_GET("js")=="addmultiplecontentfield")
				{
					$sFieldName = AnwEnv::_GET("fieldname");
					$sSuffix = AnwEnv::_GET("suffix");
					$this->JS_AddMultipleContentField($sFieldName, $sSuffix);
				}
				else
				{
					$this->editForm();
				}
			}
			catch (AnwInvalidContentException $e)
			{
				$this->editForm( $this->g_("err_contentinvalid") );
			}
		}
	}
	
	protected function refreshComponentsList() {
		AnwCache_componentsMapping::clearCache();
		$this->redirectInfo($this->linkMe(), $this->t_("refreshcomponents_redirect_title"), $this->t_("refreshcomponents_redirect_info"));
	}
	
	protected function getComponent()
	{
		return $this->oComponent;
	}	
	
	protected function editForm($error=false)
	{	
		//output edit form
		$sContentHtmlDir = $this->g_("local_html_dir");
		$sRenderForm = $this->getEditionForm()->getRender();
		$this->out .= $this->tpl()->editConfigLayout(
			$this->linkMe(array("refresh"=>true)),
			AnwComponent::getAllConfigurableComponents(),
			$this->tpl()->editConfigForm(	AnwUtils::alink($this->getName()), 
											$sContentHtmlDir,
											$this->sComponentName,
											$this->sComponentType,
											$this->getComponent()->getComponentName(),
											$sRenderForm, 
											$error)
		);		
	}
		
	protected function saveEdition()
	{
		try
		{
			//save changes
			$this->getEditionForm()->saveEdition();
			
			//redirect
			$amParameters = array('c'=>$this->sComponentName, 't'=>$this->sComponentType);
			$sTarget = AnwUtils::alink($this->getName(), $amParameters);
			AnwUtils::redirect($sTarget);
		}
		catch (AnwStructuredContentEditionFormException $e)
		{
			$sError = $e->getMessage();
			$this->editForm( $sError );
		}
	}
	
	protected function getEditionForm()
	{
		if (!$this->oEditionForm)
		{
			$oEditableComponent = $this->getComponent();
			
			$amParameters = array('c'=>$this->sComponentName, 't'=>$this->sComponentType);
			$sTarget = AnwUtils::alink($this->getName(), $amParameters);
			
			$this->oEditionForm = new AnwStructuredContentEditionFormSettings($oEditableComponent, $oEditableComponent->getConfigurableContent(), $sTarget);
		}
		return $this->oEditionForm;
	}
	
	//-----------------
	
	protected function JS_AddMultipleContentField($sFieldName, $sSuffix)
	{
		//TODO search by prefix, not by fieldname
		$this->out = $this->getComponent()->getConfigurableContent()->renderAdditionalEditInput($sFieldName, $sSuffix);
		$this->printOutputRaw();
	}
}

?>