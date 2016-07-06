sap.ui.jsview("bnote.communication", {
	
	
	
	getControllerName: function() {
		return "bnote.communication";
	},	
	
	createContent: function(){		
		this.communicationForm = new sap.ui.layout.form.SimpleForm("communicationForm",{
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
						content:[
						         new sap.m.Label({text: "Betreff"}),
						         new sap.m.Input({
						        	 	type: sap.m.InputType.Text,
	 									valueLiveUpdate: true,
	  									value: "{/subject}"
						         }),
						         new sap.m.Label({text: "Nachricht"}),
							     new sap.m.TextArea({
							        	rows: 8,
							        	valueLiveUpdate: true,
							        	value: "{/body}",
							     }),
							     new sap.m.Label({text: "An"}),
			         ]
		})
		
		var page = new sap.m.Page("CommunicationPage", {
	        title: "Kommunikation",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ this.communicationForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	