<?php 
/**
 * Main entry file for the web application.
 */
# debugging
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";
require_once $GLOBALS["DIR_LOGIC"] . "init.php";

# Login forward if necessary
if(isset($_GET["mod"]) && ($_GET["mod"] === "login" || $_GET["mod"] == $system_data->getModuleId("Login")) 
		&& isset($_GET["mode"]) && $_GET["mode"] === "login") {
	require_once $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
	require_once $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
	require_once $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
	$ctrl = new LoginController();
	$loginData = new LoginData();
	$ctrl->setData($loginData);
	$ctrl->doLogin();
}

require_once $GLOBALS["DIR_LOGIC"] . "controller.php";
$mainController = new Controller();
global $mainController;

?>

<!DOCTYPE html>
<HTML>

<?php
# Display HEAD
require_once $GLOBALS["DIR_PRESENTATION"] . "head.php";
?>

<BODY>

<?php
include "content.php";

# Display Footer
require_once $GLOBALS["DIR_PRESENTATION"] . "footer.php";
?>

</BODY>

</HTML>
