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
 * Anwiki's JS toolbox.
 * @package Anwiki
 * @version $Id: class_utils.js 324 2010-09-18 21:23:13Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

var AnwUtils = {

cfg: function(sParam)
{
	return g_anwcfg[sParam];
},

getPageName: function(){
	return g_pagename;
},

getActionUrl: function(){
	return g_actionurl;
},

getActionPageUrl: function(){
	return g_actionpageurl;
},

/*
getCurrentUrl: function(){
	var sLocation = window.location.href;
	var nPos = sLocation.lastIndexOf('#');
	if (nPos != -1)
	{
		sLocation = sLocation.substr(0, nPos-1);
	}
	return sLocation;
},
*/

chkall: function(sClassName, oParent){
	if (!oParent) oParent = document.body;
	var chks=oParent.select('.'+sClassName);
	for (var i=0, count=chks.length; i<count; i++) if (!chks[i].disabled) chks[i].checked='checked';
},

chknone: function(sClassName, oParent){
	if (!oParent) oParent = document.body;
	var chks=oParent.select('.'+sClassName);
	for (var i=0, count=chks.length; i<count; i++) if (!chks[i].disabled) chks[i].checked='';
},

showHide: function(oElement){
	if (oElement.style.display!='none'){
		oElement.style.display = 'none';
	}
	else{
		oElement.style.display = 'block';
	}
},

getCookie: function(sVarName){
	var cook = document.cookie;
	sVarName += "=";
	var place = cook.indexOf(sVarName,0);
	if(place <= -1) return("0");
	else {
		var end = cook.indexOf(";",place)
		if(end <= -1) return(unescape(cook.substring(place+sVarName.length,cook.length)));
		else return(unescape(cook.substring(place+sVarName.length,end)));
	}
},

setCookie: function(sVarName, sValue, sPermanent){
	if(sPermanent){
		dateExp = new Date(2020,11,11);
		dateExp = dateExp.toGMTString();
		ifpermanent = '; expires=' + dateExp + ';';
	} else ifpermanent = '';
	domain = '; path=/; ';
	document.cookie = sVarName + '=' + escape(sValue) + domain + ifpermanent;
}
} //end class AnwUtils



// textarea focus line - thanks to http://www.the-asw.com !

function setTextareaFocusLine(nLine, textarea)
{
	var asLines = textarea.value.split("\n");
	var nTotal = 0;
	nLine--;
	for (var i=0; i<asLines.length && i < nLine; i++)
	{
		nTotal += (asLines[i].length + 1);
	}
	setCaretPos( nTotal, nTotal+asLines[nLine].length, textarea);
	textarea.scrollTop = nLine*15;
}

function setCaretPos(start, end, textarea)
{
	end = end || start;
	textarea.focus();
	if (textarea.setSelectionRange)
		textarea.setSelectionRange(start, end);
	else if (document.selection) {
		var range = textarea.createTextRange();
		range.moveStart('character', start);
		range.moveEnd('character', - textarea.value.length + end);
		range.select();
	}textarea.focus();
}

function getSelection(textarea)
{
	var start = getSelectionStart(textarea);
	var stop = getSelectionEnd(textarea);
	return textarea.value.substring(start, stop);
}

function replaceSelection(str, keep, textarea)
{
	textarea.focus();
	
	var start = getSelectionStart(textarea);
	var stop = getSelectionEnd(textarea);
	var end = start + str.length;
	var scrollPos = textarea.scrollTop;
		
	textarea.value = textarea.value.substring(0, start) + str + textarea.value.substring(stop);
	if ( keep ) setCaretPos(start, end, textarea);
	else setCaretPos(end, 0, textarea);
	textarea.scrollTop = scrollPos;
}

function getSelectionStart(textarea)
{
	if ( typeof textarea.selectionStart != 'undefined' )
		return textarea.selectionStart;
	
	// IE Support
	textarea.focus();
	var range = textarea.createTextRange();
	range.moveToBookmark(document.selection.createRange().getBookmark());
	range.moveEnd('character', textarea.value.length);
	return textarea.value.length - range.text.length;
}

