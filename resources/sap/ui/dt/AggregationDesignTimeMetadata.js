/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/dt/DesignTimeMetadata'],function(q,D){"use strict";var A=D.extend("sap.ui.dt.AggregationDesignTimeMetadata",{metadata:{library:"sap.ui.dt"}});A.prototype.getPropagation=function(e,c){var d=this.getData();if(!d.propagationInfos){return false;}d.propagationInfos.some(function(p){return c(p);});};A.prototype.getRelevantContainerForPropagation=function(e){var d=this.getData();var r=false;if(!d.propagationInfos){return false;}this.getPropagation(e,function(p){if(p.relevantContainerFunction&&p.relevantContainerFunction(e)){r=p.relevantContainerElement;return true;}});return r?r:false;};A.prototype.getMetadataForPropagation=function(e){var r=false;this.getPropagation(e,function(p){if(p.metadataFunction){r=p.metadataFunction(e,p.relevantContainerElement);return r?true:false;}});return r?r:false;};return A;},true);
