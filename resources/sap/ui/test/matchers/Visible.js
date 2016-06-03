/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Matcher'],function($,M){"use strict";return M.extend("sap.ui.test.matchers.Visible",{isMatching:function(c){if(!c.getDomRef()){$.sap.log.debug("The control "+c+" is not rendered",this._sLogPrefix);return false;}var v=c.$().is(":visible");if(!v){$.sap.log.debug("The control "+c+" is not visible",this._sLogPrefix);}return v;}});},true);
