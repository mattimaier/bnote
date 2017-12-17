/*!
	 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
	 */
sap.ui.define(["sap/ui/fl/Utils","sap/ui/fl/changeHandler/BaseRename"],function(U,B){"use strict";var r={propertyName:"title",changePropertyName:"newText",translationTextType:"XGRP"};var R=B.createRenameChangeHandler(r);R.applyChange=function(c,C,p){var m=p.modifier;var P=r.propertyName;var o=c.getDefinition();var t=o.texts[r.changePropertyName];var v=t.value;var a=C;var s=m.getAggregation(C,"subSections");if(s&&s.length===1&&m.getProperty(s[0],"title")&&m.getProperty(m.getParent(C),"subSectionLayout")==="TitleOnTop"){a=s[0];}if(o.texts&&t&&typeof(v)==="string"){c.setRevertData(m.getProperty(a,P));if(U.isBinding(v)){m.setPropertyBinding(a,P,v);}else{m.setProperty(a,P,v);}return true;}else{U.log.error("Change does not contain sufficient information to be applied: ["+o.layer+"]"+o.namespace+"/"+o.fileName+"."+o.fileType);}};return R;},true);
