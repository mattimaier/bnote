sap.ui.controller("bnote.signup", {

	setdirtyflag: function() {
		contactaddView.getController().dirty = true;
	},
	
	prepareModel: function() {
		contactaddView.getController().dirty = false;
		
		var oSignup = {
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
				oSignup.groups = data.group;
				var model = new sap.ui.model.json.JSONModel(oSignup);
				signupaddView.setModel(model);
			    
				for (var i = 0; model.getProperty("/groups/" + i + "/name") != undefined; i++) {
					signupaddView.signupForm.addContent(new sap.m.CheckBox({						
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
        		signupaddView.loadinstruments(instruments);
        	},
        	error : function(a,b,c){
        		console.log(a,b,c);
        	}
        })
        signupaddView.instrumentitems.setSelectedKey("23"); // 23 = Keine Angabe
	},
	
});