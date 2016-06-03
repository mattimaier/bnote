/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Matcher'],function(q,M){"use strict";return M.extend("sap.ui.test.matchers.AggregationFilled",{metadata:{publicMethods:["isMatching"],properties:{name:{type:"string"}}},isMatching:function(c){var a=this.getName(),A=c["get"+q.sap.charToUpperCase(a,0)];if(!A){q.sap.log.error("Control "+c.sId+" does not have an aggregation called: "+a,this._sLogPrefix);return false;}return!!A.call(c).length;}});},true);
