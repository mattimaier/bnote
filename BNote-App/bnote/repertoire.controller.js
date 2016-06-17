sap.ui.controller("bnote.repertoire", {

	onRepertoireClick: function() {
		var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getSongs"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
               
                oCtrl.getView().setModel(model);
                repertoiredetailView.setModel(model);
                
                app.to("repertoire");
            },
        error: function() {
        	console.log("error");
        }
        });
    },
    
    filterList: function(oEvent){  
        var like = oEvent.getParameter("newValue");  
        var oFilter = new sap.ui.model.Filter("title",   
                                                sap.ui.model.FilterOperator.Contains,   
                                                like);  
        var element = sap.ui.getCore().getElementById("repertoireList");  
        var listBinding = element.getBinding("items");  
        listBinding.filter([oFilter]);  
}

});