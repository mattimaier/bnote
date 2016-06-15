sap.ui.controller("bnote.repertoireadd", {

	setdirtyflag: function() {
		this.dirty = true;
	},
	
	savechanges: function(){
		var model = repertoireaddView.getModel();
		var path = repertoireaddView.getBindingContext().getPath();
		var updateSongData = model.getProperty(path);
		console.log(updateSongData);
		updateSongData.genre. = repertoireaddView.genreitems.indexOfItem(repertoireaddView.genreitems.getSelectedItem());
		updateSongData.status = repertoireaddView.statusitems.indexOfItem(repertoireaddView.statusitems.getSelectedItem());
		console.log(updateSongData);
		//update backend
		if(this.dirty){
			jQuery.ajax({
				type: "POST",
	        	url: backend.get_url("updateSong"),
	        	data: updateSongData,
	        	success: function(data) {
	        		console.log(data);
	        		repertoiredetailView.getModel().setProperty(path, data);
	            }
	        });
			
		}
	},
	
	checkdirtyflag: function() {
		if (this.dirty){
			var model = repertoireaddView.getModel();
			var path = repertoireaddView.getBindingContext().getPath();
			var songid = model.getProperty(path + "/id");
			
			jQuery.ajax({
				type: "GET",
	        	url: backend.get_url("getSong"),
	        	data: {"id": songid},
	        	success: function(data) {
	        		repertoiredetailView.getModel().setProperty(path, data);
	            }
	        });
		}
	}, 
	 
	setData: function() {
		this.dirty = false;
		
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getGenres"),
        	success: function(data) {
                var genremodel = new sap.ui.model.json.JSONModel(data);
                repertoireaddView.loadgenres(genremodel);
            }
        });
        
        jQuery.ajax({
        	url: backend.get_url("getStatuses"),
        	success: function(data) {
                var statusmodel = new sap.ui.model.json.JSONModel(data);
                repertoireaddView.loadstatuses(statusmodel);
            }
        });
        
        var model = repertoireaddView.getModel();
        var path = repertoireaddView.getBindingContext().getPath();
     
        repertoireaddView.genreitems.setSelectedItemId(model.getProperty(path + "/genre/id"));
        repertoireaddView.statusitems.setSelectedItemId(model.getProperty(path + "/status/id"));
    },
});