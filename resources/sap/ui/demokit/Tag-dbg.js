/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

// Provides control sap.ui.demokit.Tag.
sap.ui.define(['jquery.sap.global', 'sap/ui/core/Element', './library'],
	function(jQuery, Element, library) {
	"use strict";



	/**
	 * Constructor for a new Tag.
	 *
	 * @param {string} [sId] id for the new control, generated automatically if no id is given
	 * @param {object} [mSettings] initial settings for the new control
	 *
	 * @class
	 * A Tag in a TagCloud
	 * @extends sap.ui.core.Element
	 * @version 1.36.11
	 *
	 * @constructor
	 * @public
	 * @name sap.ui.demokit.Tag
	 * @ui5-metamodel This control/element also will be described in the UI5 (legacy) designtime metamodel
	 */
	var Tag = Element.extend("sap.ui.demokit.Tag", /** @lends sap.ui.demokit.Tag.prototype */ { metadata : {

		library : "sap.ui.demokit",
		properties : {

			/**
			 * The text to be disaplyed for this tag.
			 */
			text : {type : "string", group : "Misc", defaultValue : null},

			/**
			 * The weight for this tag. Can be any integer value.
			 */
			weight : {type : "int", group : "Misc", defaultValue : 1}
		}
	}});

	Tag.prototype.onclick = function(oEvent){
		//Inform the parent about the onclick event
		this.oParent.firePressEvent(this);
	};


	return Tag;

}, /* bExport= */ true);
