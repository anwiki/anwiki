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
 * MySQL abstraction class.
 * @package Anwiki
 * @version $Id: class_mysql.php 227 2009-11-12 23:06:33Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

if (!defined("ABSTRACTION_MYSQL_LOADED"))
{
define("ABSTRACTION_MYSQL_LOADED", 1);

class AnwMysql
{
	private $oLink;
	//private $oLastRessource;
	private $sPrefix;
	private $sDatabase;
	private $nTransactionDeep = 0;
	private $bTransactionHasError;
	private static $aoInstances = array();
	
	static function getInstance($sUser, $sPassword, $sHost, $sDatabase, $sPrefix="")
	{
		$sInstanceId = md5($sUser.$sPassword.$sHost.$sDatabase.$sPrefix);
		if (!isset(self::$aoInstances[$sInstanceId]))
		{
			self::$aoInstances[$sInstanceId] = new AnwMysql($sUser, $sPassword, $sHost, $sDatabase, $sPrefix);
		}
		else
		{
			self::debug("Requested MySQL connexion already opened, returning existing instance.");
		}
		return self::$aoInstances[$sInstanceId];
	}
	
	private function __construct($sUser, $sPassword, $sHost, $sDatabase, $sPrefix)
	{
		$this->sDatabase = $sDatabase;
		$this->sPrefix = $sPrefix;
		
		// make sure that MySQL is installed
		if (!function_exists("mysql_connect"))
		{
			throw new AnwDbConnectException("MySQL is not installed on the system.");
		}
		
		// open the MySQL link
		$this->oLink = @mysql_connect($sHost, $sUser, $sPassword, true);
		if (!$this->oLink)
		{
			throw new AnwDbConnectException("Unable to connect to MySQL server (Server: ".$sHost.", user:".$sUser.")");
		}
		
		//force MySQL to use UTF8
		try {
			$this->query("SET NAMES 'utf8'"); // for MySQL5+
		}
		catch(AnwException $e) {
			$this->query("SET CHARACTER SET 'utf8'"); // for older versions of MySQL
		}
		
		if (! @mysql_select_db($sDatabase, $this->oLink))
		{
			throw new AnwDbConnectException("Unable to select the database (Database: ".$sDatabase.")");
		}
	}
	
	function transactionStart()
	{
		if ($this->nTransactionDeep == 0)
		{
			$this->bTransactionHasError = false;
			$this->query("START TRANSACTION");
		}
		$this->nTransactionDeep++;
		$this->debug(" ************ [TRANSACTION ".$this->nTransactionDeep." STARTED] ************");
	}
	
	function transactionCommit()
	{
		if ($this->nTransactionDeep == 0)
		{
			//we are desynchronized due to errors... just ignore this call.
			$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." ignoring COMMIT (unsynchronized)] ************");
			return;
		}
		
		if (!$this->bTransactionHasError)
		{
			$this->nTransactionDeep--;
			
			if ($this->nTransactionDeep == 0)
			{
				$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." COMMIT] ************");
				$this->query("COMMIT");
			}
			else
			{
				$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." READY for COMMIT] ************");
			}
		}
		else
		{
			//we should never go here if all code dealing with transactions is OK
			//the following process is just in case of bad code, if when rollbacking a subquery, we forget to throw again the exception to the upper level
			$this->debug("[TRANSACTION ".$this->nTransactionDeep." COMMIT DENIED due to errors from sub-transactions]");
			$this->transactionRollback(); //we rollback immediately, in case of we forgot to catch the following exception and never rollback
			throw new AnwUnexpectedException("[TRANSACTION ".$this->nTransactionDeep." COMMIT DENIED due to errors from sub-transactions]");
		}
	}
	
	function transactionRollback()
	{
		if ($this->nTransactionDeep == 0)
		{
			//we are desynchronized due to errors... just ignore this call.
			$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." ignoring ROLLBACK (unsynchronized)] ************");
			return;
		}
		
		$this->bTransactionHasError = true;
		$this->nTransactionDeep--;
		
		if ($this->nTransactionDeep == 0)
		{
			$this->bTransactionHasError = false; //reset - before executing rollback query
			$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." ROLLBACK] ************");
			$this->query("ROLLBACK");
		}
		else
		{
			$this->debug(" ************ [TRANSACTION ".($this->nTransactionDeep+1)." AWAITING for ROLLBACK] ************");
		}
	}
	
	function query($sQuery)
	{
		AnwDebug::startBench("mysql query", true);
		//add tables prefix
		$sQuery = str_replace("#PFX#", $this->sPrefix, $sQuery);
		
		if ($this->nTransactionDeep == 0)
		{
			$this->debug("query : ".htmlentities($sQuery));
		}
		else
		{
			$this->debug("[TRANSACTION ".$this->nTransactionDeep."] query : ".htmlentities($sQuery));
		}
		
		//$this->oLastRessource = @mysql_query($sQuery, $this->oLink);
		$oRessource = @mysql_query($sQuery, $this->oLink);
		AnwDebug::stopBench("mysql query");
		
		if ($oRessource == false && mysql_error($this->oLink) != "")
		{
			throw new AnwUnexpectedException("A database query failed - Query: ".$sQuery." : ".mysql_error($this->oLink));
		}
		return $oRessource;
	}
	
	function free($oRessource)
	{
		//if (!$oRessource) $oRessource = $this->getLastRessource();
		if (!$oRessource) throw new AnwUnexpectedException("No valid database ressource for num_rows");
		mysql_free_result($oRessource);
	}
	
	function num_rows($oRessource)
	{
		//if (!$oRessource) $oRessource = $this->getLastRessource();
		if (!$oRessource) throw new AnwUnexpectedException("No valid database ressource for num_rows");
		return mysql_num_rows($oRessource);
	}
	
	function affected_rows()
	{
		return mysql_affected_rows($this->oLink);
	}
	
	function fto($oRessource)
	{
		//if (!$oRessource) $oRessource = $this->getLastRessource();
		if (!$oRessource) throw new AnwUnexpectedException("No valid database ressource for fetch_object");
		return mysql_fetch_object($oRessource);
	}
	
	function insert_id()
	{
		return mysql_insert_id($this->oLink);
	}
	
	function do_insert($amInsert, $sTable)
	{
		$sFields = "";
		$sValues = "";
		foreach ($amInsert as $sField => $mValue)
		{
			$sFields .= $sField.", ";
			$sValues .= $mValue.", "; 
		}
		$sFields = substr($sFields, 0, -2);
		$sValues = substr($sValues, 0, -2);
		
		$sQuery = "INSERT INTO `".$this->sPrefix.$sTable."` (".$sFields.") VALUES (".$sValues.") ";
		return $this->query($sQuery);
	}
	
	function do_update($amUpdate, $sTable, $sAdditional="")
	{
		$sUpdates = "";
		foreach ($amUpdate as $sField => $mValue)
		{
			$sUpdates .= $sField."=".$mValue.", ";
		}
		$sUpdates = substr($sUpdates, 0, -2);
		$sQuery = "UPDATE `".$this->sPrefix.$sTable."` SET ".$sUpdates." ".$sAdditional;
		return $this->query($sQuery);
	}
	
	function table_exists($sTable)
	{
		$sQuery = "SHOW TABLES FROM ".$this->sDatabase." LIKE ".$this->strtosql($this->sPrefix.$sTable);
		$q = $this->query($sQuery);
		if ($this->num_rows($q) > 0)
		{
			$bReturn = true;
		}
		else
		{
			$bReturn = false;
		}
		$this->free($q);
		return $bReturn;
	}
	
	//------------------
	
	function strtosql($mValue, $bNull=false)
	{
		if ($bNull && !$mValue)
		{
			$mValue = "NULL";
		}
		else
		{
			$mValue = "'".mysql_real_escape_string( $mValue, $this->oLink )."'";
		}
		return $mValue;
	}
	
	function inttosql($mValue, $bNull=false)
	{
		if ($bNull && !$mValue)
		{
			$mValue = "NULL";
		}
		else
		{
			$mValue = (int)$mValue;
		}
		return $mValue;
	}
	
	function booltosql($mValue)
	{
		$mValue = ($mValue ? 1 : 0);
		$mValue = (int)$mValue;
		return $mValue;
	}
	
	/*private function getLastRessource()
	{
		return $this->oLastRessource;
	}*/
	
	private static function debug($sMessage)
	{
		return AnwDebug::log("(AnwMysql)".$sMessage);
	}
}

}

?>