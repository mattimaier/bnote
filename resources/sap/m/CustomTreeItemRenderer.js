/*
 * ! UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./TreeItemBaseRenderer','sap/ui/core/Renderer'],function(q,T,R){"use strict";var C=R.extend(T);C.renderLIAttributes=function(r,l){r.addClass("sapMCTI");T.renderLIAttributes.apply(this,arguments);};C.renderLIContent=function(r,l){l.getContent().forEach(function(c){r.renderControl(c);});};return C;},true);
