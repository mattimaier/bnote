sap.ui.jsview("bnote.taskadd",{
	
	getControllerName: function() {
		return "bnote.taskadd";
	},
	
	loadcontacts: function(contacts){		
		this.contactitems.destroyItems();
		
		for(var i=0; i < contacts.getProperty("/contacts").length; i++){
			var name = contacts.getProperty("/contacts/" + i + "/name");
			var surname = contacts.getProperty("/contacts/" + i + "/surname");
			var key = contacts.getProperty("/contacts/" + i + "/id");
			this.contactitems.addItem(new sap.ui.core.Item({text : surname + "," + " " +  name, key : key}));
		};
	},
	
	createContent: function(oController){		
		var view = this;
		
		this.contactitems = new sap.m.Select({
			change: oController.setdirtyflag,
			items: []			
			});
		
		this.taskaddForm = new sap.ui.layout.form.SimpleForm({
		    layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,				    
        	content:[
        	         new sap.m.Label({text: "Titel"}),	
        	         new sap.m.Input({
				    	 value: "{/title}",
				    	 change: oController.setdirtyflag,
				         valueLiveUpdate: true
				         }),				         
			         new sap.m.Label({text: "Beschreibung"}),	
        	         new sap.m.Input({
				    	 value: "{/description}",
				    	 change: oController.setdirtyflag,
				         valueLiveUpdate: true
				         }),
			         new sap.m.Label({text: "Erledigen bis"}),
			         new sap.m.DateTimeInput("taskadd_due_at",{
        	        	 type: sap.m.DateTimeInputType.DateTime,		        	        	 
 					     change: oController.setdirtyflag,
 						 dateValue: "{/due_at}"
 					 }),			  
			         new sap.m.Label({text: "Verantwortlicher"}),
				     this.contactitems,
        	         ]
		});		
		
		var createTaskButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),			
			press: oController.addTask
		});
		
		var page = new sap.m.Page("TaskaddPage", {
            title: "Aufgabe hinzufügen",
            showNavButton: true,
            navButtonPress: function() {
            	view.getModel().destroy();
                app.back();
            },
            headerContent : [ createTaskButton ],
			content: [ this.taskaddForm ],
			footer: [getNaviBar()]
		});
		return page;
	}
});