sap.ui.controller("bnote.concert", {

	onParticipationPress : function(concertSetParticipation) {
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();

		var oData = {
			concert : model.getProperty(path + "/id"),
			participation : concertSetParticipation,
			explanation : "-"
		}

		model.setProperty(path + "/participate", concertSetParticipation);
		
		jQuery.ajax({
			url : backend.get_url("setConcertParticipation"),
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