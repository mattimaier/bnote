/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides a simple search feature
sap.ui.define(['jquery.sap.global'],
	function(jQuery) {
	"use strict";


	var ObjectSearch = {

		getEntityPath : function (oData, sId) {
			if (!oData.entities) {
				return null;
			}
			var oResult = null;
			jQuery.each(oData.entities, function (i, oEnt) {
				if (oEnt.id === sId) {
					oResult = "/entities/" + i + "/";
					return false;
				}
			});
			return oResult;
		}
	};

	return ObjectSearch;

}, /* bExport= */ true);
