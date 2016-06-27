sap.ui.jsview("bnote.rehearsaladd",{
	
	getControllerName: function() {
		return "bnote.rehearsaladd";
	},
	
	loadlocations: function(locations){		
		this.locationitems.destroyItems();
		this.locationitems.addItem(new sap.ui.core.Item({text : "Neue Location hinzufügen", key : "-1"}));
		
		for(var i=0; i < locations.getProperty("/locations").length; i++){			
			var name = locations.getProperty("/locations/" + i + "/name");
			var key = locations.getProperty("/locations/" + i + "/id");
			this.locationitems.addItem(new sap.ui.core.Item({text : name, key : key}));
		};
		
	},
	
	createContent: function(oController) {
		var view = this;
		
		this.locationitems = new sap.m.Select({
			change: function(){
					oController().setdirtyflag();
					oController().checknewlocation();
			},	
			items: []			
		})
		
		 this.locationaddForm = new sap.ui.layout.form.SimpleForm({	
			 layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
			 visible: true,
			 content:[
			          
		        	 new sap.m.Label({text: "Straße"}),
		        	 new sap.m.Input("rehearsaladd_addlocation_street",{
		        		 change: oController.addlocation_setdirtyflag,
		        		 liveChange: validator.street   		 
		        	 }),			        
		        	 new sap.m.Label({text: "Stadt"}),
		        	 new sap.m.Input("rehearsaladd_addlocation_city",{
		        		 change: oController.addlocation_setdirtyflag,
		        		 liveChange: validator.city 
		        	 }),			        
		        	 new sap.m.Label({text: "Postleitzahl"}),
		        	 new sap.m.Input("rehearsaladd_addlocation_zip",{
		        		 change: oController.addlocation_setdirtyflag,
		        		 liveChange: validator.zip 
		        	 }),			        
		        	 new sap.m.Label({text: "Location Notizen"}),
		        	 new sap.m.Input("rehearsaladd_addlocation_notes",{
		        		 change: oController.addlocation_setdirtyflag,
		              	 liveChange: validator.text
		        	 }),
		        	 
		        	 new sap.m.Label({text: "Location Name"}),			        
		        	 new sap.m.Input("rehearsaladd_addlocation_name",{
		        		 change: oController.addlocation_setdirtyflag,
		        		 liveChange: validator.name
		        	 }),
			        ]
		 });
		
		this.rehearsaladdForm = new sap.ui.layout.form.SimpleForm({
				    layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,				    
		        	content:[
		        	         new sap.m.Label({text: "Beginn"}),	
		        	         new sap.m.DateTimeInput("rehearsaladd_begin",{
		        	        	 type: sap.m.DateTimeInputType.DateTime,		        	        	 
		 					     change: function(){		 					    	 
		 			        		 oController.setdirtyflag();
		 			        		 var oldtime = sap.ui.getCore().byId("rehearsaladd_begin").getDateValue();
					        		 sap.ui.getCore().byId("rehearsaladd_end").setDateValue(new Date(oldtime.getTime() + 120*60000)); // 120 (minutes) * 60000 (milliseconds) = 2 hours
		 			        		 },		 			        		 
		 						 dateValue: "{/begin}",			 						 
		 						 liveChange: validator.datetime
		 					 }),
						     new sap.m.Label({text: "Ende"}),
						     new sap.m.DateTimeInput("rehearsaladd_end",{
		        	        	 type: sap.m.DateTimeInputType.DateTime,		        	        	 
		 					     change: oController.setdirtyflag,
		 						 dateValue: "{/end}",		 						 
		 						 liveChange: validator.datetime
		 					 }),
					         new sap.m.Label({text: "Zusage bis"}),
					         new sap.m.DateTimeInput("rehearsaladd_approve_until",{					        	 
					        	 type: sap.m.DateTimeInputType.DateTime,					        	 
		 					     change: oController.setdirtyflag,
		 						 dateValue: "{/approve_until}",			 						 
		 						 liveChange: validator.datetime
		 					 }),
					         new sap.m.Label({text: "Location"}),
						     this.locationitems,
						     this.locationaddForm,
						    
					         new sap.m.Label({text: "Notizen"}),
						     new sap.m.Input({
						    	 value: "{/notes}",
						    	 change: oController.setdirtyflag,
						         valueLiveUpdate: true
						         }),
		        	         ]
		});		
		
		var addRehearsalButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),			
			press: oController.addRehearsal
		});
		
		var page = new sap.m.Page("RehearsaladdPage", {
            title: "Probe hinzufügen",
            showNavButton: true,
            navButtonPress: function() {            	
                app.back();
            },
            headerContent: [addRehearsalButton],
			content: [ this.rehearsaladdForm ],
			footer: [getNaviBar()]
		});
		return page;
	}
});