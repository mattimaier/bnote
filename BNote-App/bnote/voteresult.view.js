sap.ui.jsview("bnote.voteresult", {
	
	getControllerName: function() {
		return "bnote.voteresult";
	},	
	
	createContent: function() {
		var voteResultForm = new sap.ui.layout.form.SimpleForm({
			content:[			         
					new sap.m.Label({text: "Beginn"}),
					new sap.m.Text({text: "{begin}"}),
					
					new sap.m.Label({text: "Ende"}),
			        new sap.m.Text({text: "{end}"}),

			        new sap.m.Label({text: "Name"}),
			        new sap.m.Text({text: "{name}"}),
			         
			        new sap.m.Label({text: "Ort"}),
			        new sap.m.Text({text: "{location/name}"}),
			        
			        new sap.m.Label({text: "Kontakt"}),
			        new sap.m.Text({text: "{contact/fullname}"}),
			        
			        new sap.m.Label({text: "Notizen"}),
			        new sap.m.Text({text: "{notes}"}),
			       ]  
		});
		
		
		var page = new sap.m.Page("VoteresultPage", {
            title: "Abstimmungsergebnis",
            showNavButton: true,
            navButtonPress: function() {
                app.back();
            },
			content: [],
			footer: [getNaviBar()]
		});		
		return page;
	}		
});
	