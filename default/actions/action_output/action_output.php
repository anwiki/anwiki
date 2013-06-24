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
 * Export contents as RAW output.
 * @package Anwiki
 * @version $Id: action_output.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwActionDefault_output extends AnwActionGlobal
{
	const ERR_NOTFOUND = "NOT_FOUND";
	const ERR_BADCALL = "BAD_CALL";
	const ERR_UNKNOWN = "OUTPUT_ERROR";
	const ERR_ACLS = "NOT_AUTHORIZED";
	
	function run()
	{
		//usage: www.anwiki.com/?a=output&outputname=en/_include/menu&outputlang=de&outputcurrent=en/news
		//outputname: Page name for output
		//outputlang: Page lang for output
		//outputcurrent: (optional) Simulate a specific page as current
		$this->out = $this->getOutput();
		$this->printOutputRaw();
	}
	
	private function getOutput()
	{
		if (!AnwEnv::_GET("outputname") || !AnwEnv::_GET("outputlang"))
		{
			return self::ERR_BADCALL;
		}
		
		$sOutput = "";
		try
		{			
			//fake current page
			$_GET[AnwActionPage::GET_PAGENAME] = AnwEnv::_GET("outputcurrent");
			
			$sPageName = AnwEnv::_GET("outputname");
			if (substr($sPageName,0,1)=='/') //prevent errors
			{
				$sPageName = substr($sPageName, 1);
			}
			$sPageLang = AnwEnv::_GET("outputlang");
			
			//additionnal permissions check
			if (!AnwCurrentSession::isActionAllowed($sPageName, 'output', $sPageLang)) //TODO: not very clean, as output is a globalAction...
			{
				return self::ERR_ACLS;
			}
			
			//get page execution result
			$bAutoLoadTranslatedPage = true;
			$bUseCache = false; //TODO?
			$sCacheKey = "";//"currentpage-".AnwActionPage::getCurrentPageName();		
			$sOutput = AnWiki::includePage($sPageName, $sPageLang, $bAutoLoadTranslatedPage, $bUseCache, $sCacheKey);
		}
		catch(AnwPageNotFoundException $e)
		{
			$sOutput = self::ERR_NOTFOUND;
		}
		catch(AnwAclException $e)
		{
			$sOutput = self::ERR_ACLS;
		}
		catch(AnwException $e)
		{
			$sOutput = self::ERR_UNKNOWN;
		}
		return $sOutput;
	}
}

?>