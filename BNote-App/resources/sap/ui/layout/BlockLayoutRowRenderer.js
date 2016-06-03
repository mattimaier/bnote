/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";var B={};B.render=function(r,b){this.startRow(r,b);this.renderContent(r,b);this.endRow(r,b);};B.startRow=function(r,b){r.write("<div");r.writeControlData(b);r.addClass("sapUiBlockLayoutRow");this.addRowRenderingClass(r,b);r.writeStyles();r.writeClasses();r.write(">");};B.addRowRenderingClass=function(r,b){if(b.getScrollable()){r.addClass("sapUiBlockScrollingRow");if(b.getContent().length>=6){r.addClass("sapUiBlockScrollingNarrowCells");}}else{r.addClass("sapUiBlockHorizontalCellsRow");}if(b._rowSCase){r.addClass("sapUiBlockRowSCase");}};B.renderContent=function(r,b){var c,a=b.getContent(),s=b.getScrollable();for(var i=0;i<a.length;i++){c=a[i];if(s){c.addStyleClass("sapUiBlockScrollableCell");}else{c.addStyleClass("sapUiBlockHorizontalCell");}r.renderControl(c);}};B.endRow=function(r){r.write("</div>");};return B;},true);
