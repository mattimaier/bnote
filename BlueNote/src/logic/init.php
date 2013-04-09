<?php
/**
 * Initializes System
**/
# Check for Login
session_start();
if(!isset($_SESSION["user"])) {
  	header("Location: index.php");
}

# Load all widgets - not automated, due to exclusion of widgets and order
$widgets = array(
	"iwriteable", "box", "dropdown", "dataview", "error", "field",
	"form", "link", "message", "table", "writing", "textwriteable",
	"htmleditor", "imagetable", "filebrowser"
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
if($system_data->getModuleId() == "logout") {
	$_SESSION["user"] = "null";
	unset($_SESSION);
	session_destroy();
	header("Location: index.php");
}

$SYSTEM = array(
 "appname" => $system_data->getApplicationName(),
 "modid" => $system_data->getModuleId(),
 "modtitle" => $system_data->getModuleTitle(),
 "company" => $system_data->getCompany(),
 "username" => $system_data->getUsername()
 );

?>