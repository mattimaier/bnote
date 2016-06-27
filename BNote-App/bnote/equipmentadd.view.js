sap.ui.jsview("bnote.equipmentadd", {
		
	getControllerName: function() {
		return "bnote.equipmentadd";
	},
	
	createContent: function(oController){
		var view = this;
        
		this.equipmentaddForm = new sap.ui.layout.form.SimpleForm({
            title: "",
            content: [
              new sap.m.Label({text: "Name"}),
              new sap.m.Input({
            	  value: "{name}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.name
              }),                
              new sap.m.Label({text: "Modell"}),
              new sap.m.Input({
            	  value: "{model}",
            	  change: oController.setdirtyflag,
              }),              
              new sap.m.Label({text: "Hersteller"}),
              new sap.m.Input({
            	  value: "{make}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.name
              }),              
              new sap.m.Label({text: "Kaufpreis"}),
              new sap.m.Input({
            	  value: "{purchase_price}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.money
              }),  
              
              new sap.m.Label({text: "Aktueller Wert"}),
              new sap.m.Input({
            	  value: "{current_value}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.money
              }),              
              
              new sap.m.Label({text: "Menge"}),
              new sap.m.Input({
            	  value: "{quantity}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.positive_amount
              }),
              
              new sap.m.Label({text: "Notizen"}),
              new sap.m.Input({
            	  value: "{notes}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.text
              })  
            ]
		});
		
		var updateButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),
			press: oController.savechanges			
		})
		
		var page = new sap.m.Page("EquipmentAddPage", {
	        title: "",
	        showNavButton: true,
	        navButtonPress: function() {
	        	oController.checkdirtyflag();
	            app.back();
	        },
	        headerContent: [ updateButton ],
			content: [ this.equipmentaddForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

