/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Action'],function($,A){"use strict";var P=A.extend("sap.ui.test.actions.Press",{metadata:{publicMethods:["executeOn"]},executeOn:function(c){var f,a=P._controlAdapters[c.getMetadata().getName()];if(a){f=c.$(a);}else{f=$(c.getFocusDomRef());}if(f.length){f.focus();$.sap.log.debug("Pressed the control "+c,this._sLogPrefix);this._triggerEvent("mousedown",f);this._getUtils().triggerEvent("selectstart",f);this._triggerEvent("mouseup",f);this._triggerEvent("click",f);}else{$.sap.log.error("Control "+c+" has no dom representation",this._sLogPrefix);}},_triggerEvent:function(n,f){var F=f[0],x=f.offset().x,y=f.offset().y;var m={identifier:1,pageX:x,pageY:y,clientX:x,clientY:y,screenX:x,screenY:y,target:f[0],radiusX:1,radiusY:1,rotationAngle:0,button:0,type:n};this._getUtils().triggerEvent(n,F,m);}});P._controlAdapters={"sap.m.SearchField":"search"};return P;},true);
