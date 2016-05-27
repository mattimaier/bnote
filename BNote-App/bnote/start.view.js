sap.ui.jsview("bnote.start", {
    
	getControllerName: function() {
		return "bnote.start";
	},
	
	createContent: function(oController) {
		var mainList = new sap.m.List({
            headerText: "Proben"
        });
		
        mainList.bindItems({
            path : "/rehearsals", 
            sorter : new sap.ui.model.Sorter("begin"),
            template : new sap.m.StandardListItem({
                title: "{begin}",
                icon: "icons/proben.png",
                description: "{name}",
                type: sap.m.ListType.Navigation,
                press: function(evt) {
                    var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
                    rehearsalView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
                    
                    var model = oBindingContext.getModel();
                    var path = oBindingContext.getPath();
                    var participate = model.getProperty(path + "/participate");
                    rehearsalView.setButtons(participate);
                    
                    app.to("rehearsal");
                }
            })
        });
        
		var page = new sap.m.Page("StartPage", {
            title: "Start",
            showNavButton: true,
            navButtonPress: oController.onLogout,
			content: [ mainList ]
		});
		
		return page;
	}
});