// my application is in folder portalcss/
sap.ui.localResources("bnote");


// Global Data
mobilePin = null;  // Default null

backend = {
	host: "localhost",
	protocol: "http",
	path: "/Projekte/bnote/BNote/src/export/bna-json.php",
	
	get_url: function(func) {
		var url = this.protocol + "://" + this.host + this.path + "?func=" + func;
		if(func != "mobilePin") {
			// add token
			url += "&pin=" + mobilePin;
		}
		return url;
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


// Build the app together
app = new sap.m.App("bnoteApp", {
    initialPage: "login"
});

app.addPage(loginView);
app.addPage(startView);
app.addPage(rehearsalView);

var shell = new sap.m.Shell("bnoteShell", {
    title: "BNote WebApp",
    app: app
});
shell.placeAt("content");