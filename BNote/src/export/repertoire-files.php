<?php

/**
 * Handles files in the repertoire (JSON REST interface).
 * @author matti
 *
 */
session_start();

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");
require_once($GLOBALS["DIR_WIDGETS"] . "message.php");
require_once($GLOBALS["DIR_WIDGETS"] . "link.php");

require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "repertoiredata.php");

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;
$db = $system_data->dbcon;

// check whether a user is registered and has contact (mod=3) permission
$deniedMsg = Lang::txt("repertoire_files_start.deniedMsg");
if(!isset($_SESSION["user"])) {
	http_response_code(403);
	new BNoteError($deniedMsg);
}
else {
	$userCt = $db->getCell("privilege", "count(*)", "module = 6 AND user = " . $_SESSION["user"]);
	if($userCt < 1) {
		http_response_code(403);
		new BNoteError($deniedMsg);
	}
}

// read system config
$sysconfig = new XmlData($dir_prefix . $GLOBALS["DIR_CONFIG"] . "config.xml", "Software");

// get access to repertoire data
$repertoireData = new RepertoireData($dir_prefix);

// check if search term is present, otherwise return nothing
if(!isset($_GET["term"]) || strlen($_GET["term"]) < 3) {
	http_response_code(400);
	return json_encode(array("success" => False, "message" => Lang::txt("repertoire_files_start.message")));
}

// search filesystem for file with term
// Does not support flag GLOB_BRACE
function glob_recursive($pattern, $flags = 0) {
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
	}
	return $files;
}

$shareDirPrefix = "../../data/share/";
$searchTerm = $shareDirPrefix . $_GET["term"] . "*.*";
$filesFound = glob_recursive($searchTerm);

$results = array();
foreach($filesFound as $i => $found) {
	array_push($results, substr($found, strlen($shareDirPrefix)));
}

// output
header("Content-Type: application/json");
echo json_encode($results);
?>