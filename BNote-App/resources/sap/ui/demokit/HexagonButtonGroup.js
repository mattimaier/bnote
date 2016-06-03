/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Control','./library'],function(q,C,l){"use strict";var H=C.extend("sap.ui.demokit.HexagonButtonGroup",{metadata:{library:"sap.ui.demokit",properties:{colspan:{type:"int",group:"Misc",defaultValue:3}},aggregations:{buttons:{type:"sap.ui.demokit.HexagonButton",multiple:true,singularName:"button"}}}});return H;},true);
