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
 * JS tools for on-the-fly content translation.
 * @package Anwiki
 * @version $Id: class_translator.js 356 2010-12-13 00:02:45Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */



var AnwTranslator = Class.create({

initialize: function(eForm, eDivMain, eDivPanel, eDivOriginal, eInputTranslate, eChkDone, eChkApply, eChkAutoTranslate, eChkSkipTranslated, sEmptyValue, sEmptyValueSrc)
{
	this.aoItems = new Array();
	
	this.eForm = eForm;
	this.eDivMain = eDivMain;
	this.eDivPanel = eDivPanel;
	this.eDivOriginal = eDivOriginal;
	this.eInputTranslate = eInputTranslate;
	this.eChkDone = eChkDone;
	this.eChkApply = eChkApply;
	this.eChkAutoTranslate = eChkAutoTranslate;
	this.eChkSkipTranslated = eChkSkipTranslated;
	this.sEmptyValue = sEmptyValue;
	this.sEmptyValueSrc = sEmptyValueSrc;
	
	this.eCurrentItem = null;

	this.bDoWarning = false;
	this.bDoLeftRightShortcuts = false;
	
	this.bStarted = false;
	
	//keyboard shortcuts
	shortcut.add("Ctrl+ENTER", this.translateNext.bind(this));
	shortcut.add("Ctrl+Shift+ENTER", this.markTranslatedAndTranslateNext.bind(this));
	
	//warning message for unsaved changes
	Event.observe(window, 'beforeunload', this.doWarning.bind(this));
	
	//initialize
	this.eInputTranslate.onkeyup = this.translationKeyUp.bind(this);
	this.eInputTranslate.onblur = this.updateCurrentTranslation.bind(this);

	this.eChkDone.onclick = this.markDone.bind(this);
	this.eChkApply.onclick = this.markApply.bind(this);
},

start: function()
{
	this.translateNext();
	this.bStarted = true;
},

addItem: function(oItem)
{
	//set span
	oItem.getSpan().onclick = this.selectItem.bind(this, oItem.getId());
	
	//register
	oItem.setTranslator(this);
	this.aoItems[oItem.getId()] = oItem;
},

translationKeyUp: function(ev)
{
	if (this.eInputTranslate.value != this.eCurrentItem.getDefaultValue())
	{
		this.setTranslated();
		this.bDoWarning = true;
	}
},

doWarning: function(event)
{
	if (this.bDoWarning)
	{
		if (!confirm("You have unsaved changes. Do you really want to quit ?\nAll unsaved translations will be lost."))
		{
			Event.stop(event);
		}
	}
},

//------------------------------------------------
// PUBLIC ACTIONS
//------------------------------------------------

setTranslated: function()
{
	if (!this.eCurrentItem.isTranslated())
	{
		this.eCurrentItem.setTranslated( true );
		this.refreshInputTranslate();
	}
},

updateCurrentTranslation: function()
{
	this.eCurrentItem.setCurrentValue( this.eInputTranslate.value );
	
	if (this.eChkAutoTranslate.checked)
	{
		// FS#103 only autoupdate for translated strings
		if (this.eChkDone.checked)
		{
			var nbAutoTranslations = 0;
			for (var i=0; i<this.aoItems.length; i++)
			{
				var oItem = this.aoItems[i];
				if (i != this.eCurrentItem.getId() && oItem.getOriginalValue() == this.eCurrentItem.getOriginalValue())
				{
					oItem.setCurrentValue( this.eInputTranslate.value );
					oItem.setTranslated( true );
					nbAutoTranslations++;
				}
			}
			/*
			if (nbAutoTranslations > 0)
			{
				alert(nbAutoTranslations+" similar translations have been automatically updated !");
			}*/
		}
	}
},

showPanel: function()
{
	//move the translation panel to the selected item
	this.movePanelToSelectedItem();

	//show the translation panel
	this.eDivPanel.style.display = "block";

	//move window to current item
	new Effect.ScrollTo(this.eCurrentItem.getSpan().id, {offset: -150, duration: 0.3});
	//document.location='#'+this.eCurrentItem.getSpan().id;
},

movePanelToSelectedItem: function()
{
	this.eForm.parentNode.removeChild(this.eForm);

	//find where to place the panel. Panel shouldn't be placed into inline elements such as H1, H2..., span...
	var oBeforeElement = this.eCurrentItem.getSpan();
	var oParentElement = oBeforeElement.parentNode;

	while (!this.isAllowedParentForTranslator(oParentElement) && oParentElement.parentNode!=null){
		oBeforeElement = oParentElement;
		oParentElement = oParentElement.parentNode;
	}
	
	nodeInsertAfter(oParentElement, this.eForm, oBeforeElement);
},

isAllowedParentForTranslator: function(oParentElement)
{
	var forbiddenParents = new Array('H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HTML', 'SPAN', 'P', 'LI', 'A', 'B', 'I', 'U' );
	for (i in forbiddenParents)
	{
		if (forbiddenParents[i] == oParentElement.nodeName)
		{
			return false;
		}
	}
	return true;
},


startLeftRightShortcuts: function()
{
	//this.stopLeftRightShortcuts();
	if (!this.bDoLeftRightShortcuts)
	{
		this.bDoLeftRightShortcuts = true;

		this.eInputTranslate.observe('keydown', this.onKeyDown.bind(this));
	}
},

stopLeftRightShortcuts: function()
{
	if (this.bDoLeftRightShortcuts)
	{
		/*shortcut.remove("left");
		shortcut.remove("right");*/
		this.eInputTranslate.stopObserving('keydown', this.onKeyDown);
		this.bDoLeftRightShortcuts = false;
	}
},

hidePanel: function()
{
	this.eDivPanel.style.display = "none";
},

revert: function()
{
	this.eCurrentItem.revert();
	this.eInputTranslate.value = this.eCurrentItem.getCurrentValue();
	this.refreshInputTranslate();
},

doneall: function()
{
	for (var i=0; i<this.aoItems.length; i++)
	{
		var eItem = this.aoItems[i];
		if (eItem) eItem.setTranslated(true);
	}
},

undoneall: function()
{
	for (var i=0; i<this.aoItems.length; i++)
	{
		var eItem = this.aoItems[i];
		if (eItem) eItem.setTranslated(false);
	}
},

markDone: function()
{
	this.eCurrentItem.setTranslated( this.eChkDone.checked );
	this.refreshInputTranslate();
},

markApply: function()
{
	this.eCurrentItem.setApply( this.eChkApply.checked );
},

translatePrevious: function()
{
	var nPreviousItemid;
	if (!this.eCurrentItem)
	{
		nPreviousItemid = 0;
	}
	else
	{
		this.updateCurrentTranslation();
		nPreviousItemid = this.eCurrentItem.getId()-1;

		if (this.eChkSkipTranslated.checked)
		{
			while (this.getItem(nPreviousItemid) && this.getItem(nPreviousItemid).isTranslated())
			{
				nPreviousItemid--;
			}
		}
	}
	if (!this.getItem(nPreviousItemid))
	{
		alert("It's already the first translation...");
	}
	else
	{
		this.selectItem(nPreviousItemid);
	}
},

translateNext: function()
{
	var nNextItemId;
	if (!this.eCurrentItem)
	{
		nNextItemId = 0;
	}
	else
	{
		this.updateCurrentTranslation();
		nNextItemId = this.eCurrentItem.getId()+1;
	}	
	
	if (this.eChkSkipTranslated.checked)
	{
		while (this.getItem(nNextItemId) && this.getItem(nNextItemId).isTranslated())
		{
			nNextItemId++;
		}
	}
	if (!this.getItem(nNextItemId))
	{
		if (this.bStarted)
		{
			alert("Hey, it seems you've done ! :-)");
		}
	}
	else
	{
		this.selectItem(nNextItemId);
	}
},

markTranslatedAndTranslateNext: function()
{
	if (this.eCurrentItem)
	{
		this.setTranslated();
	}
	this.translateNext();
},

selectItem: function(nItemId)
{
	//unset old active item
	this.unactiveCurrentItem();
	
	//set new active item
	this.eCurrentItem = this.getItem(nItemId);
	this.activeCurrentItem();
	
	//update translation panel
	this.eDivOriginal.update(this.eCurrentItem.getOriginalValue());
	this.eInputTranslate.value = this.eCurrentItem.getCurrentValue();
	this.eChkDone.checked = this.eCurrentItem.isTranslated();
	this.eChkApply.checked = this.eCurrentItem.isApply();
	this.refreshInputTranslate();

	//open panel and move near current item
	this.showPanel();
	
	//focus on input
	this.eInputTranslate.focus();
	//this.eInputTranslate.select();

	//don't select starting/ending spaces if any
	var tmpValue = this.eInputTranslate.value;
	var selectBegin = 0;
	var selectEnd = tmpValue.length;
	if (tmpValue.substring(0,1)==" ") selectBegin++;
	if (tmpValue.substring(tmpValue.length-1,tmpValue.length)==" ") selectEnd--;
	setCaretPos(selectBegin, selectEnd, this.eInputTranslate);

	this.startLeftRightShortcuts();
},

refreshInputTranslate: function()
{
	if (this.eCurrentItem.isTranslated())
	{
		this.eChkDone.checked = true;
		this.eInputTranslate.removeClassName('untranslated');
	}
	else
	{
		this.eChkDone.checked = false;
		this.eInputTranslate.addClassName('untranslated');
	}
},

save: function()
{
	this.bDoWarning = false;

	for (var i=0; i<this.aoItems.length; i++)
	{
		var oItem = this.aoItems[i];
		
		input = document.createElement("input");
		input.setAttribute("name","translation-"+oItem.getSpanId());
		input.setAttribute("type","hidden");
		input.setAttribute("value",oItem.getCurrentValue());
		this.eDivMain.appendChild(input);
		
		input = document.createElement("input");
		input.setAttribute("name","done-"+oItem.getSpanId());
		input.setAttribute("type","hidden");
		input.setAttribute("value",(oItem.isTranslated() ? "1" : "0"));
		this.eDivMain.appendChild(input);
		
		input = document.createElement("input");
		input.setAttribute("name","apply-"+oItem.getSpanId());
		input.setAttribute("type","hidden");
		input.setAttribute("value",(oItem.isApply() ? "1" : "0"));
		this.eDivMain.appendChild(input);
	}
	input = document.createElement("input");
	input.setAttribute("name","spancount");
	input.setAttribute("type","hidden");
	input.setAttribute("value",this.aoItems.length);
	this.eDivMain.appendChild(input);
	
	this.eForm.submit();
},


//------------------------------------------------
// PRIVATE
//------------------------------------------------

getItem: function(nItemId)
{
	return this.aoItems[nItemId];
},

unactiveCurrentItem: function()
{
	if (this.eCurrentItem) this.eCurrentItem.getSpan().removeClassName("translationactive");
},

activeCurrentItem: function()
{
	this.eCurrentItem.getSpan().addClassName("translationactive");
},

onKeyDown: function(e)
{
	var oInput = e.target;
	var key=(window.Event)?e.which:e.keyCode;
	if (key == 39 || key == 37)
	{
		if (this.bDoLeftRightShortcuts)
		{
			var len = oInput.value.length;
			if (getSelectionStart(oInput) == 0 && getSelectionEnd(oInput) == len)
			{
				if (key == 37) //left
				{
					setCaretPos(0, 0, oInput);
				}
				else //right
				{
					setCaretPos(len, len, oInput);
				}
			}
		}
	}
	this.stopLeftRightShortcuts();
}



}); //end class AnwTranslator


