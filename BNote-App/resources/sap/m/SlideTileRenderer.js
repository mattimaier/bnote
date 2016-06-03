/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var S={};S.render=function(r,c){r.write("<div");r.writeControlData(c);r.addClass("sapMST");r.writeClasses();var t=c.getTooltip_AsString();if(t){r.writeAttributeEscaped("title",t);}r.writeAttribute("tabindex","0");r.writeAttribute("role","presentation");r.write(">");var l=c.getTiles().length;for(var i=0;i<l;i++){r.write("<div");r.writeAttribute("id",c.getId()+"-wrapper-"+i);r.addClass("sapMSTWrapper");r.writeClasses();r.write(">");r.renderControl(c.getTiles()[i]);r.write("</div>");}r.write("</div>");};return S;},true);
