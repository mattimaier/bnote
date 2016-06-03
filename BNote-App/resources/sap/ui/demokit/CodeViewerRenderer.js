/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";var C=function(){};C.render=function(r,c){if(!c.getVisible()){return;}r.write("<pre");r.writeControlData(c);if(c.getEditable()){r.addClass("sapUiCodeViewer");r.addClass("editable");r.writeAttribute("contentEditable","true");}else{r.addClass("prettyprint");}if(c.getLineNumbering()){r.addClass("linenums");}var h=c.getHeight();if(h){r.addStyle("height",h);}var w=c.getWidth();if(w){r.addStyle("width",w);}r.writeClasses();r.writeStyles();r.write(">");if(c.getSource()){r.write(q.sap.encodeHTML(c.getSource()));}r.write("</pre>");};return C;},true);
