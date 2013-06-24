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
 * Debug tools for Anwiki.
 * @package Anwiki
 * @version $Id: class_debug.php 259 2010-03-10 20:58:40Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwDebug
{
	private static $log=array();
	private static $afBenchmarks = array();
	private static $afBenchmarksSums = array();
	private static $bEmergencyDebug = false;
	private static $bLogDetails = false;
	private static $bTraceEnabled = true;
	
	static function isEmergencyDebugEnabled()
	{
		return self::$bEmergencyDebug;
	}
	
	static function setTraceEnabled($bEnabled)
	{
		self::$bTraceEnabled = $bEnabled;
		if (!$bEnabled)
		{
			self::$log = array();
		}
	}
	
	static function log($str, $type=null)
	{
		if (self::$bTraceEnabled)
		{
			AnwDebug::$log[] = $str;
			if (self::$bEmergencyDebug)
			{
				print self::colorizeLine($str).'<br/>'."\n"; flush();
			}
		}
	}
	
	static function logdetail($str)
	{
		//if (class_exists("AnwUtils") && !AnwUtils::isDebugEnabled()) return;
		
		if (self::$bTraceEnabled && self::$bLogDetails)
		{
			self::log($str);
		}
	}
	
	private static function colorizeLine($str)
	{
		preg_match("/^\((.*?)\)/", $str, $asMatches);
		if ($asMatches)
		{
			switch($asMatches[1])
			{
				case "AnwEnv":
					$str = '<span style="color:#ae92d4">'.$str.'</span>'; break;
				case "AnwMysql":
					$str = '<span style="background:#30936f;color:#FFF; padding:0 3px;">'.$str.'</span>'; break;
				case "AnwAction":
					$str = '<span style="color:black">'.$str.'</span>'; break;
				case "AnwPage":
					$str = '<span style="color:green">'.$str.'</span>'; break;
				case "AnwStorage":
					$str = '<span style="color:orange">'.$str.'</span>'; break;
				case "AnwSessions":
					$str = '<span style="color:blue">'.$str.'</span>'; break;
				case "AnwUsers":
					$str = '<span style="color:purple">'.$str.'</span>'; break;
				case "AnwCache":
					$str = '<span style="color:#003333">'.$str.'</span>'; break;
				case "benchmark":
					$str = '<span style="font-weight:bold">'.$str.'</span>'; break;
				default:
					$str = '<span style="color:grey">'.$str.'</span>'; break;
			}
		}
		else if (substr($str,0,2) == '//') {
			$str = '<span style="color:blue">'.$str.'</span>';
		}
		return $str;
	}
	
	private static function colorizeLogs()
	{
		foreach (self::$log as $i => $sLine)
		{
			self::$log[$i] = self::colorizeLine($sLine);
		}
	}
	
	static function getLog()
	{
		$str = "";
		if (self::$bTraceEnabled)
		{
			self::endLog();
			self::colorizeLogs();
			$str = implode("<br/>",self::$log);
		}
		return $str;
	}
	
	private static function endLog()
	{
		//benchmark
		if (self::$afBenchmarksSums['GLOBAL'] == 0)
		{
			self::stopBench("GLOBAL");
		}
		
		$fSum = 0;
		foreach (self::$afBenchmarksSums as $sName => $fElapsedTime)
		{
			$fSum += $fElapsedTime;
			self::log('(benchmark) SUM : '.$sName.' = '.round($fElapsedTime, 3).' sec');
		}
		self::log('(benchmark) SUMS TOTAL : '.round($fSum, 3).' sec');
		self::log('Memory Usage : '.self::getMemoryUsage().' MB');
		
		//context
		AnwEnv::writeDebug();
	}
	
	static function reportError($oException, $nErrorNumber=null)
	{
		if (AnwComponent::globalCfgReportFileEnabled() || AnwComponent::globalCfgReportMailEnabled())
		{		
			if (!$nErrorNumber) $nErrorNumber = time();
			$sExceptionName = get_class($oException);
			$sExceptionMessage = $oException->getMessage();
			self::log("=== exception ".$sExceptionName." - logged as error #".$nErrorNumber." : ".$sExceptionMessage);
			self::endLog();
			$sLoggedStr = "\n\n";
			$sLoggedStr .= "********* BEGIN ERROR LOG #".$nErrorNumber."*********\n";
			$sLoggedStr .= "Date : ".date("Y-m-d H:i:s")."\n";
			$sLoggedStr .= "Exception : ".$sExceptionName."\n";
			$sLoggedStr .= "Message : ".$sExceptionMessage."\n";
			$sLoggedStr .= "Stack trace below :\n";
			$sLoggedStr .= " * File ".$oException->getFile().", line ".$oException->getLine()."\n";
			
			$asTrace = $oException->getTrace();
			foreach ($asTrace as $sTrace)
			{
				$sLoggedStr .= ' * ';
				if (isset($sTrace['class'])) $sLoggedStr .= $sTrace['class'];
				if (isset($sTrace['type'])) $sLoggedStr .= $sTrace['type'];
				$sLoggedStr .= $sTrace['function'];
				
				//hide args for security reasons
				if (is_array(@$sTrace['args']))
				{
					foreach ($sTrace['args'] as $i => $sArg)
					{
						$sTrace['args'][$i] = 'p'.($i+1);
						//$sTrace['args'][$i] = serialize($sArg);
					}
					$sLoggedStr .= '('.implode($sTrace['args'],", ").') - ';
				}
				
				$sLoggedStr .= 'File '.@$sTrace['file'].', line '.@$sTrace['line'];
				$sLoggedStr .= "\n";
			}
			$sLoggedStr .= "Execution log below :\n\n";
			$sLoggedStr .= implode("\n", self::$log)."\n";
			$sLoggedStr .= "********* END ERROR LOG #".$nErrorNumber." *********\n";
			
			if (AnwComponent::globalCfgReportFileEnabled())
			{
				try 
				{
					AnwUtils::file_put_contents(ANWPATH_ERRORLOG, $sLoggedStr, LOCK_EX|FILE_APPEND);
				}
				catch(AnwException $e){}
			}
			if (AnwComponent::globalCfgReportMailEnabled())
			{
				$sSubject = AnwComponent::g_("err_mailreport_subject", array("errornumber"=>$nErrorNumber));
				$sBody = AnwComponent::g_("err_mailreport_body", array("website"=>AnwComponent::globalCfgWebsiteName()));
				$sBody .= "\n\n".$sLoggedStr;
				
				$asEmail = AnwComponent::globalCfgReportMailAddresses();
				foreach ($asEmail as $sEmail)
				{
					AnwUtils::mail($sEmail, $sSubject, $sBody);
				}
			}
			return $nErrorNumber;
		}
		return false;
	}
	
	static function startBench($sName, $bDoSum=false)
	{
		if (!self::$bTraceEnabled && $sName!='GLOBAL')
		{
			return;
		}
		
		//if (class_exists("AnwUtils") && $sName != 'GLOBAL' && !AnwUtils::isDebugEnabled()) return;
		
		self::log("(bench) start : ".$sName." (".self::getMemoryUsage()."MB)");
		//self::$afBenchmarks[$sName] = microtime(true);
		if (function_exists("getrusage"))
		{
			$oUsage = getrusage();
			$fUserTime = $oUsage["ru_utime.tv_sec"]*1e6 + $oUsage["ru_utime.tv_usec"];
			$fSystemTime = $oUsage["ru_stime.tv_sec"]*1e6 + $oUsage["ru_stime.tv_usec"];
			self::$afBenchmarks[$sName]['u'] = $fUserTime;
			self::$afBenchmarks[$sName]['s'] = $fSystemTime;
		}
		self::$afBenchmarks[$sName]['m'] = microtime(true);
		
		if ($bDoSum && !isset(self::$afBenchmarksSums[$sName])) self::$afBenchmarksSums[$sName]=0;
	}
	
	static function stopBench($sName)
	{
		if (!self::$bTraceEnabled && $sName!='GLOBAL')
		{
			return -1;
		}
		
		//if (class_exists("AnwUtils") && $sName != 'GLOBAL' && !AnwUtils::isDebugEnabled()) return;
		
		if (!isset(self::$afBenchmarks[$sName])) throw new AnwUnexpectedException("Benchmark ".$sName." was never started");
		
		if (function_exists("getrusage"))
		{
			$oUsage = getrusage();
			$fUserTime = ($oUsage["ru_utime.tv_sec"]*1e6 + $oUsage["ru_utime.tv_usec"]) - self::$afBenchmarks[$sName]['u'];
			$fUserTime /= 1e6;
			$sUserTime = round($fUserTime,3);
			$fSystemTime = ($oUsage["ru_stime.tv_sec"]*1e6 + $oUsage["ru_stime.tv_usec"]) - self::$afBenchmarks[$sName]['s'];
			$fSystemTime /= 1e6;
			$sSystemTime = round($fSystemTime,3);
			//self::log('(benchmark) '.$sName.' (user) = '.$fUserTime.' sec');
			//self::log('(benchmark) '.$sName.' (sys) = '.$fSystemTime.' sec');
			$fElapsedTime = $fUserTime+$fSystemTime;
		}
		else
		{
			$fElapsedTime = microtime(true) - self::$afBenchmarks[$sName]['m'];
			$sUserTime = 'N/A';
			$sSystemTime = 'N/A';
		}
		if (isset(self::$afBenchmarksSums[$sName]))	self::$afBenchmarksSums[$sName] += $fElapsedTime;
		
		$sElapsedTime = round($fElapsedTime,3);
		self::log('(benchmark) '.$sName.' = '.$sElapsedTime.' sec (user: '.$sUserTime.' sec, sys: '.$sSystemTime.' sec) ('.self::getMemoryUsage().'MB)');
		return $sElapsedTime;
	}
	
	static function getMemoryUsage()
	{
		return round( memory_get_usage()/1024/1024, 2 );
	}
	
	static function whereAmI()
	{
		try
		{
			throw new AnwUnexpectedException("where am I?");
		}
		catch(AnwException $e)
		{
			print_r($e->getTrace());
		}
	}
}

?>