sap.ui.jsview("bnote.member", {
	
	getControllerName: function() {
		return "bnote.member";
	},
	 
	createContent: function(oController){	
		var memberSearch = new sap.m.SearchField("memberSearch",{ 
			tooltip: "Liste durchsuchen",  
	        liveChange: oController.filterList  
		});
		
		var memberList = new sap.m.List("memberList");
		
		memberList.bindItems({			
	    	growingScrollToLoad : "true",
	        path : "/contacts",
	        sorter : new sap.ui.model.Sorter("name"),
	        template : new sap.m.StandardListItem({
	            title: "{fullname}",
	            icon: "{icon}",
	            description: "{instrumentname}",
	            type: sap.m.ListType.Navigation,
	            press: function(evt) {
	            	  var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
	                  memberdetailView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
	                 
	                  var model = oBindingContext.getModel();
	                  var path = oBindingContext.getPath();
	                  var dataVisibility = [model.getProperty(path + "/phone") , model.getProperty(path + "/mobile") , model.getProperty(path + "/email")];
	                  memberdetailView.setDataVisibility(dataVisibility);
	                  
	                  app.to("memberdetail","slide");
	            }
	        })
    });	
		
	  var page = new sap.m.Page("MemberPage", {
          showNavButton: true,
          navButtonPress: function() {        	  
              app.back();
          }, 
		  content: [memberSearch, memberList ],
          footer: [getNaviBar()]
	 });
	return page;
	}
});