<?php 

/*
 * processor to login the user and forward to the main system
 */
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");
require_once($GLOBALS["DIR_WIDGETS"] . "message.php");
require_once($GLOBALS["DIR_WIDGETS"] . "link.php");

// Verify Input
$username = "";
$password = "";

if(!isset($_POST["login"]) || !isset($_POST["password"])) {	
	new Error('Ung&uuml;ltige Eingabe. Deine Anmeldedaten sind nicht korrekt.<br /> <a href="' . $dir_prefix . '">Zur&uuml;ck</a>');
}
else {
	$regex = new Regex();
	$username = $_POST["login"];
	$password = $_POST["password"];
	$regex->isLogin($username);
	$regex->isPassword($_POST["password"]);
}

// Build Database Connection
$db = new Database();

// read system config
$sysconfig = new XmlData($dir_prefix . $GLOBALS["DIR_CONFIG"] . "config.xml", "Software");

// Verify login Data
$db_pw = $db->getCell($db->getUserTable(), "password", "login = '" . $username . "' AND isActive = 1");

// Encrypt password
$password = crypt($password, CRYPT_BLOWFISH);

//echo "encpw: " . $password;

// Forward OR Reject
if($db_pw == $password) {
	$userid = $db->getCell($db->getUserTable(), "id", "login = '" . $username . "'");
	session_start();
	$_SESSION["user"] = $userid;
	
	// Save last logged in
	$db->execute("UPDATE " . $db->getUserTable() . " SET lastlogin = NOW() WHERE id = $userid");
	
	// go to application
	header("Location: ../../main.php?mod=" . $sysconfig->getParameter("StartModule"));
}
else {
	new Error("Bitte &uuml;berpr&uuml;fe deine Anmeldedaten.<br />
		Falls diese Nachricht erneut auftritt, wende dich bitte an deinen Bandleiter.<br />
		<a href=\"" . $dir_prefix . "\">Zur&uuml;ck</a><br />
		");
}


?>
