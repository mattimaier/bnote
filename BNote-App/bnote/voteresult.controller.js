sap.ui.controller("bnote.voteresult",{
	
	getVoteResult: function(voteid) {
		var oCtrl = this;	
		
		
		jQuery.ajax({	
			 type: "GET",
			 data: {"id": voteid},
			 url: backend.get_url("getVoteResult"),
			 success: function(data) {
				 var model = new sap.ui.model.json.JSONModel(data);
				
				 // format dates
				 if (model.oData.is_date == 1){			
						backend.formatdate("/options", "/name", model);			
					}
				 oCtrl.getView().setModel()
			 },
	         error: function() {
	        	 console.log("error loading voteresults");
	        }
	   });		
	}
});