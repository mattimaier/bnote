sap.ui.jsview("bnote.reservationadd", {

	getControllerName : function() {
		return "bnote.reservationadd";
	},
	
	loadlocations: function(locations){
		this.locationitems.destroyItems();
		this.locationitems.addItem(new sap.ui.core.Item({text : "Neue Location hinzufügen", key : "-1"}));
		for(i=0; i < locations.getProperty("/locations").length; i++){
			var name = locations.getProperty("/locations/" + i + "/name");
			var key = locations.getProperty("/locations/" + i + "/id");
			this.locationitems.addItem(new sap.ui.core.Item({text : name, key : key}));
		};
		
	},
	
	loadcontacts: function(contacts){
		this.contactitems.destroyItems();
		for(i=0; i < contacts.getProperty("/contact").length; i++){
			var name = contacts.getProperty("/contact/" + i + "/name");
			var surname = contacts.getProperty("/contact/" + i + "/surname");
			var key = contacts.getProperty("/contact/" + i + "/id");
			this.contactitems.addItem(new sap.ui.core.Item({text : surname + "," + " " +  name, key : key}));
		};
	},
	
	createContent: function() {
		this.locationitems = new sap.m.Select({
			change: function(){
					reservationaddView.getController().setdirtyflag();
					reservationaddView.getController().checknewreservation();
			},	
      	  	items: []
        });
        
        this.contactitems = new sap.m.Select({
			change: function(){
				reservationaddView.getController().setdirtyflag();
			},
      	  	items: []
        });
        
        this.locationaddForm = new sap.ui.layout.form.SimpleForm({
        	visible: true,
        	content:[
			        new sap.m.Label({text: "Straße"}),
			        new sap.m.Input("locationaddstreet",{}),
			        
			        new sap.m.Label({text: "Stadt"}),
			        new sap.m.Input("locationaddcity",{}),
			        
			        new sap.m.Label({text: "Postleitzahl"}),
			        new sap.m.Input("locationaddzip",{}),
			        
			        new sap.m.Label({text: "Location Notizen"}),
			        new sap.m.Input("locationaddnotes",{}),
			        
			        new sap.m.Label({text: "Location Name"}),			        
			        new sap.m.Input("locationaddname",{}),
		   ]
        });
		 
		var reservationaddForm = new sap.ui.layout.form.SimpleForm({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
			content:[			         
					new sap.m.Label({text: "Beginn"}),
					new sap.m.DateTimeInput({
						type: sap.m.DateTimeInputType.DateTime,
						change: function(){
			        		 reservationaddView.getController().setdirtyflag();
			        		 },
						dateValue: "{/begin}"
					}),
					
					
					new sap.m.Label({text: "Ende"}),
					new sap.m.DateTimeInput({
						type: sap.m.DateTimeInputType.DateTime,
						change: function(){
			        		 reservationaddView.getController().setdirtyflag();
			        		 },
						dateValue: "{/end}"
					}),
					
	
			        new sap.m.Label({text: "Name"}),
			        new sap.m.Input({
			        	 value: "{/name}",
			        	 valueLiveUpdate: true,
			        	 liveChange: function(){
			        		 reservationaddView.getController().setdirtyflag();
		                 }
			        }),
			         
			        new sap.m.Label({text: "Ort"}),
			        this.locationitems,
			              
			        // Invisible until Buttonpress	
			        this.locationaddForm,
			        
			        new sap.m.Label({text: "Kontakt"}),
			        this.contactitems,
			        
			        new sap.m.Label({text: "Notizen"}),
			        new sap.m.Input({
			        	 value: "{/notes}",
			        	 valueLiveUpdate: true,
			        	 liveChange: function(){
			        		 reservationaddView.getController().setdirtyflag();
		                 }
			        })
			]  
	});
	var view = this;
		
		var createReservationButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),			
			press: view.getController().createReservation
		});
		
		var page = new sap.m.Page("ReservationaddPage", {
	        title: "Reservierung hinzufügen",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [ createReservationButton ],
			content: [ reservationaddForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
		}		
});