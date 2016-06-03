/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global'],function(q){"use strict";var O={getEntityPath:function(d,I){if(!d.entities){return null;}var r=null;q.each(d.entities,function(i,e){if(e.id===I){r="/entities/"+i+"/";return false;}});return r;}};return O;},true);
