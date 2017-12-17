/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/rta/command/FlexCommand'],function(q,F){"use strict";var C=F.extend("sap.ui.rta.command.CreateContainer",{metadata:{library:"sap.ui.rta",properties:{index:{type:"int"},newControlId:{type:"string"},label:{type:"string"}},associations:{},events:{}}});C.prototype._getChangeSpecificData=function(f){var s={changeType:this.getChangeType(),index:this.getIndex(),newControlId:this.getNewControlId(),newLabel:this.getLabel()};return s;};return C;},true);
