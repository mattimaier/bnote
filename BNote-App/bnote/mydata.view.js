sap.ui.jsview("bnote.mydata", {

	getControllerName: function() {
		return "bnote.mydata";
	},
	
	createContent: function() {
        // main form
		this.mydataForm = new sap.ui.layout.form.SimpleForm("myDataForm", {
            content: [
                new sap.m.Label({text: "Vorname"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/name}"
                }),
                new sap.m.Label({text: "Nachname"}),
                new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/surname}"
                }),
                new sap.m.Label({text: "Spitzname"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/nickname}"
                }),
                new sap.m.Label({text: "Phone"}),
				new sap.m.Input({
                    type: sap.m.InputType.Tel,
                    value: "{/phone}"
                }),
                new sap.m.Label({text: "Fax"}),
				new sap.m.Input({
                    type: sap.m.InputType.Tel,
                    value: "{/fax}"
                }),
                new sap.m.Label({text: "Mobil"}),
				new sap.m.Input({
                    type: sap.m.InputType.Tel,
                    value: "{/mobile}"
                }),
                new sap.m.Label({text: "Geschäftlich"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/business}"
                }),
                new sap.m.Label({text: "E-Mail"}),
				new sap.m.Input({
                    type: sap.m.InputType.Email,
                    value: "{/email}"
                }),
                new sap.m.Label({text: "Website"}),
				new sap.m.Input({
                    type: sap.m.InputType.Url,
                    value: "{/web}"
                }),
                new sap.m.Label({text: "Instrument"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/instrument_object/name}",
                    editable: false
                }),
                new sap.m.Label({text: "Geburtstag"}),
				new sap.m.Input({
                    type: sap.m.InputType.Date,
                    value: "{/birthday}"
                }),
                new sap.m.Label({text: "Straße"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/address_object/street}"
                }),
                new sap.m.Label({text: "PLZ"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/address_object/zip}"
                }),
                new sap.m.Label({text: "Ort"}),
				new sap.m.Input({
                    type: sap.m.InputType.Text,
                    value: "{/address_object/city}"
                })
             ]
        });
        
        var saveButton = new sap.m.Button({
            icon : sap.ui.core.IconPool.getIconURI("save"),
            press: function() {
                jQuery.ajax({
                    url: backend.get_url("updateContact"),
                    method: "POST",
                    data: mydataView.getModel().getData(),
                    success: function(data) {
                        sap.m.MessageToast.show("Kontaktedaten aktualisiert");
                    },
                    error: function(a,b,c) {
                        console.log(b,c);
                    }
                });
            }
        });

		var page = new sap.m.Page("mydataPage", {
	        title: "Meine Kontaktdaten",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
            headerContent: [ saveButton ],
			content: [ this.mydataForm ],
	        footer: [ getNaviBar() ]
		});
        
		return page;
	}
});	