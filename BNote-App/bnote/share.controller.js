sap.ui.controller("bnote.share", {

	onAfterRendering: function() {
    	var oCtrl = this;
        
        var model = new sap.ui.model.json.JSONModel({
            "framecode": "<iframe style='width:100%;height:98%;z-index:-1;overflow-y: hidden;' width='100%' height='100%' "
                     + "src='../BNote/embed.php?mod=12&mobilePin=" + mobilePin + "'></iframe>"
        });
        oCtrl.getView().setModel(model);
    }

});