<?php

/**
 * Individual controller for the task module.
 * @author Matti
 *
 */
class AufgabenController extends DefaultController {
	
	public function start() {
		parent::start();
		
		// inform user about his tasks
		if(isset($_GET["mode"]) && ($_GET["mode"] == "add" || $_GET["mode"] == "edit_process")) {
			$this->informUser($_GET["mode"]);
		}
	}
	
	private function informUser($mode) {
		if($mode == "add") {
			$to = $this->getData()->getContactmail($_POST["Verantwortlicher"]);
			$subject = "Neue Aufgabe: " . $_POST["title"];
			$body = "Es wurde eine neue Aufgabe für dich erstellt. Bitte melde dich bei BNote an um weitere Details zu sehen.\n\n";
			$body .= "Beschreibung der Aufgabe:\n\n";
			$body .= $_POST["description"];
		}
		else {
			$to = $this->getData()->getContactmail($_POST["Verantwortlicher"]);
			$subject = "Aufgabe geändert: " . $_POST["title"];
			$body = "Die im Betreff genannte Aufgabe hat sich geändert.";
			$body .= " Bitte melde dich in BNote an um die Aufgabe einzusehen.";
		}
		
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($to, $subject, $body);
		$mail->sendMailWithFailError();
	}
}