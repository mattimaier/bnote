/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var B={};B.render=function(r,b){this.startLayout(r,b);this.addContent(r,b);this.endLayout(r);};B.startLayout=function(r,b){var a=b.getBackground();r.write("<div");r.writeControlData(b);r.addClass("sapUiBlockLayout");if(a=="Light"){r.addClass("sapUiBlockLayoutLightBackground");}r.writeStyles();r.writeClasses();r.write(">");};B.addContent=function(r,b){var c=b.getContent();c.forEach(r.renderControl);};B.endLayout=function(r){r.write("</div>");};return B;},true);
