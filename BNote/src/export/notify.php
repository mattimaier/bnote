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
if(!isset($_POST["token"]) || !isset($_POST["otype"]) || !isset($_POST["oid"])) {
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
require_once($dir_prefix . "lang.php");

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");

// Check token
$trigger_key = $system_data->getDynamicConfigParameter("trigger_key");
if($_POST["token"] != $trigger_key) {
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
	
	private function getMailAddresses($contacts) {
		$whereQ = array();
		$params = array();
		foreach($contacts as $cid) {
			array_push($whereQ, "id = ?");
			array_push($params, $cid);
		}
		$q = "SELECT DISTINCT email FROM contact WHERE " . join(" OR ", $whereQ);
		global $system_data;
		$addressesDbSel = $system_data->dbcon->getSelection($q, $params);
		return $system_data->dbcon->flattenSelection($addressesDbSel, "email");
	}
	
	private function sendEmailToContacts($contacts, $subject, $body) {
		// no email must be sent, all good
		if(count($contacts) == 0) {
			return true;
		}
		global $dir_prefix;
		require_once($dir_prefix . $GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($subject, null);
		$mail->setBcc($this->getMailAddresses($contacts));  // string of addresses separated properly
		$mail->setBodyInHtml($body);
		return $mail->sendMail();
	}
	
	private function ok($ok=true) {
		if(!$ok) {
			header("HTTP/1.0 500 Unable to send notification.");
		}
		else {
			echo json_encode(array("success" => $ok, "message" => "OK"));
		}
	}
	
	private function sendRehearsalNotification($rehearsalId) {
		// data access object
		global $dir_prefix;
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php");
		$dao = new ProbenData($dir_prefix);
		
		// open participation
		$laggards = $dao->getOpenParticipation($rehearsalId);
		$laggardIds = Database::flattenSelection($laggards, "id");
		
		// message
		$rehearsal = $dao->findByIdNoRef($rehearsalId);
		$reh_begin = Data::convertDateFromDb($rehearsal['begin']);
		$subject = Lang::txt("Notifier_sendRehearsalNotification.message_1") . $reh_begin . Lang::txt("Notifier_sendRehearsalNotification.message_2");
		$body = Lang::txt("Notifier_sendRehearsalNotification.message_3") . $reh_begin . Lang::txt("Notifier_sendRehearsalNotification.message_4");
		$bnote_url = $dao->getSysdata()->getSystemURL();
		$body .= "<a href=\"$bnote_url\">" . Lang::txt("Notifier_sendRehearsalNotification.message_5") . "</a><br/>";
		$body .= Lang::txt("Notifier_sendRehearsalNotification.message_6");
		
		// send and ok
		$this->ok($this->sendEmailToContacts($laggardIds, $subject, $body));
	}
	
	private function sendConcertNotification($concertId) {
		// data access object
		global $dir_prefix;
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php");
		$dao = new KonzerteData($dir_prefix);
		
		// open participation
		$laggards = $dao->getOpenParticipants($concertId);
		$laggardIds = Database::flattenSelection($laggards, "id");
		
		// message
		$concert = $dao->findByIdNoRef($concertId);
		$subject = $concert['title'] . Lang::txt("Notifier_sendConcertNotification.message_1");
		$body = Lang::txt("Notifier_sendConcertNotification.message_2") . $concert['title'] . Lang::txt("Notifier_sendConcertNotification.message_3");
		$bnote_url = $dao->getSysdata()->getSystemURL();
		$body .= '<a href="' . $bnote_url . '">' . Lang::txt("Notifier_sendConcertNotification.message_4") . "</a><br/>";
		$body .= Lang::txt("Notifier_sendConcertNotification.message_5");
		
		// send and ok
		$this->ok($this->sendEmailToContacts($laggardIds, $subject, $body));
	}
	
	private function sendVoteNotification($voteId) {
		// data access object
		global $dir_prefix;
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "abstimmungdata.php");
		$dao = new AbstimmungData($dir_prefix);
		
		// open votes
		$laggardIds = $dao->getOpenVoters($voteId);
		
		// message
		$vote = $dao->findByIdNoRef($voteId);
		$subject = $vote['name'] . Lang::txt("Notifier_sendVoteNotification.message_1");
		$body = Lang::txt("Notifier_sendVoteNotification.message_2") . $vote['name'] . Lang::txt("Notifier_sendVoteNotification.message_3");
		$bnote_url = $dao->getSysdata()->getSystemURL();
		$body .= "<a href=\"$bnote_url\">" . Lang::txt("Notifier_sendVoteNotification.message_4") . "</a><br/>";
		$body .= Lang::txt("Notifier_sendVoteNotification.message_5");
		
		// send and ok
		$this->ok($this->sendEmailToContacts($laggardIds, $subject, $body));
	}
	
}

$notifier = new Notifier();
$notifier->sendNotification($_POST["otype"], $_POST["oid"]);


?>