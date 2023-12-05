<?php

/**
 * Special controller for start module.
 * @author matti
 *
 */
class StartController extends DefaultController {

	public function start() {
		if(isset($_GET['mode'])) {
			$mode = $_GET["mode"];
			if($mode == "saveParticipation") {
				$this->saveParticipation();
			}
			else if($mode == "addComment") {
				$this->getView()->addComment();
				$this->notifyContactsOnComment();
			}
			else if($mode == "gdprOk") {
				$accept = $_GET["accept"];
				// save in DB
				$this->getData()->getSysdata()->gdprAccept($accept);
				if($accept == 0) {
					$this->getView()->flash(Lang::txt("StartController_start.flash"));
				}
				else {
					$this->getView()->start();
				}
			}
			else {
				$this->getView()->$mode();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	private function saveParticipation() {
		if(isset($_GET["otype"]) && isset($_GET["oid"])) {
			if(!isset($_POST["participation"])) {
				new BNoteError("Bug - no participation set");
			}
			$part = intval($_POST["participation"]);
			$reason = isset($_POST["reason"]) ? $_POST["reason"] : "";
			$this->getData()->saveParticipation($_GET["otype"], null, $_GET["oid"], $part, $reason);
			$this->getView()->start();
		}
		else {
			new BNoteError("Bug - no otype/oid set.");
		}
	}
	
	public function notifyContactsOnComment($uid = -1) {
		// get contacts to notify
		$contacts = $this->getData()->getContactsForObject($_GET["otype"], $_GET["oid"]);
		
		// dont notify anyone if nobody has to be notified
		if($contacts == null) return;
		else if(count($contacts) <= 1) return;
		
		// create message
		$subject = "Diskussion: " . $this->getData()->getObjectTitle($_GET["otype"], $_GET["oid"]);
		$body = "<h3>Neue Nachricht zu Diskussion</h3>";
		$sender = $this->getData()->getSysdata()->getUsersContact($uid);
		$body .= "<p>von " . $sender["name"] . " " . $sender["surname"] . "</p>";
		$body .= "<p>" . $_POST["message"] . "</p>"; // checked here already
		
		// create receipients as BCC, no to
		$bcc = array();
		foreach($contacts as $i => $contact) {
			if($i == 0) continue; // header
			
			// check whether the user has turned email notification on
			$emn = $this->getData()->getSysdata()->contactEmailNotificationOn($contact["id"]);
			if($emn) {
				array_push($bcc, $contact["email"]);
			}
		}
		
		// send only one email to notify all users
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($subject, null);
		$mail->setBodyInHtml($body);
		$mail->setFromUser($uid);
		$mail->setBcc($bcc);
		
		// no error handling since it is just a notification
		$mail->sendMail();
	}
	
	public function usersToIntegrate() {
		$inactiveUsers = $this->getData()->hasInactiveUsers();
		$usersWithoutRelations = $this->getData()->hasMembersWithoutRelations();
		return ($inactiveUsers || $usersWithoutRelations);
	}
}