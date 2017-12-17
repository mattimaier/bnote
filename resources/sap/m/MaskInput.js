/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./InputBase','./MaskEnabler'],function(q,I,M){"use strict";var a=I.extend("sap.m.MaskInput",{metadata:{library:"sap.m",properties:{placeholderSymbol:{type:"string",group:"Misc",defaultValue:"_"},mask:{type:"string",group:"Misc",defaultValue:null}},aggregations:{rules:{type:"sap.m.MaskInputRule",multiple:true,singularName:"rule"}}}});M.call(a.prototype);return a;},true);
