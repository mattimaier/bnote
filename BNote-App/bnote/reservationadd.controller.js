sap.ui.controller("bnote.reservationadd",{
	
	buildModel: function(){
		 var oData = {
					begin: "",
					end: "",
					name: "",
					location: "",
					contact: "",
					notes: "",	
		   		};
		 var model = new sap.ui.model.json.JSONModel(oData); 
		 reservationaddView.setModel(model);	
		 
		 jQuery.ajax({	
			 type: "POST",
			 url: backend.get_url("getLocations"),
			 success: function(data) {
			 var locations = new sap.ui.model.json.JSONModel(data);
			 console.log(locations);
		     reservationaddView.loadlocations(locations);		   
             },
	         error: function() { 
	        	 console.log("error");
	         }
	    });
	},
	
	createReservation: function(){
		
		var model = reservationaddView.getModel();
		console.log(model.oData);
		
		 jQuery.ajax({
			 type: "POST",
		  	 data: model.oData,
        	 url: backend.get_url("addReservation"),
        	 success: function(data) {
             },
	         error: function() { 
	        	 console.log("error");
	         }
	    });
		
	}
	
});