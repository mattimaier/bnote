sap.ui.jsview("bnote.equipmentdetail", {
	
	getControllerName: function() {
		return "bnote.equipmentdetail";
	},
	
	createContent: function(){
		var equipmentdetailForm = new sap.ui.layout.form.SimpleForm({
            title: "",
            content: [
                      new sap.m.Label({text: "Name"}),
                      new sap.m.Text({text: "{name}"}),  
                      
                      new sap.m.Label({text: "Modell"}),
                      new sap.m.Text({text: "{model}"}),
                      
                      new sap.m.Label({text: "Hersteller"}),
                      new sap.m.Text({text: "{make}"}),
                      
                      new sap.m.Label({text: "Kaufpreis"}),
                      new sap.m.Text({text: "{purchase_price}"}),
                      
                      new sap.m.Label({text: "Aktueller Wert"}),
                      new sap.m.Text({text: "{current_value}"}),
                      
                      new sap.m.Label({text: "Menge"}),
                      new sap.m.Text({text: "{quantity}"}),
                      
                      new sap.m.Label({text: "Notizen"}),
                      new sap.m.Text({text: "{notes}"}),
                   ]
		});
		
		var equipmentUpdateButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("edit"),
			press: function(){
				equipmentaddView.setModel(this.getModel());
				equipmentaddView.setBindingContext(this.getBindingContext());
				equipmentaddView.getController().setData();  // equipment dirtyflag = false
				equipmentaddView.getController().mode = "edit";
				app.to("equipmentadd");
			}
		});
		
		var equipmentDeleteButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("delete"),
			press: function(){
				equipmentdetailView.deleteDialog.open();
			}
		});
		
		  
		  this.deleteButton = new sap.m.Button({
			  		text: "Löschen",
			  		press: function(){
			  			equipmentdetailView.getController().deleteEquipment();
			  			equipmentdetailView.deleteDialog.close();
			  		}
		  });
		  
		  this.closeButton = new sap.m.Button({
			  		text: "Abbrechen",
			  		press: function(){
			  			equipmentdetailView.deleteDialog.close();
			  		}
		  });
		  
		   this.deleteDialog = new sap.m.Dialog({
			   		title: "Sind Sie Sicher?",
			   		modal: true,
			   		contentWidth:"1em",
			   		buttons: [ this.deleteButton, this.closeButton ],
			   		content: [
			   		          new sap.m.Text({text: "Dieses Item wird aus dem Equipment gelöscht."})
			   		          ]
		   });       
		
		
		var page = new sap.m.Page("EquipmentdetailPage", {
	        title: "Equipmentdetail",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [ equipmentUpdateButton, equipmentDeleteButton ],
			content: [ equipmentdetailForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});