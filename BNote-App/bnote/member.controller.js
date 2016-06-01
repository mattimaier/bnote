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
	
	
});