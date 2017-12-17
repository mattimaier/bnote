/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/rta/command/appDescriptor/AppDescriptorCommand','sap/ui/fl/descriptorRelated/api/DescriptorInlineChangeFactory'],function(A,D){"use strict";var a=A.extend("sap.ui.rta.command.appDescriptor.AddLibrary",{metadata:{library:"sap.ui.rta",properties:{requiredLibraries:{type:"object"},layer:{type:"string"}},events:{}}});a.prototype.prepare=function(f){this.setLayer(f.layer);return true;};a.prototype.execute=function(){var p=[];if(this.getRequiredLibraries()){var l=Object.keys(this.getRequiredLibraries());l.forEach(function(L){p.push(sap.ui.getCore().loadLibrary(L,true));});}return Promise.all(p);};a.prototype._create=function(){var p={};p.libraries=this.getRequiredLibraries();return D.create_ui5_addLibraries(p);};return a;},true);
