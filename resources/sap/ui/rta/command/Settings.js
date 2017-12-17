/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/rta/command/FlexCommand'],function(F){"use strict";var S=F.extend("sap.ui.rta.command.Settings",{metadata:{library:"sap.ui.rta",properties:{content:{type:"any"}},associations:{},events:{}}});S.prototype._getChangeSpecificData=function(f){var s={changeType:this.getChangeType(),content:this.getContent()};return s;};S.prototype.execute=function(){if(this.getElement()){return F.prototype.execute.apply(this,arguments);}else{return Promise.resolve();}};S.prototype.undo=function(){if(this.getElement()){return F.prototype.undo.apply(this,arguments);}else{return Promise.resolve();}};return S;},true);
