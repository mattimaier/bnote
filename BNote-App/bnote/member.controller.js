sap.ui.controller("bnote.member", {
	
	onAfterRendering: function() {
      var backend_method = "getMembers";  // default
      // if the user has access to module 3 -> show contacts instead
      if(permission.indexOf("3") != -1) {
          backend_method = "getContacts";
      }
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url(backend_method),
        	success: function(data) {
                for(var i = 0; i < data.contacts.length; i++) {
                    var icon = "icons/alphabet/dot_blue_10px.png";
                    if(data.contacts[i].groups && data.contacts[i].groups.length > 0) {
                        var firstGroup = data.contacts[i].groups[0];
                        switch(firstGroup.id) {
                          case "1": icon = "icons/alphabet/dot_red_10px.png"; break; // admins
                          case "2": icon = "icons/alphabet/dot_blue_10px.png"; break;  // members
                          default: icon = "icons/alphabet/dot_gray_10px.png";  // all others
                        }
                    }
                    data.contacts[i].icon = icon;
                }
                var model = new sap.ui.model.json.JSONModel(data);
                oCtrl.getView().setModel(model);
                memberdetailView.setModel(model);
            }
        });
    },
	

    filterList: function(oEvent){  
        var like = oEvent.getParameter("newValue");  
        var oFilter = new sap.ui.model.Filter("fullname",   
                                                sap.ui.model.FilterOperator.Contains,   
                                                like);  
        var element = sap.ui.getCore().getElementById("memberList");  
        var listBinding = element.getBinding("items");  
        listBinding.filter([oFilter]);
    }
	
});