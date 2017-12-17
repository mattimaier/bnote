/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var S={};S.render=function(r,c){var i=c._getIncrementButton(),d=c._getDecrementButton(),I=c._getInput(),w=c.getWidth(),e=c.getEnabled(),E=c.getEditable(),m=c.getMin(),M=c.getMax(),v=c.getValue(),D=false;r.write("<div ");if(e&&E){r.write("tabindex='-1'");}r.addStyle("width",w);r.writeStyles();r.writeControlData(c);r.writeAccessibilityState(c);r.addClass("sapMStepInput");r.addClass("sapMStepInput-CTX");!e&&r.addClass("sapMStepInputReadOnly");!E&&r.addClass("sapMStepInputNotEditable");r.writeClasses();r.write(">");if(E&&d){D=!e||(v<=m);this.renderButton(r,d,["sapMStepInputBtnDecrease"],D);}r.renderControl(I);if(E&&i){D=!e||(v>=M);this.renderButton(r,i,["sapMStepInputBtnIncrease"],D);}r.write("</div>");};S.renderButton=function(r,b,w,d){b.addStyleClass("sapMStepInputBtn");w.forEach(function(c){b.addStyleClass(c);});d?b.addStyleClass("sapMStepInputIconDisabled"):b.removeStyleClass("sapMStepInputIconDisabled");r.renderControl(b);};return S;},true);
