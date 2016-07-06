sap.ui.controller("bnote.rehearsaladd",{
	
	setdirtyflag: function() {
		rehearsaladdView.getController().dirty = true;
	},
	
	addlocation_setdirtyflag: function() {
		rehearsaladdView.getController().addlocation_dirty = true;		
	},	
	
	checknewlocation: function() {
		if (rehearsaladdView.locationitems.getSelectedKey() == "-1"){
			rehearsaladdView.locationaddForm.setVisible(true);
			rehearsaladdView.getController().addlocation_dirty = false;
		}
		else {
			rehearsaladdView.locationaddForm.setVisible(false);
		}
	},
	
	prepareModelFromVoteresult: function(title) {
		this.dirty = false;		
		var oRehearsaladd = {
				begin: "",
				end: "",
				approve_until: "",
				notes: "",
				location: "",
				groupboxes: {},
				groups: ""
		};
		
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getLocations"),
			 success: function(data) {				 
				 var model = new sap.ui.model.json.JSONModel(data);
			     rehearsaladdView.loadlocations(model);
			     rehearsaladdView.locationitems.setSelectedKey("-1"); 
			     rehearsaladdView.getController().addlocation_dirty = false;	
            },
	         error: function() { 
	        	 console.log("error loading locations");
	         }
	    });		
		
		jQuery.ajax({
			url : backend.get_url("getGroups"),
			type : "GET",
			success : function(data) {
				oRehearsaladd.groupboxes = data.group;
				var model = new sap.ui.model.json.JSONModel(oRehearsaladd);
			    rehearsaladdView.setModel(model);
				for (var i = 0; model.getProperty("/groupboxes/" + i + "/name") != undefined; i++) {
					rehearsaladdView.rehearsaladdForm.addContent(new sap.m.CheckBox({
						text : model.getProperty("/groupboxes/" + i + "/name"),
						selected: "{/groupboxes/" + i + "/selected}"
					}));
				}				
			},
        	error: function(a,b,c){
        		console.log(a,b,c);
        	}
        });	  	
		var titledate = backend.parsedate(title);
		sap.ui.getCore().byId("rehearsaladd_begin").setDateValue(backend.parsedate(title));
		sap.ui.getCore().byId("rehearsaladd_end").setDateValue(new Date(backend.parsedate(title).getTime() + 120*60000));
	},
	
	prepareModel: function(){
		this.dirty = false;		
		var oRehearsaladd = {
				begin: "",
				end: "",
				approve_until: "",
				notes: "",
				location: "",
				groupboxes: {},
				groups: ""
		};
		
		jQuery.ajax({	
			 type: "GET",
			 url: backend.get_url("getLocations"),
			 success: function(data) {				 
				 var model = new sap.ui.model.json.JSONModel(data);
			     rehearsaladdView.loadlocations(model);
			     rehearsaladdView.locationitems.setSelectedKey("-1"); 
			     rehearsaladdView.getController().addlocation_dirty = false;	
            },
	         error: function() { 
	        	 console.log("error loading locations");
	         }
	    });		
		
		jQuery.ajax({
			url : backend.get_url("getGroups"),
			type : "GET",
			success : function(data) {
				oRehearsaladd.groupboxes = data.group;
				var model = new sap.ui.model.json.JSONModel(oRehearsaladd);
			    rehearsaladdView.setModel(model);
				for (var i = 0; model.getProperty("/groupboxes/" + i + "/name") != undefined; i++) {
					rehearsaladdView.rehearsaladdForm.addContent(new sap.m.CheckBox({
						text : model.getProperty("/groupboxes/" + i + "/name"),
						selected: "{/groupboxes/" + i + "/selected}"
					}));
				}				
			},
        	error: function(a,b,c){
        		console.log(a,b,c);
        	}
        });	  
		sap.ui.getCore().byId("rehearsaladd_begin").setValue(null);
		sap.ui.getCore().byId("rehearsaladd_end").setValue(null);
		sap.ui.getCore().byId("rehearsaladd_approve_until").setValue(null);	
	},
	
	addlocation: function(){
		var oLocation = {
				name: sap.ui.getCore().byId("rehearsaladd_addlocation_name").getValue(),
				notes:  sap.ui.getCore().byId("rehearsaladd_addlocation_notes").getValue(),
				street:  sap.ui.getCore().byId("rehearsaladd_addlocation_street").getValue(),
				city:  sap.ui.getCore().byId("rehearsaladd_addlocation_city").getValue(),
				zip:  sap.ui.getCore().byId("rehearsaladd_addlocation_zip").getValue()
		};
		jQuery.ajax({
			 async: false,
			 type: "POST",
		  	 data: oLocation,
	       	 url: backend.get_url("addLocation"),
	       	 success: function(data) {
	       		var key = data;
	       		rehearsaladdView.locationitems.addItem(new sap.ui.core.Item({text : oLocation.name, key : key}));
	       		rehearsaladdView.locationitems.setSelectedKey(key);
	       		sap.m.MessageToast.show("Location erfolgreich gespeichert.");	
             },
	         error: function() { 
	        	 sap.m.MessageToast.show("Location konnte nicht gespeichert werden");
	         }
	    });
	},
	
	addRehearsal: function(){
		if (rehearsaladdView.getController().dirty){			
			var model = rehearsaladdView.getModel();			
			if (rehearsaladdView.locationitems.getSelectedKey() == "-1" && rehearsaladdView.getController().addlocation_dirty == true){
				rehearsaladdView.getController().addlocation();
			}
			else if (rehearsaladdView.locationitems.getSelectedKey() == "-1"){			
				sap.m.MessageToast.show("Keine neue Location eingegeben.");
				return;
			}
				model.oData.location = rehearsaladdView.locationitems.getSelectedKey();
			var model = rehearsaladdView.getModel();
			var groupboxes = model.oData.groupboxes;
			var groups_values = [];
			for(var i = 0;i < groupboxes.length;i++){
				if(groupboxes[i].selected){					
					groups_values.push(model.oData.groupboxes[i].id);
				}
			}	
			model.oData.groups = groups_values.join();
			
			// format the date values
			var oldbegin = model.oData.begin;
			var oldend = model.oData.end;
			var oldapprove_until = model.oData.approve_until;
			
			model.oData.begin = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(oldbegin);
			model.oData.end = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(oldend);
			model.oData.approve_until = sap.ui.core.format.DateFormat.getDateTimeInstance({pattern: "yyyy-MM-dd HH:mm:00"}).format(oldapprove_until );
			
			 jQuery.ajax({	
				 type: "POST",
				 data: model.oData,
				 url: backend.get_url("addRehearsal"),
				 beforeSend: function() {
					 sap.ui.core.BusyIndicator.show(500);   
				 },
				 success: function(data) {
					 sap.m.MessageToast.show("Probe wurde erfolgreich gespeichert.");
					 startView.getController().loadAllData();
					 app.to("start");
	             },
		         error: function() { 
		        	 sap.ui.core.BusyIndicator.hide();
		        	 sap.m.MessageToast.show("Probe konnte nicht gespeichert werden.");
		         }
		    });		
		}
		else {			
			sap.m.MessageToast.show("Es wurde nichts verändert.");
		}
	}
	
});