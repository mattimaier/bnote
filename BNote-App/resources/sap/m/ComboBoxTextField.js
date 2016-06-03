/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./InputBase','./library'],function(q,I,l){"use strict";var C=I.extend("sap.m.ComboBoxTextField",{metadata:{library:"sap.m",properties:{maxWidth:{type:"sap.ui.core.CSSSize",group:"Dimension",defaultValue:"100%"}}}});C.prototype.updateValueStateClasses=function(v,o){I.prototype.updateValueStateClasses.apply(this,arguments);var V=sap.ui.core.ValueState,a=this.getRenderer().CSS_CLASS_COMBOBOXTEXTFIELD,d=this.$();if(o!==V.None){d.removeClass(a+"State "+a+o);}if(v!==V.None){d.addClass(a+"State "+a+v);}};return C;},true);
