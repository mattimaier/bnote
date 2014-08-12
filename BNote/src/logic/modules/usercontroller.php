<?php

/**
 * Custom user controller with only deviating functionality to standard.
 * @author Matti
 *
 */
class UserController extends DefaultController {
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "activate") {
			$this->activate();
		}
		else {
			parent::start();
		}
	}
	
	function activate() {
		$this->getView()->checkID();
	
		// change status of user and send email in case the user was activated
		if($this->getData()->changeUserStatus($_GET["id"])) {
			// prepare mail
			$to = $this->getData()->getUsermail($_GET["id"]);
			$subject = "Benutzerkonto freigeschaltet.";
			$body = "Dein " . $this->getData()->getSysdata()->getCompany() . " Benutzerkonto wurde aktiviert. ";
			$body .= "Du kannst dich nun unter " . $this->getData()->getSysdata()->getSystemURL() . " anmelden.";
			
			// send mail
			require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
			$mail = new Mailing($to, $subject, $body);
			
			if(!$mail->sendMail()) {
				new Message("Aktivierungsemail fehlgeschlagen",
						"Das Senden der Aktivierungsemail war nicht erfolgreich. Bitte benachrichtigen Sie den Benutzer selbst.");
			}
		}
	
		// simply show the user view again
		$this->getView()->view();
	}
	
}

?>