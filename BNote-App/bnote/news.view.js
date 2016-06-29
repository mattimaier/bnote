sap.ui.jsview("bnote.news", {
	
	htmlView: null,

	getControllerName: function() {
		return "bnote.news";
	},

	setNewsData: function(data) {
		this.htmlView.setContent('<div style="padding: 10px;">' + data + "</div>");
	},
	
	createContent: function() {
		this.htmlView = new sap.ui.core.HTML({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
			content: ""
		});

		var page = new sap.m.Page("newsPage", {
	        title: "Nachrichten",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ this.htmlView ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}
});	