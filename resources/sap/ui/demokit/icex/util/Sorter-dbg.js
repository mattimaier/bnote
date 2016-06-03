/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

sap.ui.define([], function() {
	"use strict";

	return {
		sortByName : function(a, b) {
			if (!a || !a.name) {
				return -1;
			} else if (!b || !b.name) {
				return 1;
			} else {
				var aName = a.name.toLowerCase();
				var bName = b.name.toLowerCase();

				if (aName < bName) {
					return -1;
				} else {
					return (aName > bName) ? 1 : 0;
				}
			}
		}
	};
});
