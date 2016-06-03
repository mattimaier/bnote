/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";var H=function(){};H.render=function(r,c){var a=r;a.write("<div ");a.writeControlData(c);a.addClass("sapUiHexBtn");a.addClass("sapUiHexBtn"+q.sap.encodeHTML(c.getEnabled()?c.getColor():"Gray"));if(c.getEnabled()&&c.hasListeners('press')){a.addClass("sapUiHexBtnActive");}a.writeClasses();a.write(" style='"+q.sap.encodeHTML(c.getPosition())+"'");if(c.getTooltip_AsString()){a.writeAttributeEscaped("title",c.getTooltip_AsString());}a.write(">");if(c.getIcon()){a.write("<IMG ");a.writeAttributeEscaped("src",c.getIcon());var i=c.getImagePosition();if(i){a.write(" style='"+q.sap.encodeHTML(i)+"'");}else{a.write(" style='position:relative;left:40px;top:45px;'");}a.write(" border='0'");a.write("/>");}a.write("</div>");};return H;},true);
