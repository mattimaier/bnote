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
		Writing::h1(Lang::txt("KommunikationView_start.title"));
	
		if($GLOBALS["system_data"]->inDemoMode()) {
			echo '<p style="font-size: 15px; font-weight: bold; text-align: center;">';
			echo Lang::txt("KommunikationView_start.message");
			echo '</p>';
		}
		
		// Rundmail form
		$form = $this->createMailForm($this->modePrefix() . "groupmail");
		$form->write();
	}
	
	function startOptions() {
		$rh = new Link($this->modePrefix() . "rehearsalMail", Lang::txt("KommunikationView_startOptions.rehearsalMail"));
		$rh->addIcon("arrow_right");
		$rh->write();
		
		$rs = new Link($this->modePrefix() . "rehearsalSerieMail", Lang::txt("KommunikationView_startOptions.rehearsalSerieMail"));
		$rs->addIcon("arrow_right");
		$rs->write();
		
		$cm = new Link($this->modePrefix() . "concertMail", Lang::txt("KommunikationView_startOptions.concertMail"));
		$cm->addIcon("arrow_right");
		$cm->write();
		
		$vm = new Link($this->modePrefix() . "voteMail", Lang::txt("KommunikationView_startOptions.voteMail"));
		$vm->addIcon("arrow_right");
		$vm->write();
	}
	
	function rehearsalMail() {
		Writing::h2(Lang::txt("KommunikationView_rehearsalMail.Title"));
				
		$dd = new Dropdown("rehearsal");
		$rhs = $this->getData()->adp()->getFutureRehearsals();
		
		for($i = 1; $i < count($rhs); $i++) {
			$label = Data::getWeekdayFromDbDate($rhs[$i]["begin"]) . ", ";
			$label .= Data::convertDateFromDb($rhs[$i]["begin"]);
			$label .= " - " . substr($rhs[$i]["end"], strlen($rhs[$i]["end"])-8, 5);
			$label .= Lang::txt("KommunikationView_rehearsalMail.hour") . $rhs[$i]["name"];
			$dd->addOption($label, $rhs[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "rehearsal", "", false);
		if(isset($_GET["preselect"])) {
			$rhs = $this->getData()->getRehearsal($_GET["preselect"]);
			$form->setFieldValue("Nachricht", $rhs["notes"]);
			$label = Data::getWeekdayFromDbDate($rhs["begin"]) . ", ";
			$label .= Data::convertDateFromDb($rhs["begin"]);
			$label .= " - " . substr($rhs["end"], strlen($rhs["end"])-8, 5);
			$label .= Lang::txt("KommunikationView_rehearsalMail.hour") . $rhs["location"];
			$form->addElement("Probe", new Field("rehearsal_view", $label, 99));
			$form->addHidden("rehearsal", $_GET["preselect"]);
		}
		else {
			$form->addElement(Lang::txt("KommunikationView_rehearsalMail.addElement"), $dd);
		}
		$form->removeElement("Betreff");
		$form->write();
	}
	
	protected function rehearsalMailOptions() {
		if(!isset($_GET["preselect"])) {
			$this->backToStart();
		}
	}
	
	function rehearsalSerieMail() {
		Writing::h2(Lang::txt("KommunikationView_rehearsalSerieMail.Title"));
		
		$dd = new Dropdown("rehearsalSerie");
		$rs = $this->getData()->getRehearsalSeries();
		for($i = 1; $i < count($rs); $i++) {
			$dd->addOption($rs[$i]["name"], $rs[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "rehearsalSerie", "", false);
		$form->addElement(Lang::txt("KommunikationView_rehearsalSerieMail.addElement"), $dd);
		$form->removeElement("Betreff");
		$form->write();
	}
	
	function concertMail() {
		Writing::h2(Lang::txt("KommunikationView_concertMail.Title"));
		
		$dd = new Dropdown("concert");
		$concerts = $this->getData()->getConcerts();
		
		for($i = 1; $i < count($concerts); $i++) {
			$label = Data::getWeekdayFromDbDate($concerts[$i]["begin"]) . ", ";
			$label .= Data::convertDateFromDb($concerts[$i]["begin"]);
			$label .= Lang::txt("KommunikationView_concertMail.begin_1") . $concerts[$i]["location_name"] . ")";
			$dd->addOption($label, $concerts[$i]["id"]);
		}
		
		$form = $this->createMailForm($this->modePrefix() . "concert", "", false);
		if(isset($_GET["preselect"])) {
			$concert = $this->getData()->getConcert($_GET["preselect"]);
			$form->setFieldValue("Nachricht", $concert["notes"]);
			$label = Data::getWeekdayFromDbDate($concert["begin"]) . ", ";
			$label .= Data::convertDateFromDb($concert["begin"]);
			$label .= Lang::txt("KommunikationView_concertMail.begin_2");
			$form->addElement(Lang::txt("KommunikationView_concertMail.concert"), new Field("concert_view", $label, Field::FIELDTYPE_UNEDITABLE));
			$form->addHidden("concert", $_GET["preselect"]);
		}
		else {
			$form->addElement(Lang::txt("KommunikationView_concertMail.concert"), $dd);
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
		Writing::h2(Lang::txt("KommunikationView_voteMail.Title"));
		
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
			$form->addElement(Lang::txt("KommunikationView_voteMail.Vote"), new Field("vote_view", $label, Field::FIELDTYPE_UNEDITABLE));
			$form->addHidden("vote", $_GET["preselect"]);
		}
		else {
			$form->addElement(Lang::txt("KommunikationView_voteMail.Vote"), $dd);
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
			$form->addElement(Lang::txt("KommunikationView_createMailForm.recipient"), $gs);
		}
		$form->addElement(Lang::txt("KommunikationView_createMailForm.subject"), new Field("subject", "", FieldType::CHAR));
		$form->addElement(Lang::txt("KommunikationView_createMailForm.Message"), new Field("message", $message, 98));
		$form->changeSubmitButton(Lang::txt("KommunikationView_createMailForm.Submit"));
		
		return $form;
	}
	
	function reportMailError($email) {
		Writing::p(Lang::txt("KommunikationView_reportMailError.message_1") . "$email" . Lang::txt("KommunikationView_reportMailError.message_2"));
	}
	
	function messageSent() {
		new Message(Lang::txt("KommunikationView_messageSent.message_1"), Lang::txt("KommunikationView_messageSent.message_2"));
	} 
}

?>