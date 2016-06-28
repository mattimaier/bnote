sap.ui.controller("bnote.reservationadd",{
	
	setdirtyflag: function() {
		reservationaddView.getController().dirty = true;
	},
	
	addlocation_setdirtyflag: function() {
		reservationaddView.getController().addlocation_dirty = true;		
	},
	
	prepareModel: function(){
		reservationaddView.getController().dirty = false;
		var oReservationadd = {
			begin: "",
			end: "",
			name: "",
			location: "",
			contact: "",
			notes: "",	
   		};		 
		var model = new sap.ui.model.json.JSONModel(oReservationadd); 
		reservationaddView.setModel(model);	
		 
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getLocations"),
			 success: function(data) {
				 var locations = new sap.ui.model.json.JSONModel(data);
			     reservationaddView.loadlocations(locations);			     
			     reservationaddView.locationitems.setSelectedKey(-1); 
			 	 reservationaddView.getController().addlocation_dirty = false;		
			 	
             },
	         error: function() { 
	        	 console.log("error loading locations");
	         }
	    });		
		 
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getContacts"),
			 success: function(data) {
				 data.contacts.sort(function(a, b) {
					 return a.surname.localeCompare(b.surname);
				 });
				 var model = new sap.ui.model.json.JSONModel(data);
				 reservationaddView.loadcontacts(model);	
				 reservationaddView.contactitems.setSelectedKey(-1);
			 },
	         error: function() {
	        	 console.log("error loading contacts");
	         }
	    });		 
		sap.ui.getCore().byId("reservationadd_begin").setValue(null);
		sap.ui.getCore().byId("reservationadd_end").setValue(null)
		
	},
	
	checknewlocation: function(){	
		if (reservationaddView.locationitems.getSelectedKey() == "-1"){
			reservationaddView.locationaddForm.setVisible(true);			
			reservationaddView.getController().addlocation_dirty = false;
		}
		else {
			reservationaddView.locationaddForm.setVisible(false);
		}
	},
	
	addlocation: function(){
		var oLocation = {
				name: sap.ui.getCore().byId("reservationadd_addlocation_name").getValue(),
				notes:  sap.ui.getCore().byId("reservationadd_addlocation_notes").getValue(),
				street:  sap.ui.getCore().byId("reservationadd_addlocation_street").getValue(),
				city:  sap.ui.getCore().byId("reservationadd_addlocation_city").getValue(),
				zip:  sap.ui.getCore().byId("reservationadd_addlocation_zip").getValue()
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
	       		reservationaddView.getController().addlocation_dirty = false;
             },
	         error: function() { 
	        	 sap.m.MessageToast.show("Location konnte nicht gespeichert werden");
	         }
	    });
	},
	
	addReservation: function(){		
		if (reservationaddView.getController().dirty == true){			
				var model = reservationaddView.getModel();
				if (reservationaddView.locationitems.getSelectedKey() == "-1" && reservationaddView.getController().addlocation_dirty == true){
					reservationaddView.getController().addlocation();
				}
				else if (reservationaddView.locationitems.getSelectedKey() == "-1"){
					sap.m.MessageToast.show("Keine neue Location eingegeben.");
					return;
				}
				model.oData.location = reservationaddView.locationitems.getSelectedKey();
				model.oData.contact = reservationaddView.contactitems.getSelectedKey();
				
				// format the date values
				var old_begin = model.oData.begin;
				var old_end = model.oData.end;
				
				model.oData.begin = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(old_begin);
				model.oData.end = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(old_end);
								
				 jQuery.ajax({
					 type: "POST",
				  	 data: model.oData,
		        	 url: backend.get_url("addReservation"),
		        	 beforeSend: function() {
						 sap.ui.core.BusyIndicator.show(500);   
					 },
		        	 success: function(data) {		   
		        		 model.oData.id = data;
		        		 sap.m.MessageToast.show("Reservierung erfolgreich gespeichert.");		        		
		        		 startView.getController().loadAllData();
		        		 app.to("start");
		             },
			         error: function() { 
			        	 sap.ui.core.BusyIndicator.hide(); 
			        	 sap.m.MessageToast.show("Speichern der Reservierung fehlgeschlagen.");
			         }
			    });
		}
		else{
			 sap.m.MessageToast.show("Bitte fülle erst das Formular aus.");
		}
	}
	
});