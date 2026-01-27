<?php
/**
 * Initializes System
**/

# Start session
session_start();

# Load all widgets - not automated, due to exclusion of widgets and order
$widgets = array(
	"iwriteable", "box", "dropdown", "dataview", "error", "field",
	"form", "link", "message", "table", "writing", "textwriteable",
	"htmleditor", "filebrowser", "groupselector", "filterbox",
	"list", "plainlist", "card", "listfield", "participation", "chat"
);

foreach($widgets as $id => $file) {
	$widget_file = $GLOBALS["DIR_WIDGETS"] . $file . ".php";
	if(file_exists($widget_file)) {
		require($widget_file);
	}
}

# Validate mod parameter
if (isset($_GET['mod'])) {
    $mod = $_GET['mod'];
    // Validate: alphanumeric only, 1-100 characters
	if (!preg_match('/^[a-zA-Z0-9]{1,100}$/', $mod)) {
	    die('Error: "mod" parameter must contain only alphanumeric characters.');
	}
}

# Inizialize System Array
require_once $GLOBALS["DIR_DATA"] . "systemdata.php";
$system_data = new Systemdata();

# Load language
require_once "lang.php";

# Logout
if(isset($_GET["mod"]) && (
		$_GET["mod"] === "logout" 
		|| $system_data->getModuleId("Logout") == $_GET["mod"]) 
		|| ($_GET["mod"] == "login" && isset($_GET["mode"]) && $_GET["mode"] == "logout")
		) {
	$_SESSION["user"] = NULL;
	unset($_SESSION);
	session_destroy();
	header("Location: main.php?mod=login");
}

?>