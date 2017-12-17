/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/library"],function(l){"use strict";sap.ui.getCore().initLibrary({name:"sap.ui.support",dependencies:["sap.ui.core","sap.ui.fl","sap.m","sap.ui.layout"],types:["sap.ui.support.Severity"],interfaces:[],controls:[],elements:[],noLibraryCSS:false,version:"1.50.7"});sap.ui.support.Severity={Medium:"Medium",High:"High",Low:"Low"};sap.ui.support.Audiences={Control:"Control",Internal:"Internal",Application:"Application"};sap.ui.support.Categories={Accessibility:"Accessibility",Performance:"Performance",Memory:"Memory",Bindings:"Bindings",Consistency:"Consistency",Functionality:"Functionality",Usability:"Usability",DataModel:"DataModel",Usage:"Usage",Other:"Other"};return sap.ui.support;});
