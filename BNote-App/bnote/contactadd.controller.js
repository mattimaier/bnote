sap.ui.controller("bnote.contactadd",{
	
	

	prepareModel: function() {
		var oContact = {
				surname: "",
				name: "",
				phone: "",
				fax: "",
				mobile: "",
				business: "",
				email: "",
				web: "",
				notes: "",
				instrument: "", 
				birthday: "",
				street: "",
				city: "",
				zip: "",
				groups: {}
		};		
		
        jQuery.ajax({
			url : backend.get_url("getGroups"),
			type : "GET",
			success : function(data) {
				oContact.groups = data.group;
				var model = new sap.ui.model.json.JSONModel(oContact);
			    contactaddView.setModel(model);
				for (var i = 0; model.getProperty("/groups/" + i + "/name") != undefined; i++) {
					contactaddView.contactaddForm.addContent(new sap.m.CheckBox({
						text : model.getProperty("/groups/" + i + "/name"),
						selected: "{/groups/" + i + "/selected}"
					}));
				}
			},
        	error: function(a,b,c){
        		console.log(a,b,c);
        	}
        });	  
        
        jQuery.ajax({
        	url : backend.get_url("getInstruments"),
        	type : "GET",
        	success : function(data){
        		var instruments = new sap.ui.model.json.JSONModel(data);
        		contactaddView.loadinstruments(instruments);
        	},
        	error : function(a,b,c){
        		console.log(a,b,c);
        	}
        })
        contactaddView.instrumentitems.setSelectedKey("23"); // 23 = Keine Angabe
	},
	
	addContact: function(){
		var model = contactaddView.getModel();
		var groups = model.oData.groups;
		var groupids = [];
		for(var i = 0;i < groups.length;i++){
			if(groups[i].selected){
				model.oData["group_" + groups[i].id] = 1;
			}
		}	
		
		// format the date values
		var oldbirthday = model.oData.birthday;
		model.oData.birthday = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd"}).format(oldbirthday);
		
		model.oData.instrument =  contactaddView.instrumentitems.getSelectedKey();
		
		 jQuery.ajax({	
			 type: "POST",
			 data: model.oData,
			 url: backend.get_url("addContact"),
			 success: function(data) {
				 sap.m.MessageToast.show("Kontakt wurde erfolgreich gespeichert.");
				 app.to("start");
             },
	         error: function() { 
	        	 sap.m.MessageToast.show("Kontakt konnte nicht gespeichert werden.");
	         }
	    });		
	}
});