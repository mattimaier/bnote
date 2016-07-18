sap.ui.controller("bnote.start", {
	
	loadAllData: function() {
    	var oCtrl = this;

        // load all data asynchronously
        jQuery.ajax({
        	url: backend.get_url("getAll"),
        	success: function(response) {
        		var data = response.data;
                var rehearsals = data[0]['rehearsals'];
                var concerts = data[1]['concerts'];
                var tasks = data[4]['task'];
                var reservation = data[5]['reservation'];
                var votes = data[6]['votes'];                
                var items = oCtrl.startItemMapping(rehearsals, "Rehearsal", "begin", ["location", "name"], "icons/proben.png");
                var concert_items = oCtrl.startItemMapping(concerts, "Concert", "begin", ["location","name"], "icons/konzerte.png");
                items = items.concat(concert_items);
                var tasks_items = oCtrl.startItemMapping(tasks, "Task", "due_at", ["title"], "icons/tasks.png");
                items = items.concat(tasks_items);
                var votes_items = oCtrl.startItemMapping(votes, "Vote", "end", ["name"], "icons/abstimmung.png");
                items = items.concat(votes_items);
                var reservation_items = oCtrl.startItemMapping(reservation, "Reservation", "begin", ["name"], "icons/booking.png");
                items = items.concat(reservation_items);
                
                
     	        // set model on related views
                var model = new sap.ui.model.json.JSONModel({
                  /*
                   * The common interface for all start items is:
                   * - id
                   * - type: Rehearsal, Concert, Vote, Task
                   * - start
                   * - description
                   * - icon
                   */
                  "items": items
	              }); 
	              // transform the date into a readable format
	              backend.formatdate("/items", "/due_at", model);
	              backend.formatdate("/items", "/start", model);
	              backend.formatdate("/items", "/begin", model);
	              backend.formatdate("/items", "/end", model);
                
	              oCtrl.generateModel(model);
            },
            error: function(a,b,c) {
            	sap.ui.core.BusyIndicator.hide();
                sap.m.MessageToast.show("Laden der Daten fehlgeschlagen");
                console.log(b + ": " + c);
            }
        });
      
        // set visibility of "add" Buttons and actionsheet
        if (permission.indexOf("3") != -1) {
        	startView.contactaddButton.setVisible(true);
        	startView.startaddButton.setVisible(true);
        } else {
        	startView.contactaddButton.setVisible(false);
        }
        if (permission.indexOf("5") != -1) {
        	startView.rehearsaladdButton.setVisible(true);
        	startView.startaddButton.setVisible(true);
        } else {
        	startView.rehearsaladdButton.setVisible(false);
        }
        if (permission.indexOf("16") != -1) {
        	startView.taskaddButton.setVisible(true);
        	startView.startaddButton.setVisible(true);
        } else {
        	startView.taskaddButton.setVisible(false);
        }
        if (permission.indexOf("20") != -1) {
        	startView.reservationaddButton.setVisible(true);
        	startView.startaddButton.setVisible(true);
        } else {
        	startView.reservationaddButton.setVisible(false);
        }              
	},	
	
	generateModel: function(model){
		var oCtrl = this;
	      
         // hand over the model to the Views
         oCtrl.getView().setModel(model);
         rehearsalView.setModel(model);
         concertView.setModel(model);
         taskView.setModel(model);
         voteView.setModel(model);
         reservationView.setModel(model);
         sap.ui.core.BusyIndicator.hide(); 
	},
	
	reloadList: function(data){		
		var oCtrl = this;		
		$.each(data.items, function(item_idx,item){	
			switch (item.type) {						
				case "Rehearsal": 
					var participate = item.participate; 	        	
	        		switch (participate){            														
	        			case 1 : icon = "icons/proben_green.png"; break;            			
	        			case 2 : icon = "icons/proben_orange.png"; break;		
	        			case 0 : icon = "icons/proben_grey.png"; 	break;		
	        			default : icon = "icons/proben.png";						
	        		};
	        		item['icon'] = icon;
	        		break; 
				case "Concert": 	
					var participate = item.participate;             		
            		switch (participate){            														
            			case 1 : icon = "icons/konzerte_green.png"; break;            			
            			case 2 : icon = "icons/konzerte_orange.png"; break;		
            			case 0 : icon = "icons/konzerte_grey.png"; 	break;		
            			default : icon = "icons/konzerte.png";						
            		};       
            		item['icon'] = icon;
            		break;
			};			
		}); 
	    var model = new sap.ui.model.json.JSONModel({
             /*
              * The common interface for all start items is:
              * - id
              * - type: Rehearsal, Concert, Vote, Task
              * - start
              * - description
              * - icon
              */
              "items": data.items
	    });
	   
	    oCtrl.generateModel(model);
	},
	
    startItemMapping: function(data, typename, titlefield, descriptionfield, icon) {
        var items = [];
        for(var item_idx in data) {
            var item = data[item_idx];     
            console.log()
            switch (typename){
            	case "Task":
            		// Check the remaining time for the task
                	var startDate = backend.parsedate(item.due_at);
            		var delta = startDate.getTime() - new Date().getTime();
            		
            		var deltadays = delta/(3600000*24); 
            		if(0 < deltadays && deltadays < 3){
            			icon = "icons/task_orange.png";
            		}
            		else if(deltadays < 0){
            			icon = "icons/task_red.png";
            		}; break;             		
            	case "Rehearsal": 
            		var participate = item.participate; 
            		console.log(item);
            		switch (participate){            														
            			case 1 : icon = "icons/proben_green.png"; break;            			
            			case 2 : icon = "icons/proben_orange.png"; break;		
            			case 0 : icon = "icons/proben_grey.png"; 	break;		
            			default : icon = "icons/proben.png";						
            		}; break;           		
            	case "Concert": 
            		var participate = item.participate;             		
            		switch (participate){            														
            			case 1 : icon = "icons/konzerte_green.png"; break;            			
            			case 2 : icon = "icons/konzerte_orange.png"; break;		
            			case 0 : icon = "icons/konzerte_grey.png"; 	break;		
            			default : icon = "icons/konzerte.png";						
            		}; break;         		
            };	
            item['type'] = typename;
            item['start'] = item[titlefield];
            var desc = item[descriptionfield[0]];
            if(typeof(desc) == "object") {
                desc = desc[descriptionfield[1]];
            }
            item['listdescription'] = desc;
            item['icon'] = icon
            items.push(item);
        }
        return items;
    },
    
    onLogout: function() {
        mobilePin = null;
        app.to("login");
    }
	
});