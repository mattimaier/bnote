/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";var T={updateMode:function(e,v){if(!this._oShell){this._oShell=sap.ui.getCore().byId('Shell');}var s=(this._getSplitApp().getMode()==="ShowHideMode");if(s){this._getSplitApp().setMode('HideMode');this._oShell.setAppWidthLimited(false);}else{this._getSplitApp().setMode('ShowHideMode');this._oShell.setAppWidthLimited(true);}this.updateControl(e.getSource(),v,s);},_getSplitApp:function(){if(!this._oSplitApp){this._oSplitApp=sap.ui.getCore().byId('splitApp');}return this._oSplitApp;},updateControl:function(b,v,f){if(arguments.length===2){f=!(this._getSplitApp().getMode()==="ShowHideMode");}var i=v.getModel('i18n');if(!f){b.setTooltip(i.getProperty('sampleFullScreenTooltip'));b.setIcon('sap-icon://full-screen');}else{b.setTooltip(i.getProperty('sampleExitFullScreenTooltip'));b.setIcon('sap-icon://exit-full-screen');}},cleanUp:function(){this._oSplitApp=null;this._oShell=null;}};return T;},true);
