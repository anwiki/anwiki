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
 * ContentFieldSettings for Sessions driver: MySQL.
 * @package Anwiki
 * @version $Id: sessionsdriver_mysql-settings.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */
class AnwContentFieldSettings_mysqlsessionpreference extends AnwContentFieldSettings_container implements AnwISettings_mysqlsessionpreference
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldSettings_mysqlsessionpublic(self::FIELD_PUBLIC);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_mysqlsessionresume(self::FIELD_RESUME);
		$this->addContentField($oContentField);
	}
}

class AnwContentFieldSettings_mysqlsessionpublic extends AnwContentFieldSettings_container implements AnwISettings_mysqlsessionpublic
{
	function init()
	{
		parent::init();
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_DURATION_IDLE);
		$this->addContentField($oContentField);
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_DURATION_MAX);
		$this->addContentField($oContentField);
	}
	
	function doTestContentFieldValueComposed($oSubContent)
	{
		//session duration IDLE should be <= duration max
		$nDurationIdle = $oSubContent->getContentFieldValue(self::FIELD_DURATION_IDLE);
		$nDurationMax = $oSubContent->getContentFieldValue(self::FIELD_DURATION_MAX);
		
		if ($nDurationIdle > $nDurationMax)
		{
			$sError = $oSubContent->getComponent()->t_contentfieldsettings("err_setting_sessions_duration_idle_gt_max");
			throw new AnwInvalidContentFieldValueException($sError);
		}
	}
}

class AnwContentFieldSettings_mysqlsessionresume extends AnwContentFieldSettings_container implements AnwISettings_mysqlsessionresume
{
	function init()
	{
		parent::init();
		
		$oContentField = new AnwContentFieldSettings_delay(self::FIELD_DELAY_MAX);
		$this->addContentField($oContentField);
	}
}

?>