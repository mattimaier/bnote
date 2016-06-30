sap.ui.controller("bnote.songstopractise", {
	
	buildList: function (model) {
		var songs = model.songsToPractice;
		
		for (var i = 0; i < songs.length; i++){
			var oItem = new sap.m.StandardListItem({
				title: songs[i].title, 
				description: songs[i].notes				
			});
			console.log(oItem);
			songstopractiseView.songList.addItem(oItem);
		}
	}	
});