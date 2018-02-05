sap.ui.jsview("bnote.share", {
    
    getControllerName: function() {
		return "bnote.share";
	},
    
    createContent: function() {
        var page = new sap.m.Page("sharePage", {
	        title: "Share",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ 
                new sap.ui.core.HTML({
                    content: "{/framecode}"
                })
            ],
	        footer: [ getNaviBar() ]
		});
        
		return page;
    }
});