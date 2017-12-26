/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/dt/plugin/ControlDragDrop','sap/ui/rta/plugin/RTAElementMover','sap/ui/rta/plugin/Plugin'],function(q,C,R,P){"use strict";var D=C.extend("sap.ui.rta.plugin.DragDrop",{metadata:{library:"sap.ui.rta",properties:{commandFactory:{type:"object",multiple:false}},events:{dragStarted:{},elementModified:{command:{type:"sap.ui.rta.command.BaseCommand"}}}}});D.prototype.init=function(){C.prototype.init.apply(this,arguments);this.setElementMover(new R({commandFactory:this.getCommandFactory()}));};D.prototype.setCommandFactory=function(c){this.setProperty("commandFactory",c);this.getElementMover().setCommandFactory(c);};D.prototype.registerElementOverlay=function(o){C.prototype.registerElementOverlay.apply(this,arguments);if(o.isMovable()){this._attachMovableBrowserEvents(o);P.prototype.addToPluginsList.apply(this,arguments);}};D.prototype.deregisterElementOverlay=function(o){C.prototype.deregisterElementOverlay.apply(this,arguments);P.prototype.removeFromPluginsList.apply(this,arguments);this._detachMovableBrowserEvents(o);};D.prototype._attachMovableBrowserEvents=function(o){o.attachBrowserEvent("mouseover",this._onMouseOver,this);o.attachBrowserEvent("mouseleave",this._onMouseLeave,this);};D.prototype._detachMovableBrowserEvents=function(o){o.detachBrowserEvent("mouseover",this._onMouseOver,this);o.detachBrowserEvent("mouseleave",this._onMouseLeave,this);};D.prototype.onDragStart=function(o){this.fireDragStarted();C.prototype.onDragStart.apply(this,arguments);this.getDesignTime().getSelection().forEach(function(o){o.setSelected(false);});o.$().addClass("sapUiRtaOverlayPlaceholder");};D.prototype.onDragEnd=function(o){this.fireElementModified({"command":this.getElementMover().buildMoveCommand()});o.$().removeClass("sapUiRtaOverlayPlaceholder");o.setSelected(true);o.focus();C.prototype.onDragEnd.apply(this,arguments);};D.prototype.onMovableChange=function(o){C.prototype.onMovableChange.apply(this,arguments);if(o.isMovable()){this._attachMovableBrowserEvents(o);}else{this._detachMovableBrowserEvents(o);}};D.prototype._onMouseOver=function(e){var o=sap.ui.getCore().byId(e.currentTarget.id);if(o!==this._oPreviousHoverTarget){if(this._oPreviousHoverTarget){this._oPreviousHoverTarget.$().removeClass("sapUiRtaOverlayHover");}this._oPreviousHoverTarget=o;o.$().addClass("sapUiRtaOverlayHover");}e.preventDefault();e.stopPropagation();};D.prototype._onMouseLeave=function(e){if(this._oPreviousHoverTarget){this._oPreviousHoverTarget.$().removeClass("sapUiRtaOverlayHover");}delete this._oPreviousHoverTarget;e.preventDefault();e.stopPropagation();};return D;},true);