sap.ui.controller("bnote.vote",{
	
	onVotePress: function(oController){
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		
		model.setProperty(path + "/currentdate", new Date());
		var delta = backend.comparedates(path + "/currentdate", path + "/end_original", model);
		
		var deltams = delta.getTime()/(3600000*24);
		var remainingtime = (deltams-deltams%1) + " Tage" + ", " + Math.round((deltams%1)*24) + " Stunden";
		model.setProperty(path + "/remainingtime", remainingtime);
		
		var is_date = model.getProperty(path + "/is_date");
		var is_multi = model.getProperty(path + "/is_multi");	
		
		
		if (is_date == 1 && model.getProperty(path +"/options/1/name_original") == null){
			console.log("huhu");
			backend.formatdate(path + "/options", "/name", model);			
		}
	
		for (var i = 0; i < model.getProperty(path + "/options").length; i++) {
			if (is_multi == "0"){
				model.setProperty(path + "/options/" + 0 + "/selected_single", undefined);
				model.setProperty(path + "/options/" + 1 + "/selected_single", undefined);
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
				
				this.getView().voteForm.addContent(new sap.m.HBox({
					items:[selectGroup, selectText]
				}));	
			}
		}
		
		this.getView().voteForm.addContent(
				new sap.m.Button({
					text: "Abstimmen",
					press: function(){						 
						voteView.getController().vote();
					}
				})
		);
	},
	
	vote: function(){
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		var vote_option = 0;
		var vote_optionid = 0;
		
		
		var oData = {
				vid : model.getProperty(path + "/id"),				
		};
		
		for (var i = 0; i < model.getProperty(path + "/options").length; i++){
			//get VoteOption
			if (model.getProperty(path + "/options/" + i + "/selected") == 0  || model.getProperty(path + "/options/" + i + "/selected") == undefined || model.getProperty(path + "/options/" + i + "/selected_single") == true){
				vote_option = 1;
			}
			else if (model.getProperty(path + "/options/" + i + "/selected") == 1){
				vote_option = 2;
			}
			else if (model.getProperty(path + "/options/" + i + "/selected") == 2){
				vote_option = 0;
			}
			
			//get VoteOptionId
			vote_optionid = model.getProperty(path + "/options/" + i + "/id");
			
			
			if (vote_option != 0 &&  model.getProperty(path + "/is_multi") == 0){
				oData[vote_optionid] = vote_option;
			}
			else if ( model.getProperty(path + "/is_multi") == 1){
				oData[vote_optionid] = vote_option;
			}
		};
		
		jQuery.ajax({
			url : backend.get_url("vote"),
			type : "POST",
			data : oData,
			success : function(result) {
				console.log(result);
				sap.m.MessageToast.show("Abstimmen erfolgreich");
			},
			error : function(a, b, c) {
				sap.m.MessageToast.show("Abstimmen fehlgeschlagen");
				console.log(a, b, c);
			}
		});
	},
	
});