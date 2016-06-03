/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Matcher'],function(q,M){"use strict";return M.extend("sap.ui.test.matchers.AggregationLengthEquals",{metadata:{publicMethods:["isMatching"],properties:{name:{type:"string"},length:{type:"int"}}},isMatching:function(c){var a=this.getName(),A=c["get"+q.sap.charToUpperCase(a,0)];if(!A){q.sap.log.error("Control "+c+" does not have an aggregation called: "+a,this._sLogPrefix);return false;}var i=A.call(c).length===this.getLength();q.sap.log.debug("Control "+c+" has an aggregation '"+a+"' and its length "+A.call(c).length+(i?" matches.":" does not match."),this._sLogPrefix);return i;}});},true);
