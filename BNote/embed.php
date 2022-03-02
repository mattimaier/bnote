<?php
/**
 * Main entry file for the web application.
*/

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";
require_once $GLOBALS["DIR_LOGIC"] . "init.php";

# Login forward if necessary
if(isset($_GET["mod"]) && $_GET["mod"] === "login" && isset($_GET["mode"]) && $_GET["mode"] === "login") {
	require_once $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
	require_once $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
	require_once $GLOBALS["DIR_DATA"] . "fieldtype.php";
	require_once $GLOBALS["DIR_DATA"] . "abstractdata.php";
	require_once $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
	$ctrl = new LoginController();
	$loginData = new LoginData();
	$ctrl->setData($loginData);
	$ctrl->doLogin();
}

require_once $GLOBALS["DIR_LOGIC"] . "controller.php";
$mainController = new Controller();
?>
<link type="text/css" href="<?php echo "style/css/" . $system_data->getTheme() . "/bnote.css"?>" rel="stylesheet" />

<?php
# content
$mainController->getController()->start();

?>