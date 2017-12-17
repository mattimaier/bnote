/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides a bridge between the SAPUI5 runtime and the SAPUI5 Eclipse Tooling.
sap.ui.define(['jquery.sap.global', 'sap/ui/debug/Highlighter', './ControlTree'],
	function(jQuery, Highlighter, ControlTree) {
	"use strict";


	/*global selectControl *///declare unusual global vars for JSLint/SAPUI5 validation

	/**
	 * Creates an instance of the class <code>sap.ui.debug.TestEnv</code>
	 *
	 * @class Central Class for the Test Environment
	 *
	 * @author SAPUI5 Designtime
	 * @version 1.50.7
	 * @constructor
	 * @private
	 * @name sap.ui.test.TestEnv
	 */
	var TestEnv = function() {
	};

	/**
	 * Will be invoked by <code>sap.ui.core.Core</code> to notify the plugin to start
	 * @param {sap.ui.core.Core} oCore reference to the Core
	 * @public
	 * @name sap.ui.test.TestEnv#startPlugin
	 * @function
	 */
	TestEnv.prototype.startPlugin = function(oCore) {
		this.oCoreOther = oCore;
		this.oCore = oCore;
		this.oCore.attachControlEvent(this.onControlEvent, this);
		this.oWindow = window;
		this.oControlTree = new ControlTree(this.oCore, window);
	};

	/**
	 * Will be invoked by <code>sap.ui.core.Core</code> to notify the plugin to start
	 * @param {sap.ui.core.Core} oCore reference to the Core
	 * @public
	 * @name sap.ui.test.TestEnv#stopPlugin
	 * @function
	 */
	TestEnv.prototype.stopPlugin = function() {
		this.oCore.detachControlEvent(this.onControlEvent, this);
		this.oCore = null;
	};


	/**
	 * Callback method for listening to any event
	 * @param {sap.ui.base.Event} oEvent event object
	 * @private
	 * @name sap.ui.test.TestEnv#onControlEvent
	 * @function
	 */
	TestEnv.prototype.onControlEvent = function(oEvent) {

		// logging for testing!
	//	jQuery.sap.log.info(oEvent.getParameter("browserEvent").getName() + " - " + this.oCore.isLocked());

		// special handling only if the Core is locked
		if (this.oCore.isLocked()) {

			// get the ref to the browser event
			var oBrowserEvent = oEvent.getParameter("browserEvent");

			// only act for click events
			if (oBrowserEvent.type == "click") {

				// determine the clicked element
				var oElement = oBrowserEvent.srcControl;
				if (oElement) {

	//				// show the dimension rect and select the control
					var oSelectionHighlighter = new Highlighter('sap-ui-testsuite-SelectionHighlighter');
					oSelectionHighlighter.highlight(oElement.getDomRef());

					// (TODO: function selectControl needs to be implemented by DesignTime!)
					if (selectControl) {
						selectControl(oElement.getId());
					}

				}

			}

		}

	};

	/**
	 * Create the <code>sap.ui.test.TestEnv</code> plugin and register
	 * it within the <code>sap.ui.core.Core</code>.
	 */
	(function(){
		var oThis = new TestEnv();
		sap.ui.getCore().registerPlugin(oThis);
	}());

	return TestEnv;

}, /* bExport= */ true);
