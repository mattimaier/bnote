<?php
/**
 * Initializes System
**/

# Start session (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

# Validate mod parameter (only if present - API calls don't have it)
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

# Load language - use absolute path from project root
$langFile = __DIR__ . "/../lang.php";
if (file_exists($langFile)) {
    require_once $langFile;
} else {
    // Fallback: try relative path (for backward compatibility)
    require_once "lang.php";
}

# Logout (only if mod parameter exists - API calls don't have it)
if(isset($_GET["mod"])) {
	$mod = $_GET["mod"];
	if($mod === "logout" 
		|| $system_data->getModuleId("Logout") == $mod
		|| ($mod == "login" && isset($_GET["mode"]) && $_GET["mode"] == "logout")
		) {
		$_SESSION["user"] = NULL;
		unset($_SESSION);
		session_destroy();
		header("Location: main.php?mod=login");
	}
}

?>