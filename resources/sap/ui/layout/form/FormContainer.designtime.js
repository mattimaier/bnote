/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";function _(f){return f.getFormElements().every(function(F){return F.getVisible()===false;});}return{actions:{remove:{changeType:"hideControl"},rename:{changeType:"renameGroup",domRef:function(f){return jQuery(f.getRenderedDomRef()).find(".sapUiFormTitle")[0];},isEnabled:function(f){return!(f.getToolbar()||!f.getTitle());}}},aggregations:{formElements:{domRef:function(f){var d=f.getDomRef();var h=f.getTitle()||f.getToolbar();if(!d&&(f.getFormElements().length===0||_(f))&&h instanceof sap.ui.core.Element){return h.getDomRef();}else if(typeof h==="string"){return jQuery(d).find(".sapUiFormTitle").get(0);}else{return d;}},actions:{move:"moveControls"}},toolbar:{domRef:function(f){var t=f.getToolbar();if(t){return t.getDomRef();}}}},name:{singular:"GROUP_CONTROL_NAME",plural:"GROUP_CONTROL_NAME_PLURAL"}};},false);
