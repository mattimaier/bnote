/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides the Design Time Metadata for the sap.m.Panel control
sap.ui.define([],
	function() {
	"use strict";

	return {
		aggregations: {
			headerToolbar: {
				domRef: ":sap-domref > .sapMPanelHdr, :sap-domref > .sapUiDtEmptyHeader"
			},
			infoToolbar: {
				domRef: ":sap-domref > .sapUiDtEmptyInfoToolbar"
			},
			content: {
				domRef: ".sapMPanelContent",
				show: function () {
					this.setExpanded(true);
				}
			}
		}
	};

}, /* bExport= */ false);
