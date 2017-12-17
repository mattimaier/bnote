/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var c=null;function C(o){c=o;return{getMetadata:function(){return c.getMetadata();},getUIAreas:function(){return c.mUIAreas;},getComponents:function(){return c.mObjects.component;},getModels:function(){return c.oModels;}};}return C;},true);
