/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";return function(p){return function(c){var i=true;q.each(p,function(P,o){var f=c["get"+q.sap.charToUpperCase(P,0)];if(!f){i=false;q.sap.log.error("Control "+c.sId+" does not have a property called: "+P,this._sLogPrefix);return false;}var C=f.call(c);if(o instanceof RegExp){i=o.test(C);}else{i=C===o;}if(!i){return false;}});return i;};};},true);
