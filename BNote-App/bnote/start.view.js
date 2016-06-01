sap.ui.jsview("bnote.start", {

	getControllerName : function() {
		return "bnote.start";
	},

	createContent : function(oController) {
		var mainList = new sap.m.List();

		mainList.bindItems({
			growingScrollToLoad : "true",
			path : "/items",
			sorter : new sap.ui.model.Sorter("start"),
			template : new sap.m.StandardListItem({
				title : "{start}",
				icon : "{icon}",
				description : "{description}",
				type : sap.m.ListType.Navigation,
				press : function(evt) {
					var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
					var model = oBindingContext.getModel();
					var path = oBindingContext.getPath();
					var objType = model.getProperty(path + "/type");

					if(objType == "Rehearsal") {
						rehearsalView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
						var participate = model.getProperty(path + "/participate");
						rehearsalView.setButtons(participate);
						app.to("rehearsal");
					}
					else {
						sap.m.MessageToast.show("Konzert nicht implementiert");
					}
				}
			})
		});

		jQuery.sap.require("sap.ui.core.IconPool");
		var rehearsalBar = new sap.m.OverflowToolbar({

			active : true,
			design : sap.m.ToolbarDesign.Solid,
			content : [ new sap.m.Button("rehearsalStartBtn", {
				text : "Start",
				icon : sap.ui.core.IconPool.getIconURI("home"),
				press : function() {
					app.to("start")
				}
			}), new sap.m.Button({
				text : "Mitspieler",
				icon : sap.ui.core.IconPool.getIconURI("person-placeholder"),
				press : function() {
					app.to("member")
				}
			}), new sap.m.Button({
				text : "Kommunikation",
				icon : sap.ui.core.IconPool.getIconURI("email"),
				press : function() {
					app.to("communication")
				}
			}) ]

		});

		var page = new sap.m.Page("StartPage", {
			title : "Start",
			showNavButton : true,
			navButtonPress : oController.onLogout,
			content : [ mainList ],
			footer : [ rehearsalBar ]
		});

		return page;
	}
});