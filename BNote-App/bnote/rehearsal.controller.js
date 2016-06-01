sap.ui.controller("bnote.rehearsal", {

	onParticipationPress : function(rehearsalSetParticipation) {
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();

		var oData = {
			rehearsal : model.getProperty(path + "/id"),
			participation : rehearsalSetParticipation,
			reason : "-"
		}

		model.setProperty(path + "/participate", rehearsalSetParticipation);
		
		jQuery.ajax({
			url : backend.get_url("setRehearsalParticipation"),
			type : "POST",
			data : oData,
			success : function(result) {
				console.log(result);
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});

	}

});
