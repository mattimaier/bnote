sap.ui.jsview("bnote.communication", {
	
	getControllerName: function() {
		return "bnote.communication";
	},
	
	createContent: function(){
	   jQuery.sap.require("sap.ui.core.IconPool");
       var communicationBar = new sap.m.OverflowToolbar({
      	  active: true,
      	  design: sap.m.ToolbarDesign.Solid,
      	  content:[
      	       new sap.m.Button({
      			   text: "Start",
      			   icon: sap.ui.core.IconPool.getIconURI( "home" ),
      			   press: function(){
      				   app.to("start");
      			   }
      		   }),
      		   new sap.m.Button({
      			   text: "Mitspieler",
      			   icon: sap.ui.core.IconPool.getIconURI( "person-placeholder" ),
      			   press: function(){
      				   app.to("member");
      			   }
      		   }),
      		   new sap.m.Button({
      			   text: "Kommunikation",
      			   icon: sap.ui.core.IconPool.getIconURI( "email" ),
      			   press: function(){
      				   app.to("communication");
      			   }
      		   })
      		   ]
        });
		
		var page = new sap.m.Page("CommunicationPage", {
	        title: "Kommunikation",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [  ],
	        footer: [ communicationBar ]
		});
		return page;
	}
});

	