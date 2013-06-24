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
 * Anwiki's global JS scripts.
 * @package Anwiki
 * @version $Id: global.js 243 2010-02-19 22:52:00Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

document.observe("dom:loaded",
	function()
	{
		if ($('pageactions'))
		{
			new AnwExpandableDiv($('pageactions'), "pageactions", "n");
		}
		if ($('globalnav'))
		{
			new AnwExpandableDiv($('globalnav'), "globalnav", "n");
		}
	}
);

var AnwExpandableDiv = Class.create({

	initialize: function (oDiv, sCookieName, sDefaultIsExpanded)
	{
		this.oDiv = oDiv;
		this.sCookieName = "anwexpand"+sCookieName;
		this.sDefaultIsExpanded = sDefaultIsExpanded;

		//create container
		var oContainer = document.createElement("div");
		oContainer.className = "expandablecontainer";
		var nLength = this.oDiv.childNodes.length;
		for (var i=0; i<nLength; i++)
		{
			var oChild = this.oDiv.childNodes[0];
			oContainer.appendChild(oChild); //moves automatically
		}
		this.oDiv.appendChild(oContainer);

		//create switch
		var oSwitch = document.createElement("a");
		oSwitch.onclick = this.onclick.bind(this);
		oSwitch.className = "expandableswitch";
		this.oDiv.insertBefore(oSwitch, this.oDiv.childNodes[0]);

		//hover
		this.oDiv.onmouseover = this.onmouseover.bind(this);
		this.oDiv.onmouseout = this.onmouseout.bind(this);

		var sCookieValue = AnwUtils.getCookie(this.sCookieName);
		if ( (sCookieValue != 0 && sCookieValue == "n") || (sCookieValue == 0 && sDefaultIsExpanded == "n") )
		{
			this.isExpanded = "n";
			this.minimize();
		}
		else
		{
			this.isExpanded = "y";
			this.expand();
		}
	},

	expand: function ()
	{
		this.oDiv.removeClassName("unexpanded");
		this.oDiv.addClassName("expanded");		
	},

	minimize: function ()
	{
		this.oDiv.removeClassName("expanded");
		this.oDiv.addClassName("unexpanded");
	},

	onclick: function()
	{
		if (this.isExpanded == "n")
		{
			this.isExpanded = "y";
			this.expand();
		}
		else
		{
			this.isExpanded = "n";
			this.minimize();
		}
		AnwUtils.setCookie(this.sCookieName, this.isExpanded);		
	},

	onmouseover: function()
	{
		if (this.isExpanded == "n")
		{
			this.expand();
		}
	},

	onmouseout: function()
	{
		if (this.isExpanded == "n")
		{
			this.minimize();
		}
	}

}); //end class AnwExpandableDiv


