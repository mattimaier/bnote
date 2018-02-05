sap.ui.jsview("bnote.concert", {

    concertMaybeBtn: null,
    
	getControllerName: function() {
		return "bnote.concert";
	},
	
	prepareModel: function(location, program){
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
		
        // check if there is a program
        if(program == null || program.id == "0") {
            this.programButton.setVisible(false);
        }
        else {
            this.programButton.setVisible(true);
        }
        
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
    
    disableMaybeButtons: function() {
        if(this.concertMaybeBtn != null) {
            this.concertMaybeBtn.setEnabled(false);
        }
    },
	
    createContent: function(oController) {
    	var view = this;    
    	this.programButton = new sap.m.Button({
            text: "{program/name}",
            press: function() {
                var oBindingContext = concertView.getBindingContext();
                var path = oBindingContext.getPath();
                var concert = concertView.getModel().getObject(path);
                if(concert != null && concert.program != null) {
                    oController.onProgramPress(concert.program, function(programData) {
                        programView.setModel(new sap.ui.model.json.JSONModel(programData));
                        app.to("program", "slide");
                    });
                }
            }
        });
		this.concertForm = new sap.ui.layout.form.SimpleForm({ 
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
            content: [
                // title     
				new sap.m.Label({text: "Titel"}),
				new sap.m.Text({text: "{title}"}),
                
                // begin
                new sap.m.Label({text: "Auftrittsbeginn"}),
                new sap.m.Text({text: "{begin}"}),
                // end
                new sap.m.Label({text: "Auftrittsende"}),
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
                
                // outfit
                new sap.m.Label({text: "Outfit"}),
                new sap.m.Text({text: "{outfit}"}),
                
                // notes
                new sap.m.Label({text: "Anmerkungen"}),
                new sap.m.Text({text: "{notes}"}),
                
                new sap.m.Label({text: "Programm"}),
                this.programButton
            ]
        });
        
		var participationlayout = new sap.m.FlexBox({
			items: [
			        new sap.m.Label({text: "Nimmst du am Auftritt teil?"})
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
		                
	    this.concertMaybeBtn = new sap.m.Button({
    	      text: "vielleicht",
    	      press: function(){
    	    	  var concertSetParticipation = 2;
    	    	  oController.onParticipationPress(concertSetParticipation);  
    	   	  }
    	});
	    
	   	var concertNoBtn = new sap.m.Button({
    	      text: "nicht dabei",
    	      press: function(){
    	    	  var concertSetParticipation = 0;
    	    	  oController.onParticipationPress(concertSetParticipation);  
        	  }
    	});

	    this.buttonBar = new sap.m.SegmentedButton({             
            width: "100%", 
            buttons: [concertOkBtn, this.concertMaybeBtn, concertNoBtn]
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
            title: "Auftritt",
            showNavButton: true,
            navButtonPress: function() {
            	startView.getController().reloadList(view.getModel().oData);
                app.back();
            },
			content: [ this.concertForm, 
			           participationlayout,
			           this.buttonBar 
			         ],
			footer: [getNaviBar()]
		});
		return page;
	}
});