/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Control','./library'],function(q,C,l){"use strict";var H=C.extend("sap.ui.demokit.HexagonButton",{metadata:{library:"sap.ui.demokit",properties:{icon:{type:"string",group:"Misc",defaultValue:null},color:{type:"string",group:"Misc",defaultValue:'blue'},position:{type:"string",group:"Misc",defaultValue:null},enabled:{type:"boolean",group:"Misc",defaultValue:true},imagePosition:{type:"string",group:"Misc",defaultValue:null}},events:{press:{}}}});H.prototype.onclick=function(b){if(this.getEnabled()){this.firePress({id:this.getId()});}b.preventDefault();b.stopPropagation();};H.prototype._attachPress=H.prototype.attachPress;H.prototype.attachPress=function(){this._attachPress.apply(this,arguments);this.invalidate();};H.prototype._detachPress=H.prototype.detachPress;H.prototype.detachPress=function(){this._detachPress.apply(this,arguments);this.invalidate();};return H;},true);
