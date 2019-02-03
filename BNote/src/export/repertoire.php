<?php

/**
 * Creates a csv export from the repertoire.
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
$deniedMsg = "Du hast keine Berechtigung das Repertoire zu exportieren!";
if(!isset($_SESSION["user"])) {
	new BNoteError($deniedMsg);
}
else {
	$userCt = $db->getCell("privilege", "count(*)", "module = 6 AND user = " . $_SESSION["user"]);
	if($userCt < 1) {
		new BNoteError($deniedMsg);
	}
}

// read system config
$sysconfig = new XmlData($dir_prefix . $GLOBALS["DIR_CONFIG"] . "config.xml", "Software");

// get access to repertoire data
$repertoireData = new RepertoireData($dir_prefix);

$data = $repertoireData->exportData();
$header = array();

header("Content-Type: application/csv");

for($i = 0; $i < count($data); $i++) {
	$row = $data[$i];
	if($i == 0) {
		echo '"' . join('","', $row) . '"' . "\n";
		$header = $row;
		continue;
	}
	
	// body
	$rowData = array();
	foreach($header as $j => $field) {
		$fieldName = strtolower($field);
		if(!isset($row[$fieldName])) {
			$fieldName = $field;
		}
		if(isset($row[$fieldName])) {
			array_push($rowData, $row[$fieldName]);
		}
		else {
			array_push($rowData, "");
		}
	}
	echo '"' . join('","', $rowData) . '"' . "\n";
}

?>