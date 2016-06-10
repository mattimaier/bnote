sap.ui.jsview("bnote.communication", {
	
	
	
	getControllerName: function() {
		return "bnote.communication";
	},
	
	
	
	//This function is dynamically adding a checkbox for every group
	onAfterRendering : function() {
		console.log("render communicationview");
		var model = this.getModel();
		for (var i = 1; model.getProperty("/group/" + i + "/name") != undefined; i++) {
			this.communicationForm.addContent(new sap.m.CheckBox({
				text : model.getProperty("/group/" + i + "/name"),
				selected: "{/group/" + i + "/selected}"
			}));
		}
		this.communicationForm.addContent(new sap.m.Button({
			text : "Senden",
			press: function(){
				var subject = model.getProperty("/subject");
				var body = model.getProperty("/body");
				var groups = model.getProperty("/group");
				var groupids = [];
				for(var i = 0;i < groups.length;i++){
					if(groups[i].selected){
						groupids.push(groups[i].id);
					}
				}
				var requestdata = {subject: subject, body: body, groups: groupids.join(",")};
				jQuery.ajax({
			        	url: backend.get_url("sendMail"),
			            type: "POST",          	         
			            data: requestdata, 
			            success: function(data) {
			            	// TODO: reset form and toast 
			            	
			            	this.communicationForm.reset();
			            	sap.m.MessageToast.show("Senden erfolgreich");
			                console.log(data);
			            },
			            error: function(a,b,c) {

			            	var frm_elements = communicationView.communicationForm.elements; //TODO needs fixing
			            	console.log(frm_elements );
			            	
			            	for (i = 0; i < frm_elements.length; i++)
			            	{
			            	    field_type = frm_elements[i].type.toLowerCase();
			            	    switch (field_type)
			            	    {
			            	    case "text":
			            	    case "password":
			            	    case "textarea":
			            	    case "hidden":
			            	        frm_elements[i].value = "";
			            	        break;
			            	    case "radio":
			            	    case "checkbox":
			            	        if (frm_elements[i].checked)
			            	        {
			            	            frm_elements[i].checked = false;
			            	        }
			            	        break;
			            	    case "select-one":
			            	    case "select-multi":
			            	        frm_elements[i].selectedIndex = -1;
			            	        break;
			            	    default:
			            	        break;
			            	    }
			            	}
			            	
			                sap.m.MessageToast.show("Senden fehlgeschlagen");
			                console.log(b + ": " + c);
			            }
			       });
			}
		}))
	},

	createContent: function(){
		
		this.communicationForm = new sap.ui.layout.form.SimpleForm({
			layout: sap.ui.layout.form.SimpleFormLayout.ResponsiveGridLayout,
						content:[
						         new sap.m.Label({text: "Betreff"}),
						         new sap.m.Input({
						        	 	name: "inputfield",
						        	 	type: sap.m.InputType.Text,
	 									valueLiveUpdate: true,
	  									value: "{/subject}"
						         }),
						         new sap.m.Label({text: "Nachricht"}),
							     new sap.m.TextArea({
							        	rows: 8,
							        	valueLiveUpdate: true,
							        	value: "{/body}",
							     }),
							     new sap.m.Label({text: "An"}),
			         ]
		})
		
		var page = new sap.m.Page("CommunicationPage", {
	        title: "Kommunikation",
	        showNavButton: true,
	        navButtonPress: function() {
	            app.back();
	        },
			content: [ this.communicationForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	