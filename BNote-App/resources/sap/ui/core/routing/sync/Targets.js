/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";return{display:function(t,d){this._display(t,d);},_display:function(t,d){var a=this;if(jQuery.isArray(t)){jQuery.each(t,function(i,T){a._displaySingleTarget(T,d);});}else{this._displaySingleTarget(t,d);}return this;},_displaySingleTarget:function(n,d){var t=this.getTarget(n);if(t!==undefined){t.display(d);}else{jQuery.sap.log.error("The target with the name \""+n+"\" does not exist!",this);}}};});
