sap.ui.jsview("bnote.memberdetail", {
	
	getControllerName: function() {
		return "bnote.memberdetail";
	},
	
	createContent: function(){
		   jQuery.sap.require("sap.ui.core.IconPool");
		var memberdetailsForm = new sap.ui.layout.form.SimpleForm({
            title: "Kontaktdaten",
            content: [
                new sap.m.Label({text: "Name"}),
                new sap.m.Text({text: "{fullname}"}),
                
                
               
                new sap.m.Label({text: "Telefon"}),
                //new sap.m.Text({text: "{phone}"}),
                new sap.m.Button({
                	text: "{phone}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "phone" ),
                }),
               
                new sap.m.Label({text: "Handy"}),
                //new sap.m.Text({text: "{mobile}"}),
                new sap.m.Button({
                	text: "{mobile}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "iphone-2" ),
                }),
                
                new sap.m.Label({text: "Email"}),
                //new sap.m.Text({text: "{email}"}),
                new sap.m.Button({
                	text: "{email}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "email" ),
                }),
                
                new sap.m.Label({text: "Instrument"}),
                new sap.m.Text({text: "{instrument}"}),
            ]
        });
		
	var page = new sap.m.Page("memberdetailPage", {
        title: "Kontaktdaten",
        showNavButton: true,
        navButtonPress: function() {
            app.back();
        },
		content: [ memberdetailsForm ],
        footer: [  ]
	});
	return page;
	}
	
});