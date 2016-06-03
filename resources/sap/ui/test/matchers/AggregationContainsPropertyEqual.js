/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Matcher'],function(q,M){"use strict";return M.extend("sap.ui.test.matchers.AggregationContainsPropertyEqual",{metadata:{publicMethods:["isMatching"],properties:{aggregationName:{type:"string"},propertyName:{type:"string"},propertyValue:{type:"any"}}},isMatching:function(c){var a,A=this.getAggregationName(),p=this.getPropertyName(),P=this.getPropertyValue(),f=c["get"+q.sap.charToUpperCase(A,0)];if(!f){q.sap.log.error("Control "+c+" does not have an aggregation called: "+A,this._sLogPrefix);return false;}a=f.call(c);var m=a.some(function(v){var b=v["get"+q.sap.charToUpperCase(p,0)];if(!b){return false;}return b.call(v)===P;});if(!m){q.sap.log.debug("Control "+c+" has no Control with the value "+this.getPropertyValue()+" in the aggregation "+this.getAggregationName(),this._sLogPrefix);}return m;}});},true);
