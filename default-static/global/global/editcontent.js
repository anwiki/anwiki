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
 * JS tools for content edition.
 * @package Anwiki
 * @version $Id: editcontent.js 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

var AnwContentFieldMultiple_asObjects = new Hash(); //TODO static member

var AnwContentFieldMultiple = Class.create({

initialize: function(sId, nMin, nMax, oDivParent, sSuffix, sContentFieldName, bSortable){
	this.sId = sId; //this is the cssClass of childs, too
	this.sChildsClass = sId;
	this.nMin = nMin;
	this.nMax = nMax;
	this.oDivParent = oDivParent;
	this.sSuffix = sSuffix;
	this.sContentFieldName = sContentFieldName;
	this.bSortable = bSortable;
	
	if (AnwContentFieldMultiple_asObjects.get(sId))
	{
		alert("error, sortable "+sId+" already initialized!"); return;
	}
	AnwContentFieldMultiple_asObjects.set(sId, this);

	if (bSortable)
	{
		//make it sortable
		this.makeSortable();
	}
},

makeSortable: function(){
	Sortable.destroy(this.oDivParent);
	Sortable.create(this.oDivParent, {tag: 'div', only:this.sChildsClass, constraint:'vertical', scroll:window});
},

moveUp: function(oDivInput){
	move_li(this.oDivParent, oDivInput, 'up');
	this.makeSortable();
},

moveDown: function(oDivInput){
	move_li(this.oDivParent, oDivInput, 'down');
	this.makeSortable();
},

getMin: function(){
	return this.nMin;
},

getMax: function(){
	return this.nMax;
},

getInstances: function(){
	return $$('div.'+this.sId);
},

getInstancesCount: function(){
	return this.getInstances().length;
},

addInstance: function(callback){
	if (this.getInstancesCount()<this.nMax){

		var url = g_editcontentform_url;
		var pars = '&js=addmultiplecontentfield&fieldname='+this.sContentFieldName+'&suffix='+this.sSuffix;

		var myAjax = new Ajax.Request(
			url, 
			{
				method: 'get',
				parameters: pars,
				onComplete: this.cbkAddInstance.bind(this, callback)
			}
		);
	}
	else{
		alert("Maximum number of items reached");
	}
},

removeInstance: function(oDivInput){
	if (this.getInstancesCount()>this.nMin){
		var oDivParent = oDivInput.parentNode;
		oDivParent.removeChild(oDivInput);
	}
	else{
		alert("Minimum number of items reached");
	}
},

cbkAddInstance: function(callback, originalRequest){
	//this.oDivParent.innerHTML += originalRequest.responseText;
	this.oDivParent.insert(originalRequest.responseText);
	
	//evaluate new javascript coming from ajax
	//originalRequest.responseText.evalScripts();
	
	if (this.bSortable)
	{
		this.makeSortable();
	}

	if (callback) callback(originalRequest);	
}

});

//static
AnwContentFieldMultiple.get = function(sId){
	return AnwContentFieldMultiple_asObjects.get(sId);
}


//collapsable fields
anwExpandField = function(oParentNode)
{
	var divs=oParentNode.childNodes;
	for (var i=0, count=divs.length; i<count; i++)
	{
		var div = divs[i];
		if (div.nodeName=="DIV")
		{
			if (Element.hasClassName(div,"contentfield_collapsed_expanded"))
			{
				//show expanded content
				div.style.display = "block";
			}
			else if (Element.hasClassName(div,"contentfield_collapsed_collapsed"))
			{
				//show expanded content
				div.style.display = "none";
			}
		}
	}
}

anwExpandFieldAncestors = function(theNode)
{
	while (theNode.parentNode)
	{
		theNode = theNode.parentNode;
		if (theNode.nodeName == "DIV")
		{
			if (Element.hasClassName(theNode,"contentfield_collapsed_expanded"))
			{
				anwExpandField(theNode.parentNode);
			}
			else if (Element.hasClassName(theNode,"contentfield_tab"))
			{
				anwExpandTab(theNode);
			}
		}
	}
}

