sap.ui.jsview("bnote.repertoiredetail", {
	
	getControllerName: function() {
		return "bnote.repertoiredetail";
	},
	
	createContent: function(){
		var repertoiredetailForm = new sap.ui.layout.form.SimpleForm({
            title: "",
            content: [
                      new sap.m.Label({text: "Name"}),
                      new sap.m.Text({text: "{title}"}),  
                      
                      new sap.m.Label({text: "Komponist / Arrangeur"}),
                      new sap.m.Text({text: "{composer}"}),
                      
                      new sap.m.Label({text: "Länge"}),
                      new sap.m.Text({text: "{length}"}),
                      
                      new sap.m.Label({text: "Tonart"}),
                      new sap.m.Text({text: "{music-key}"}),
                      
                      new sap.m.Label({text: "Genre"}),
                      new sap.m.Text({text: "{genre/name}"}),
                      
                      new sap.m.Label({text: "Tempo (bpm)"}),
                      new sap.m.Text({text: "{bpm}"}),
                      
                      new sap.m.Label({text: "Notizen"}),
                      new sap.m.Text({text: "{notes}"}),
                      
                      new sap.m.Label({text: "Status"}),
                      new sap.m.Text({text: "{status/name}"})
                   ]
		});
		
		var repertoireUpdateButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("edit"),
			press: function(){
				repertoireaddView.setModel(this.getModel());
				repertoireaddView.setBindingContext(this.getBindingContext());
				repertoireaddView.getController().setData();
				app.to("repertoireadd");
			}
		});
		
		var repertoireDeleteButton = new sap.m.Button({
			icon : sap.ui.core.IconPool.getIconURI("delete"),
			
		});
		
		var page = new sap.m.Page("RepertoiredetailPage", {
	        title: "Repertoiredetail",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [ repertoireUpdateButton, repertoireDeleteButton ],
			content: [ repertoiredetailForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	