<?php

/**
 * Creates an iCalendar output of a data set.
 * @author matti
 *
 */

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");

/*
 * The user login is NOT checked in this case, because otherwise
 * it is not possible to include the .ics in an external application
 * easily.
 */

// Build Database Connection
$db = new Database();

// read system config
$sysconfig = new XmlData($dir_prefix . $GLOBALS["DIR_CONFIG"] . "config.xml", "Software");
$bandcfg = new XmlData($dir_prefix . $GLOBALS["DIR_CONFIG"] . "company.xml", "Company");

// set content format
header( "Content-type:text/calendar charset=utf-8" );
echo "BEGIN:VCALENDAR\n";
echo "VERSION:2.0\n";

// set timezone
echo "BEGIN:VTIMEZONE
TZID:Europe/Berlin
X-LIC-LOCATION:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE\n";

// get all rehearsals
$query = "SELECT rehearsal.id as id, DATE_FORMAT(begin, \"%Y%m%dT%H%i%sZ\") as begin,";
$query .= " DATE_FORMAT(end, \"%Y%m%dT%H%i%sZ\") as end, ";
$query .= " rehearsal.notes, name, street, city ";
$query .= " FROM rehearsal, location, address";
$query .= " WHERE location = location.id AND address = address.id";
$rehearsals = $db->getSelection($query);

// write them
for($i = 1; $i < count($rehearsals); $i++) {
	echo "BEGIN:VEVENT\n";
	echo "SUMMARY:Probe " . $bandcfg->getParameter("Name") . "\n";
	echo "ORGANIZER:" . $bandcfg->getParameter("Name") . "\n";
	echo "DTSTART:" . $rehearsals[$i]["begin"] . "\n";
	echo "DTEND:" . $rehearsals[$i]["end"] . "\n";
	echo "LOCATION:" . $rehearsals[$i]["name"] . " - " .
			$rehearsals[$i]["street"] . ", " . $rehearsals[$i]["city"] . "\n";
	
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

// get all concerts
$query = "SELECT DATE_FORMAT(begin, \"%Y%m%dT%H%i%sZ\") as begin,";
$query .= " DATE_FORMAT(end, \"%Y%m%dT%H%i%sZ\") as end, ";
$query .= " concert.notes, name, street, city ";
$query .= " FROM concert, location, address";
$query .= " WHERE location = location.id AND address = address.id";
$concerts = $db->getSelection($query);

// write them
for($i = 1; $i < count($concerts); $i++) {
	echo "BEGIN:VEVENT\n";
	echo "SUMMARY:Konzert " . $concerts[$i]["name"] . "\n";
	echo "ORGANIZER:" . $bandcfg->getParameter("Name") . "\n";
	echo "DTSTART:" . $concerts[$i]["begin"] . "\n";
	echo "DTEND:" . $concerts[$i]["end"] . "\n";
	$location = $concerts[$i]["name"] . " (" .$concerts[$i]["street"] . ", ";
	$location .= $concerts[$i]["city"] . ")"; 
	echo "LOCATION:" . $location . "\n";
	echo "COMMENT:" . $concerts[$i]["notes"] . "\n";
	echo "END:VEVENT\n";
}

// finish
echo "END:VCALENDAR\n";
?>