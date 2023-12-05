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
			$subject = Lang::txt("UserController_activate.message_1");
			$body = Lang::txt("UserController_activate.message_2") . $this->getData()->getSysdata()->getCompany() . Lang::txt("UserController_activate.message_3");
			$body .= Lang::txt("UserController_activate.message_4") . $this->getData()->getSysdata()->getSystemURL() . Lang::txt("UserController_activate.message_5");
			
			// send mail
			require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
			$mail = new Mailing($subject, $body);
			$mail->setTo($to);
			
			if(!$mail->sendMail()) {
				new Message(Lang::txt("UserController_activate.message_6"), Lang::txt("UserController_activate.message_7"));
			}
		}
	
		// simply show the user view again
		$this->getView()->view();
	}
	
}

?>