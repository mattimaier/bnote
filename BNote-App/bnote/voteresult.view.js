sap.ui.jsview("bnote.voteresult", {
	
	getControllerName: function() {
		return "bnote.voteresult";
	},	
	
	createContent: function() {		
		this.voteResultList = new sap.m.List({
		});		
		
		this.headerBar = new sap.m.Bar({			
			contentLeft: [],
			contentMiddle: [],
			contentRight: []
		});
		
		var page = new sap.m.Page("VoteresultPage", {
			showNavButton: true,
			navButtonPress : function(){
				app.back();
				},
			subHeader : [this.headerBar],
            customHeader :[],
			content: [this.voteResultList],
			footer: [getNaviBar()]
		});		
		return page;
	}		
});
	