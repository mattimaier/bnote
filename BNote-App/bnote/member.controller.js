sap.ui.controller("bnote.member", {
	
	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getContacts"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
                oCtrl.getView().setModel(model);
                memberdetailView.setModel(model);
            }
        });
    },
	

    filterList: function(oEvent){  
              var like = oEvent.getParameter("newValue");  
              var oFilter = new sap.ui.model.Filter("fullname",   
                                                      sap.ui.model.FilterOperator.Contains,   
                                                      like);  
              var element = sap.ui.getCore().getElementById("memberList");  
              var listBinding = element.getBinding("items");  
              listBinding.filter([oFilter]);  
    }
	
});