sap.ui.controller("bnote.login", {
    
	onInit: function() {
        var data = {
            login: null,
            password: null,
            save: false
        }
      
        
        if (typeof(Storage) !== "undefined" && localStorage.getItem("username") != undefined) {        	
            data.login = localStorage.getItem("username");
            data.save = true;
        }
        
        var model = new sap.ui.model.json.JSONModel(data);
        this.getView().loginForm.setModel(model);
    },
    
    onAfterRendering: function() {
        //document.getElementById("loginField-inner").setAttribute("autocomplete","off");
    },
    
    onLoginPress: function(oEvent) {
        var thisCtrl = this;
        // check credentials and get mobilePin (set to global var)
        var model = oEvent.getSource().getParent().getModel();
        var login = model.getProperty("/login");
        var pw = model.getProperty("/password");
        
        if (model.oData.save){
        	localStorage.username = login;
        } 
        else {
        	localStorage.removeItem("username");
        }
        	
               
        jQuery.ajax({
        	url: backend.get_url("mobilePin"),
            type: "POST",          	         
            data:  {"login": login, "password": pw},            
            beforeSend: function (){ 
            	 sap.ui.core.BusyIndicator.show(500);   
            },
            success: function(data) {
            	mobilePin = data;
            	username = login;
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