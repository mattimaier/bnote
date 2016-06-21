sap.ui.jsview("bnote.reservation", {

	getControllerName : function() {
		return "bnote.reservation";
	},
	
	createContent: function() {
		
		var reservationForm = new sap.ui.layout.form.SimpleForm({
			content:[			         
					new sap.m.Label({text: "Beginn"}),
					new sap.m.Text({text: "{begin}"}),
					
					new sap.m.Label({text: "Ende"}),
			        new sap.m.Text({text: "{end}"}),

			        new sap.m.Label({text: "Name"}),
			        new sap.m.Text({text: "{text}"}),
			         
			        new sap.m.Label({text: "Ort"}),
			        new sap.m.Text({text: "{location}"}),
			        
			        new sap.m.Label({text: "Kontakt"}),
			        new sap.m.Text({text: "{contact}"}),
			        
			        new sap.m.Label({text: "Notizen"}),
			        new sap.m.Text({text: "{notes}"}),
			       ]  
		});
		
		var page = new sap.m.Page("ReservationPage", {
	        title: "Reservierungen",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [],
			content: [ reservationForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}		
});