/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides the Design Time Metadata for the sap.uxap.ObjectPageLayout control
sap.ui.define([],
	function() {
	"use strict";

	return {
		aggregations : {
			sections : {
				domRef : ":sap-domref > .sapUxAPObjectPageWrapper"
			}
		},

		cloneDomRef : ":sap-domref > header"
	};

}, /* bExport= */ false);