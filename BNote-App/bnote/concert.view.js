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
		model.setProperty(path + "/location/preparedlocation", preparedlocation);
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
		var concertForm = new sap.ui.layout.form.SimpleForm({
            title: "Konzertdetails",
            content: [
                // begin
                new sap.m.Label({text: "Konzertbeginn"}),
                new sap.m.Text({text: "{begin}"}),
                // end
                new sap.m.Label({text: "Konzertende"}),
                new sap.m.Text({text: "{end}"}),
                // location
                new sap.m.Label({text: "Ort"}),
                new sap.m.Text({text: "{location/name}"}),
                new sap.m.Text({text: "\u00a0"}),
                new sap.m.Text({text: "{location/preparedlocation}" }),
                // notes
                new sap.m.Label({text: "Anmerkungen"}),
                new sap.m.Text({text: "{notes}"}),
                
                new sap.m.Label({text: "Programm"}),
                new sap.m.Text({text: "{program/name}"}),
                
            ]
        });
		
	  this.buttonBar = new sap.m.SegmentedButton({
		            width: "100%", 
		            buttons: [
		                  new sap.m.Button("concertOkBtn", {
		                      text: "OK",       
		            	      press: function(){
		            		  var concertSetParticipation = 1;
		            	      oController.onParticipationPress(concertSetParticipation); 
		            	      oController.submit();
		            	   	  },            	  
		            	      }),
		           	      new sap.m.Button("concertMaybeBtn",{
		            	      text: "vielleicht",
		            	      press: function(){
		            		  var concertSetParticipation = 2;
		            		  oController.onParticipationPress(concertSetParticipation);  
		            	   	  }, 
		            	      }),
		            	  new sap.m.Button("concertNoBtn",{
		            	      text: "Kann nicht",
		            	      press: function(){
		                	  var concertSetParticipation = 0;
		                	  oController.onParticipationPress(concertSetParticipation);  
		                	  }
		            	      }),
		                   ]
		        });
	  
	  this.submitButton = new sap.m.Button({
	  		text: "Abschicken",
	  		press: function(){
	  			oController.submit();
	  			concertView.oDialog.close();
	  		}
	  });

	  this.closeButton = new sap.m.Button({
	  		text: "Abbrechen",
	  		press: function(){
	  			concertView.oDialog.close();
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
            title: "Konzert",
            showNavButton: true,
            navButtonPress: function() {
                app.back();
            },
			content: [ concertForm, this.buttonBar ],
			footer: [getNaviBar()]
		});
		return page;
	}
});