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
	        		var BindingContext = repertoiredetailView.getBindingContext().oModel.sPath;
	        		var spliced = model.oData.songs.splice(BindingContext, 1)
	        		model.setProperty("/songs", model.oData.songs); 
	        		app.to("repertoire");
	                console.log("success");
	            },
	        error: function() {
	        	console.log("error");
	        }
	        });
				
	}
	
});