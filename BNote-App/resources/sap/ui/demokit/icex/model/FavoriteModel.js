/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2016 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/demokit/icex/util/Sorter","jquery.sap.storage"],function(S,q){"use strict";return sap.ui.model.json.JSONModel.extend("sap.ui.demokit.icex.model.FavoriteModel",{_STORAGE_KEY:"ICON_EXPLORER_FAVORITES",_storage:q.sap.storage(q.sap.storage.Type.local),constructor:function(s){sap.ui.model.json.JSONModel.apply(this,arguments);this.setSizeLimit(1000000);var j=this._storage.get(this._STORAGE_KEY);var d=JSON.parse(j);if(!d){d={count:0,icons:[]};}this.setData(d);},isFavorite:function(n){var d=this.getData();for(var i=0;i<d.icons.length;i++){if(d.icons[i].name===n){return true;}}return false;},toggleFavorite:function(a){var d=this.getData();var f=this.isFavorite(a);if(f){var b=q.grep(d.icons,function(n){return n.name!=a;});d.icons=b;d.count--;}else{d.icons[d.icons.length]={name:a};d.count++;}d.icons.sort(S.sortByName);this.setData(d);var s=JSON.stringify(d);this._storage.put(this._STORAGE_KEY,s);return!f;}});});
