sap.ui.controller("bnote.reservationadd",{
	
	setdirtyflag: function() {
		reservationaddView.getController().dirty = true;
	},
	
	buildModel: function(){
		reservationaddView.getController().dirty = false;
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
			 type: "GET",
			 url: backend.get_url("getLocations"),
			 success: function(data) {
				 var locations = new sap.ui.model.json.JSONModel(data);
			     reservationaddView.loadlocations(locations);
             },
	         error: function() { 
	        	 console.log("error loading locations");
	         }
	    });		
		 
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getContacts"),
			 success: function(data) {
				 data.contact.sort(function(a, b) {
					 return a.surname.localeCompare(b.surname);
				 });
				 var model = new sap.ui.model.json.JSONModel(data);
				 reservationaddView.loadcontacts(model);	
			 },
	         error: function() {
	        	 console.log("error loading contacts");
	         }
	    });		 
	},
	
	checknewreservation: function(){	
		if (reservationaddView.locationitems.getSelectedKey() == "-1"){
			reservationaddView.locationaddForm.setVisible(true);
		}
		else {
			reservationaddView.locationaddForm.setVisible(false);
		}
	},
	
	addlocation: function(){
		var oLocation = {
				name: sap.ui.getCore().byId("locationaddname").getValue(),
				notes:  sap.ui.getCore().byId("locationaddnotes").getValue(),
				street:  sap.ui.getCore().byId("locationaddstreet").getValue(),
				city:  sap.ui.getCore().byId("locationaddcity").getValue(),
				zip:  sap.ui.getCore().byId("locationaddzip").getValue()
		};
		jQuery.ajax({
			 async: false,
			 type: "POST",
		  	 data: oLocation,
	       	 url: backend.get_url("addLocation"),
	       	 success: function(data) {
	       		var key = data;
	       		reservationaddView.locationitems.addItem(new sap.ui.core.Item({text : oLocation.name, key : key}));
	       		reservationaddView.locationitems.setSelectedKey(key);
	       		sap.m.MessageToast.show("Location erfolgreich gespeichert.");	
             },
	         error: function() { 
	        	 sap.m.MessageToast.show("Location konnte nicht gespeichert werden");
	         }
	    });
	},
	
	createReservation: function(){		
		if (reservationaddView.getController().dirty == true){			
				var model = reservationaddView.getModel();
				if (reservationaddView.locationitems.getSelectedKey() == "-1"){
					reservationaddView.getController().addlocation();
				}
				model.oData.location = reservationaddView.locationitems.getSelectedKey();
				model.oData.contact = reservationaddView.contactitems.getSelectedKey();
				
				// format the date
				var oldbegin = model.oData.begin;
				var oldend = model.oData.end;
				
				oldbegin = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm"}).format(oldbegin);
				oldend = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm"}).format(oldend);
				
				var emptyseconds = ":00";
				model.oData.begin = oldbegin.concat(emptyseconds);
				model.oData.end = oldend.concat(emptyseconds);
			
				
				 jQuery.ajax({
					 type: "POST",
				  	 data: model.oData,
		        	 url: backend.get_url("addReservation"),
		        	 success: function(data) {		   
		        		 model.oData.id = data;
		        		 sap.m.MessageToast.show("Reservierung erfolgreich gespeichert.")
		        		 reservationaddView.getController().dirty = false;
		        		 startView.getController().loadAllData();
		        		 app.to("start");
		             },
			         error: function() { 
			        	 sap.m.MessageToast.show("Speichern der Reservierung fehlgeschlagen.");
			         }
			    });
		}
		else{
			 sap.m.MessageToast.show("Bitte fülle erst das Formular aus.");
		}
	}
	
});