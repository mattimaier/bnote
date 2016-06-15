sap.ui.jsview("bnote.repertoire", {
	
	getControllerName: function() {
		return "bnote.repertoire";
	},
	
	createContent: function(){
		var repertoireList = new sap.m.List();
		
			repertoireList.bindItems({
	        	growingScrollToLoad : "true",
	            path : "/songs",
	            sorter : new sap.ui.model.Sorter("title"),
	            template : new sap.m.StandardListItem({
	                title: "{title}",
	                icon: "icons/music_folder.png",
	                description: "{composer}",
	                type: sap.m.ListType.Navigation,
	                press: function(evt) {
	                	  var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
	                      repertoiredetailView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
	                      app.to("repertoiredetail");
	                }
	            })
	        });
			
	var repertoireAddButton = new sap.m.Button({
		icon : sap.ui.core.IconPool.getIconURI("add"),
		press: function() {
			   var data = {};
			   var model = new sap.ui.model.json.JSONModel(data);
			   repertoireaddView.setModel(model);
			   repertoireaddView.getController().setData();
			   app.to("repertoireadd");
		}		
	});
	
		
		var page = new sap.m.Page("RepertoirePage", {
	        title: "Repertoire",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [ repertoireAddButton ],
			content: [ repertoireList ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	