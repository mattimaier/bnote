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
        		var a = equipmentdetailView.getBindingContext().sPath.split("/");
        		var BindingContext = a[a.length -1];
        		
        		console.log(BindingContext);
        		model.oData.equipment.splice(BindingContext, 1)
        		console.log(model);
        		
        		model.setProperty("/equipment", model.oData.equipment.splice(BindingContext, 1));
        			
        				console.log(model);
        		console.log("success");
        		app.to("equipment");
        		
        	},            
        	error: function() {
        		console.log("error");
        	}
        });
	}
	
});