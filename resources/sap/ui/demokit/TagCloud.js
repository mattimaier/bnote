/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Control','./library'],function(q,C,l){"use strict";var T=C.extend("sap.ui.demokit.TagCloud",{metadata:{library:"sap.ui.demokit",properties:{maxFontSize:{type:"int",group:"Misc",defaultValue:30},minFontSize:{type:"int",group:"Misc",defaultValue:10}},defaultAggregation:"tags",aggregations:{tags:{type:"sap.ui.demokit.Tag",multiple:true,singularName:"tag"}},events:{press:{parameters:{tagId:{type:"string"}}}}}});T.prototype.firePressEvent=function(t){this.firePress({tagId:t.getId()});};return T;},true);
