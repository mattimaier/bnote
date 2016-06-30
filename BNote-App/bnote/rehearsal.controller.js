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
	
	prepareView: function() {
		var model = rehearsalView.getModel();
		var oBindingContext = rehearsalView.getBindingContext();
		var path = oBindingContext.getPath();		
		
		var songs = model.getProperty(path + "/songsToPractice");
		if(songs == null || songs.length == 0){
			model.setProperty(path + "/songs", "");
		}
		else {
			var song_titles = [];
			for (var i = 0; i < songs.length; i++){
				var song = model.getProperty(path + "/songsToPractice/" + i + "/title");
				var notes = model.getProperty(path + "/songsToPractice/" + i + "/notes");
				
				if (notes != null && notes != ""){
					song = song.concat(" (" + notes + ")" );
				}			
				song_titles.push(song);				
			}
			model.setProperty(path + "/songs", song_titles.join("\n"));
		}			
		
	},
	
	submit: function(){		 
		this.oData.reason = this.getView().reason.getValue();		
		
		jQuery.ajax({
			url : backend.get_url("setRehearsalParticipation"),
			type : "POST",
			data : this.oData,
			success : function(result) {
				sap.m.MessageToast.show("Teilnahme wurde aktualisiert.");				
			},
			error : function(a, b, c) {
				sap.m.MessageToast.show("Teilnahme konnte nicht aktualisiert werden.");	
				console.log(a, b, c);
			}
		});
	}
});
