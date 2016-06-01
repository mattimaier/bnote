sap.ui.controller("bnote.start", {
	
	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getRehearsalsWithParticipation"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
               
                backend.formatdate("/begin", model);
                backend.formatdate("/end", model);
              
                oCtrl.getView().setModel(model);
                rehearsalView.setModel(model);
                
            }
        });
    },
    
    onLogout: function() {
        mobilePin = null;
        app.to("login");
    }
	
});