sap.ui.controller("bnote.start", {
	
	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getRehearsalsWithParticipation"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
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