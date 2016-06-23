sap.ui.controller("bnote.taskadd",{
	

	setdirtyflag: function() {
		taskaddView.getController().dirty = true;
	},
	
	prepareModel: function() {
		taskaddView.getController().dirty = false;
		var oTaskadd = {
				title: "",
				description: "",
				due_at: "",
				Verantwortlicher: ""				
		};
		
		var model = new sap.ui.model.json.JSONModel(oTaskadd); 
		taskaddView.setModel(model);	
		
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getContacts"),
			 success: function(data) {
				 data.contacts.sort(function(a, b) {
					 return a.surname.localeCompare(b.surname);
				 });
				 var model = new sap.ui.model.json.JSONModel(data);
				 taskaddView.loadcontacts(model);
				 taskaddView.contactitems.setSelectedKey("0");
			 },
	         error: function() {
	        	 console.log("error loading contacts");
	         }			
	    });
		 sap.ui.getCore().byId("taskadd_due_at").setValue(null);
	},
	
	addTask: function() {
		if (taskaddView.getController().dirty){
			var model = taskaddView.getModel();			
			model.oData.Verantwortlicher = taskaddView.contactitems.getSelectedKey();
			
			// format the date values			
			var old_due_at = model.oData.due_at;			
			model.oData.due_at = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(old_due_at);
										
			 jQuery.ajax({
				 type: "POST",
			  	 data: model.oData,
	        	 url: backend.get_url("addTask"),
	        	 beforeSend: function() {
					 sap.ui.core.BusyIndicator.show(500);   
				 },
	        	 success: function(data) {		   
	        		 model.oData.id = data;
	        		 sap.m.MessageToast.show("Aufgabe erfolgreich gespeichert.")
	        		 reservationaddView.getController().dirty = false;
	        		 startView.getController().loadAllData();
	        		 app.to("start");
	             },
		         error: function() { 
		        	 sap.ui.core.BusyIndicator.hide(); 
		        	 sap.m.MessageToast.show("Speichern der Aufgabe fehlgeschlagen.");
		         }
		    });			
		}
		else {
			sap.m.MessageToast.show("Bitte fülle erst das Formular aus.");
		}
	}
	
	
});