//-------------------------------------------------
var TranslateItem_static_id = 0;

var TranslateItem = Class.create({

initialize: function(sSpanId, sDefaultValue, sOriginalValue, bTranslated)
{
	this.nId = TranslateItem_static_id; 
	TranslateItem_static_id++;

	this.sSpanId = sSpanId;
	this.sDefaultValue = sDefaultValue;
	this.sOriginalValue = sOriginalValue;
	this.sCurrentValue = sDefaultValue;
	this.setTranslated(bTranslated);
	this.bDefaultTranslated = bTranslated;
	this.bApply = false;
},

setTranslator: function(oTranslator)
{
	this.oTranslator = oTranslator;

	//we can now initialize span
	this.getSpan().addClassName("translateitem");
	this.setCurrentValue(this.sCurrentValue);
},

//------------------------------------------------
// SETTERS
//------------------------------------------------

setCurrentValue: function(sCurrentValue)
{
	this.sCurrentValue = sCurrentValue;

	if (this.hasEmptyValue())
	{
		this.getSpan().update(this.oTranslator.sEmptyValueSrc);
		this.getSpan().addClassName("emptyitem");
	}
	else
	{
		this.getSpan().update(sCurrentValue);
		this.getSpan().removeClassName("emptyitem");
	}
},

hasEmptyValue: function()
{
	var trimValue = this.sCurrentValue.trim();
	return (trimValue=="" || trimValue==this.oTranslator.sEmptyValue);
},

setTranslated: function(bTranslated)
{
	this.bTranslated = bTranslated;
	if (bTranslated)
	{
		this.getSpan().removeClassName("untranslated");
	}
	else
	{
		this.getSpan().addClassName("untranslated");
	}
},

setApply: function(bApply)
{
	this.bApply = bApply;
	if (bApply)
	{
		this.getSpan().addClassName("apply");
	}
	else
	{
		this.getSpan().removeClassName("apply");
	}
},

revert: function()
{
	this.setCurrentValue( this.getDefaultValue() );
	this.setTranslated( this.bDefaultTranslated );
},

//------------------------------------------------
// ACCESSORS
//------------------------------------------------

getId: function()
{
	return this.nId;
},

getDefaultValue: function()
{
	return this.sDefaultValue;
},

getOriginalValue: function()
{
	return this.sOriginalValue;
},

getCurrentValue: function()
{
	return this.sCurrentValue;
},

isTranslated: function()
{
	return this.bTranslated;
},

isApply: function()
{
	return this.bApply;
},

getSpanId: function()
{
	return this.sSpanId;
},

getSpan: function()
{
	return $(this.getSpanId());
}
}); //end class TranslateItem
