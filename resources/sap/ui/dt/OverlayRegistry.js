/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/base/ManagedObject","sap/ui/dt/ElementUtil"],function(M,E){"use strict";var O={};var o={};O.getOverlay=function(e){var a=E.getElementInstance(e);if(a){var i=a.getId();return o[i];}};O.register=function(e,a){var i=g(e);o[i]=a;};O.deregister=function(e){var i=g(e);delete o[i];};O.hasOverlays=function(){return!jQuery.isEmptyObject(o);};function g(e){return(e instanceof M)?e.getId():e;}return O;},true);
