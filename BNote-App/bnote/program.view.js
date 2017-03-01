sap.ui.jsview("bnote.program", {

	getControllerName: function() {
		return "bnote.program";
	},
    
    createContent: function() {
        var songList = new sap.m.List();
        
        var itemTemplate = new sap.m.StandardListItem({
			title : "{title}",
			description : "{composer}"
        });
        
        songList.bindItems({
			growingScrollToLoad : "true",
			path : "/songs",
			template : itemTemplate 
		});
        
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