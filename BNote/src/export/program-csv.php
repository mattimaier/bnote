<?php

/**
 * Creates a csv from a program ID.
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
require_once($dir_prefix . "lang.php");
require_once("csvcreator.php");

// Build Database Connection
$db = new Database();

// check whether a user is registered and has program (mod=4) permission
$deniedMsg = "Du hast keine Berechtigung die Kontakte zu exportieren!";
if(!isset($_SESSION["user"])) {
	new Error($deniedMsg);
}
else {
	$userCt = $db->getCell("privilege", "count(*)", "module = 4 AND user = " . $_SESSION["user"]);
	if($userCt < 1) {
		new Error($deniedMsg);
	}
}

// check if ID is set
if(!isset($_GET["id"]) || $_GET["id"] == "") {
	new Error("Bitte geben die Nummer des Programms an, das du exportieren möchtest.");
}
// fetch data
$query = "SELECT s.title, c.name as composer, s.length, s.bpm, s.music_key, g.name as genre, status.name as status, s.notes
		FROM program_song ps JOIN song s ON ps.song = s.id
			JOIN genre g ON s.genre = g.id
			JOIN composer c ON s.composer = c.id
			JOIN status ON s.status = status.id
		WHERE ps.program = " . $_GET["id"] . " 
		ORDER BY ps.rank ASC";
$pieces = $db->getSelection($query);

// rename headers
$pieces[0] = array("Titel", "Komponist/Arrangeur", "Länge", "BPM", "Tonart", "Genre", "Status", "Notizen");

// build header
header('Content-type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="program_' . $_GET["id"] . '.csv"');

// create csv
$csv = new CsvCreator($pieces);
$csv->setSeparator(";");
$csv->write();


?>