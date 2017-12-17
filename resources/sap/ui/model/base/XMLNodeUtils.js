/*
 * ! UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/base/DataType','sap/ui/base/ManagedObject'],function(q,D,M){"use strict";return{parseScalarType:function(t,v,n,c){var b=M.bindingParser(v,c,true);if(b&&typeof b==="object"){return b;}var V=v=b||v;var T=D.getType(t);if(T){if(T instanceof D&&!T.isValid(V)){V=T.parseValue(v);}}else{throw new Error("Property "+n+" has unknown type "+t);}return typeof V==="string"?M.bindingParser.escape(V):V;},localName:function(x){return x.localName||x.baseName||x.nodeName;},findControlClass:function(n,l){var c;var L=sap.ui.getCore().getLoadedLibraries();q.each(L,function(s,o){if(n===o.namespace||n===o.name){c=o.name+"."+((o.tagNames&&o.tagNames[l])||l);}});c=c||n+"."+l;q.sap.require(c);var C=q.sap.getObject(c);if(C){return C;}else{q.sap.log.error("Can't find object class '"+c+"' for XML-view","","XMLTemplateProcessor.js");}}};});
