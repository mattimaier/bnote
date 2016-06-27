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
        		var path = repertoiredetailView.getBindingContext().sPath.split("/");
        		var idxDelItem = path[path.length -1];	        		
        		model.oData.songs.splice(idxDelItem, 1);
        		model.setProperty("/songs", model.oData.songs); 
        		app.to("repertoire");
            },
	        error: function() {        	
	        	sap.m.MessageToast.show("Löschen derzeit nicht möglich. Bitte Internetverbindung überprüfen.");
	        }
	   });
	}
});