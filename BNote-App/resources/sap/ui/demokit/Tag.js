/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Element','./library'],function(q,E,l){"use strict";var T=E.extend("sap.ui.demokit.Tag",{metadata:{library:"sap.ui.demokit",properties:{text:{type:"string",group:"Misc",defaultValue:null},weight:{type:"int",group:"Misc",defaultValue:1}}}});T.prototype.onclick=function(e){this.oParent.firePressEvent(this);};return T;},true);
