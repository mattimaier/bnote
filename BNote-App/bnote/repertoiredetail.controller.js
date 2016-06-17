sap.ui.controller("bnote.repertoiredetail", {	
	
	deleteSong: function(){
		var model = repertoiredetailView.getModel();
		var path = repertoiredetailView.getBindingContext().getPath();
		var songid = model.getProperty(path + "/id")
		
		 jQuery.ajax({
			 	type:"POST",
	        	url: backend.get_url("deleteSong"),
	        	data: {"id": songid},
	        	success: function(data) {
	                console.log("success");
	            },
	        error: function() {
	        	console.log("error");
	        }
	        });
	}
	
});