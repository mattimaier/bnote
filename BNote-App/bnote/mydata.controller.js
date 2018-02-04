sap.ui.controller("bnote.mydata", {

	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getContact"),
        	success: function(data) {
                var model = new sap.ui.model.json.JSONModel(data);
                oCtrl.getView().setModel(model);
            },
            error: function(a,b,c) {
            	console.log(b,c);
            }
        });
    },

});