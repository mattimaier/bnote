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
		$rh->addIcon("arrow_right");
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
		
		//TODO for rehearsal mails no receipients are needed, take the ones from the list
		$selectedGroups = array(); // by default
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), $selectedGroups, "group");
		$form->addElement("Empf&auml;nger", $gs);
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