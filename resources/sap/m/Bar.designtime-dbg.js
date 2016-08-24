/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides the Design Time Metadata for the sap.m.Bar control
sap.ui.define([],
	function() {
	"use strict";

	return {
		aggregations: {
			contentLeft: {
				domRef: ":sap-domref > .sapMBarLeft"
			},
			contentMiddle: {
				domRef: ":sap-domref > .sapMBarMiddle > .sapMBarPH"
			},
			contentRight: {
				domRef: ":sap-domref > .sapMBarRight"
			}
		}
	};

}, /* bExport= */ false);
