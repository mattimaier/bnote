/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Matcher'],function(q,M){"use strict";return M.extend("sap.ui.test.matchers.BindingPath",{metadata:{publicMethods:["isMatching"],properties:{path:{type:"string"},modelName:{type:"string"}}},isMatching:function(c){var b;if(!this.getPath()){q.sap.log.error(this,"matchers.BindingPath: the path needs to be a not empty string",this._sLogPrefix);return false;}if(this.getModelName()){b=c.getBindingContext(this.getModelName());}else{b=c.getBindingContext();}if(!b){q.sap.log.debug("The control "+c+" has no binding context for the model "+this.getModelName(),this._sLogPrefix);return false;}var r=this.getPath()===b.getPath();if(!r){q.sap.log.debug("The control "+c+" does not "+"have a matching binding context expected "+this.getPath()+" but got "+b.getPath(),this._sLogPrefix);}return r;}});},true);
