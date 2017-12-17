/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Renderer'],function(q,R){'use strict';var I={};I.render=function(r,c){var i,a,b=c.getVisibleItems(),d=b.length,e=c._iconTabHeader,f=true;if(e){e._checkTextOnly(b);f=e._bTextOnly;c._bIconOnly=c.checkIconOnly(b);}r.write('<ul');r.writeAttribute('role','listbox');r.writeControlData(c);r.addClass('sapMITBSelectList');if(f){r.addClass('sapMITBSelectListTextOnly');}r.writeClasses();r.write('>');for(i=0;i<d;i++){a=b[i];a.renderInSelectList(r,c,i,d);}r.write('</ul>');};return I;},true);
