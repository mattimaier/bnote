<?php

/**
 * View for communication module.
 * @author matti
 *
 */
class KommunikationView extends AbstractView {
	
	/**
	 * Create the communication view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Kommunikation");
	
		if($GLOBALS["system_data"]->inDemoMode()) {
			echo '<p style="font-size: 15px; font-weight: bold; text-align: center;">';
			echo 'Der Versandt von E-Mails wurde zu Demonstrationszwecken deaktiviert. Sie können gerne auf "SENDEN" klicken.';
			echo '</p>';
		}
			
		// options
		$rh = new Link($this->modePrefix() . "rehearsalMail", "Probenbenachrichtigung");
		$rh->write();
		
		// Rundmail form
		$form = $this->createMailForm($this->modePrefix() . "groupmail");
		$form->write();
	}
	
	function rehearsalMail() {
		Writing::h2("Probenbenachrichtigung");
				
		$dd = new Dropdown("rehearsal");
		$rhs = $this->getData()->getRehearsals();
		
		for($i = 1; $i < count($rhs); $i++) {
			$label = Data::getWeekdayFromDbDate($rhs[$i]["begin"]) . ", ";
			$label .= Data::convertDateFromDb($rhs[$i]["begin"]);
			$label .= " - " . substr($rhs[$i]["end"], strlen($rhs[$i]["end"])-8, 5);
			$label .= " Uhr " . $rhs[$i]["name"];
			$dd->addOption($label, $rhs[$i]["id"]);
		}
		if(isset($_GET["preselect"])) {
			$dd->setSelected($_GET["preselect"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "rehearsal");
		$form->addElement("Probe", $dd);
		$form->removeElement("Betreff");
		$form->write();
		
		if(!isset($_GET["preselect"])) {
			$this->verticalSpace();
			$this->backToStart();
		}
	}
	
	private function createMailForm($action) {
		$form = new Form("Rundmail", $action . "&sub=send");
		
		$dd = new Dropdown("group");
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_ADMIN) . "en",
				KontakteData::$STATUS_ADMIN);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_MEMBER) . "er",
				KontakteData::$STATUS_MEMBER);
		$dd->addOption("Externe Mitspieler", KontakteData::$STATUS_EXTERNAL);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_APPLICANT),
				KontakteData::$STATUS_APPLICANT);
		$dd->addOption("Sonstige Kontakte",	KontakteData::$STATUS_OTHER);
		$dd->addOption("Administratoren, Mitglieder", 100);
		$dd->addOption("Administratoren, Mitglieder, Externe", 101);
		$dd->setSelected(101);
		$form->addElement("Empf&auml;nger", $dd);
		$form->addElement("Betreff", new Field("subject", "", FieldType::CHAR));
		$form->addElement("Nachricht", new Field("message", "", 98));
		$form->changeSubmitButton("SENDEN");
		
		return $form;
	}
	
	function rehearsal() {
		$this->messageSent();
		$this->backToStart();
	}
	
	function groupmail() {
		$this->messageSent();
		$this->backToStart();
	}
	
	function reportMailError($email) {
		Writing::p("<strong>Mail Error:</strong> Die E-Mail an <strong>$email</strong> konnte nicht gesendet werden.");
	}
	
	private function messageSent() {
		new Message("E-Mails versandt", "Alle E-Mails wurden erfolgreich versandt.");
	} 
}

?>