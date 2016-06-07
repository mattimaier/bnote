sap.ui.jsview("bnote.communication", {
	
	getControllerName: function() {
		return "bnote.communication";
	},
	
	createContent: function(){
		
		this.communicationForm = new  sap.ui.layout.form.SimpleForm({
			title: "Formular",
			content:[
			         new sap.m.Label({text: "Betreff"}),
			         new sap.m.Input({
			        	  type: sap.m.InputType.Text,
			        	  valueLiveUpdate: true,
			        	  value: "{/subject}"
			         }),
			        		  
			        new sap.m.Label({text: "Nachricht"}),
			        new sap.m.TextArea({			        	
			        	rows: 10,
			        	value: "{/message}"
			        })
			        
			         ]
		});
		
		
		
		
	   jQuery.sap.require("sap.ui.core.IconPool");
       var communicationBar = new sap.m.OverflowToolbar({
      	  active: true,
      	  design: sap.m.ToolbarDesign.Solid,
      	  content:[
      	       new sap.m.Button({
      			   icon: sap.ui.core.IconPool.getIconURI( "home" ),
      			   press: function(){
      				   app.to("start");
      			   }
      		   }),
      		   new sap.m.Button({
      			   icon: sap.ui.core.IconPool.getIconURI( "person-placeholder" ),
      			   press: function(){
      				   app.to("member");
      			   }
      		   }),
      		   new sap.m.Button({
      			   icon: sap.ui.core.IconPool.getIconURI( "email" ),
      			   press: function(){
      				   app.to("communication");
      			   }
      		   }),
      		  new sap.m.Button({
     			   icon: sap.ui.core.IconPool.getIconURI( "marketing-campaign" ),
     		   }),
     		  new sap.m.Button({
     			   icon: sap.ui.core.IconPool.getIconURI( "documents" ),
     		   }),
     		  new sap.m.Button({
     			   icon: sap.ui.core.IconPool.getIconURI( "projector"),
     		   })
      		   ]
        });
		
		var page = new sap.m.Page("CommunicationPage", {
	        title: "Kommunikation",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ this.communicationForm ],
	        footer: [ communicationBar ]
		});
		return page;
	}
});

	