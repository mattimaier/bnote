/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";return{sortByName:function(a,b){if(!a||!a.name){return-1;}else if(!b||!b.name){return 1;}else{var n=a.name.toLowerCase();var N=b.name.toLowerCase();if(n<N){return-1;}else{return(n>N)?1:0;}}}};});
