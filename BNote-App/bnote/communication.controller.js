sap.ui.controller("bnote.communication",{
	
	onEmailClick: function() {
        jQuery.ajax({
			url : backend.get_url("getGroups"),
			type : "POST",			
			success : function(data) {
				  var model = new sap.ui.model.json.JSONModel(data);
				  communicationView.setModel(model);
				  app.to("communication");
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});
        
        
	}
});