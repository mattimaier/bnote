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
			else {
				$this->getView()->$_GET['mode']();
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
			if($_GET["obj"] == "rehearsal") {
				$_GET["rid"] = $_GET["id"];
				$_POST["rehearsal"] = $_GET["id"];
			}
			else if($_GET["obj"] == "concert") {
				$_GET["cid"] = $_GET["id"];
				$_POST["concert"] = $_GET["id"];
			}
			$_GET["status"] = $_GET["action"];
			
			$this->getData()->saveParticipation();
			$this->getView()->start();
		}
	}
	
	private function notifyContactsOnComment() {
		// get contacts to notify
		$contacts = $this->getData()->getContactsForObject($_GET["otype"], $_GET["oid"]);
		
		// dont notify anyone if nobody has to be notified
		if($contacts == null) return;
		else if(count($contacts) <= 1) return;
		
		// create message
		$headers  = "From: " . $this->getData()->getSysdata()->getCompany() . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		$contact = $this->getData()->getSysdata()->getUsersContact();
		
		$to = ""; // no to, only bcc
		$bcc = "";
		$subject = "Diskussion: " . $this->getView()->getObjectTitle($_GET["otype"], $_GET["oid"]);
		$body = "<h3>Neue Nachricht zu Diskussion</h3>";
		$body .= "<p>von " . $contact["name"] . " " . $contact["surname"] . "</p>";
		$body .= "<p>" . $_POST["message"] . "</p>"; // checked here already
		
		foreach($contacts as $i => $contact) {
			if($i == 0) continue; // header
			
			// check whether the user has turned email notification on
			$emn = $this->getData()->getSysdata()->contactEmailNotificationOn($contact["id"]);
			if($emn) {
				if($bcc != "") $bcc .= ",";
				$bcc .= $contact["email"];
			}
		}
		$headers .= 'Bcc: ' . $bcc . "\r\n";
		
//  	echo "headers: $headers<br/>\n";
//  	echo "receipient: $to<br/>\n";
// 		echo "subject: $subject<br/>\n";
// 		echo "body: $body<br/>\n";
		
		// send only one email to notify all users
		mail($to, $subject, $body, $headers);
	}
}

?>