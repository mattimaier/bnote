<?php 
/**
 * Main entry file for the web application.
 * @see index.php for details on the authors.
 */

# Desktop or mobile
$isMobile = false;
if(isset($_GET["device"]) && $_GET["device"] == "mobile") {
	$isMobile = true;
}
global $isMobile;

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";

# change dirs for mobile
if($isMobile) {
	$DIR_PRESENTATION_MODULES = $GLOBALS["DIR_PRESENTATION_MOBILE"];
}

include $GLOBALS["DIR_LOGIC"] . "init.php";

# Login forward if necessary
if(isset($_GET["mod"]) && $_GET["mod"] === "login" && isset($_GET["mode"]) && $_GET["mode"] === "login") {
	include $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
	include $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
	include $GLOBALS["DIR_DATA"] . "fieldtype.php";
	include $GLOBALS["DIR_DATA"] . "abstractdata.php";
	include $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
	$ctrl = new LoginController();
	$loginData = new LoginData();
	$ctrl->setData($loginData);
	$ctrl->doLogin();
}

include $GLOBALS["DIR_LOGIC"] . "controller.php";
$mainController = new Controller();
global $mainController;

?>

<!DOCTYPE html>
<HTML lang="de"> <!--  manifest="bnote.appcache" -->

<?php

# Display HEAD
if($isMobile) {
	include $GLOBALS["DIR_PRESENTATION_MOBILE"] . "head.php";
}
else {
	include $GLOBALS["DIR_PRESENTATION"] . "head.php";
}

?>

<BODY>

<?php
if($isMobile) {
	include "mobile.php";
}
else {
	include "desktop.php";
}

# Display Footer
include $GLOBALS["DIR_PRESENTATION"] . "footer.php";
?>

</BODY>

</HTML>
