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
					else if (objType == "Concert"){
						
						concertView.setBindingContext(oBindingContext);
						var participate = model.getProperty(path + "/participate");
						var location = model.getProperty(path + "/location");
						concertView.prepareModel(location);
						concertView.setButtons(participate);
						app.to("concert");
					}
					else if (objType == "Task"){
						taskView.setBindingContext(oBindingContext);
						taskView.getController().onTaskPress();
						app.to("task");						
					}
					else if (objType == "Vote"){
						voteView.setBindingContext(oBindingContext);
						voteView.getController().onVotePress();
						app.to("vote");
					}
				}
			})
		});

		
		var startaddButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("add"),
			
		});
		
		var page = new sap.m.Page("StartPage", {
			title : "Start",
			showNavButton : true,			
			navButtonPress : oController.onLogout,
			headerContent : [ startaddButton ],
			content : [ mainList ],
			footer : [ getNaviBar() ]
		});

		return page;
	}
});