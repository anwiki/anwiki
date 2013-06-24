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
 * JS tools for content locking.
 * @package Anwiki
 * @version $Id: class_lock.js 116 2009-02-07 11:09:11Z anw $
 * @copyright 2007-2009 Antoine Walter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License 3
 */

var AnwLock = Class.create();
AnwLock.getLock = function()
{
	return AnwLock.oLock;
}
AnwLock.prototype = 
{
	initialize: function (nType, oDiv, sMessageExpired, sRenewLink, fOnExpireSoon, fOnExpire, fOnContinue)
	{
		AnwLock.oLock = this;
	
		this.nType = nType;
		this.sTarget = AnwUtils.getActionPageUrl();
		this.oDiv = oDiv;

		this.sMessageExpired = sMessageExpired;
		this.sRenewLink = sRenewLink;

		this.fOnExpireSoon = fOnExpireSoon;
		this.fOnExpire = fOnExpire;
		this.fOnContinue = fOnContinue;
		
		this.nRemainingTime = 0;
		this.oLastRenew = new Date();
		this.bValid = true;

		this.bWarned = false;
		this.nCountDownInterval = AnwUtils.cfg("lock_countdowninterval");
		
		this._renew();
		this._startCountDown();
	},
	
	renew: function()
	{
		var now = new Date();
		var dif = now.getTime() - this.oLastRenew.getTime();

		if (dif > AnwUtils.cfg("lock_renewrate")*1000) //renew max every x seconds
		{
			this._renew();
		}
	},
	
	_renew: function()
	{
		this.oLastRenew = new Date();
		var url = this.sTarget;
		var pars = 'locktype='+this.nType;
		
		var myAjax = new Ajax.Request(
			url, 
			{
				method: 'get', 
				parameters: pars,
				onComplete: this.parseResponse.bind(this)
			});
	},

	_startCountDown: function()
	{
		this.oCountDown = new PeriodicalExecuter( this.countdown.bind(this), this.nCountDownInterval );
	},
	
	parseResponse: function(oRequest)
	{
		var oXml = oRequest.responseXML.documentElement;
		var sStatus = oXml.getElementsByTagName('status')[0].firstChild.data;
		var nRemainingTime = oXml.getElementsByTagName('remainingtime')[0].firstChild.data;
		
		if (sStatus == "OK")
		{
			var sMessage = oXml.getElementsByTagName('message')[0].firstChild.data;
			this.setMessage(sMessage);
			if (!this.bValid)
			{
				//ok, we got another lock, we can continue to work...
				this.bValid = true;
				this.setStatus("ok");
				this._startCountDown();
				if (this.fOnContinue) this.fOnContinue();
			}
			if (this.bWarned)
			{
				this.bWarned = false;
			}
			this.nRemainingTime = nRemainingTime;
			this.countdown(); //update
		}
		else
		{
			this.expired();
		}
		
	},
	
	countdown: function()
	{
		this.nRemainingTime -= this.nCountDownInterval;

		if (this.nRemainingTime <= 0)
		{
			this.expired();
		}
		else
		{
			if(this.nRemainingTime <= AnwUtils.cfg("lock_expirealert"))
			{
				if (!this.bWarned)
				{
					this.expiresoon();
					this.bWarned = true;
				}
			}
			else
			{
				this.oDiv.removeClassName("lockinfo_expiresoon");
			}
			this.updateRemainingTime();
		}
	},
	
	expiresoon: function()
	{
		this.setStatus("expiresoon");
		if (this.fOnExpireSoon) this.fOnExpireSoon();
	},
	
	expired: function()
	{
		this.bValid = false;
		this.oCountDown.stop();
		this.setStatus("expired");
		if (this.fOnExpire) this.fOnExpire();
	},

	updateRemainingTime: function()
	{
		$('lockinfo_remainingtime').update(this.getRemainingTimeTxt());
	},

	getRemainingTimeTxt: function()
	{
		var nSeconds = this.nRemainingTime;		
		var nHours = Math.floor(nSeconds / 3600);
		nSeconds -= nHours*3600;
		var nMinutes = Math.floor(nSeconds / 60);
		nSeconds -= nMinutes*60;

		if (nHours < 10) nHours = '0'+nHours;
		if (nMinutes < 10) nMinutes = '0'+nMinutes;
		if (nSeconds < 10) nSeconds = '0'+nSeconds;

		var sStr = nHours+":"+nMinutes+":"+nSeconds;
		return sStr;
	},

	//status : ok, expiresoon, expired
	setStatus: function(sStatus)
	{
		if (this.sStatus != sStatus)
		{
			if (sStatus == "ok")
			{
				this.oDiv.removeClassName("lockinfo_expiresoon");
				this.oDiv.removeClassName("lockinfo_expired");
			}
			else if(sStatus == "expiresoon")
			{
				this.oDiv.addClassName("lockinfo_expiresoon");
				this.oDiv.removeClassName("lockinfo_expired");
			}
			else if(sStatus == "expired")
			{
				this.oDiv.removeClassName("lockinfo_expiresoon");
				this.oDiv.addClassName("lockinfo_expired");
				this.setMessage(this.sMessageExpired);
			}
			this.sStatus = sStatus;
		}
	},

	setMessage: function(sMessage)
	{
		this.oDiv.update(sMessage+this.sRenewLink);
	}
};
