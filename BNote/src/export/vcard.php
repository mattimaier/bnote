<?php

/**
 * Creates a vcard out of a data set.
 * @author matti
 *
 */
session_start();

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");
require_once($GLOBALS["DIR_WIDGETS"] . "message.php");
require_once($GLOBALS["DIR_WIDGETS"] . "link.php");
require_once($dir_prefix . "lang.php");

// Build Database Connection
$db = new Database();

// check whether a user is registered and has contact (mod=3) permission
$deniedMsg = Lang::txt("vcard_input.deniedMsg");
if(!isset($_SESSION["user"])) {
	http_response_code(403);
	new BNoteError($deniedMsg);
}
else {
	$userCt = $db->colValue("SELECT count(*) as cnt FROM privilege WHERE module = 3 AND user = ?", "cnt", array(array("i", $_SESSION["user"])));;
	if($userCt < 1) {
		http_response_code(403);
		new BNoteError($deniedMsg);
	}
}

// get data
$query = "SELECT c2.*, i.name as instrumentname ";
$query .= "FROM ";
$query .= " (SELECT c.*, a.street, a.city, a.zip ";
$query .= "  FROM contact c ";
$query .= "  LEFT JOIN address a ";
$query .= "  ON c.address = a.id) as c2 ";
$query .= "LEFT JOIN instrument i ";
$query .= "ON c2.instrument = i.id ";
$query .= "ORDER BY c2.name";
$data = $db->getSelection($query);

// write out
header( "Content-type:text/x-vCard" );

for($i = 1; $i < count($data); $i++) {
	$c = $data[$i];
	echo 'BEGIN:VCARD' . "\n";
	echo 'VERSION:3.0' . "\n";
	echo 'N:' . $c["surname"] .';' . $c["name"] . "\n";
	echo 'FN:' . $c["name"] . " " . $c["surname"] . "\n";
	if($c['nickname'] != "") {
		echo 'NICKNAME:' . $c['nickname'] . "\n";
	}
	if(isset($c["company"]) && $c["company"] != "") {
		echo 'ORG:' . $c["company"] . "\n";
	}
	echo 'TEL;TYPE=HOME,VOICE:' . $c["phone"] . "\n";
	echo 'ADR;TYPE=HOME:;;' . $c["street"] . ';' . $c["city"] . ";;" . $c["zip"] . ";\n";
	echo 'EMAIL;PREF;INTERNET:' . $c["email"] . "\n";
	echo 'REV:' . date('Ymd') . "T" . date('His') . "Z" . "\n";
	echo 'END:VCARD' . "\n";
	echo "\n";
}

?>