sap.ui.jsview("bnote.rehearsal", {
	
	buttonBar: null,
	
	getControllerName: function() {
		return "bnote.rehearsal";
	},
    
	setButtons: function(participate){
		if(this.buttonBar != null) {
			var bid = "";
			switch(participate) {
			case 0: bid = "rehearsalNoBtn"; break;
			case 2: bid = "rehearsalMaybeBtn"; break;
			case 1: bid = "rehearsalOkBtn";
			}
			this.buttonBar.setSelectedButton(bid);
		}
	},
	
    createContent: function(oController) {
		var rehearsalForm = new sap.ui.layout.form.SimpleForm({
            title: "Probendetails",
            content: [
                // begin
                new sap.m.Label({text: "Probenbeginn"}),
                new sap.m.Text({text: "{begin}"}),
                // end
                new sap.m.Label({text: "Probenende"}),
                new sap.m.Text({text: "{end}"}),
                // location
                new sap.m.Label({text: "Ort"}),
                new sap.m.Text({text: "{name}"}),
                // notes
                new sap.m.Label({text: "Anmerkungen"}),
                new sap.m.Text({text: "{notes}"}),
            ]
        });
		
	 
         var rehearsalOkBtn = new sap.m.Button({
	          text: "OK",       
		      press: function(){
				  var rehearsalSetParticipation = 1;
			      oController.onParticipationPress(rehearsalSetParticipation); 
			      oController.submit();
		   	  },            	  
	     });
         
   	    var rehearsalMaybeBtn = new sap.m.Button({
		      text: "vielleicht",
		      press: function(){
				  var rehearsalSetParticipation = 2;
				  oController.onParticipationPress(rehearsalSetParticipation); 
		   	  }, 
	    });
   	    
       var rehearsalNoBtn = new sap.m.Button({
		      text: "Kann nicht",
		      press: function(){
		    	  var rehearsalSetParticipation = 0;
		    	  oController.onParticipationPress(rehearsalSetParticipation); 
	    	  }
	  });
		       
      rehearsalOkBtn.addStyleClass("bn-green-bg bn-black-txt"); 
      rehearsalMaybeBtn.addStyleClass("bn-orange-bg bn-black-txt"); 
      rehearsalNoBtn.addStyleClass("bn-red-bg bn-black-txt"); 
       
	  this.buttonBar = new sap.m.SegmentedButton({
        width: "100%", 
        buttons: [rehearsalOkBtn, rehearsalMaybeBtn, rehearsalNoBtn]
	  });  	  
	  
	  this.submitButton = new sap.m.Button({
		  		text: "Abschicken",
		  		press: function(){
		  			oController.submit();
		  			rehearsalView.oDialog.close();
		  		}
	  });
	  
	  this.closeButton = new sap.m.Button({
		  		text: "Abbrechen",
		  		press: function(){
		  			rehearsalView.oDialog.close();
		  		}
	  });
	  
	   this.oDialog = new sap.m.Dialog({
		   		title: "Grund",
		   		modal: true,
		   		contentWidth:"1em",
		   		buttons: [ this.submitButton, this.closeButton ],
		   		content:[
		   		         this.reason = new sap.m.Input({
		   		        	 	type: sap.m.InputType.Text,
		   		        	 	value: "",
		   		        	 	valueLiveUpdate: true
		   		         })
		      	]
	   });       
	
		var page = new sap.m.Page("RehearsalPage", {
            title: "Probe",
            showNavButton: true,
            navButtonPress: function() {
                app.back();
            },
			content: [ rehearsalForm, this.buttonBar ],
			footer: [getNaviBar()]
		});
		
		return page;
	}
    
});