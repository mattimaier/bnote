/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["jquery.sap.global","sap/ui/test/_LogCollector","sap/ui/test/_opaCorePlugin","sap/ui/test/autowaiter/_XHRCounter","sap/ui/test/autowaiter/_timeoutCounter"],function($,_,a,b,c){"use strict";var l=$.sap.log.getLogger("sap.ui.test.autowaiter._autoWaiter",_.DEFAULT_LEVEL_FOR_OPA_LOGGERS);function h(){var C="sap.m.NavContainer";var n=$.sap.getObject(C);if(sap.ui.lazyRequire._isStub(C)||!n){return false;}return a.getAllControls(n).some(function(N){if(N._bNavigating){l.debug("The NavContainer "+N+" is currently navigating");}return N._bNavigating;});}function d(){var u=a.isUIDirty();if(u){l.debug("The UI needs rerendering");}return u;}return{hasToWait:function(){return h()||b.hasPendingRequests()||d()||c.hasPendingTimeouts();}};},true);
