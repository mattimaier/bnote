/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/layout/form/Form','sap/ui/layout/form/FormContainer','sap/ui/layout/form/ResponsiveGridLayout'],function(F,a,R){"use strict";return{domRef:function(f){var p=f.getParent();if(p instanceof a){p=p.getParent();if(p instanceof F){var l=p.getLayout();if(l instanceof R){var b=f.getFields();var L=f.getLabelControl();if(L){b.unshift(L);}return b.filter(function(e){return e.getDomRef&&e.getDomRef();}).map(function(e){var d=e.getDomRef();return d.parentNode;});}}}},actions:{remove:{changeType:"hideControl"},rename:{changeType:"renameField",domRef:function(c){return c.getLabelControl().getDomRef();}},reveal:{changeType:"unhideControl"}},name:{singular:"FIELD_CONTROL_NAME",plural:"FIELD_CONTROL_NAME_PLURAL"}};},false);
