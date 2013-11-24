<?php

/**
 * Creates an iCalendar output of a data set.
 * @author matti
 *
 */

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
// include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
// include $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");

/*
 * The user login is NOT checked in this case, because otherwise
 * it is not possible to include the .ics in an external application
 * easily.
 */

// SETUP
$timezone = "Europe/Berlin"; // timezone in which the datetimes are specified

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;

$db = $system_data->dbcon;
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php");
$startdata = new StartData($dir_prefix);
$adp = $startdata->adp();

// read system config
$organizer = $system_data->getCompany();

// set username if set
if(isset($_GET["user"])) {
	$userid = $db->getCell($db->getUserTable(), "id", "login = '" . $_GET["user"] . "'");
	$_SESSION["user"] = $userid;
}
else {
	$userid = null;
}

// set content format
header( "Content-type:text/calendar charset=utf-8" );
echo "BEGIN:VCALENDAR\n";
echo "VERSION:2.0\n";

if($userid == null || $userid < 1) {
	// get all rehearsals
	$query = "SELECT rehearsal.id as id, DATE_FORMAT(begin, \"%Y%m%dT%H%i%s\") as begin,";
	$query .= " DATE_FORMAT(end, \"%Y%m%dT%H%i%s\") as end, ";
	$query .= " rehearsal.notes, name, street, city ";
	$query .= " FROM rehearsal, location, address";
	$query .= " WHERE location = location.id AND address = address.id";
	$rehearsals = $db->getSelection($query);
}
else {
	// get user's rehearsals including those from phases
	$rehearsals = $startdata->getUsersRehearsals();	
}

// write them
for($i = 1; $i < count($rehearsals); $i++) {
	echo "BEGIN:VEVENT\n";
	echo "SUMMARY:Probe $organizer\n";
	echo "ORGANIZER:$organizer\n";
	echo "DTSTART;TZID=$timezone:" . $rehearsals[$i]["begin"] . "\n";
	echo "DTEND;TZID=$timezone:" . $rehearsals[$i]["end"] . "\n";
	
	if($rehearsals[$i]["name"] != "") { 
		echo "LOCATION:" . $rehearsals[$i]["name"] . " - " .
			$rehearsals[$i]["street"] . ", " . $rehearsals[$i]["city"] . "\n";
	}
	else if($rehearsal[$i]["location"] != "") {
		// fetch rehearsal location
		$query = "SELECT l.name, a.street, a.city ";
		$query .= "FROM location l JOIN address a ON l.address = a.id ";
		$query .= "WHERE l.id = " . $rehearsal[$i]["location"];
		$addy = $db->getRow($query);
		echo "LOCATION:" . $addy["name"] . " - " . $addy["street"] . ", " . $addy["city"] . "\n";
	}
	
	// get songs to practise
	$query = "SELECT title ";
	$query .= "FROM song s, rehearsal_song rs ";
	$query .= "WHERE rs.rehearsal = " . $rehearsals[$i]["id"] . " AND s.id = rs.song ";
	$query .= "ORDER BY title";
	$songs = $db->getSelection($query);
	
	// write songs to practise in notes
	$notes = "Bitte folgende Stücke üben: ";
	for($j = 1; $j < count($songs); $j++) {
		$notes .= $songs[$j]["title"] . ", ";
	}
	if(count($songs) > 1) {
		$notes = substr($notes, 0, strlen($notes)-2);
	}
	else {
		$notes .= "keine";
	}
	
	echo "COMMENT:$notes\n";
	echo "END:VEVENT\n";
}

if($userid == null || $userid < 1) {
	// get all concerts
	$query = "SELECT DATE_FORMAT(begin, \"%Y%m%dT%H%i%s\") as begin,";
	$query .= " DATE_FORMAT(end, \"%Y%m%dT%H%i%s\") as end, ";
	$query .= " concert.notes, name, street, city ";
	$query .= " FROM concert, location, address";
	$query .= " WHERE location = location.id AND address = address.id";
	$concerts = $db->getSelection($query);
}
else {
	// get all concert including those from phases
	$concerts = $startdata->getUsersConcerts();
}

// write them
for($i = 1; $i < count($concerts); $i++) {
	echo "BEGIN:VEVENT\n";
	echo "SUMMARY:Konzert " . $concerts[$i]["name"] . "\n";
	echo "ORGANIZER:$organizer\n";
	echo "DTSTART;TZID=$timezone:" . $concerts[$i]["begin"] . "\n";
	echo "DTEND;TZID=$timezone:" . $concerts[$i]["end"] . "\n";
	if($userid == null || $userid < 1) {
		$location = $concerts[$i]["name"] . " (" .$concerts[$i]["street"] . ", ";
		$location .= $concerts[$i]["city"] . ")";
	}
	else {
		$location = $concerts[$i]["location_name"] . " (" . $concerts[$i]["location_street"] . ", ";
		$location .= $concerts[$i]["location_city"] . ")";
	}
	echo "LOCATION:" . $location . "\n";
	echo "COMMENT:" . $concerts[$i]["notes"] . "\n";
	echo "END:VEVENT\n";
}

// finish
echo "END:VCALENDAR\n";
?>