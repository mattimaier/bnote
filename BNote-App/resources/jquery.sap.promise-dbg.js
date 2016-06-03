/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

/*global ES6Promise */

// the Promise behaves wrong in MS Edge - therefore we rely on the Promise
// polyfill for the MS Edge which works properly (copy from jQuery.sap.global)
// Related to MS Edge issue: https://connect.microsoft.com/IE/feedback/details/1658365
if (sap.ui.Device.browser.edge) {
	window.Promise = undefined;
}

if (!window.Promise) {
	jQuery.sap.require("sap.ui.thirdparty.es6-promise");
	ES6Promise.polyfill();
}
