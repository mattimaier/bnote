// my application is in folder portalcss/
sap.ui.localResources("bnote");


// Global Data
mobilePin = null;  // Default null
logindata = {
    	bnoteserver_adress: "",
        login: "",
        password: "",
    };
prefs = AppPreferences;

// Please change this if your desktop application is at a different location
desktop_path = "../BNote/main.php?mod=login&device=desktop";

backend = {
		
	comparedates: function(startDatePath, stopDatePath, model){
		var startdate = model.getProperty(startDatePath); // Already Date Object
		var stopdate = backend.parsedate(model.getProperty(stopDatePath));
		var deltams = stopdate.getTime() - startdate.getTime();
		var delta = new Date (deltams);
		return delta;
	},	
		
	get_url: function(func) {
		var path = logindata.bnoteserver_adresse + "/BNote/src/export/bna-json.php";
		var path = "../BNote/src/export/bna-json.php";
		var url = path + "?func=" + func;
		if(func != "mobilePin") {
			// add token
			url += "&pin=" + mobilePin;
		}
		return url;
	},
	
	parsedate: function(date_str){
		// manual parsing due to a Safari bug is necessary
		var y = date_str.substr(0,4);
		var m = date_str.substr(5,2);
		var d = date_str.substr(8,2);
		var h = 0;
		var i = 0;
		var s = 0;
		if(date_str.length > 10) {
			h = date_str.substr(11,2);
			i = date_str.substr(14,2);
		}
		return new Date(y,parseInt(m)-1,d,h,i,s);
	},
	
	formatdate: function(collectionpath, datepath, model){
		var items = model.getProperty(collectionpath);
		items.forEach(function(entity, idx) {
			var olddate = model.getProperty(collectionpath + "/" + idx + datepath);
			if(typeof(olddate) == "undefined") {
				return;
			}
			var newdate = backend.parsedate(olddate);
			newdate.toString = function() {
				var d = backend.leadingZero(this.getDate());
				var m = backend.leadingZero(this.getMonth()+1); // getMonth begins with 0 for January
				var h = backend.leadingZero(this.getHours());
				var min = backend.leadingZero(this.getMinutes());
				return d + "." + m + "." + this.getFullYear() + " " + h + ":" + min + " Uhr";
			};
			model.setProperty(collectionpath + "/"+ idx + datepath, newdate);
			model.setProperty(collectionpath + "/"+ idx + datepath + "_original", olddate);
		});
		return model;
	},
	
	leadingZero: function(i) {
		if (i<10){
			return "0" + i;
		}
		return i;
	}
	
};

// Permission Control
permission = null;
/*
 * Foreach module contains access controlled controls
 */
accessControls = {
		3: [],
		5: [],
		6: [],
		7: [],
		16: [],
		20: [],
		21: []		
}

function setPermissions() {
	permission = null;
		jQuery.ajax({
			async: false,
			type: "POST",
        	url: backend.get_url("hasUserAccess"),        	
        	success: function(data) {        		
        		permission = data;
        		$.each(accessControls, function(index, value) { 
        		    if (permission.indexOf(index) != -1){
        		    	value.forEach(function(element, arrindex, array){
        		    		var myelement = sap.ui.getCore().byId(element.sId);
        		    		myelement.setVisible(true);        		    		
        		    	});
        		    } else{
        		    	value.forEach(function(element, arrindex, array){
        		    		var myelement = sap.ui.getCore().byId(element.sId);
        		    		myelement.setVisible(false);        		    		
        		    	});
        		    
        		    }       		    
        		});
            },
			error: function(){		
				permission = null;
			}
        });	
	return permission;
}

// Global Navigation bar
jQuery.sap.require("sap.ui.core.IconPool");
	
function getNaviBar(){
	
	var emailButton = new sap.m.Button({
		visible: false,
		icon : sap.ui.core.IconPool.getIconURI("email"),
		press : function() {
			communicationView.getController().getData();
			app.to("communication");
		}
	});	
	accessControls[7].push(emailButton);

	var repertoireButton = new sap.m.Button({
		visible: false,
		icon: sap.ui.core.IconPool.getIconURI( "documents" ),
		press: function() {
			repertoireView.getController().onRepertoireClick();
		}
	});
	accessControls[6].push(repertoireButton);
	   	
	var equipmentButton = new sap.m.Button({
		visible: false,
		icon: sap.ui.core.IconPool.getIconURI("projector"),
		press: function() {
			equipmentView.getController().onEquipmentClick();
		}
	});
	accessControls[21].push(equipmentButton);
	
	return new sap.m.OverflowToolbar({
			active : true,
			design : sap.m.ToolbarDesign.Solid,
			content : [
				new sap.m.Button({
					icon : sap.ui.core.IconPool.getIconURI("home"),
					press : function() {
						app.to("start")
					}
				}),
				new sap.m.Button({
					icon : sap.ui.core.IconPool.getIconURI("person-placeholder"),
					press : function() {
						memberView.getController().onMemberClick();
						app.to("member")
					}
				}), 
				emailButton,
				new sap.m.Button({
				    icon: sap.ui.core.IconPool.getIconURI( "marketing-campaign" ),
				    press: function() {
				    	app.to("news")
				    }
			    }),
			   repertoireButton,			   
			   equipmentButton
			]		
		});	
	}

