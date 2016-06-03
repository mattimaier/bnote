/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/base/Object','./PipelineFactory'],function($,U,P){"use strict";var p=new P({name:"Matcher",functionName:"isMatching"});function d(m,v){var o=v;var i=m.every(function(M){var a=M.isMatching(v);if(a){if(a!==true){v=a;}return true;}return false;});if(i){return(o===v)?true:v;}return false;}return U.extend("sap.ui.test.pipelines.MatcherPipeline",{process:function(o){var r,c,C=o.control;var m=p.create(o.matchers);var e;if(!m||!m.length){return C;}if(!$.isArray(C)){e=1;c=[C];}else{c=C;}var M=[];c.forEach(function(a){var v=d(m,a);if(v){if(v===true){M.push(a);}else{M.push(v);}}},this);if(!M.length){$.sap.log.debug("all results were filtered out by the matchers - skipping the check",this);return false;}if(e===1){r=M[0];}else{r=M;}return r;}});});
