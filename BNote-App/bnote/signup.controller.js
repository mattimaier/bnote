sap.ui.controller("bnote.signup", {

	setdirtyflag: function() {
		signupView.getController().dirty = true;
	},
	
	prepareModel: function() {
		signupView.getController().dirty = false;
		
		var oSignup = {
				surname: "",
				name: "",
				phone: "",				
				email: "",
				instrument: "", 				
				street: "",
				city: "",
				zip: "",	
				login: "",
				pw1: "",
				pw2: "",
				terms: "",
		};		
		
        jQuery.ajax({
        	url : backend.get_url("getInstruments"),
        	type : "GET",
        	success : function(data){
        		var instruments = new sap.ui.model.json.JSONModel(data);
        		signupView.loadinstruments(instruments);
    			var model = new sap.ui.model.json.JSONModel(oSignup);
			    signupView.setModel(model);
        	},
        	error : function(a,b,c){
        		console.log(a,b,c);
        	}
        })
        signupView.instrumentitems.setSelectedKey("23"); // 23 = Keine Angabe
	},
	
	signup: function(){
		if (signupView.getController().dirty){
			var model = signupView.getModel();
			
			if (model.oData.terms){
				model.oData.instrument =  signupView.instrumentitems.getSelectedKey();
				
				 jQuery.ajax({	
					 type: "POST",
					 data: model.oData,
					 url: backend.get_url("signup"),
					 success: function(data) {
                         if(!data.success) {
                             sap.m.MessageToast.show(data.message);
                         }
                         else {
                             signupView.getController().dirty = false;
                             sap.m.MessageToast.show("Benutzer erfolgreich angelegt.");
                             app.to("start");
                         }
		             },
			         error: function() { 
			        	 sap.m.MessageToast.show("Benutzer konnte nicht angelegt werden.");
			         }
				 });	
			}
			else {
				 sap.m.MessageToast.show("Nutzerbedingungen wurden nicht zugestimmt.");
			}
		}
		else {
			sap.m.MessageToast.show("Bitte fülle erst die Felder aus.");
		}
	}

});