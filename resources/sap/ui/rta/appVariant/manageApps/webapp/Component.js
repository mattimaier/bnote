/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/UIComponent"],function(U){"use strict";var _;return U.extend("sap.ui.rta.appVariant.manageApps.webapp.Component",{metadata:{"manifest":"json","library":"sap.ui.rta","version":"0.9","properties":{adaptedAppProperties:{type:"object"}}},constructor:function(){_=arguments[1].adaptedAppProperties;U.prototype.constructor.apply(this,arguments);},init:function(){this.setAdaptedAppProperties(_);U.prototype.init.apply(this,arguments);}});});
