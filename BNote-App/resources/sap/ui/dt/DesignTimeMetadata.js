/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/base/ManagedObject'],function(q,M){"use strict";var D=M.extend("sap.ui.dt.DesignTimeMetadata",{metadata:{library:"sap.ui.dt",properties:{data:{type:"object"}}}});D.prototype.setData=function(d){var m=q.extend(true,this.getDefaultData(),d||{});this.setProperty("data",m);return this;};D.prototype.getData=function(){var d=this.getProperty("data");if(!d){this.setData({});d=this.getProperty("data");}return d;};D.prototype.getDefaultData=function(){return{ignore:false,domRef:undefined,cloneDomRef:false};};D.prototype.isIgnored=function(){return this.getData().ignore;};D.prototype.getCloneDomRef=function(){return this.getData().cloneDomRef;};D.prototype.getDomRef=function(){return this.getData().domRef;};return D;},true);
