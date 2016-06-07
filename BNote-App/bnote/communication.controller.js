sap.ui.controller("bnote.communication",{
	
	onInit: function() {
        var data = {
            subject: "",
            message: ""
        }
        var model = new sap.ui.model.json.JSONModel(data);
        this.getView().communicationForm.setModel(model);
    
	}
});