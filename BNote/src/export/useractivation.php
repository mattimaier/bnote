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
$uid_exists = $db->colValue("SELECT count(id) as cnt FROM user WHERE id = ?", "cnt", array(array("i", $userid)));
if($uid_exists < 1) {
	printError(Lang::txt("useractivation_validate.printError"));
}
$user_email = $db->colValue("SELECT email FROM contact c JOIN user u ON u.contact = c.id WHERE u.id = ?", "email", array(array("i", $userid)));
if($email != $user_email) {
	printError(Lang::txt("useractivation_user_email.printError"));
}

// ACTIVATE USER
$query = "UPDATE user SET isActive = 1 WHERE id = ?";
$db->execute($query, array(array("i", $userid)));
echo Lang::txt("useractivation_update.message");


// error function
function printError($err) {
	echo Lang::txt("useractivation_update.error_1");
	echo Lang::txt("useractivation_update.error_2") . $err . Lang::txt("useractivation_update.error_3");
	exit();
}
?>