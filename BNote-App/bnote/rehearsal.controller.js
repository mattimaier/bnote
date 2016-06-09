sap.ui.controller("bnote.rehearsal", {
	
	onParticipationPress: function(rehearsalSetParticipation){
		
		var model = rehearsalView.getModel();
		var oBindingContext = rehearsalView.getBindingContext();
		var path = oBindingContext.getPath();
		
		if (rehearsalSetParticipation == 2 || rehearsalSetParticipation == 0){
			this.getView().oDialog.open();
		}
		
		this.oData = {
			rehearsal : model.getProperty(path + "/id"),
			participation :rehearsalSetParticipation,
			reason : ""
		};
	},	
	
	submit: function(){
		 
		this.oData.reason = this.getView().reason.getValue()
		
		console.log(this.oData);
		jQuery.ajax({
			url : backend.get_url("setRehearsalParticipation"),
			type : "POST",
			data : this.oData,
			success : function(result) {
				console.log(result);
			},
			error : function(a, b, c) {
				console.log(a, b, c);
			}
		});
	}
});
