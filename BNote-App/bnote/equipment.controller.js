sap.ui.controller("bnote.equipment", {
	
	onEquipmentClick: function() {
		var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getEquipment"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
               
                oCtrl.getView().setModel(model);
                equipmentdetailView.setModel(model);                
                console.log(model);
                app.to("equipment");
            },
        error: function() {
        	console.log("error");
        }
        });
    },
    
    filterList: function(oEvent){ 
        var like = oEvent.getParameter("newValue");  
        var oFilter = new sap.ui.model.Filter("name",   
                                                sap.ui.model.FilterOperator.Contains,   
                                                like);  
        var element = sap.ui.getCore().getElementById("equipmentList");  
        var listBinding = element.getBinding("items");  
        listBinding.filter([oFilter]);  
}	
	
});