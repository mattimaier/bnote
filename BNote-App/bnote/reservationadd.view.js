sap.ui.jsview("bnote.reservationadd", {

	getControllerName : function() {
		return "bnote.reservationadd";
	},
	
	loadlocations: function(locations){
		this.locationsAutoComplete.destroyItems();
		for(i=0; i < locations.getProperty("/locations").length; i++){
			var name = locations.getProperty("/locations/" + i + "/name");
			
			this.locationsAutoComplete.addItem(new sap.ui.core.ListItem({text : name}));
			console.log(name);
		};
	},
	
	createContent: function() {
		
		 this.locationsAutoComplete =  new sap.ui.commons.AutoComplete({
			maxPopupItems: 5,
			value: "{/location}",
			items: []
		});
		
		var reservationaddForm = new sap.ui.layout.form.SimpleForm({
			growingScrollToLoad : "true",
			content:[			         
					new sap.m.Label({text: "Beginn"}),
					new sap.m.Input({
						value: "{/begin}",
						valueLiveUpdate: true	
					}),
					
					new sap.m.Label({text: "Ende"}),
			        new sap.m.Input({
			        	value: "{/end}",
			        	valueLiveUpdate: true,
			        }),
	
			        new sap.m.Label({text: "Name"}),
			        new sap.m.Input({
			        	value: "{/name}",
			        	valueLiveUpdate: true
			        }),
			         
			        new sap.m.Label({text: "Ort"}),
			        this.locationsAutoComplete,
			        
			        new sap.m.Label({text: "Kontakt"}),
			        new sap.m.Input({
			        	value: "{/contact}",
			        	valueLiveUpdate: true
			        }),
			        
			        new sap.m.Label({text: "Notizen"}),
			        new sap.m.Input({
			        	value: "{/notes}",
			        	valueLiveUpdate: true
			        })
			]  
	});
	
		var createReservationButton = new sap.m.Button({
			text: "Reservierung hinzufügen",
			press: function(){
				reservationaddView.getController().createReservation();
			}
		});
		
		var page = new sap.m.Page("ReservationaddPage", {
	        title: "Reservierung hinzufügen",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [],
			content: [ reservationaddForm, createReservationButton ],
	        footer: [ getNaviBar() ]
		});
		return page;
		}		
});