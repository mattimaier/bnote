/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["jquery.sap.global","sap/ui/rta/Utils"],function(q,R){"use strict";var _;var m;return{load:function(r){var o=sap.ui.getCore().byId(r);if(!_){_=new Promise(function(a){sap.ui.require(["sap/ui/rta/appVariant/ManageAppsDialog"],function(M){return a(M);});});}return _.then(function(M){if(!m){m=new M({rootControl:o,close:function(){this.destroy();m=null;}});}return m.open();});},hasAppVariantsSupport:function(l,i){if(i&&R.getUshellContainer()&&l==="CUSTOMER"){var u=q.sap.getUriParameters();var U=u.mParams["sap-ui-xx-rta-save-as"];if(U&&U.length>0){return U[0]==='true'?true:false;}}return false;}};});
