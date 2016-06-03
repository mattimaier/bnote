/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";return{display:function(t,d){var s=Promise.resolve();return this._display(t,d,s);},_display:function(t,d,s){var a=this,v=[];if(!jQuery.isArray(t)){t=[t];}return t.reduce(function(p,T){return a._displaySingleTarget(T,d,p).then(function(V){v.push(V);});},s).then(function(){return v;});},_displaySingleTarget:function(n,d,s){var t=this.getTarget(n);if(t!==undefined){return t._display(d,s);}else{var e="The target with the name \""+n+"\" does not exist!";jQuery.sap.log.error(e,this);return Promise.resolve({name:n,error:e});}}};});
