sap.ui.jsview("bnote.equipment", {
	
	getControllerName: function() {
		return "bnote.equipment";
	},
	
	
	
	createContent: function(oController){
		
		var equipmentSearch = new sap.m.SearchField("equipmentSearch",{  
	         tooltip: "Equipment durchsuchen",  
	         liveChange: oController.filterList  
	  });  
		
		var equipmentList = new sap.m.List("equipmentList");
		
			equipmentList.bindItems({
	        	growingScrollToLoad : "true",
	            path : "/equipment",
	            sorter : new sap.ui.model.Sorter("name"),
	            template : new sap.m.StandardListItem({
	                title: "{name}",
	                icon: "icons/equipment.png",
	                description: "{model}",
	                type: sap.m.ListType.Navigation,
	                press: function(evt) {
	                	  var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
	                      equipmentdetailView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
	                      app.to("equipmentdetail");
	                }
	            })
	        });
			
	var equipmentAddButton = new sap.m.Button({
		icon : sap.ui.core.IconPool.getIconURI("add"),
		press: function() {
				var model = equipmentView.getModel();
					 var emptydata = {
							 id: -1,
							   name: "",
							   model: "",
							   make: "",
							   purchase_price: "",
							   current_value: "",
							   quantity: "",
							   notes: ""
				   		};
		         model.oData.equipment.push(emptydata);
		         var newindex = model.oData.equipment.length -1;
		         var bcEmpty = model.createBindingContext("/equipment/" + newindex);
		         equipmentaddView.setModel(model);
		         equipmentaddView.setBindingContext(bcEmpty);
		         equipmentaddView.getController().setData(); // equipment dirtyflag = false
			     equipmentaddView.getController().mode = "add";
			     app.to("equipmentadd");
		}		
	});
	

	
		var page = new sap.m.Page("EquipmentPage", {
	        title: "Equipment",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
	        headerContent: [ equipmentAddButton ],
			content: [ equipmentSearch, equipmentList ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	