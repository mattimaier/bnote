sap.ui.jsview("bnote.communication", {
	
	getControllerName: function() {
		return "bnote.communication";
	},
	
	//This function is dynamically adding a checkbox for every group
	setCheckboxVisibility : function(model) {
		this.setModel(model);
		for (i = 1; model.getProperty("/group/" + i + "/name") != undefined; i++) {
			console.log(model.getProperty("/group/" + i + "/name"));
			this.communicationForm.addContent(new sap.m.CheckBox({
				text : model.getProperty("/group/" + i + "/name"),
				selected: "{/group/" + i + "/selected}"
			}));
		}
		this.communicationForm.addContent(new sap.m.Button({
			text : "Senden",
			press: function(){
				var subject = model.getProperty("/subject");
				var body = model.getProperty("/body");
				var groups = model.getProperty("/group");
				console.log(subject,body,groups);
				
			}
		}))
	},

	createContent: function(){
		
		this.communicationForm = new sap.ui.layout.form.SimpleForm({
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
	        enableScrolling: true,
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ this.communicationForm ],
	        footer: [ naviBar ]
		});
		return page;
	}	
});

	