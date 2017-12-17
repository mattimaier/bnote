/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["jquery.sap.global","./_Helper","./_V2MetadataConverter","./_V4MetadataConverter"],function(q,_,a,b){"use strict";return{create:function(h,o,Q){var s=_.buildQuery(Q);return{read:function(u,A){return new Promise(function(r,R){q.ajax(A?u:u+s,{method:"GET",headers:h}).then(function(d,t,j){var c=o==="4.0"||A?b:a,J=c.convertXMLMetadata(d,u),l=j.getResponseHeader("Last-Modified")||j.getResponseHeader("Date");if(l){J.$LastModified=l;}r(J);},function(j,t,e){var E=_.createError(j);q.sap.log.error("GET "+u,E.message,"sap.ui.model.odata.v4.lib._MetadataRequestor");R(E);});});}};}};},false);
