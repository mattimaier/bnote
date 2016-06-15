sap.ui.controller("bnote.repertoire", {

	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getSongs"),
        	success: function(data) {
        		console.log(data);
                var model = new sap.ui.model.json.JSONModel(data);
                oCtrl.getView().setModel(model);
                repertoiredetailView.setModel(model);
            }
        });
    },

});