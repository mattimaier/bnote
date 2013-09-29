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
	"htmleditor", "imagetable", "filebrowser", "groupselector"
);

foreach($widgets as $id => $file) {
	require($GLOBALS["DIR_WIDGETS"] . $file . ".php");
}

# load additional PHP libraries
require_once $GLOBALS["DIR_LIB"] . "simpleimage.php";

# Inizialize System Array
include $GLOBALS["DIR_DATA"] . "systemdata.php";
$system_data = new Systemdata();

# Logout
if($system_data->getModuleId() === "logout") {
	$_SESSION["user"] = "null";
	unset($_SESSION);
	session_destroy();
	new Message("Abmeldung erfolgreich", "Sie wurden abgemeldet.");
	exit(0);
}

?>