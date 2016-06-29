sap.ui.controller("bnote.equipmentadd", {

	mode: "edit",
	
	setData: function() {
		equipmentaddView.getController().dirty = false;
	},
	
	setdirtyflag: function() {
		equipmentaddView.getController().dirty = true;
	},
	
	savechanges: function(){
		var model = equipmentaddView.getModel();
		var path = equipmentaddView.getBindingContext().getPath();
		var updateEquipmentData = model.getProperty(path);	
		
		//update backend
		if(equipmentaddView.getController().dirty){
			if(equipmentaddView.getController().mode == "edit") {
				// update
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
				// add
				jQuery.ajax({
					type: "POST",
		        	url: backend.get_url("addEquipment"),
		        	data: updateEquipmentData,
		        	success: function(data) {
		        		var equipmentid = data;
		        		updateEquipmentData.id = equipmentid;
		        		equipmentaddView.getModel().setProperty(path, updateEquipmentData);
		        		equipmentaddView.getController().dirty = false;
		        		sap.m.MessageToast.show("Speichern erfolgreich");
		        		app.to("equipment");
		            },
					error: function(){
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
		if (equipmentaddView.getController().dirty && equipmentaddView.getController().mode == "edit"){
			var model = equipmentaddView.getModel();
			var path = equipmentaddView.getBindingContext().getPath();
			var equipmentid = model.getProperty(path + "/id");
										
				jQuery.ajax({
					type: "GET",
		        	url: backend.get_url("getEquipment"),
		        	data: {"id" : equipmentid},
		        	success: function(data) {
		        		equipmentdetailView.getModel().setProperty(path, data);
		        		equipmentaddView.getController().dirty = false;
		            },
		        	error: function(){	        		
		        		sap.m.MessageToast.show("Fehler! Bitte lade die App neu.");		        		
		        	}
				});
		}
		else if (equipmentaddView.getController().mode == "add"){
			var model = equipmentaddView.getModel();			
			var path = equipmentaddView.getBindingContext().sPath.split("/");
    		var idxNewItem = path[path.length -1];
			model.oData.equipment.splice(idxNewItem, 1);
			model.setProperty("/equipment", model.oData.equipment);
			equipmentaddView.getController().dirty = false;
		}
	}		
});