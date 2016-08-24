/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/base/ManagedObject','sap/ui/qunit/QUnitUtils','sap/ui/test/Opa5'],function($,M,Q,O){"use strict";return M.extend("sap.ui.test.actions.Action",{metadata:{properties:{idSuffix:{type:"string"}},publicMethods:["executeOn"]},executeOn:function(){return true;},$:function(c){var f,a=this._getAdapter(c.getMetadata()),A=this.getIdSuffix()||a;if(A){f=c.$(A);}else{f=$(c.getFocusDomRef());}if(!f.length){$.sap.log.error("Control "+c+" has no dom representation idSuffix was "+A,this._sLogPrefix);}else{$.sap.log.info("Found a domref for the Control "+c+" the action is going to be executed on the dom id"+f[0].id,this._sLogPrefix);}return f;},getUtils:function(){return O.getUtils()||Q;},init:function(){this.controlAdapters={};},_getAdapter:function(m){var a=this.controlAdapters[m.getName()];if(a){return a;}var p=m.getParent();if(p){return this._getAdapter(p);}return null;},_sLogPrefix:"Opa5 actions"});},true);
