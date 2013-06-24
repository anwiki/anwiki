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
 * Syndication feeds generating.
 * @package Anwiki
 * @version $Id: class_feed.php 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwFeed
{
	const TYPE_DEFAULT = "rss2";
	const TYPE_RSS1 = "rss1";
	const TYPE_RSS2 = "rss2";
	
	private $aoItems = array();
	private $sType;
	private $sTitle;
	private $sLink;
	private $sUrl;
	private $sDescription;
	
	function __construct($sFeedType="", $sFeedTitle, $sFeedLink, $sFeedDescription="")
	{
		if (in_array($sFeedType, self::getTypes()))
		{
			$this->sType = $sFeedType;
		}
		else
		{
			$this->sType = self::TYPE_DEFAULT;
		}
		$this->sTitle = $sFeedTitle;
		$this->sLink = $sFeedLink;
		$this->sUrl = AnwEnv::_SERVER('REQUEST_URI');
		$this->sDescription = $sFeedDescription;
	}
	
	function addItem($oItem)
	{
		$this->aoItems[] = $oItem;
	}
	
	function getType()
	{
		return $this->sType;
	}
	
	function getLink()
	{
		return $this->sLink;
	}
	
	function getUrl()
	{
		return $this->sUrl;
	}
	
	static function getTypes()
	{
		return array(self::TYPE_RSS1, self::TYPE_RSS2);
	}
	
	function output()
	{
		$sOut = "";
		
		switch($this->getType())
		{
			case self::TYPE_RSS1:
				$sOut .= '<?xml version="1.0"?>'."\n";
				$sOut .= '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:co="http://purl.org/rss/1.0/modules/company/" xmlns:ti="http://purl.org/rss/1.0/modules/textinput/" xmlns="http://purl.org/rss/1.0/"> '."\n";
				$sOut .= '<channel rdf:about="<![CDATA['.$this->sUrl.']]>">'."\n";
				$sOut .= "<title><![CDATA[".$this->sTitle."]]></title>\n";
				$sOut .= "<link><![CDATA[".$this->sLink."]]></link>\n";
				$sOut .= "<description><![CDATA[".$this->sDescription."]]></description>\n";
				$sOut .= "</channel>\n";
				$sOut .= "<items>\n";
				$sOut .= "<rdf:Seq>\n";
				foreach ($this->aoItems as $oItem)
				{
					$sOut .= '<rdf:li resource="'.$oItem->getLink().'" />'."\n";
				}
				$sOut .= "</rdf:Seq>\n";
				$sOut .= "</items>\n";
				foreach ($this->aoItems as $oItem)
				{
					$sOut .= "<item>\n";
					$sOut .= "<title><![CDATA[".$oItem->getTitle()."]]></title>\n";
					$sOut .= "<link><![CDATA[".$oItem->getLink()."]]></link>\n";
					$sOut .= "<dc:description><![CDATA[".$oItem->getDescription()."]]></dc:description>\n";
					if ($oItem->getDate()) $sOut .= "<dc:date>".date("Y-m-d\TH:i:sP", $oItem->getDate())."</dc:date>\n";
					$sOut .= "</item>\n";
				}
				$sOut .= "</rdf:RDF>";
			break;
			
			case self::TYPE_RSS2:
				$sOut .= '<?xml version="1.0"?>'."\n";
				$sOut .= '<rss version="2.0">'."\n";
				$sOut .= "<channel>\n";
				$sOut .= "<title><![CDATA[".$this->sTitle."]]></title>\n";
				$sOut .= "<link><![CDATA[".$this->sLink."]]></link>\n";
				$sOut .= "<description><![CDATA[".$this->sDescription."]]></description>\n";
				foreach ($this->aoItems as $oItem)
				{
					$sOut .= "<item>\n";
					$sOut .= "<title><![CDATA[".$oItem->getTitle()."]]></title>\n";
					$sOut .= "<link><![CDATA[".$oItem->getLink()."]]></link>\n";
					$sOut .= "<author><![CDATA[".$oItem->getAuthor()."]]></author>\n";
					$sOut .= "<description><![CDATA[".$oItem->getDescription()."]]></description>\n";
					if ($oItem->getDate()) $sOut .= "<pubDate>".date("D, d M Y H:i:s e", $oItem->getDate())."</pubDate>\n";
					$sOut .= "</item>\n";
				}
				$sOut .= "</channel>\n";
				$sOut .= "</rss>";
			break;
			
		}
		
		$sOut = AnwUtils::stripUntr($sOut);
		
		//do output
		header("Content-type: application/rss+xml; charset=UTF-8");
		print $sOut;
		exit;
	}
}

class AnwFeedItem
{
	private $sTitle;
	private $sLink;
	private $sAuthor;
	private $sDescription;
	private $nDate;
	
	function __construct($sTitle, $sLink, $sDescription="")
	{
		$this->sTitle = $sTitle;
		$this->sLink = $sLink;
		$this->sDescription = "";
	}
	
	function setDate($nDate)
	{
		$this->nDate = $nDate;
	}
	
	function setAuthor($sAuthor)
	{
		$this->sAuthor = $sAuthor;
	}
	
	function getTitle()
	{
		return $this->sTitle;
	}
	
	function getLink()
	{
		return $this->sLink;
	}
	
	function getAuthor()
	{
		return $this->sAuthor;
	}
	
	function getDate()
	{
		return $this->nDate;
	}
	
	function getDescription()
	{
		return $this->sDescription;
	}
}

?>