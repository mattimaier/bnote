sap.ui.controller("bnote.concert", {

onParticipationPress: function(concertSetParticipation){
	
	var model = concertView.getModel();
	var oBindingContext = concertView.getBindingContext();
	var path = oBindingContext.getPath();
	
	if (concertSetParticipation == 2 || concertSetParticipation == 0){
		this.getView().oDialog.open();
	}
	
	this.oData = {
		concert : model.getProperty(path + "/id"),
		participation : concertSetParticipation,
		explanation : ""
	};
	model.setProperty(path + "/participate", concertSetParticipation);
},	

submit: function(){
	 
	this.oData.explanation = this.getView().explanation.getValue()
	
	console.log(this.oData);
	jQuery.ajax({
		url : backend.get_url("setConcertParticipation"),
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