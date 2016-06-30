sap.ui.jsview("bnote.songstopractise", {

	getControllerName : function() {
		return "bnote.songstopractise";
	},

	createContent : function(oController) {
		
		this.songList = new sap.m.List();
		
		var page = new sap.m.Page("SongstopracticePage", {
			showNavButton: true,
		    navButtonPress: function() {
		    	app.back();
		    },
			title : "Songs zum Üben",
			customHeader : [],
			content : [],
			footer : [ getNaviBar() ]
		});
		return page;
	}
});
	