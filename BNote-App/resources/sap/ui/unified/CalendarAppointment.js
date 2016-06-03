/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./DateTypeRange','./library'],function(q,D,l){"use strict";var C=D.extend("sap.ui.unified.CalendarAppointment",{metadata:{library:"sap.ui.unified",properties:{title:{type:"string",group:"Data"},text:{type:"string",group:"Data"},icon:{type:"sap.ui.core.URI",group:"Data",defaultValue:null},tentative:{type:"boolean",group:"Data",defaultValue:false},selected:{type:"boolean",group:"Data",defaultValue:false},key:{type:"string",group:"Data",defaultValue:null}}}});C.prototype.applyFocusInfo=function(f){var p=this.getParent();if(p){p.applyFocusInfo(f);}return this;};return C;},true);
