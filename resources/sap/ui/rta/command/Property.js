/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/rta/command/FlexCommand',"sap/ui/rta/Utils"],function(F,U){"use strict";var P=F.extend("sap.ui.rta.command.Property",{metadata:{library:"sap.ui.rta",properties:{propertyName:{type:"string"},newValue:{type:"any"},semanticMeaning:{type:"string"},changeType:{type:"string",defaultValue:"propertyChange"}},associations:{},events:{}}});P.prototype._getChangeSpecificData=function(){var e=this.getElement();return{changeType:this.getChangeType(),selector:{id:e.getId(),type:e.getMetadata().getName()},content:{property:this.getPropertyName(),newValue:this.getNewValue(),semantic:this.getSemanticMeaning()}};};return P;},true);
