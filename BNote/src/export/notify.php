<?php

/**
 * Sends an email notification to all contacts of the object that have not selected to participate yet.
 * @author matti
 */

/*
 * Required URL-Parameters to use this script:
 * - token: From dynamic configuration
 * - otype: R=rehearlal, C=concert, V=vote
 * - oid: ID of the object, e.g. Rehearsal ID
 */

// check if parameters are set correctly
if(!isset($_GET["token"]) || !isset($_GET["otype"]) || !isset($_GET["oid"])) {
	header("HTTP/1.0 400 Insufficient Parameters");
	exit(400);
}

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;
$db = $system_data->dbcon;
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");

// Check token
$trigger_key = $system_data->getDynamicConfigParameter("trigger_key");
if($_GET["token"] != $trigger_key) {
	header("HTTP/1.0 403 Invalid Key");
	exit(403);
}

// Implementation
class Notifier {
	
	public function sendNotification($otype, $oid) {
		if(!is_numeric($oid) || $oid < 1) {
			header("HTTP/1.0 404 Cannot find object with this ID.");
			exit(404);
		}
		switch($otype) {
			case "R": $this->sendRehearsalNotification($oid); break;
			case "C": $this->sendConcertNotification($oid); break;
			case "V": $this->sendVoteNotification($oid); break;
			default:
				header("HTTP/1.0 404 Unknown object type.");
				exit(404);
		}
	}
	
	private function sendEmailToContacts($contacts) {
		//TODO implement
	}
	
	private function ok() {
		echo json_encode(array("success" => true, "message" => "OK"));
	}
	
	private function sendRehearsalNotification($rehearsalId) {
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php");
		$dao = new ProbenData($dir_prefix);
		$laggardIds = $dao->getOpenParticipation($rehearsalId);
		$this->sendEmailToContacts($laggardIds);
		$this->ok();
	}
	
	private function sendConcertNotification($concertId) {
		//TODO implement
	}
	
	private function sendVoteNotification($voteId) {
		//TODO implement
	}
	
}

$notifier = new Notifier();
$notifier->sendNotification($_GET["otype"], $_GET["oid"]);


?>