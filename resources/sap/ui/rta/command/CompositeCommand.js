/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/rta/command/BaseCommand','sap/ui/fl/Utils'],function(B,f){"use strict";var C=B.extend("sap.ui.rta.command.CompositeCommand",{metadata:{library:"sap.ui.rta",properties:{},aggregations:{commands:{type:"sap.ui.rta.command.BaseCommand",multiple:true}},events:{}}});C.prototype.execute=function(){var p=[];this._forEachCommand(function(c){p.push(c.execute.bind(c));});return f.execPromiseQueueSequentially(p);};C.prototype.undo=function(){var p=[];this._forEachCommandInReverseOrder(function(c){p.push(c.undo.bind(c));});return f.execPromiseQueueSequentially(p);};C.prototype._forEachCommand=function(d){var c=this.getCommands();c.forEach(d,this);};C.prototype._forEachCommandInReverseOrder=function(d){var c=this.getCommands();for(var i=c.length-1;i>=0;i--){d.call(this,c[i]);}};return C;},true);
