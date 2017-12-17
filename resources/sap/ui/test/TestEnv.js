/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/debug/Highlighter','./ControlTree'],function(q,H,C){"use strict";var T=function(){};T.prototype.startPlugin=function(c){this.oCoreOther=c;this.oCore=c;this.oCore.attachControlEvent(this.onControlEvent,this);this.oWindow=window;this.oControlTree=new C(this.oCore,window);};T.prototype.stopPlugin=function(){this.oCore.detachControlEvent(this.onControlEvent,this);this.oCore=null;};T.prototype.onControlEvent=function(e){if(this.oCore.isLocked()){var b=e.getParameter("browserEvent");if(b.type=="click"){var E=b.srcControl;if(E){var s=new H('sap-ui-testsuite-SelectionHighlighter');s.highlight(E.getDomRef());if(selectControl){selectControl(E.getId());}}}}};(function(){var t=new T();sap.ui.getCore().registerPlugin(t);}());return T;},true);
