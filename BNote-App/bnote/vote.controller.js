sap.ui.controller("bnote.vote",{
	
	onVotePress: function(oController){
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		
		model.setProperty(path + "/currentdate", new Date());
		var delta = backend.comparedates(path + "/currentdate", path + "/end_original", model);
		
		deltams = delta.getTime()/(3600000*24);
		var remainingtime = (deltams-deltams%1) + " Tage" + ", " + Math.round((deltams%1)*24) + " Stunden";
		model.setProperty(path + "/remainingtime", remainingtime);
		
		var is_date = model.getProperty(path + "/is_date");
		var is_multi = model.getProperty(path + "/is_multi");	
		
		if (is_multi == "0"){
			
			for (var i = 0; i < model.getProperty(path + "/options").length; i++) {
				this.getView().voteForm.addContent(
						new sap.m.RadioButton({
							text : model.getProperty(path + "/options/" + i + "/name"),
							selected: "{" + path + "/options/" + i + "/selected}"
						})
				);
			}	
		};
		
		if(is_multi == "1"){
				for (var i = 0; i < model.getProperty(path + "/options").length; i++) {
					this.getView().voteForm.addContent(
							new sap.m.HBox({
									items:[
										new sap.m.RadioButtonGroup({
											columns: 3,
											Buttons:[
													new sap.m.RadioButton({
														text : "J",
														selected: "{" + path + "/options/" + i + "/selected}"
													}),
													new sap.m.RadioButton({
														text : "?",
														selected: "{" + path + "/options/" + i + "/selected}"
													}),
													new sap.m.RadioButton({
														text : "N",
														selected: "{" + path + "/options/" + i + "/selected}"
													}),
													]
										}),
										new sap.m.Text({text: model.getProperty(path + "/options/" + i + "/name")})
									]
							})
					)	
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
		var option = [];
		
		oData = {
				vid : model.getProperty(path + "/id"),
				options : []
		};
		console.log(model);
		for (var i = 0; i < model.getProperty(path + "/options").length; i++){
			
			//get VoteOption
			if (model.getProperty(path + "/options/" + i + "/selected") == true){
				vote_option = 1;
			}
			else if (model.getProperty(path + "/options/" + i + "/selected") == false || model.getProperty(path + "/options/" + i + "/selected") == undefined){
				vote_option = 0;
			}
			else if (model.getProperty(path + "/options/" + i + "/selected") == "maybe"){
				vote_option = 2;
			}
			
			//get VoteOptionId
			vote_optionid = model.getProperty(path + "/options/" + i + "/id");
			
			
			if (vote_option != 0 &&  model.getProperty(path + "/is_multi") == 0){
				oData[vote_optionid] = vote_option;
			}
			else if ( model.getProperty(path + "/is_multi") == 2){
				oData[vote_optionid] = vote_option;
			}
		};
		console.log(model);
		oData.options = option;
		console.log(oData);
		
		jQuery.ajax({
			url : backend.get_url("vote"),
			type : "POST",
			data : oData,
			success : function(result) {
				console.log(result);
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});
	},
	
});