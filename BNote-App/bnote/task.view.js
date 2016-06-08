sap.ui.jsview("bnote.task", {

	getControllerName: function() {
		return "bnote.task";
	},
	
	createContent: function(oController){
		
		var taskForm = new sap.ui.layout.form.SimpleForm({
            title: "{title}",
            content: [
                new sap.m.Label({text: "Erstellt von"}),
                new sap.m.Text({text: "{creator}"}),
                
                new sap.m.Label({text: "Beschreibung"}),
                new sap.m.Text({text: "{description}"}),
                
                new sap.m.Label({text: "Deadline"}),
                new sap.m.Text({text: "{due_at}"}),
                
                new sap.m.Label({text: "Verbleibende Zeit"}),
                new sap.m.Text({text: "{remainingtime}"}),
                
                new sap.m.Text({text: "\u00a0"}),
                new sap.m.Button({
                		text: "Aufgabe erledigt",
                		press: function(){
                			oController.ontaskDoneBtnpress();
                		}
                }),
            ]
        });
	
	var page = new sap.m.Page("TaskPage", {
		title : "Aufgaben",
		showNavButton : true,			
		navButtonPress : function(){
			app.back()
		},
		headerContent : [  ],
		content : [ taskForm ],
		footer : [  ]
	});
	return page;
	},
});