/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

sap.ui.define(['jquery.sap.global', './Matcher'], function ($, Matcher) {
	"use strict";

	/**
	 * @class Visible - check if a controls domref is visible
	 * @private
	 * @extends sap.ui.test.matchers.Matcher
	 * @name sap.ui.test.matchers.Visible
	 * @author SAP SE
	 * @since 1.34
	 */
	return Matcher.extend("sap.ui.test.matchers.Visible", /** @lends sap.ui.test.matchers.Visible.prototype */ {
		isMatching:  function(oControl) {
			if (!oControl.getDomRef()) {
				$.sap.log.debug("The control " + oControl + " is not rendered", this._sLogPrefix);
				return false;
			}

			var bVisible = oControl.$().is(":visible");
			if (!bVisible) {
				$.sap.log.debug("The control " + oControl + " is not visible", this._sLogPrefix);
			}

			return bVisible;
		}
	});

}, /* bExport= */ true);
