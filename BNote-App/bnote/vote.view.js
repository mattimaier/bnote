sap.ui.jsview("bnote.vote", {

	getControllerName : function() {
		return "bnote.vote";
	},
	
	createContent: function(oController){
		
		
		
		this.voteinfoForm = new sap.ui.layout.form.SimpleForm({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
            title: "{name}",
            content: [
                new sap.m.Label({text: "Beschreibung"}),
                new sap.m.Text({text: "{description}"}),
                
                new sap.m.Label({text: "Abstimmungsende"}),
                new sap.m.Text({text: "{end}"}),
                
                new sap.m.Label({text: "Verbleibende Zeit"}),
                new sap.m.Text({text: "{remainingtime}"}),
                
                
            ]
        });
		
		this.voteForm = new sap.ui.layout.form.SimpleForm({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
            title: "Abstimmung",
            content: [] 
		});
		
		var page = new sap.m.Page("VotePage", {
			title : "",
			showNavButton : true,			
			navButtonPress : function(){
			voteView.voteForm.destroyContent();
			app.back();
			},
			headerContent : [ ],
			content : [ this.voteinfoForm, this.voteForm ]
		});
		return page;
	}
});