function getSelectionEnd(textarea)
{
	if ( typeof textarea.selectionEnd != 'undefined' )
		return textarea.selectionEnd;

	// IE Support
	textarea.focus();
	var range = textarea.createTextRange();
	range.moveToBookmark(document.selection.createRange().getBookmark());
	range.moveStart('character', - textarea.value.length);
	return range.text.length;
}

function textareaInit(element)
{
	textareaAutoExpandInit(element);
}
function textareaKeyPress(evt)
{
	textareaAutoExpandOnKey(evt);
	
	//only if selectionStart is supported (TODO:)
	if ( typeof evt.target != 'undefined' && typeof evt.target.selectionStart != 'undefined' ){
		textareaSmartTabsOnKey(evt);
	}
}

// textarea resizable

function textareaCountLines(element) 
{
	strtocount=element.value;
	cols=element.cols;
	var hard_lines = 1;
	var last = 0;
	while ( true ) 
	{
		last = strtocount.indexOf("\n", last+1);
		hard_lines ++;
		if ( last == -1 ) break;
	}
	var soft_lines = Math.round(strtocount.length / (cols-1));
	var hard = eval("hard_lines  " + unescape("%3e") + "soft_lines;");
	if ( hard ) soft_lines = hard_lines;
	return soft_lines;
}

//est appellé a chaque frappe sur le clavier
function textareaAutoExpandOnKey(e) 
{
	//var element = e.target;
	var element = (window.event)?window.target:e.target;
	if (!element) element=window.event.srcElement;
	
	//var key=(window.Event)?e.which:e.keyCode;
	var key=(window.event)?window.event.keyCode:e.which;
	if(key==13 //enter
		|| key==8 //back (firefox only)
		|| key==0 //delete (firefox only)
		|| (key >= 115 && key <= 125))
	{
		textareaAutoExpandInit(element);
	}
	if (key == 118) //quick hack for paste
	{
		setTimeout(function(){textareaAutoExpandInit(element);},1);
	}
} 

function textareaAutoExpandInit(element) 
{
	element.rows=textareaCountLines(element)+1;
}


// string utils
String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

function nodeInsertAfter(parentNode, noeudAInserer, noeudDeReference) {
	if(noeudDeReference.nextSibling) {
		return parentNode.insertBefore(noeudAInserer, noeudDeReference.nextSibling);
	} else {
		return parentNode.appendChild(noeudAInserer);
	}
}


// scriptaculous enhancements

//Some code found on Scriptaculous website and quickly customized. Thanks to Ian for the original code!
function move_li(obj,oChild,dir){
  var old_obj = null;
  var new_obj = null;

  CN = obj.childNodes; // get nodes
  x = 0;
  while(x < CN.length){ // loop through elements for the desired one
    if(CN[x] == oChild){
      new_obj = CN[x].cloneNode(true); //create copy of node
      break; // End the loop since we found the element
    }else{
      x++;
      }
    }
  if(new_obj){
    if(dir == 'down'){ // Count up, as the higher the number, the lower on the page
      y = x + 1;
      while(y < CN.length){ // loop trhough elements from past the point of the desired element
        if(CN[y].tagName == oChild.tagName){ // check if node is the right kind
          old_obj = CN[y].cloneNode(true);
          break; // End the loop
        }else{
          y++;
          }
        }
      }
    if(dir == 'up'){ // Count down, as the lower the number, the higher on the page
      if(x > 0){
        y = x - 1;
        while(y >= 0){ // loop trhough elements from past the point of the desired element
          if(CN[y].tagName == oChild.tagName){ // check if node is the right kind
            old_obj = CN[y].cloneNode(true);
            break; // End the loop
          }else{
            y--;
            }
          }
        }
      }
    if(old_obj){ // if there is an object to replace, replace it.
        new_obj.style.display='none';
        old_obj.style.display='none';
        obj.replaceChild(new_obj,CN[y]);
        Effect.Appear(new_obj, {duration:.3});
		obj.replaceChild(old_obj,CN[x]);
        Effect.Appear(old_obj, {duration:.3});
      }
    else{
      alert("error");
      }
    }
    else{
      alert("error");
      }
  }

