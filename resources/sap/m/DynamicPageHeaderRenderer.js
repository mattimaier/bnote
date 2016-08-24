/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var D={};D.render=function(r,d){var c=d.getContent();r.write("<header");r.writeControlData(d);r.writeAttribute("tabindex","0");r.writeAccessibilityState({role:"region"});r.addClass("sapContrastPlus");r.addClass("sapMDynamicPageHeader");r.writeClasses();r.write(">");if(c.length>0){r.write("<div");r.addClass("sapMDynamicPageHeaderContent");r.writeClasses();r.write(">");c.forEach(r.renderControl);r.write("</div>");if(d.getPinnable()&&!sap.ui.Device.system.phone){D._renderPinUnpinArea(d,r);}}r.write("</header>");};D._renderPinUnpinArea=function(d,r){r.write("<div");r.addClass("sapMDynamicPageHeaderPinButtonArea");r.writeClasses();r.write(">");r.renderControl(d._getPinButton());r.write("</div>");};return D;},true);
