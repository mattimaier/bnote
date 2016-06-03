/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Renderer'],function(q,R){"use strict";var P={};P.render=function(r,t){var T=t.getTooltip_AsString();r.write("<div");r.writeControlData(t);r.addClass("sapMPlanCal");if(!t.getSingleSelection()){r.addClass("sapMPlanCalMultiSel");}if(!t.getShowRowHeaders()){r.addClass("sapMPlanCalNoHead");}if(T){r.writeAttributeEscaped('title',T);}var w=t.getWidth();if(w){r.addStyle("width",w);}var h=t.getHeight();if(h){r.addStyle("height",h);}r.writeAccessibilityState(t);r.writeClasses();r.writeStyles();r.write(">");var o=t.getAggregation("table");r.renderControl(o);r.write("</div>");};return P;},true);
