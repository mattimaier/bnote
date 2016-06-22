sap.ui.jsview("bnote.contactadd", {
	
	getControllerName : function() {
		return "bnote.contactadd";
	},
	

	loadinstruments: function(instruments){
		this.instrumentitems.destroyItems();
		for(i=0; i < instruments.getProperty("/instrument").length; i++){
			var name = instruments.getProperty("/instrument/" + i + "/name");
			var key = instruments.getProperty("/instrument/" + i + "/id");
			this.instrumentitems.addItem(new sap.ui.core.Item({text : name, key : key}));
		};
		
	},
	
	createContent: function(){
		 this.instrumentitems = new sap.m.Select({
	      	  	items: []
	        });
		
		 this.contactaddForm = new sap.ui.layout.form.SimpleForm({
			    layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
	        	content:[
				        new sap.m.Label({text: "Nachname"}),
				        new sap.m.Input({
				        	value: "{/surname}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Vorname"}),
				        new sap.m.Input({
				        	value: "{/name}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Instrument"}),
				        this.instrumentitems,
				        
				        new sap.m.Label({text: "Telefon (privat)"}),
				        new sap.m.Input({
				        	value: "{/phone}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Telefon (geschäftlich)"}),
				        new sap.m.Input({
				        	value: "{/business}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Handy"}),			        
				        new sap.m.Input({
				        	value: "{/mobile}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Email"}),
				        new sap.m.Input({
				        	value: "{/email}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Homepage"}),
				        new sap.m.Input({
				        	value: "{/web}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Fax"}),
				        new sap.m.Input({
				        	value: "{/fax}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Geburtstag"}),	
				        new sap.m.DateTimeInput({
							type: sap.m.DateTimeInputType.Date,
							dateValue: "{/birthday}"
						}),
						
				        
				        new sap.m.Label({text: "Straße"}),
				        new sap.m.Input({
				        	value: "{/street}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Stadt"}),
				        new sap.m.Input({
				        	value: "{/city}",
				        	valueLiveUpdate: true
				        }),
				        
				        new sap.m.Label({text: "Postleitzahl"}),	
				        new sap.m.Input({
				        	value: "{/zip}",
				        	valueLiveUpdate: true
				        }),
				        new sap.m.Label({text: "Gruppen"}),
			   ]
	        });
	
	  var view = this;
		
		var addContactButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),			
			press: view.getController().addContact
		});
		  
	var page = new sap.m.Page("contactaddPage", {
		  showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
		title : "Kontakte",
		headerContent : [ addContactButton ],
		content : [ this.contactaddForm ],
		footer : [ getNaviBar() ]
	});
	return page;
}	
});