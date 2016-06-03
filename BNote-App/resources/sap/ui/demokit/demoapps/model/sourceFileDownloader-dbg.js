/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

/*global Promise*/
sap.ui.define([], function () {
	"use strict";

	var codeCache = {};
	return function (sUrl) {
		return new Promise(function (fnResolve) {
			var fnSuccess = function (result) {
				codeCache[sUrl] = result;
				fnResolve(result);
			};
			var fnError = function () {
				fnResolve({ errorMessage: "not found: '" + sUrl + "'" });
			};

			if (!(sUrl in codeCache)) {
				jQuery.ajax(sUrl, {
					dataType: "text",
					success: fnSuccess,
					error: fnError
				});
			} else {
				fnResolve(codeCache[sUrl]);
			}
		});
	};
});
