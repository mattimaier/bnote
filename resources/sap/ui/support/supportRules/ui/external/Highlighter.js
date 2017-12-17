/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var _=null;function a(){_.style.display="none";}function b(){_.style.display="block";}function c(){var h=document.createElement("div");h.style.cssText="box-sizing: border-box;border:1px solid blue;background: rgba(20, 20, 200, 0.4);position: absolute";var d=document.createElement("div");d.id="ui5-highlighter";d.style.cssText="position: fixed;top:0;right:0;bottom:0;left:0;z-index: 1000;overflow: hidden;";d.appendChild(h);document.body.appendChild(d);_=document.getElementById("ui5-highlighter");_.onmouseover=a;}return{highlight:function(e){var h;var t;var d;if(_===null&&!document.getElementById("ui5-highlighter")){c();}else{b();}h=_.firstElementChild;t=document.getElementById(e);if(t){d=t.getBoundingClientRect();h.style.top=d.top+"px";h.style.left=d.left+"px";h.style.height=d.height+"px";h.style.width=d.width+"px";}return this;},hideHighLighter:a};});
