/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/base/ManagedObject','sap/ui/qunit/QUnitUtils','sap/ui/test/Opa5'],function(M,Q,O){"use strict";return M.extend("sap.ui.test.actions.Action",{metadata:{publicMethods:["executeOn"]},executeOn:function(){return true;},_getUtils:function(){return O.getUtils()||Q;},_sLogPrefix:"Opa5 actions"});},true);
