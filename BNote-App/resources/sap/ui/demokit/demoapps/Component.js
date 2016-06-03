/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/core/UIComponent','sap/ui/demokit/demoapps/model/libraryData',"sap/ui/model/json/JSONModel"],function(U,l,J){"use strict";return U.extend("sap.ui.demokit.demoapps.Component",{metadata:{rootView:"sap.ui.demokit.demoapps.view.Root",includes:["css/style.css"]},init:function(){U.prototype.init.apply(this,arguments);var m=new J();this.setModel(m);l.fillJSONModel(m);}});});
