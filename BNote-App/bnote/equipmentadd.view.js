sap.ui.jsview("bnote.equipmentadd", {
		
	getControllerName: function() {
		return "bnote.equipmentadd";
	},
	
	createContent: function(){
        
		this.equipmentaddForm = new sap.ui.layout.form.SimpleForm({
            title: "",
            content: [
              new sap.m.Label({text: "Name"}),
              new sap.m.Input({
            	  value: "{name}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();
            	  }
              }),                
              new sap.m.Label({text: "Modell"}),
              new sap.m.Input({
            	  value: "{model}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();
            	  }
              }),              
              new sap.m.Label({text: "Hersteller"}),
              new sap.m.Input({
            	  value: "{make}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();
                 }
              }),              
              new sap.m.Label({text: "Kaufpreis"}),
              new sap.m.Input({
            	  value: "{purchase_price}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();
             	  }
              }),  
              
              new sap.m.Label({text: "Aktueller Wert"}),
              new sap.m.Input({
            	  value: "{current_value}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();                  	 
              	  }
              }),              
              
              new sap.m.Label({text: "Menge"}),
              new sap.m.Input({
            	  value: "{quantity}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();                  	 
              	  }
              }),
              
              new sap.m.Label({text: "Notizen"}),
              new sap.m.Input({
            	  value: "{notes}",
            	  liveChange: function(){
            		  equipmentaddView.getController().setdirtyflag();
              	  }
              })  
            ]
		});
		
		var updateButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),
			press: function() {
				equipmentaddView.getController().savechanges();
			}
		})
		
		var page = new sap.m.Page("EquipmentAddPage", {
	        title: "",
	        showNavButton: true,
	        navButtonPress: function() {
	        	equipmentaddView.getController().checkdirtyflag();
	            app.back();
	        },
	        headerContent: [ updateButton ],
			content: [ this.equipmentaddForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