document.observe("dom:loaded", function(){
	//init tabs
	var divTabGroup = document.getElementsByClassName("contentfield_tabs")[0];
	if (divTabGroup)
	{
		var bIsFirstTab = true;
		
		var divTabs=divTabGroup.childNodes;
		for (var i=0, count=divTabs.length; i<count; i++)
		{
			var divTab = divTabs[i];
			if (divTab.nodeName == "A")
			{
				//expand first tab
				if (bIsFirstTab)
				{
					anwExpandTab($(divTab.className));
					bIsFirstTab = false;
				}
				//tab onclick
				divTab.onclick = function(){anwExpandTab($(this.className)); return false;}
			}
		}
	}
	
	//expand erroneous fields to show errors
	var divs=document.getElementsByClassName("contentfield_error");
	for (var i=0, count=divs.length; i<count; i++)
	{
		var div = divs[i];
		anwExpandField(div);
		anwExpandFieldAncestors(div);
	}
});


//fields tabs

anwExpandTab = function(oDiv)
{
	var divs=document.getElementsByClassName("contentfield_tab");
	for (var i=0, count=divs.length; i<count; i++)
	{
		var div = divs[i];
		if (div.id == oDiv.id)
		{
			div.style.display = 'block';
		}
		else
		{
			div.style.display = 'none';
		}
	}
}


//locks auto-renew
anwLockAutoRenew = function(oInput)
{
	if (typeof(AnwLock)!="undefined" && AnwLock.getLock()){
		oInput.observe('keypress', AnwLock.getLock().renew.bind(AnwLock.getLock()));
	}
}




//textarea smart tabs

// thanks to http://bitprophet.org/code/javascript_tab.html !

function textareaSmartTabsOnKey(evt)
{
	var tab = "	";	
	var t = evt.target;
	var ss = getSelectionStart(t);
	var se = getSelectionEnd(t);
	
	// Tab key - insert tab expansion
	if (evt.keyCode == 9) {
	    evt.preventDefault();
	    
	    // Special case of multi line selection
	    if (ss != se && t.value.slice(ss,se).indexOf("\n") != -1) {
	        // In case selection was not of entire lines (e.g. selection begins in the middle of a line)
	        // we ought to tab at the beginning as well as at the start of every following line.
	        var pre = t.value.slice(0,ss);
	        var sel = t.value.slice(ss,se).replace(/\n/g,"\n"+tab);
	        var post = t.value.slice(se,t.value.length);
	        t.value = pre.concat(tab).concat(sel).concat(post);
	        
	        var countTabs = (t.value.slice(ss,se).split("\n"+tab).length);
	        
	        t.selectionStart = ss + tab.length;
	        t.selectionEnd = se + tab.length*countTabs;
	    }
	    
	    // "Normal" case (no selection or selection on one line only)
	    else {
	        t.value = t.value.slice(0,ss).concat(tab).concat(t.value.slice(ss,t.value.length));
	        if (ss == se) {
	            t.selectionStart = t.selectionEnd = ss + tab.length;
	        }
	        else {
	            t.selectionStart = ss + tab.length;
	            t.selectionEnd = se + tab.length;
	        }
	    }
	}
	
	// Backspace key - delete preceding tab expansion, if exists
	else if (evt.keyCode==8 && t.value.slice(ss - 4,ss) == tab) {
	    evt.preventDefault();
	    
	    t.value = t.value.slice(0,ss - 4).concat(t.value.slice(ss,t.value.length));
	    t.selectionStart = t.selectionEnd = ss - tab.length;
	}
	
	// Delete key - delete following tab expansion, if exists
	else if (evt.keyCode==46 && t.value.slice(se,se + 4) == tab) {
	    evt.preventDefault();
	    
	    t.value = t.value.slice(0,ss).concat(t.value.slice(ss + 4,t.value.length));
	    t.selectionStart = t.selectionEnd = ss;
	}
	
	// Left/right arrow keys - move across the tab in one go
	else if (evt.keyCode == 37 && t.value.slice(ss - 4,ss) == tab) {
	    evt.preventDefault();
	    t.selectionStart = t.selectionEnd = ss - 4;
	}
	else if (evt.keyCode == 39 && t.value.slice(ss,ss + 4) == tab) {
	    evt.preventDefault();
	    t.selectionStart = t.selectionEnd = ss + 4;
	}
}

