/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','./Action'],function($,A){"use strict";return A.extend("sap.ui.test.actions.EnterText",{metadata:{properties:{text:{type:"string"}},publicMethods:["executeOn"]},executeOn:function(c){var f=c.getFocusDomRef();if(!f){$.sap.log.error("Control "+c+" has no focusable dom representation",this._sLogPrefix);return;}if(this.getText()===undefined){$.sap.log.error("Please provide a text for this EnterText action",this._sLogPrefix);return;}var F=$(f);F.focus();if(!F.is(":focus")){$.sap.log.warning("Control "+c+" could not be focused - maybe you are debugging?",this._sLogPrefix);}var u=this._getUtils();u.triggerKeydown(f,$.sap.KeyCodes.DELETE);u.triggerKeyup(f,$.sap.KeyCodes.DELETE);F.val("");u.triggerEvent("input",f);this.getText().split("").forEach(function(C){u.triggerCharacterInput(f,C);u.triggerEvent("input",f);});u.triggerKeydown(f,"ENTER");u.triggerKeyup(f,"ENTER");u.triggerEvent("blur",f);}});},true);
