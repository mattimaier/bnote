<?php
/**
 * Activates a user if the link in the mail is clicked.
 */

// LOAD CONTEXT
$dir_prefix = "../../";
global $dir_prefix;
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
$db = new Database();

// CHECK INPUT
if(!isset($_GET["uid"]) || !isset($_GET["email"])) {
	printError(Lang::txt("useractivation_input.printError"));
}

$userid = $_GET["uid"];
$email = $_GET["email"];

// VALIDATE VALUES
$uid_exists = $db->getCell($db->getUserTable(), "count(id)", "id = $userid");
if($uid_exists < 1) {
	printError(Lang::txt("useractivation_validate.printError"));
}
$cid = $db->getCell($db->getUserTable(), "contact", "id = $userid");
$user_email = $db->getCell("contact", "email", "id = $cid");
if($email != $user_email) {
	printError(Lang::txt("useractivation_user_email.printError"));
}

// ACTIVATE USER
$query = "UPDATE " . $db->getUserTable() . " SET isActive = 1 WHERE id = $userid";
$db->execute($query);
echo Lang::txt("useractivation_update.message");


// error function
function printError($err) {
	echo Lang::txt("useractivation_update.error_1");
	echo Lang::txt("useractivation_update.error_2") . $err . Lang::txt("useractivation_update.error_3");
	exit();
}
?>