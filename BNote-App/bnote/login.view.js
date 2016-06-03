sap.ui.jsview("bnote.login", {
    
    login_form: null,

	getControllerName: function() {
		return "bnote.login";
	},
	
	createContent: function(oController) {        
		this.loginForm = new sap.ui.layout.form.SimpleForm({
            content: [
                // login
                new sap.m.Label({text: "Benutzername / E-Mail-Adresse"}),
                new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/login}",
                    valueLiveUpdate: true
                }),
                
                // end
                new sap.m.Label({text: "Password"}),
                new sap.m.Input({
                    type: sap.m.InputType.Password,
                    value: "{/password}",
                    valueLiveUpdate: true
                }),
                
                // submit
                new sap.m.Label({text: ""}),  // spacer
                new sap.m.Button({
                    text: "Login",
                    press: oController.onLoginPress
                })
            ]
        });

		var bnoteImg = new sap.m.Image({
			src: "img/BNote_Logo_blue_on_white_192px.png",
			height: "128px"
		});
		var logo_layout = new sap.m.HBox({
			justifyContent: sap.m.FlexJustifyContent.SpaceAround,
			alignItems: sap.m.FlexAlignItems.Center,
			items: [bnoteImg]
		});
		logo_layout.addStyleClass("bnote_logo")
		
		var page = new sap.m.Page("LoginPage", {
            title: "Login",
			content: [ logo_layout, this.loginForm ]
		});
		
		return page;
	}
});