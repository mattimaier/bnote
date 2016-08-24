/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Control','./library'],function(q,C,l){"use strict";var H=C.extend("sap.ui.layout.HorizontalLayout",{metadata:{library:"sap.ui.layout",properties:{allowWrapping:{type:"boolean",group:"Misc",defaultValue:false}},defaultAggregation:"content",aggregations:{content:{type:"sap.ui.core.Control",multiple:true,singularName:"content"}},designtime:true}});H.prototype.getAccessibilityInfo=function(){var c=this.getContent();var a=[];for(var i=0;i<c.length;i++){if(c[i].getAccessibilityInfo){var I=c[i].getAccessibilityInfo();if(I){a.push(I);}}}return{children:a};};return H;},true);
