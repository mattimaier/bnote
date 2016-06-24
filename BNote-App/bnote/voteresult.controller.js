sap.ui.controller("bnote.voteresult",{
	
	getVoteResult: function(voteid) {
		var oCtrl = this;		
		voteresultView.voteResultList.destroyItems();
		
		jQuery.ajax({	
			 type: "GET",
			 data: {"id": voteid},
			 url: backend.get_url("getVoteResult"),
			 success: function(data) {
				 var model = new sap.ui.model.json.JSONModel(data);
				
				 // format dates
				 if (model.oData.is_date == 1){			
						backend.formatdate("/options", "/name", model);			
					}	
				 oCtrl.buildList(model);
				 console.log(model);
			 },
	         error: function() {
	        	 sap.m.MessageToast.show("Das Abstimmungsergebnis konnte nicht geladen werden.");	        	
	        }
	   });		
	},

	buildList: function(model) {
			for (var i = 0; i < model.getProperty("/options").length; i++){
				
				var yesresult = "Ja: " + model.getProperty("/options/" + i + "/choice/1");
				var noresult = model.getProperty("/options/" + i + "/choice/0");
				var resultname = model.getProperty("/options/" + i + "/name");
				
				var voter = parseInt(model.getProperty("/options/0/choice/0")) + 
							parseInt(model.getProperty("/options/0/choice/1")) +
							parseInt(model.getProperty("/options/0/choice/2"));
				
				if (model.getProperty("/is_multi") == 1){
					var mayberesult = model.getProperty("/options/" + i + "/choice/2");
					var intro = new sap.m.ObjectStatus({text: "Nein: " + noresult + " Vielleicht: " + mayberesult});
				}
				else{
					var intro =  new sap.m.ObjectStatus({text: "Nein: " + noresult});
				}				
				var objlistItem = new sap.m.ObjectListItem({
					type: "Active",
					title: resultname,
					number: yesresult,
					firstStatus: intro,
				});
				
				objlistItem.resultname_original = model.getProperty("/options/" + i + "/name_original");
				
				if (model.getProperty("/is_date") == 1){
					objlistItem.attachPress(function(evt){			
						var resultname_original = evt.getSource().resultname_original;
						rehearsaladdView.getController().prepareModelFromVoteresult(resultname_original);
						app.to("rehearsaladd");
					});
				}
				
				voteresultView.voteResultList.addItem(objlistItem);	
			}
			sap.ui.getCore().byId("VoteresultPage").setTitle(model.getProperty("/name"));
			
			voteresultView.headerBar.destroyContentLeft();
			voteresultView.headerBar.destroyContentMiddle();			
			voteresultView.headerBar.destroyContentRight();
			
			
			voteresultView.headerBar.insertContentMiddle(new sap.m.Text({text: "Teilnehmer: " + voter}));
			voteresultView.headerBar.insertContentRight(new sap.m.Text({text: "Ergebnis"}));
			voteresultView.headerBar.insertContentLeft(new sap.m.Text({text: "Option:"}));
	
	}
});