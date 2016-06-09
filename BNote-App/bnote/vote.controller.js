sap.ui.controller("bnote.vote",{
	
	onVotePress: function(){
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
							new sap.m.CheckBox({
								text : model.getProperty(path + "/options/" + i + "/name"),
								selected: "{" + path + "/options/" + i + "/selected}"
							})
					);
				}	
		}
		
		this.getView().voteForm.addContent(
				new sap.m.Button({
					text: "Abstimmen",
					press: vote()
				})
		);
	},
	
	vote: function(){
		
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		var vote_option = 0;
		var vote_optionid = 0;
		var option = new Array [];
		
		for (var i = 0; i < model.getProperty(path + "/options").length; i++){
			
			if(model.getProperty(path + "/options/" + i + "/selected") == "true"){
				vote_option = 1;
			}
			else{
				vote_option = 0;
			}
			
			vote_optionid = model.getProperty(path + "/options/" + i + "/id");
			option.push("[" + vote_optionid + ":" + vote_option + "]")
		};
		
		
		oData = {
				vid : model.getProperty(path + "/id"),
				options : option, 
		} 
		
		
		jQuery.ajax({
			url : backend.get_url("vote"),
			type : "POST",
			data : ,
			success : function(result) {
				console.log(result);
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});
	},
	
});