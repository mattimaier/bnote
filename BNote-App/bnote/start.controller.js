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
                var concert_items = oCtrl.startItemMapping(concerts, "Concert", "begin", ["notes"], "icons/konzerte.png");
                items = items.concat(concert_items);
                var tasks_items = oCtrl.startItemMapping(tasks, "Task", "due_at", ["title"], "icons/tasks.png");
                items = items.concat(tasks_items);
                var votes_items = oCtrl.startItemMapping(votes, "Vote", "end", ["name"], "icons/abstimmung.png");
                items = items.concat(votes_items);
                var reservation_items = oCtrl.startItemMapping(reservation, "Reservation", "begin", ["location"], "icons/booking.png");
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
                backend.formatdate("/items", "/due_at", model);
                backend.formatdate("/items", "/start", model);
                backend.formatdate("/items", "/begin", model);
                backend.formatdate("/items", "/end", model);
                oCtrl.getView().setModel(model);
                rehearsalView.setModel(model);
                concertView.setModel(model);
                taskView.setModel(model);
                voteView.setModel(model);
                reservationView.setModel(model);
                sap.ui.core.BusyIndicator.hide();
            },
            error: function(a,b,c) {
            	sap.ui.core.BusyIndicator.hide();
                sap.m.MessageToast.show("Laden der Daten fehlgeschlagen");
                console.log(b + ": " + c);
            }
        });
    },

    startItemMapping: function(data, typename, titlefield, descriptionfield, icon) {
        var items = [];
        for(var item_idx in data) {
            var item = data[item_idx];
            item['type'] = typename;
            item['start'] = item[titlefield];
            var desc = item[descriptionfield[0]];
            if(typeof(desc) == "object") {
                desc = desc[descriptionfield[1]];
            }
            item['description'] = desc;
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