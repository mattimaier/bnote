sap.ui.controller("bnote.login", {
    
	onInit: function() {
        var data = {
            login: "",
            password: ""
        }
        var model = new sap.ui.model.json.JSONModel(data);
        this.getView().loginForm.setModel(model);
    },
    
    onLoginPress: function(oEvent) {
        // check credentials and get mobilePin (set to global var)
        var model = oEvent.getSource().getParent().getModel();
        var login = model.getProperty("/login");
        var pw = model.getProperty("/password");
      
        
        jQuery.ajax({
        	url: "data/login.txt", // backend.get_url("mobilePin"),
            type: "POST",          	         
            data:  {"login": login, "password": pw},
            success: function(data) {
            	mobilePin = data;
                app.to("start");
            },
            error: function(a,b,c) {
               console.log(a,b,c);
               
            }
        });
    }
	
});