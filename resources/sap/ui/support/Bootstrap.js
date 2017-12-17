/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["jquery.sap.global","./library","sap/ui/support/supportRules/Main"],function(q,l,M){"use strict";var B={initSupportRules:function(s){if(s[0].toLowerCase()==="true"||s[0].toLowerCase()==="silent"){M.startPlugin(s);if('logSupportInfo'in q.sap.log){q.sap.log.logSupportInfo(true);}}}};return B;});
