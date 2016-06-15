sap.ui.jsview("bnote.equipment", {
	
	
	
	getControllerName: function() {
		return "bnote.equipment";
	},
	
	

	
	createContent: function(){
		
	
		
		var page = new sap.m.Page("EquipmentPage", {
	        title: "Equipment",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	