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
 * Toolkit for using Google Maps.
 * @package Anwiki
 * @version $Id: class_utils.php 332 2010-09-19 22:41:02Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

class AnwGoogleMap
{
	private $sMapJsVar;
	private $nCenterLat;
	private $nCenterLng;
	private $sMapDivId;
	private $nZoom;
	private $sJsDeclare = "";
	private $sJsInitMap = "";
	private $sJsAdditionnal = "";
	
	function __construct($sMapJsVar, $nCenterLat, $nCenterLng, $sMapDivId, $nZoom)
	{
		$this->sMapJsVar = $sMapJsVar;
		$this->nCenterLat = $nCenterLat;
		$this->nCenterLng = $nCenterLng;
		$this->sMapDivId = $sMapDivId;
		$this->nZoom = $nZoom;
	}
	
	function render()
	{
		$HTML = <<<EOF

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
	var {$this->sMapJsVar};
	{$this->sJsDeclare}
	document.observe("dom:loaded", function(){
		var myLatlng = new google.maps.LatLng($this->nCenterLat, $this->nCenterLng);
		var myOptions = {
			zoom: {$this->nZoom},
			center: myLatlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
		var map = new google.maps.Map(document.getElementById("$this->sMapDivId"), myOptions);
		{$this->sMapJsVar} = map;
		{$this->sJsInitMap}
	});
	{$this->sJsAdditionnal}
</script>
EOF;
		return $HTML;
	}
	
	function setListenOnCenterChange($sInputId)
	{
		$this->sJsInitMap .= <<<EOF

		google.maps.event.addListener(map, 'center_changed', function() {
			var mapCenter = map.getCenter();
			var newInputValue = mapCenter.lat()+";"+mapCenter.lng();
			document.getElementById("{$sInputId}").value = newInputValue;
		});
EOF;
	}
	
	function setGeoFinder($sInputAddressId, $sInputSubmitId)
	{
		$this->sJsDeclare .= <<<EOF

var geocoder;
EOF;
		
		$this->sJsInitMap .= <<<EOF

		geocoder = new google.maps.Geocoder();
		$('{$sInputSubmitId}').observe('click', 
			function(){ 
				var addressFinder = document.getElementById("{$sInputAddressId}").value;
				geocoder.geocode( { 'address': addressFinder}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						{$this->sMapJsVar}.setCenter(results[0].geometry.location);
						var marker = new google.maps.Marker({
							map: {$this->sMapJsVar}, 
							position: results[0].geometry.location
						});
					} else {
						alert("Address not found: " + status);
					}
				});
			}
		);
EOF;
	}
	
	function addMapComponent($oMapComponent)
	{
		$this->sJsInitMap .= $oMapComponent->getJsInitMap($this);
		$this->sJsAdditionnal .= $oMapComponent->getJsAdditionnal($this);
	}
	
	// --------
	
	function getCenterLat()
	{
		return $this->nCenterLat;
	}
	
	function getCenterLng()
	{
		return $this->nCenterLng;
	}
	
	function getMapJsVar()
	{
		return $this->sMapJsVar;
	}
}

abstract class AnwGoogleMapComponent
{
	function getJsInitMap($oMap) {return "";}
	function getJsAdditionnal($oMap) {return "";}
}

class AnwGoogleMapInfoWindow extends AnwGoogleMapComponent
{
	private $sContent;
	private $nCenterLat;
	private $nCenterLng;
	private $bOpenOnStartup;
	
	function __construct($sContent, $nCenterLat=false, $nCenterLng=false)
	{
		$this->sContent = $sContent;
		$this->nCenterLat = $nCenterLat;
		$this->nCenterLng = $nCenterLng;
	}
	
	function setOpenOnStartup($bOpenOnStartup)
	{
		$this->bOpenOnStartup = $bOpenOnStartup;
	}
	
	function getJsInitMap($oMap)
	{
		$sContentSafe = AnwUtils::escapeQuote($this->sContent);
		$sMapJsVar = $oMap->getMapJsVar();
		
		$nCenterLat = ($this->nCenterLat ? $this->nCenterLat : $oMap->getCenterLat());
		$nCenterLng = ($this->nCenterLng ? $this->nCenterLng : $oMap->getCenterLng());
		
		$HTML = <<<EOF

		var markerOptions = {map: {$sMapJsVar}, position: new google.maps.LatLng({$nCenterLat}, {$nCenterLng})};
  		var marker = new google.maps.Marker(markerOptions);
		
  		var myWindowOptions = {
			content: "{$sContentSafe}"
		};
		var myInfoWindow = new google.maps.InfoWindow(myWindowOptions);
		google.maps.event.addListener(marker, 'click', function() {
			myInfoWindow.open({$sMapJsVar}, marker);
		});
EOF;
		if ($this->bOpenOnStartup)
		{
			$HTML .= <<<EOF

		google.maps.event.trigger(marker, "click");
EOF;
		}
		return $HTML;
	}
}

?>