// Global View Definitions
loginView = sap.ui.view({
    id: "login",
    viewName: "bnote.login",
    type: sap.ui.core.mvc.ViewType.JS
});

startView = sap.ui.view({
    id: "start",
    viewName: "bnote.start",
    type: sap.ui.core.mvc.ViewType.JS
});

rehearsalView = sap.ui.view({
    id: "rehearsal",
    viewName: "bnote.rehearsal",
    type: sap.ui.core.mvc.ViewType.JS
});

memberView = sap.ui.view({
	id: "member",
	viewName: "bnote.member",
	type: sap.ui.core.mvc.ViewType.JS
});

communicationView = sap.ui.view({
	id: "communication",
	viewName: "bnote.communication",
	type: sap.ui.core.mvc.ViewType.JS
});

memberdetailView = sap.ui.view({
	id: "memberdetail",
	viewName: "bnote.memberdetail",
	type: sap.ui.core.mvc.ViewType.JS	
});

concertView = sap.ui.view({
	id: "concert",
	viewName: "bnote.concert",
	type: sap.ui.core.mvc.ViewType.JS	
});

taskView = sap.ui.view({
	id: "task",
	viewName: "bnote.task",
	type: sap.ui.core.mvc.ViewType.JS
});

voteView = sap.ui.view({
	id: "vote",
	viewName: "bnote.vote",
	type: sap.ui.core.mvc.ViewType.JS
});

newsView = sap.ui.view({
	id: "news",
	viewName: "bnote.news",
	type: sap.ui.core.mvc.ViewType.JS
});

repertoireView = sap.ui.view({
	id: "repertoire",
	viewName: "bnote.repertoire",
	type: sap.ui.core.mvc.ViewType.JS
});

equipmentView = sap.ui.view({
	id: "equipment",
	viewName: "bnote.equipment",
	type: sap.ui.core.mvc.ViewType.JS
});

repertoiredetailView = sap.ui.view({
	id: "repertoiredetail",
	viewName: "bnote.repertoiredetail",
	type: sap.ui.core.mvc.ViewType.JS
});

repertoireaddView = sap.ui.view({
	id: "repertoireadd",
	viewName: "bnote.repertoireadd",
	type: sap.ui.core.mvc.ViewType.JS
});

equipmentdetailView = sap.ui.view({
	id: "equipmentdetail",
	viewName: "bnote.equipmentdetail",
	type: sap.ui.core.mvc.ViewType.JS
});

equipmentaddView = sap.ui.view({
	id: "equipmentadd",
	viewName: "bnote.equipmentadd",
	type: sap.ui.core.mvc.ViewType.JS
});

reservationView = sap.ui.view({
	id: "reservation",
	viewName: "bnote.reservation",
	type: sap.ui.core.mvc.ViewType.JS
});

reservationaddView = sap.ui.view({
	id: "reservationadd",
	viewName: "bnote.reservationadd",
	type: sap.ui.core.mvc.ViewType.JS
});

contactaddView = sap.ui.view({
	id: "contactadd",
	viewName: "bnote.contactadd",
	type: sap.ui.core.mvc.ViewType.JS
});

rehearsaladdView = sap.ui.view({
	id: "rehearsaladd",
	viewName: "bnote.rehearsaladd",
	type: sap.ui.core.mvc.ViewType.JS
});

taskaddView = sap.ui.view({
	id: "taskadd",
	viewName: "bnote.taskadd",
	type: sap.ui.core.mvc.ViewType.JS
});

voteresultView = sap.ui.view({
	id: "voteresult",
	viewName: "bnote.voteresult",
	type: sap.ui.core.mvc.ViewType.JS
});

// Build the app together
app = new sap.m.App("bnoteApp", {
	defaultTransitionName: "flip",
    initialPage: "login"
});

app.addPage(loginView);
app.addPage(startView);
app.addPage(rehearsalView);
app.addPage(memberView);
app.addPage(communicationView);
app.addPage(memberdetailView);
app.addPage(concertView);
app.addPage(taskView);
app.addPage(voteView);
app.addPage(newsView);
app.addPage(repertoireView);
app.addPage(equipmentView);
app.addPage(repertoiredetailView);
app.addPage(repertoireaddView);
app.addPage(equipmentdetailView);
app.addPage(equipmentaddView);
app.addPage(reservationView);
app.addPage(reservationaddView);
app.addPage(contactaddView);
app.addPage(rehearsaladdView);
app.addPage(taskaddView);
app.addPage(voteresultView);

var shell = new sap.m.Shell("bnoteShell", {
    title: "BNote WebApp",
    app: app
});

shell.placeAt("content");
