sap.ui.controller("bnote.communication",{
	
	onemailClick: function() {
        jQuery.ajax({
			url : backend.get_url("getGroups"),
			type : "POST",			
			success : function(data) {
				
				  var model = new sap.ui.model.json.JSONModel(data);
				 
				 communicationView.setCheckboxVisibility(model);
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});
        
        
	}
});