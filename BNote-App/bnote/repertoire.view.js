sap.ui.jsview("bnote.repertoire", {
	
	getControllerName: function() {
		return "bnote.repertoire";
	},
	
	createContent: function(oController) {
		var repertoireSearch = new sap.m.SearchField("repertoireSearch",{ 
	        tooltip: "Repertoire durchsuchen",  
	        liveChange: oController.filterList  
	  });  
		
		var repertoireList = new sap.m.List("repertoireList");
		
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
	                      repertoiredetailView.getController().mode = "edit";
	                      app.to("repertoiredetail");
	                }
	            })
	        });
			
	var repertoireAddButton = new sap.m.Button({
		icon : sap.ui.core.IconPool.getIconURI("add"),
		press: function() {
			var model = repertoireView.getModel();
				 var emptydata = {
						 id: -1,
						   title: "",
						   length: "00:00:00",
						   bpm: "",
						   music_key: "",
						   notes: "",
						   genre: {
								   id: "",
								   name: ""
						   },
						   composer: "",
						   status: {
							   id: "",
							   name: ""
						   }
			   		};
				 
		         model.oData.songs.push(emptydata);
		         var newindex = model.oData.songs.length -1;
		         var bcEmpty = model.createBindingContext("/songs/" + newindex);
			     repertoireaddView.setModel(model);
			     repertoireaddView.setBindingContext(bcEmpty);
			     repertoireaddView.getController().setData();
			     repertoireaddView.getController().mode = "add";
			     app.to("repertoireadd");
		}		
	});
		
		var page = new sap.m.Page("RepertoirePage", {
	        title: "Repertoire",
	        showNavButton: true,
	        navButtonPress: function() {
	        	app.back()
	        },
	        headerContent: [ repertoireAddButton ],
			content: [ repertoireSearch, repertoireList ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	