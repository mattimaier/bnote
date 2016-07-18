sap.ui.jsview("bnote.signup", {
	
	getControllerName: function() {
		return "bnote.signup";
	},
	
	loadinstruments: function(instruments){
		this.instrumentitems.destroyItems();
		for(var i=0; i < instruments.getProperty("/instrument").length; i++){
			var name = instruments.getProperty("/instrument/" + i + "/name");
			var key = instruments.getProperty("/instrument/" + i + "/id");
			this.instrumentitems.addItem(new sap.ui.core.Item({text : name, key : key}));
		};		
	},
	
	createContent: function(){
		
		 this.signupForm = new sap.ui.layout.form.SimpleForm({
			    layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
			    content: [
			              new sap.m.Label({text: "Nachname"}),
					      new sap.m.Input({					    	  
					    	  value: "{/surname}",
					    	  valueLiveUpdate: true,
					          change: oController.setdirtyflag,
					          liveChange: validator.name
					      }),
					      new sap.m.Label({text: "Vorname"}),
					      new sap.m.Input({
					       	  value: "{/name}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.name
					      }),
					      
					      new sap.m.Label({text: "Instrument"}),
					      this.instrumentitems,
					        
					      new sap.m.Label({text: "Telefon (privat)"}),
					      new sap.m.Input({
					       	  value: "{/phone}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.phone
					      }),
					      new sap.m.Label({text: "Fax"}),
					      new sap.m.Input({
					       	  value: "{/fax}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.phone
					      }),
					      new sap.m.Label({text: "Handy"}),
					      new sap.m.Input({
					       	  value: "{/mobile}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.phone
					      }),
					      new sap.m.Label({text: "Telefon (geschäftlich)"}),
					      new sap.m.Input({
					       	  value: "{/phone}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.phone
					      }),
					      new sap.m.Label({text: "Email)"}),
					      new sap.m.Input({
					       	  value: "{/email}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.email
					      }),
					      new sap.m.Label({text: "Homepage"}),
					      new sap.m.Input({
					       	  value: "{/web}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.website_url
					      }),
					      new sap.m.Label({text: "Geburtstag"}),
					      new sap.m.DateTimeInput({					    	  
					    	  type: sap.m.DateTimeInputType.Date,   
					    	  dateValue: "{/birthday}",
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.datetime
					      }),
					      new sap.m.Label({text: "Straße"}),
					      new sap.m.Input({
					       	  value: "{/street}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.street
					      }),
					      new sap.m.Label({text: "Stadt"}),
					      new sap.m.Input({
					       	  value: "{/city}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.city
					      }),
					      new sap.m.Label({text: "Postleitzahl"}),
					      new sap.m.Input({
					       	  value: "{/zip}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.zip
					      }),
					      new sap.m.Label({text: "Notizen"}),
					      new sap.m.Input({
					       	  value: "{/notes}",
					    	  valueLiveUpdate: true,
					    	  change: oController.setdirtyflag,
					    	  liveChange: validator.text
					      }),
					     
			            
			              ]
		 });
	
	var page = new sap.m.Page("SignupPage", {
        title: "Anmeldung",
        headerContent : [  ],
		content: [  ]
	});
	
	return page;
	}
});