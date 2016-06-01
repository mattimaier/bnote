// my application is in folder portalcss/
sap.ui.localResources("bnote");


// Global Data
mobilePin = null;  // Default null


                
backend = {
		
	get_url: function(func) {
		var path = "../BNote/src/export/bna-json.php";
		var url = path + "?func=" + func;
		if(func != "mobilePin") {
			// add token
			url += "&pin=" + mobilePin;
		}
		return url;
	},
	
	formatdate: function(datepath, model){
		var rehearsals = model.getProperty("/rehearsals");	    
	    rehearsals.forEach(function(rehearsal, idx) {
        	var olddate = model.getProperty("/rehearsals/" + idx + datepath);
        	var newdate = new Date(Date.parse(olddate)); 
        	newdate.toString = function(){
        		var d = backend.leadingZero(this.getDate());
        		var m = backend.leadingZero(this.getMonth());
        		var h = backend.leadingZero(this.getHours());
        		var min = backend.leadingZero(this.getMinutes());
        		return d + "." + m + "." + this.getFullYear() + " " + h + ":" + min + " Uhr";
        	}
          	model.setProperty("/rehearsals/"+ idx + datepath ,newdate);
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
// Build the app together
app = new sap.m.App("bnoteApp", {
    initialPage: "login"
});

app.addPage(loginView);
app.addPage(startView);
app.addPage(rehearsalView);
app.addPage(memberView);
app.addPage(communicationView);
app.addPage(memberdetailView);

var shell = new sap.m.Shell("bnoteShell", {
    title: "BNote WebApp",
    app: app
});
shell.placeAt("content");