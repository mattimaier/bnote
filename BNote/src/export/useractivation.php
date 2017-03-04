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
	printError("Ungültige Eingabe.");
}

$userid = $_GET["uid"];
$email = $_GET["email"];

// VALIDATE VALUES
$uid_exists = $db->getCell($db->getUserTable(), "count(id)", "id = $userid");
if($uid_exists < 1) {
	printError("Benutzer-ID wurde nicht gefunden.");
}
$cid = $db->getCell($db->getUserTable(), "contact", "id = $userid");
$user_email = $db->getCell("contact", "email", "id = $cid");
if($email != $user_email) {
	printError("Ungültige E-Mail-Adresse.");
}

// ACTIVATE USER
$query = "UPDATE " . $db->getUserTable() . " SET isActive = 1 WHERE id = $userid";
$db->execute($query);
echo "<p><b>Benutzerkonto aktiviert!</b><br/>Dein Benutzerkonto wurde erfolgreich aktiviert. Du kannst dich nun anmelden.</p>";


// error function
function printError($err) {
	echo "<p><b>Fehler!</b><br/>Die Aktivierung war nicht erfolgreich. Bitte wende dich an deinen Leiter.<br/>";
	echo "<i>Fehlermeldung: $err</i></p>";
	exit();
}
?>