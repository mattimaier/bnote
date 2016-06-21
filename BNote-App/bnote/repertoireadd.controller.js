sap.ui.controller("bnote.repertoireadd", {

	mode: "edit",
	
	setdirtyflag: function() {
		repertoireaddView.getController().dirty = true;
	},
	
	savechanges: function(){
		var model = repertoireaddView.getModel();
		var path = repertoireaddView.getBindingContext().getPath();
		var updateSongData = model.getProperty(path);
				
		updateSongData["genre"] = {
				id: repertoireaddView.genreitems.getSelectedKey(),
				name: repertoireaddView.genreitems.getItemByKey(repertoireaddView.genreitems.getSelectedKey()).getText()
		};
		updateSongData["status"] = {
				id: repertoireaddView.statusitems.getSelectedKey(),
				name: repertoireaddView.statusitems.getItemByKey(repertoireaddView.statusitems.getSelectedKey()).getText()
		};
	
		
		//update backend
		if(repertoireaddView.getController().dirty){
			// update
			if(this.mode == "edit") {
				jQuery.ajax({
					type: "POST",
		        	url: backend.get_url("updateSong"),
		        	data: updateSongData,
		        	success: function(data) {
		        		sap.m.MessageToast.show("Speichern erfolgreich");
		        		repertoiredetailView.getModel().setProperty(path, updateSongData);
		        		repertoireaddView.getController().dirty = false;
		            },
					error: function(){		
						sap.m.MessageToast.show("Speichern fehlgeschlagen");
					}
		        });
			}
			else {
				// add
				jQuery.ajax({
					type: "POST",
		        	url: backend.get_url("addSong"),
		        	data: updateSongData,
		        	success: function(data) {
		        		var songid = data;
		        		updateSongData.id = songid;
		        		repertoireaddView.getModel().setProperty(path, updateSongData);
		        		repertoireaddView.getController().dirty = false;
		        		sap.m.MessageToast.show("Speichern erfolgreich");
		        		app.to("repertoire");
		            },
					error: function(){	
						sap.m.MessageToast.show("Speichern fehlgeschlagen");
					}
		        });
			}
		}
		else {
			sap.m.MessageToast.show("Es wurde nichts verändert.");
		}			
	},
	
	checkdirtyflag: function() {
		if (repertoireaddView.getController().dirty && this.mode == "edit"){
			var model = repertoireaddView.getModel();
			var path = repertoireaddView.getBindingContext().getPath();
			var songid = model.getProperty(path + "/id");
			
			jQuery.ajax({
				type: "GET",
	        	url: backend.get_url("getSong"),
	        	data: {"id" : songid},
	        	success: function(data) {
	        		repertoiredetailView.getModel().setProperty(path, data);
	        		repertoireaddView.getController().dirty = false;
	            },
	        	error: function(){
	        		console.log("Error: Cannot retrieve fresh song.");
	        		sap.m.MessageToast.show("Fehler! Bitte lade die App neu.");	
	        	}
	        });
		}
		else if (this.mode == "add"){
			var model = repertoireaddView.getModel();			
			var path = repertoireaddView.getBindingContext().sPath.split("/");
			var idxNewItem = path[path.length -1];
			model.oData.songs.splice(idxNewItem, 1)
			model.setProperty("/songs", model.oData.songs);
			repertoireaddView.getController().dirty = false;
		}
	}, 
	 
	setData: function() {
		repertoireaddView.getController().dirty = false;
		
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getGenres"),
        	success: function(data) {
               var genremodel = new sap.ui.model.json.JSONModel(data);
                repertoireaddView.loadgenres(genremodel, repertoireaddView.getModel());
            }
        });
        
        jQuery.ajax({
        	url: backend.get_url("getStatuses"),
        	success: function(data) {
                var statusmodel = new sap.ui.model.json.JSONModel(data);
                repertoireaddView.loadstatuses(statusmodel, repertoireaddView.getModel());
            }
        });
        
       // Display correct Genre and Status
       var model = repertoireaddView.getModel();
       var path = repertoireaddView.getBindingContext().getPath();
         
       repertoireaddView.genreitems.setSelectedKey(model.getProperty(path + "/genre/id"));
       repertoireaddView.statusitems.setSelectedKey(model.getProperty(path + "/status/id"));
    }
});