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
			$to = $this->getData()->getContactmail($_POST[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")]);
			$subject = Lang::txt("AufgabenController_informUser.title_1") . $_POST["title"];
			$body = Lang::txt("AufgabenController_informUser.body_1");
			$body .= Lang::txt("AufgabenController_informUser.body_2");
			$body .= $_POST["description"];
		}
		else {
			$to = $this->getData()->getContactmail($_POST[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")]);
			$subject = Lang::txt("AufgabenController_informUser.title_2") . $_POST["title"];
			$body = Lang::txt("AufgabenController_informUser.body_3");
			$body .= Lang::txt("AufgabenController_informUser.body_4");
		}
		
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($subject, $body);
		$mail->setTo($to);
		$mail->sendMailWithFailError();
	}
}