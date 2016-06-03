/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/mvc/JSView"],function(J){"use strict";sap.ui.jsview("sap.ui.demokit.explored.view.app",{getControllerName:function(){return"sap.ui.demokit.explored.view.app";},createContent:function(c){this.setDisplayBlock(true);return new sap.m.SplitApp("splitApp",{afterDetailNavigate:function(){this.hideMaster();}});}});});
