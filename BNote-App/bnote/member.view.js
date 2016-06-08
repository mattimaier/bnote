sap.ui.jsview("bnote.member", {
	
	getControllerName: function() {
		return "bnote.member";
	},
	 
	createContent: function(){
		var memberList = new sap.m.List({
            headerText: "Mitspieler",
        });
		
        memberList.bindItems({
        	growingScrollToLoad : "true",
            path : "/contacts",
            sorter : new sap.ui.model.Sorter("name"),
            template : new sap.m.StandardListItem({
                title: "{fullname}",
                icon: "icons/proben.png",
                description: "{mobile}",
                type: sap.m.ListType.Navigation,
                press: function(evt) {
                	  var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
                      memberdetailView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
                     
                      var model = oBindingContext.getModel();
                      var path = oBindingContext.getPath();
                      var dataVisibility = [model.getProperty(path + "/phone") , model.getProperty(path + "/mobile") , model.getProperty(path + "/email")];
                      memberdetailView.setDataVisibility(dataVisibility);
                      
                      app.to("memberdetail");
                }
            })
        });
        
	var page = new sap.m.Page("MemberPage", {
        title: "Mitspieler",
        showNavButton: true,
        navButtonPress: function() {
            app.back();
        }, 
		content: [ memberList ],
        footer: [naviBar]
	});
	return page;
	}
});