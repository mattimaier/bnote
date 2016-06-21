sap.ui.controller("bnote.equipmentdetail", {	
	
deleteEquipment: function(){
	var model = equipmentdetailView.getModel();
	var path = equipmentdetailView.getBindingContext().getPath();
	var equipmentid = model.getProperty(path + "/id")
	
	 jQuery.ajax({
		 	type:"POST",
        	url: backend.get_url("deleteEquipment"),
        	data: {"id": equipmentid},
        	success: function(data) {
        		var path = equipmentdetailView.getBindingContext().sPath.split("/");
        		var idxDelItem = path[path.length -1];
        		
        		model.oData.equipment.splice(idxDelItem, 1);
        		model.setProperty("/equipment", model.oData.equipment);        			
        		
        		app.to("equipment");
        		
        	},            
        	error: function() {
        		sap.m.MessageToast.show("Löschen derzeit nicht möglich. Bitte Internetverbindung überprüfen.");
        	}
        });
	}
	
});