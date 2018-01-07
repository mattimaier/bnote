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
		
		// Rundmail form
		$form = $this->createMailForm($this->modePrefix() . "groupmail");
		$form->write();
	}
	
	function startOptions() {
		$rh = new Link($this->modePrefix() . "rehearsalMail", "Probenbenachrichtigung");
		$rh->addIcon("arrow_right");
		$rh->write();
		$this->buttonSpace();
		
		$cm = new Link($this->modePrefix() . "concertMail", "Auftrittsbenachrichtigung");
		$cm->addIcon("arrow_right");
		$cm->write();
		$this->buttonSpace();
		
		$vm = new Link($this->modePrefix() . "voteMail", "Abstimmungsbenachrichtigung");
		$vm->addIcon("arrow_right");
		$vm->write();
	}
	
	function rehearsalMail() {
		Writing::h2("Probenbenachrichtigung");
				
		$dd = new Dropdown("rehearsal");
		$rhs = $this->getData()->adp()->getFutureRehearsals();
		
		for($i = 1; $i < count($rhs); $i++) {
			$label = Data::getWeekdayFromDbDate($rhs[$i]["begin"]) . ", ";
			$label .= Data::convertDateFromDb($rhs[$i]["begin"]);
			$label .= " - " . substr($rhs[$i]["end"], strlen($rhs[$i]["end"])-8, 5);
			$label .= " Uhr " . $rhs[$i]["name"];
			$dd->addOption($label, $rhs[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "rehearsal", "", false);
		if(isset($_GET["preselect"])) {
			$rhs = $this->getData()->getRehearsal($_GET["preselect"]);
			$form->setFieldValue("Nachricht", $rhs["notes"]);
			$label = Data::getWeekdayFromDbDate($rhs["begin"]) . ", ";
			$label .= Data::convertDateFromDb($rhs["begin"]);
			$label .= " - " . substr($rhs["end"], strlen($rhs["end"])-8, 5);
			$label .= " Uhr " . $rhs["location"];
			$form->addElement("Probe", new Field("rehearsal_view", $label, 99));
			$form->addHidden("rehearsal", $_GET["preselect"]);
		}
		else {
			$form->addElement("Probe", $dd);
		}
		$form->removeElement("Betreff");
		$form->write();
	}
	
	protected function rehearsalMailOptions() {
		if(!isset($_GET["preselect"])) {
			$this->backToStart();
		}
	}
	
	function concertMail() {
		Writing::h2("Auftrittsbenachrichtigung");
		
		$dd = new Dropdown("concert");
		$concerts = $this->getData()->getConcerts();
		
		for($i = 1; $i < count($concerts); $i++) {
			$label = Data::getWeekdayFromDbDate($concerts[$i]["begin"]) . ", ";
			$label .= Data::convertDateFromDb($concerts[$i]["begin"]);
			$label .= " Uhr (" . $concerts[$i]["location_name"] . ")";
			$dd->addOption($label, $concerts[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "concert", "", false);
		if(isset($_GET["preselect"])) {
			$concert = $this->getData()->getConcert($_GET["preselect"]);
			$form->setFieldValue("Nachricht", $concert["notes"]);
			$label = Data::getWeekdayFromDbDate($concert["begin"]) . ", ";
			$label .= Data::convertDateFromDb($concert["begin"]);
			$label .= " Uhr";
			$form->addElement("Auftritt", new Field("concert_view", $label, Field::FIELDTYPE_UNEDITABLE));
			$form->addHidden("concert", $_GET["preselect"]);
		}
		else {
			$form->addElement("Auftritt", $dd);
		}
		$form->removeElement("Betreff");
		$form->write();
	}
	
	protected function concertMailOptions() {
		if(!isset($_GET["preselect"])) {
			$this->backToStart();
		}
	}
	
	function voteMail() {
		Writing::h2("Abstimmungsbenachrichtigung");
		
		$dd = new Dropdown("vote");
		$votes = $this->getData()->getVotes();
		
		for($i = 1; $i < count($votes); $i++) {
			$label = $votes[$i]["name"];
			$dd->addOption($label, $votes[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "vote", "", false);
		if(isset($_GET["preselect"])) {
			$vote = $this->getData()->getVote($_GET["preselect"]);
			$label = $vote["name"];
			$form->addElement("Abstimmung", new Field("vote_view", $label, Field::FIELDTYPE_UNEDITABLE));
			$form->addHidden("vote", $_GET["preselect"]);
		}
		else {
			$form->addElement("Abstimmung", $dd);
		}
		$form->removeElement("Betreff");
		$form->write();
	}
	
	protected function voteMailOptions() {
		if(!isset($_GET["preselect"])) {
			$this->backToStart();
		}
	}
	
	private function createMailForm($action, $message = "", $showGroups = true) {
		$form = new Form("Rundmail", $action . "&sub=send");
		
		// for rehearsal mails no receipients are needed, take the ones from the list
		if($showGroups) {
			$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
			$gs->setNameColumn("name_member");
			$form->addElement("Empfänger", $gs);
		}
		$form->addElement("Betreff", new Field("subject", "", FieldType::CHAR));
		$form->addElement("Nachricht", new Field("message", $message, 98));
		$form->changeSubmitButton("SENDEN");
		
		return $form;
	}
	
	function reportMailError($email) {
		Writing::p("<strong>Mail Error:</strong> Die E-Mail an <strong>$email</strong> konnte nicht gesendet werden.");
	}
	
	function messageSent() {
		new Message("E-Mails versandt", "Alle E-Mails wurden erfolgreich versandt.");
	} 
}

?>