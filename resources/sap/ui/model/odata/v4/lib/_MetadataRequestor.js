/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["jquery.sap.global","./_Helper","./_MetadataConverter"],function(q,_,a){"use strict";return{create:function(h,Q){var s=_.buildQuery(Q);return{read:function(u){return new Promise(function(r,R){q.ajax(u+s,{method:"GET",headers:h}).then(function(d){r(d);},function(j,t,e){R(_.createError(j));});}).then(function(x){return a.convertXMLMetadata(x);});}};}};},false);
