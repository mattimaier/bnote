/*
 * ! UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./TreeItemBase','./ListItemBase','./library'],function(q,T,L,l){"use strict";var C=T.extend("sap.m.CustomTreeItem",{metadata:{library:"sap.m",defaultAggregation:"content",aggregations:{content:{type:"sap.ui.core.Control",multiple:true,singularName:"content",bindable:"bindable"}}}});C.prototype.getContentAnnouncement=function(){return this.getContent().map(function(c){return L.getAccessibilityText(c);}).join(" ").trim();};return C;},true);
