sap.ui.controller("bnote.login", {
    
	onInit: function() {		
		if (prefs.fetch("bnoteserver_adress") != undefined || prefs.fetch("username") != undefined ){
			sap.ui.getCore().byId("saveLoginCheckBox").setSelected(true);
			logindata = {
		        	bnoteserver_adress: prefs.fetch("bnoteserver_adress"),
		            login:  prefs.fetch("username"),
		            password: "",
		        }
		} else {
			sap.ui.getCore().byId("saveLoginCheckBox").getSelected(false);
		}
		
		
		
        var model = new sap.ui.model.json.JSONModel(logindata);
        this.getView().loginForm.setModel(model);
    },
        
    onLoginPress: function(oEvent) {
        var thisCtrl = this;
        if(sap.ui.getCore().byId("saveLoginCheckBox").getSelected()){
	    	prefs.store("bnoteserver_address", logindata.bnoteserver_adress);
	    	prefs.store("username", logindata.login);	    	
    	}else {
    		prefs.remove("bnoteserver_adress");
    		prefs.remove("username");
    	}
        
        // check credentials and get mobilePin (set to global var)
        var model = oEvent.getSource().getParent().getModel();
        var login = model.getProperty("/login");
        var pw = model.getProperty("/password");
        bnoteserver_address = model.getProperty("/bnoteserver_address");
               
        jQuery.ajax({
        	url: backend.get_url("mobilePin"),
            type: "POST",          	         
            data:  {"login": login, "password": pw},            
            beforeSend: function (){ 
            	 sap.ui.core.BusyIndicator.show(500);   
            },
            success: function(data) {
            	mobilePin = data;
            	setPermissions();
            	startView.getController().loadAllData();
                app.to("start");
            },
            error: function(a,b,c) {
            	sap.ui.core.BusyIndicator.hide();
                sap.m.MessageToast.show("Anmeldung fehlgeschlagen");
                console.log(b + ": " + c);
            }
        });
    }
	
});