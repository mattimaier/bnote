sap.ui.jsview("bnote.memberdetail", {
	
	getControllerName: function() {
		return "bnote.memberdetail";
	},
	
	setDataVisibility: function (dataVisibility) {
        this.phoneButton.setVisible(dataVisibility[0] != "");
    	this.mobileButton.setVisible(dataVisibility[1] != "");
        this.emailButton.setVisible(dataVisibility[2] != "");
	},
	
	createContent: function() {
	    jQuery.sap.require("sap.ui.core.IconPool");
		var memberdetailsForm = new sap.ui.layout.form.SimpleForm({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
            title: "Kontaktdaten",
            content: [
                new sap.m.Label({text: "Name"}),
                new sap.m.Text({text: "{fullname}"}),  
                
                new sap.m.Label({text: "Spitzname"}),
                new sap.m.Text({text: "{nickname}"}),  
                
                new sap.m.Label({text: "Organisation"}),
                new sap.m.Text({text: "{company}"}),  
                
                new sap.m.Label({text: "Instrument"}),
                new sap.m.Text({text: "{instrumentname}"}),
                
                new sap.m.Label({text: "Adresse"}),
                new sap.m.VBox({
                    items: [
                        new sap.m.Text({text: "{street}"}),
                        new sap.m.Text({text: "{city}"})
                    ]
                }),
           
                new sap.m.Label({text: "Telefon"}),
                this.phoneButton = new sap.m.Button({
                	text: "{phone}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "phone" ),
                	press: function(){
                		window.location = "tel:" + this.getText()
                	}
                }),
               
                new sap.m.Label({text: "Mobil"}),
                this.mobileButton = new sap.m.Button({
                	text: "{mobile}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "iphone-2" ),
                	press:  function(){
                		window.location = "tel:" + this.getText()
                	}
                }),
                
                new sap.m.Label({text: "E-Mail"}),
                this.emailButton = new sap.m.Button({
                	text: "{email}",
                	width: "100%",
                	icon: sap.ui.core.IconPool.getIconURI( "email" ),
                	press:  function(){
                		window.location = "mailto:" + this.getText()
                	}
                }),
            ]
        });
		
	var page = new sap.m.Page("memberdetailPage", {
        title: "Kontaktdaten",
        showNavButton: true,
        navButtonPress: function() {
            app.back();
        },
		content: [ memberdetailsForm ],
        footer: [ getNaviBar() ]
	});
	return page;
	}
	
});