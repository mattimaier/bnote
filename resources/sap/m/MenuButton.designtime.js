/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";return{aggregations:{menu:{ignore:true}},actions:{split:{changeType:"splitMenuButton",changeOnRelevantContainer:true,getControlsCount:function(m){return m.getMenu().getItems().length;}},rename:{changeType:"rename",domRef:function(c){return c.$().find('.sapMBtn > .sapMBtnInner > .sapMBtnContent')[0];}}}};},false);
