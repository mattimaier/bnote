sap.ui.controller("bnote.equipmentadd", {

	mode: "edit",
	
	setdirtyflag: function() {
		equipmentaddView.getController().dirty = true;
	},
	
	savechanges: function(){
		var model = equipmentaddView.getModel();
		var path = equipmentaddView.getBindingContext().getPath();
		var updateEquipmentData = model.getProperty(path);
		
		console.log(updateEquipmentData);
		
		//update backend
		if(this.dirty){
			if(this.mode == "edit") {
				jQuery.ajax({
					type: "POST",
		        	url: backend.get_url("updateEquipment"),
		        	data: updateEquipmentData,
		        	success: function(data) {
		        		sap.m.MessageToast.show("Speichern erfolgreich");
		        		equipmentdetailView.getModel().setProperty(path, updateEquipmentData);
		        		equipmentaddView.getController().dirty = false;
		            },
					error: function(){
						sap.m.MessageToast.show("Speichern fehlgeschlagen");
					}
		        });
			}
			else {
				jQuery.ajax({
					type: "POST",
		        	url: backend.get_url("addEquipment"),
		        	data: updateEquipmentData,
		        	success: function(data) {
		        		var equipmentid = data;
		        		updateEquipmentData.id = equipmentid;
		        		equipmentdetailView.getModel().setProperty(path, updateEquipmentData);
		        		equipmentaddView.getController().dirty = false;
		        		sap.m.MessageToast.show("Speichern erfolgreich");
		        		app.to("equipment");
		            },
					error: function(){
						
						var a = equipmentdetailView.getBindingContext().sPath.split("/");
		        		var BindingContext = a[a.length -1];
        				model.oData.equipment.splice(BindingContext, 1)
        				
						sap.m.MessageToast.show("Speichern fehlgeschlagen");
					}
		        });
			}
		}
		else {
			sap.m.MessageToast.show("Es wurde nichts verändert.");
		}
						
	  },
	
	checkdirtyflag: function() {
		if (equipmentaddView.getController().dirty && this.mode == "edit"){
			var model = equipmentaddView.getModel();
			var path = equipmentaddView.getBindingContext().getPath();
			var equipmentid = model.getProperty(path + "/id");
										
				jQuery.ajax({
					type: "GET",
		        	url: backend.get_url("getEquipment"),
		        	data: {"id" : equipmentid},
		        	success: function(data) {
		        		console.log("dirty and edit");
		        		console.log(data);
		        		equipmentdetailView.getModel().setProperty(path, data);
		        		this.dirty = false;
		        		console.log(model);
		            },
		        	error: function(){	        		
		        		console.log("checkdirtyflag error");
		        	}
				});
		}
		else if (equipmentaddView.getController().dirty && this.mode == "add"){
			var model = equipmentaddView.getModel();			
			var a = equipmentdetailView.getBindingContext().sPath.split("/");
    		var BindingContext = a[a.length -1];
			model.oData.equipment.splice(BindingContext, 1)
			console.log("dirty and add");
			console.log(model);
			
		}
	}		
});