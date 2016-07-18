sap.ui.jsview("bnote.start", {

	getControllerName : function() {
		return "bnote.start";
	},

	createContent : function(oController) {
			this.mainList = new sap.m.List();

		var itemTemplate = new sap.m.StandardListItem({
			title : "{start}",
			icon : "{icon}",
			description : "{listdescription}",
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
					rehearsalView.getController().prepareView();
					app.to("rehearsal","slide");
				}
				else if (objType == "Concert"){						
					concertView.setBindingContext(oBindingContext);
					var participate = model.getProperty(path + "/participate");
					var location = model.getProperty(path + "/location");
					concertView.prepareModel(location);
					concertView.setButtons(participate);
					app.to("concert","slide");
				}
				else if (objType == "Task"){
					taskView.setBindingContext(oBindingContext);
					taskView.getController().onTaskPress();
					app.to("task","slide");						
				}
				else if (objType == "Vote"){
					voteView.setBindingContext(oBindingContext);
					voteView.getController().onVotePress();
					app.to("vote","slide");					
				}
				else if (objType == "Reservation"){
					reservationView.setBindingContext(oBindingContext);
					app.to("reservation","slide");
				}
			}
		});
			
		this.mainList.bindItems({
			growingScrollToLoad : "true",
			path : "/items",
			sorter : new sap.ui.model.Sorter("start"),
			template : itemTemplate 
		});		
		
		this.reservationaddButton = new sap.m.Button({
			visible: false,
       		text: "Reservierung hinzufügen",
       		press: function(){	       			
       			reservationaddView.getController().prepareModel();
       			app.to("reservationadd","slide");
       		}
		});		
		this.rehearsaladdButton =  new sap.m.Button({	
			visible: false,
			text: "Probe hinzufügen",
			press: function(){
				rehearsaladdView.getController().prepareModel();
				app.to("rehearsaladd","slide");
			}
		});		
		this.taskaddButton =  new sap.m.Button({
			visible: false,
			text: "Aufgabe hinzufügen",
			press: function(){
				taskaddView.getController().prepareModel();
				app.to("taskadd","slide");
			}
		});		
		this.contactaddButton =  new sap.m.Button({
			visible: false,
			text: "Kontakt hinzufügen",
			press: function(){
				contactaddView.getController().prepareModel();
				app.to("contactadd","slide");
			}
		});				
		var startActionSheet = new sap.m.ActionSheet({
			cancelButtonText: "Abbrechen",
			buttons:[        			        
			         this.reservationaddButton,
			         this.rehearsaladdButton,
			         this.taskaddButton,
			         this.contactaddButton,			      
			         ],
		});		
		this.startaddButton = new sap.m.Button({	
			visible: false,
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
			contentRight: [this.startaddButton]
		});
		
		var page = new sap.m.Page("StartPage", {
			title : "Start",
			customHeader : [ headerBar ],
			content : [ this.mainList ],
			footer : [ getNaviBar() ]
		});
		return page;
	}
});