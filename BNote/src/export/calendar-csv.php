<?php
/**
 * Creates an CSV Calendar export
 * @author matti
 *
 */
if(!isset($_GET["user"])) {
	echo "PLEASE SPECIFY THE USER!";
	exit(1);
}

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($dir_prefix . "lang.php");
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");

/*
 * The user login is NOT checked in this case, because otherwise
 * it is not possible to include the .ics in an external application
 * easily.
 */

// SETUP
$timezone = "Europe/Berlin"; // timezone in which the datetimes are specified
global $timezone;
$timezone_on = false; // set to true to turn the timezone setting on.
global $timezone_on;

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;

$db = $system_data->dbcon;
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractlocationdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php");
$startdata = new StartData($dir_prefix);
$adp = $startdata->adp();

/**
 * define a function to write the timecodes correctly
 * @param String $datetime Date Time String in Format: YYYY-MM-DD HH:ii:ss
 * @return String in format YYYYMMDDTHHiissZ
 */
function convertTime($datetime) {
	$dt = DateTime::createFromFormat("Y-m-d H:i:s", $datetime);
	$utc = new DateTimeZone('UTC');
	$dt->setTimezone($utc);
	return $dt->format('m/d/Y h:i a');
}

// set username
if(isset($_GET["user"])) {
	$userid = $db->getCell($db->getUserTable(), "id", "login = '" . $_GET["user"] . "'");
	$_SESSION["user"] = $userid;
}
else if(isset($_SESSION["user"])) {
	$userid = $_SESSION["user"];
}
else {
	$userid = null;
}

// set content format
header("Content-type:text/csv");
$SEPARATOR = ";";

/*** HEADER ***/
$header = array("Subject", "Start Date", "Start Time", "End Date", "End Time", "All Day Event", "Description", "Location", "Private");
echo join($SEPARATOR, $header) . "\n";

function compileLine($start, $end, $subject, $description, $location, $separator) {
	$startDt = convertTime($start);
	$startSepPos = strpos($startDt, " ");
	$startDate = substr($startDt, 0, $startSepPos);
	$startTime = substr($startDt, $startSepPos+1);
	$endDt = convertTime($end);
	$endSepPos = strpos($endDt, " ");
	$endDate = substr($endDt, 0, $endSepPos);
	$endTime = substr($endDt, $endSepPos+1);
	$row = array($subject, $startDate, $startTime, $endDate, $endTime, "False", $description, $location, "False");
	return join($separator, $row) . "\n";
}

/*** REHEARSALS ***/
$rehearsals = $startdata->getUsersRehearsals();
for($i = 1; $i < count($rehearsals); $i++) {
	$rehearsal = $rehearsals[$i];
	$start = $rehearsal["begin"];
	$end = $rehearsal["end"];
	$subject = $system_data->getCompany() . " " . Lang::txt("calendar-csv.rehearsal");
	$description = $system_data->getSystemURL();
	$location = $rehearsal["name"];
	echo compileLine($start, $end, $subject, $description, $location, $SEPARATOR);
}

?>