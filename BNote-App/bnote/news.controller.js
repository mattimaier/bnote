sap.ui.controller("bnote.news", {

	onAfterRendering: function() {
    	var oCtrl = this;
        jQuery.ajax({
        	url: backend.get_url("getNews"),
        	dataType: "text",
        	success: function(data) {
                newsView.setNewsData(data);
            },
            error: function(a,b,c) {
            	console.log(b,c);
            }
        });
    },

});