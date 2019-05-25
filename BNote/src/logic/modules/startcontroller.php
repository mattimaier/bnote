<?php

/**
 * Special controller for start module.
 * @author matti
 *
 */
class StartController extends DefaultController {

	public function start() {
		if(isset($_GET['mode'])) {
			if($_GET['mode'] == "saveParticipation") {
				$this->saveParticipation();
			}
			else if($_GET['mode'] == "addComment") {
				$this->getView()->addComment();
				$this->notifyContactsOnComment();
			}
			else if($_GET["mode"] == "gdprOk") {
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
				$mode = $_GET['mode'];
				$this->getView()->$mode();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	private function saveParticipation() {
		if(isset($_GET["action"]) && ($_GET["action"] == "maybe" || $_GET["action"] == "no")
				&& (!isset($_POST["explanation"]) || $_POST["explanation"] == "")) {
			// show reason view
			$this->getView()->askReason($_GET["obj"]);
		}
		else {
			// map parameters (this is necessary due to old implementation)
			switch($_GET["action"]) {
				case "yes": $participate = 1; break;
				case "no": $participate = 0; break;
				case "maybe": $participate = 2; break;
				default: $participate = -1; break;  // not set
			}
			
			$reason = "";
			if(isset($_POST['explanation'])) {
				$reason = $_POST['explanation'];
			}
			
			$this->getData()->saveParticipation($_GET["obj"], null, $_GET["id"], $participate, $reason);
			$this->getView()->start();
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
		$bcc = "";
		foreach($contacts as $i => $contact) {
			if($i == 0) continue; // header
			
			// check whether the user has turned email notification on
			$emn = $this->getData()->getSysdata()->contactEmailNotificationOn($contact["id"]);
			if($emn) {
				if($bcc != "") $bcc .= ",";
				$bcc .= $contact["email"];
			}
		}
		
		// send only one email to notify all users
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing(null, $subject, null);
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

?>