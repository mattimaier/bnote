sap.ui.controller("bnote.share", {

	onAfterRendering: function() {
    	var oCtrl = this;
        
        var dest = "../BNote/embed.php?mod=12&mobilePin=" + mobilePin;
        var model = new sap.ui.model.json.JSONModel({
            "framecode": "<iframe id='shareframe' style='width:100%;height:98%;z-index:-1;overflow-y: hidden;' width='100%' height='100%' "
                     + "src='" + dest + "'></iframe>"
        });
        oCtrl.getView().setModel(model);
        oCtrl.loadIframe(dest);
    },
    
    stayInWebapp: function() {
        var oCtrl = this;
        if(("standalone" in window.navigator) && window.navigator.standalone) {
            jQuery('#shareframe').contents().find('a').click(function(event) {
                var dest = jQuery(this).attr("href");
                event.preventDefault();
                oCtrl.loadIframe(dest);
                });
        }
        return true;
    },
    
    loadIframe: function(dest) {
        var oCtrl = this;
        var node = jQuery('#shareframe');
        node.attr("src", dest);        
        oCtrl.stayInWebapp();
    }

});