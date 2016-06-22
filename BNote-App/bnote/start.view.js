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
					else if (objType == "Reservation"){
						reservationView.setBindingContext(oBindingContext);
						app.to("reservation");
					}
				}
			})
		});
		
		var startActionSheet = new sap.m.ActionSheet({
			cancelButtonText: "Abbrechen",
			buttons:[
			         new sap.m.Button({
			        	 text: "Reservierung hinzufügen",
			        	 press: function(){	
			        		 reservationaddView.getController().buildModel();
			        		 app.to("reservationadd");
			        	 }
			         }),
			         new sap.m.Button({
			        	 text: "Probe hinzufügen"
			         }),
			         new sap.m.Button({
			        	 text: "Aufgabe hinzufügen"
			         }),
			         new sap.m.Button({
			        	 text: "Kontakt hinzufügen",
			        	 press: function() {
			        		 contactaddView.getController().prepareModel();
			        		 app.to("contactadd");
			        	 }
			        	
			         })
			         ],
		});
		
		var startaddButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("add"),
			press: function(){
				startActionSheet.setPlacement(sap.m.PlacementType.HorizontalPreferedRight);
				startActionSheet.openBy(this);
			}
		});
		var logoutButton = new sap.m.Button({
			text: "Logout",
			press: oController.onLogout
		});
		var startTitle = new sap.m.Title({
			text: "Start"
		});
		var headerBar = new sap.m.Bar({
			contentLeft: [logoutButton],
			contentMiddle: [startTitle],
			contentRight: [startaddButton]
		});
		
		var page = new sap.m.Page("StartPage", {
			title : "Start",
			customHeader : [ headerBar ],
			content : [ mainList ],
			footer : [ getNaviBar() ]
		});
		return page;
	}
});