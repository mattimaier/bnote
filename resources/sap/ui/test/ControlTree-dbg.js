/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides a control tree to be used in the Eclipse preview editor
sap.ui.define(['jquery.sap.global', 'sap/ui/core/Element', 'jquery.sap.strings'],
	function(jQuery, Element/* , jQuerySap */) {
	"use strict";



	/*global addProperty, callback, restoreLockState, restoreTreeCallback,supplySelectedTheme, *///declare unusual global vars for JSLint/SAPUI5 validation

	/**
	 * Constructs the class <code>sap.ui.test.ControlTree</code> and registers
	 * to the <code>sap.ui.core.Core</code> for UI change events.
	 *
	 * @class Control Tree used for the Test Environment
	 * @author SAPUI5 Designtime
	 * @version 1.38.7
	 *
	 * @param {sap.ui.core.Core}
	 *            oCore the core instance to use for analysis
	 * @param {Window}
	 *            oWindow reference to the window object
	 *
	 * @constructor
	 * @private
	 * @name sap.ui.test.ControlTree
	 */
	var ControlTree = function(oCore, oWindow) {
		this.oWindow = oWindow;
		this.oCore = oCore;
		this.oCore.attachUIUpdated(this.renderDelayed, this);
		this.renderDelayed(); // additionally necessary due to first UI update
	};

	/**
	 * TODO: missing internal JSDoc... @author please update
	 * @private
	 * @name sap.ui.test.ControlTree#renderDelayed
	 * @function
	 */
	ControlTree.prototype.renderDelayed = function() {
		if (this.oTimer) {
			this.oWindow.jQuery.sap.clearDelayedCall(this.oTimer);
		}
		this.oTimer = this.oWindow.jQuery.sap.delayedCall(500,this,"initDT");
		//Provide a callback when the UI is updated
		restoreLockState(this);
		supplySelectedTheme(this.oCore.getConfiguration().getTheme());
	};

	/**
	 * TODO: missing internal JSDoc... @author please update
	 * @private
	 * @name sap.ui.test.ControlTree#initDT
	 * @function
	 */
	ControlTree.prototype.initDT = function() {
		//Make a Callback to reset the Outline Tree
		restoreTreeCallback();

		var oUIArea = null,
			oUIAreas = this.oCore.mUIAreas;
			//alert("initcontrol tree");
		for (var i in oUIAreas) {
			var oUIArea = oUIAreas[i];

			var aRootControls = oUIArea.getContent();
			for (var i = 0, l = aRootControls.length; i < l; i++) {
				this.renderNodeDT(aRootControls[i],0);
			}
		}
	};

	/**
	 * TODO: missing internal JSDoc... @author please update
	 * @private
	 * @name sap.ui.test.ControlTree#createTreeNodeDT
	 * @function
	 */
	ControlTree.prototype.createTreeNodeDT = function(sId,iLevel,sType) {
		callback(sId,iLevel,sType);
	};

	ControlTree.prototype.createAssocTreeNodeDT = function(sId,iLevel,sType,srcCntrlId,trgtCntrlId) {
		callback(sId,iLevel,sType,srcCntrlId,trgtCntrlId);
	};

	/**
	 * TODO: missing internal JSDoc... @author please update
	 * @private
	 * @name sap.ui.test.ControlTree#renderNodeDT
	 * @function
	 */
	ControlTree.prototype.renderNodeDT = function(oControl,iLevel) {

		if (!oControl) {
			return;
		}

		var oMetadata = oControl.getMetadata();

		var mProperties = oMetadata.getAllProperties();
		for (var sPropertyName in mProperties) {
			var oMethod =  oControl["get" + sPropertyName];
			var sName = sPropertyName;
			if (!oMethod) {
				sName = jQuery.sap.charToUpperCase(sName,0);
			}
			var oValue = oControl["get" + sName]();
			addProperty(oControl.getId(), sPropertyName, mProperties[sPropertyName].type, oValue != null ? oValue : "");
		}

		var mAggregations = oMetadata.getAllAggregations();
		for (var n in mAggregations) {
			// Ensure to analyze the actual element/control instance, not just its metadata!
			var oAggregation = oControl.getAggregation(mAggregations[n].name);
			if (oAggregation && oAggregation.length) {
				for (var i = 0;i < oAggregation.length;i++) {
					var o = oAggregation[i];
					if (o instanceof Element) {
						this.renderNodeDT(oAggregation[i],iLevel + 1);
					}
				}
			} else if (oAggregation instanceof Element) {
				this.renderNodeDT(oAggregation,iLevel + 1);
			}
		}


		//Get all the associations
		var mAssociations = oMetadata.getAllAssociations();
		for (var m in mAssociations) {
			var oAssociation = oControl.getAssociation(mAssociations[m].name);//Returns the association Name

			if (oAssociation != null) {
				//Construct the Association Name
				var assocId = mAssociations[m].name + oAssociation;
				this.createAssocTreeNodeDT(assocId,iLevel + 2,"Association",oControl.getId(),oAssociation);
				//Add the properties of the association here
				addProperty(assocId,mAssociations[m].name,"assoc_type",oAssociation);
				addProperty(assocId, "Name", "string", mAssociations[m].name);

			}
		}
	};

	return ControlTree;

}, /* bExport= */ true);
