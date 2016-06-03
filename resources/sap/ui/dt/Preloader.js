/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/Element"],function(E){"use strict";var P={aLoadedClasses:[]};P.load=function(e){var t=this;var q=[];e.forEach(function(v){var o=v;if(typeof o==="string"){o=jQuery.sap.getObject(o);}if(o&&o.getMetadata){var m=o.getMetadata();var c=m.getName?m.getName():null;var i=c&&t.aLoadedClasses.indexOf(c)!==-1;if(!i&&m.loadDesignTime){t.aLoadedClasses.push(c);q.push(m.loadDesignTime());}}});return Promise.all(q);};P.loadLibraries=function(l){var e=[];l.forEach(function(L){var o=sap.ui.getCore().getLoadedLibraries()[L];if(o){e=e.concat(o.controls).concat(o.elements);}});return this.load(e);};P.loadAllLibraries=function(){return this.loadLibraries(sap.ui.getCore().getLoadedLibraries());};return P;},true);
