sap.ui.controller("bnote.concert", {

    onParticipationPress: function(concertSetParticipation){
        var model = concertView.getModel();
        var oBindingContext = concertView.getBindingContext();
        var path = oBindingContext.getPath();

        if (concertSetParticipation == 2 || concertSetParticipation == 0){		
            this.getView().oDialog.open();
        }else{
            this.getView().explanation.setValue("");
        }

        this.oData = {
            concert : model.getProperty(path + "/id"),
            participation : concertSetParticipation,
            explanation : ""
        };	
    },	

    submit: function(){
        var participation = this.oData.participation;
        this.oData.explanation = this.getView().explanation.getValue();	
        jQuery.ajax({
            url : backend.get_url("setConcertParticipation"),
            type : "POST",
            data : this.oData,
            success : function(result) {
                sap.m.MessageToast.show("Teilnahme wurde aktualisiert.");
                var model = concertView.getModel();
                var oBindingContext = concertView.getBindingContext();
                var path = oBindingContext.getPath();
                model.setProperty(path + "/participate", participation);
            },
            error : function(a, b, c) {
                sap.m.MessageToast.show("Teilnahme konnte nicht aktualisiert werden.");
                console.log(a, b, c);
            }
        });
    },
    
    onProgramPress: function(program) {
        // request the program and set the data on the program view
        jQuery.ajax({	
             type: "GET",
             url: backend.get_url("getProgram") + "&id=" + program.id,
             beforeSend: function() {
                 sap.ui.core.BusyIndicator.show(500);   
             },
             success: function(programData) {
                 sap.ui.core.BusyIndicator.hide();
                 //TODO continue
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 sap.ui.core.BusyIndicator.hide();
                 sap.m.MessageToast.show("Fehler beim Laden des Programms.");
             }
        });
    }

});