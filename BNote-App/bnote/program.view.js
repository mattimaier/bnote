sap.ui.jsview("bnote.program", {

	getControllerName: function() {
		return "bnote.program";
	},
    
    createContent: function() {
        var songList = new sap.m.Label("Hello");
        
        var page = new sap.m.Page("ProgramPage", {			
            title: "Programm",
            showNavButton: true,
            navButtonPress: function() {
                app.back();
            },
			content: [
                songList
            ],
			footer: [getNaviBar()]
		});
		return page;
    }
});