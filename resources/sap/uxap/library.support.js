/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Adds support rules of the sap.uxap library to the support infrastructure.
 */
sap.ui.define([
	"jquery.sap.global",
	"sap/ui/support/library",
	"sap/ui/support/supportRules/RuleSet",
	"./ObjectPageLayout.support"],

	function(jQuery,
			 SupportLib,
			 Ruleset,
			 ObjectPageLayoutSupport) {

	"use strict";

	var oLib = {
		name: "sap.uxap",
		niceName: "ObjectPage library"
	};

	var oRuleset = new Ruleset(oLib);
		ObjectPageLayoutSupport.addRulesToRuleset(oRuleset);

	return {lib: oLib, ruleset: oRuleset};

}, true);
