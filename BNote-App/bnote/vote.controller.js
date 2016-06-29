sap.ui.controller("bnote.vote",{
	
	// calculate and display the remaining time
	onVotePress: function(){		
		var oController = this;
		var model = oController.getView().getModel(model);
		var oBindingContext = oController.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		
		model.setProperty(path + "/currentdate", new Date());
		var delta = backend.comparedates(path + "/currentdate", path + "/end_original", model);
		
		var deltams = delta.getTime()/(3600000*24);
		var remainingtime = (deltams-deltams%1) + " Tage" + ", " + Math.round((deltams%1)*24) + " Stunden";
		model.setProperty(path + "/remainingtime", remainingtime);
		
		var is_date = model.getProperty(path + "/is_date");
		var is_multi = model.getProperty(path + "/is_multi");	
		
		
		if (is_date == 1 && model.getProperty(path +"/options/1/name_original") == null){			
			backend.formatdate(path + "/options", "/name", model);			
		}
	
		for (var i = 0; i < model.getProperty(path + "/options").length; i++) {
			if (is_multi == "0"){
				this.getView().voteForm.addContent(new sap.m.RadioButton({
							text : model.getProperty(path + "/options/" + i + "/name"),
							selected: "{" + path + "/options/" + i + "/selected_single}"
				}));
			}
			else {				
				var selectGroup = new sap.m.RadioButtonGroup({
					width: "210px",
					columns: 3,
					selectedIndex: "{" + path + "/options/" + i + "/selected}"
				});
				
				var radioBtnJ = new sap.m.RadioButton({text: "J"});
				var radioBtnV = new sap.m.RadioButton({text : "?"});
				var radioBtnN = new sap.m.RadioButton({text : "N"});
				
				radioBtnJ.addStyleClass("bn-green-txt");
				radioBtnV.addStyleClass("bn-orange-txt");
				radioBtnN.addStyleClass("bn-red-txt");
				
				selectGroup.addButton(radioBtnJ);
				selectGroup.addButton(radioBtnV);
				selectGroup.addButton(radioBtnN);
				
				var selectText = new sap.m.Text({text: model.getProperty(path + "/options/" + i + "/name")});
				selectText.addStyleClass("bnote_vote_option_text");
				
				oController.getView().voteForm.addContent(new sap.m.HBox({
					items:[selectGroup, selectText]
				}));	
			}
		}
		
		this.getView().voteForm.addContent(
				new sap.m.Button({
					text: "Abstimmen",
					press: oController.vote,
				})
		);
	},
	
	vote: function(){
		
		var model = voteView.getModel(model);
		var oBindingContext = voteView.getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		var vote_option = 0;
		var vote_optionid = 0;	
		var is_multi = model.getProperty(path + "/is_multi");
		
		var oData = {
				vid : model.getProperty(path + "/id"),				
		};
		
		for (var i = 0; i < model.getProperty(path + "/options").length; i++){
			
			if(is_multi == 1){
				vote_option = 0;
			//get VoteOption
				if (model.getProperty(path + "/options/" + i + "/selected") == 0  || model.getProperty(path + "/options/" + i + "/selected") == undefined){
					vote_option = 1;
				}
				else if (model.getProperty(path + "/options/" + i + "/selected") == 1){
					vote_option = 2;
				}
				else if (model.getProperty(path + "/options/" + i + "/selected") == 2){
					vote_option = 0;
				}
			}
			else if(is_multi == 0){
				if(model.getProperty(path + "/options/" + i + "/selected_single") == true){
					vote_option = 1;	
				}else if(model.getProperty(path + "/options/" + i + "/selected_single") == false || model.getProperty(path + "/options/" + i + "/selected_single") == undefined){
					vote_option = 0;
				}							
			}
			
			//get VoteOptionId			
			vote_optionid = model.getProperty(path + "/options/" + i + "/id");
			if ((is_multi == 0 && vote_option == 1) || is_multi == 1){
				oData[vote_optionid] = vote_option;
			}
	    }; 
		
		jQuery.ajax({
			url : backend.get_url("vote"),
			type : "POST",
			data : oData,
			success : function(result) {
				sap.m.MessageToast.show("Abstimmen erfolgreich");
			},
			error : function(a, b, c) {
				sap.m.MessageToast.show("Abstimmen fehlgeschlagen");
				console.log(a, b, c);
			}
		});
	},
	
});