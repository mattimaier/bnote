sap.ui.controller("bnote.task", {
	
	// calculate and display the remaining time
	onTaskPress: function(){
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		
		model.setProperty(path + "/currentdate", new Date());
		var delta = backend.comparedates(path + "/currentdate", path + "/due_at_original", model);
		
		var deltams = delta.getTime()/(3600000*24);
		var remainingtime = (deltams-deltams%1) + " Tage" + ", " + Math.round((deltams%1)*24) + " Stunden";
		model.setProperty(path + "/remainingtime", remainingtime);
		console.log(model);
	},
	
	ontaskDoneBtnpress: function(){
		var model = this.getView().getModel(model);
		var oBindingContext = this.getView().getBindingContext(oBindingContext);
		var path = oBindingContext.getPath();
		
		jQuery.ajax({
			url : backend.get_url("taskCompleted"),
			type : "POST",
			data : {"taskId": model.getProperty(path + "/id")},
			success : function(result) {
				model.setProperty(path + "is_completed", "1");
				
				// Delete the task from model after completion				
				var path = oBindingContext.sPath.split("/");
				var idxDelItem = path[path.length -1];
				model.oData.items.splice(idxDelItem, 1);
        		model.setProperty("/items", model.oData.items);  
				
				sap.m.MessageToast.show("Aufgabe erfolgreich beendet");		
				app.to("start");
			},
			error : function(a, b, c) {
				sap.m.MessageToast.show("Beenden der Aufgabe fehlgeschlagen");
				console.log(a, b, c);
			}
		});
	}
	
});
    