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
	
	createContent: function(oController){
		var view = this;
		
		this.instrumentitems = new sap.m.Select({
			 change: oController.setdirtyflag,
	      	  items: []
	    });
		
		this.signupForm = new sap.ui.layout.form.SimpleForm({
			   layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
			   content: [
			             new sap.m.Label({text: "Nachname", required: true}),
					     new sap.m.Input({					    	  
					    	 value: "{/surname}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					         liveChange: validator.name
					     }),
					     new sap.m.Label({text: "Vorname", required: true}),
					     new sap.m.Input({
					      	 value: "{/name}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.name
					     }),					      
					     new sap.m.Label({text: "Instrument"}),
					     this.instrumentitems,
					        
					     new sap.m.Label({text: "Telefon"}),
					     new sap.m.Input({
					       	 value: "{/phone}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.phone
					     }),
					     new sap.m.Label({text: "Email", required: true}),
					     new sap.m.Input({
					       	 value: "{/email}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.email
					     }),
					     new sap.m.Label({text: "Straße", required: true}),
					     new sap.m.Input({
					       	 value: "{/street}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.street
					     }),
					     new sap.m.Label({text: "Stadt", required: true}),
					     new sap.m.Input({
					       	 value: "{/city}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.city
					     }),
					     new sap.m.Label({text: "PLZ", required: true}),
					     new sap.m.Input({
					       	 value: "{/zip}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.zip
					     }),	 
					     new sap.m.Label({text: "Anmeldename", required: true}),
					     new sap.m.Input({
					      	 value: "{/login}",
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.name
					     }),
					     new sap.m.Label({text: "Passwort", required: true}),
					     new sap.m.Input({
					      	 value: "{/pw1}",
					      	 type: sap.m.InputType.Password,
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.name
					     }),
					     new sap.m.Label({text: "Passwort wiederholen", required: true}),
					     new sap.m.Input({
					      	 value: "{/pw2}",
					      	 type: sap.m.InputType.Password,
					    	 valueLiveUpdate: true,
					    	 change: oController.setdirtyflag,
					    	 liveChange: validator.name
					     }),
					     new sap.m.CheckBox({
					    	 text: "Nutzerbedingungen zustimmen",
					    	 selected: "{/terms}"
					     })					      
			    ]
		 });
		
		var signupButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),	
			press: oController.signup
		});
		
		var page = new sap.m.Page("SignupPage", {
	        title: "Registrierung",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();	           
	        },
	        headerContent : [ signupButton ],
			content: [ this.signupForm  ]
		});
		
		return page;
	}
});