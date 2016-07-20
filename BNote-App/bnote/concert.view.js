sap.ui.jsview("bnote.concert", {

	getControllerName: function() {
		return "bnote.concert";
	},
	
	prepareModel: function(location){
		var model = this.getModel();
		var oBindingContext = this.getBindingContext();
		var path = oBindingContext.getPath();
		var comma = ",";
		if (location.street == "" || (location.zip == "" && location.city == "")){
			comma = "";
		}
		var preparedlocation = location.street + comma + location.zip + " " + location.city;
		var location_link =  "http://maps.google.com/?q=" + preparedlocation;
		model.setProperty(path + "/location/preparedlocation", preparedlocation);
		model.setProperty(path + "/location/link", location_link);
		
		return model;
	},
	
	setButtons: function(participate){
		if(this.buttonBar != null) {
			var bid = "";
			switch(participate) {
			case 0: bid = "concertNoBtn"; break;
			case 2: bid = "concertMaybeBtn"; break;
			case 1: bid = "concertOkBtn";
			}
			this.buttonBar.setSelectedButton(bid);			
		}
	},
	
    createContent: function(oController) {
    	var view = this;    
    	
		var concertForm = new sap.ui.layout.form.SimpleForm({ 
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
            content: [
                // title     
				new sap.m.Label({text: "Titel"}),
				new sap.m.Text({text: "{title}"}),
                // begin
                new sap.m.Label({text: "Konzertbeginn"}),
                new sap.m.Text({text: "{begin}"}),
                // end
                new sap.m.Label({text: "Konzertende"}),
                new sap.m.Text({text: "{end}"}),
                // location
                new sap.m.Label({text: "Ort"}),
                new sap.m.Text({text: "{location/name}"}),               
                new sap.m.Text({text: "{location/preparedlocation}"}),
                new sap.m.Link({
                	text: "Auf Karte zeigen",
                	href: "{location/link}", 
                	target: "_blank"
                }),
                // notes
                new sap.m.Label({text: "Anmerkungen"}),
                new sap.m.Text({text: "{notes}"}),
                
                new sap.m.Label({text: "Programm"}),
                new sap.m.Text({text: "{program/name}"}),
                
            ]
        });
		
		var participationlayout = new sap.m.FlexBox({
			items: [
			        new sap.m.Label({text: "Nimmst du an dem Konzert teil?"})
			]
		});
		participationlayout.addStyleClass("bn-participation-q");
		
		 var concertOkBtn = new sap.m.Button({
              text: "Ich bin dabei",       
    	      press: function(){    	    	 
    	    	  var concertSetParticipation = 1;
    	    	  oController.onParticipationPress(concertSetParticipation); 
    	    	  oController.submit();
    	      }           	  
    	 });
		                
	    var concertMaybeBtn = new sap.m.Button({
    	      text: "vielleicht",
    	      press: function(){
    	    	  var concertSetParticipation = 2;
    	    	  oController.onParticipationPress(concertSetParticipation);  
    	   	  }, 
    	});
	    
	   	var concertNoBtn = new sap.m.Button({
    	      text: "nicht dabei",
    	      press: function(){
    	    	  var concertSetParticipation = 0;
    	    	  oController.onParticipationPress(concertSetParticipation);  
        	  }
    	});

	    this.buttonBar = new sap.m.SegmentedButton({             width: "100%", 
            buttons: [concertOkBtn, concertMaybeBtn, concertNoBtn]
	    });
		              
		      
	
	  
	  
	  this.submitButton = new sap.m.Button({
	  		text: "Abschicken",
	  		press: function(){
	  			oController.submit();
	  			view.oDialog.close();
	  		}
	  });

	  this.closeButton = new sap.m.Button({		  
	  		text: "Abbrechen",
	  		press: function(){
	  			view.oDialog.close();
	  		}
	  });

	  this.oDialog = new sap.m.Dialog({			  
	   		title: "Grund",
	   		modal: true,
	   		contentWidth:"1em",
	   		buttons: [ this.submitButton, this.closeButton ],
	   		content:[
	   		         this.explanation = new sap.m.Input({
	   		        	 	type: sap.m.InputType.Text,
	   		        	 	value: "",
	   		        	 	valueLiveUpdate: true
	   		         })
	      	]
	  });  
	
		var page = new sap.m.Page("ConcertPage", {			
            title: "Konzertdetails",
            showNavButton: true,
            navButtonPress: function() {
            	startView.getController().reloadList(view.getModel().oData);
                app.back();
            },
			content: [ concertForm, 
			           participationlayout,
			           this.buttonBar 
			         ],
			footer: [getNaviBar()]
		});
		return page;
	}
});