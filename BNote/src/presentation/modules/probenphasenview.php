<?php

/**
 * View for rehearsal phase module.
 * @author Matti
 *
 */
class ProbenphasenView extends CrudView {

	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Probenphase");
	}

	function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		$hist = new Link($this->modePrefix() . "history", "Vergangene Probenphasen");
		$hist->addIcon("timer");
		$hist->write();
	}

	function showAllTable() {
		$data = $this->getData()->getPhases();
		$this->showPhaseTable($data);
	}

	private function showPhaseTable($data) {
		$table = new Table($data);
		$table->removeColumn("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->setEdit("id");
		$table->write();
	}

	function history() {
		Writing::h2("Vergangene Probenphasen");

		$data = $this->getData()->getPhases(false);
		$this->showPhaseTable($data);
	}
	
	protected function tab_rehearsals() {		
		$rehearsals = $this->getData()->getRehearsalsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("rehearsal", $rehearsals));
		$table->renameAndAlign(array(
				"id" => array("ID", FieldType::INTEGER),
				"begin" => array("Beginn", FieldType::DATETIME),
				"location" => array("Ort", FieldType::CHAR)
		));
		$table->setColumnFormat("begin", "DATE");
		$table->removeColumn("id");
		$table->write();
	}
	
	protected function tab_concerts() {		
		$concerts = $this->getData()->getConcertsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("concert", $concerts));
		$table->renameAndAlign(array(
				"id" => array("ID", FieldType::INTEGER),
				"title" => array("Titel", FieldType::CHAR),
				"begin" => array("Beginn", FieldType::DATETIME),
				"location" => array("Ort", FieldType::CHAR),
				"notes" => array("Notizen", FieldType::TEXT)
		));
		$table->setColumnFormat("begin", "DATE");
		$table->removeColumn("id");
		$table->write();
	}
	
	protected function tab_contacts() {		
		$contacts = $this->getData()->getContactsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("contact", $contacts));
		$table->renameAndAlign(array(
				"id" => array("ID", FieldType::INTEGER),
				"instrument" => array("Instrument", FieldType::CHAR),
				"phone" => array("Telefon", FieldType::CHAR),
				"mobile" => array("Handy", FieldType::CHAR),
				"email" => array("E-Mail", FieldType::CHAR)
		));
		$table->removeColumn("id");
		$table->write();
	}

	function viewDetailTable() {
		// stem data
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdNoRef($_GET["id"]));
		$dv->removeElement("id");
		$dv->autoRename($this->getData()->getFields());
		$dv->write();
	}
	
	function view() {
		$this->checkID();
		
		// always write title first
		$pp = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2($pp["name"]);
		
		$tabs = array(
				"details" => "Details",
				"rehearsals" => "Proben",
				"contacts" => "Teilnehmer",
				"concerts" => "Konzerte"
		);
		echo "<div class=\"view_tabs\">\n";
		foreach($tabs as $tabid => $label) {
			$href = $this->modePrefix() . "view&id=" . $_GET["id"] . "&tab=$tabid";

			$active = "";
			if(isset($_GET["tab"]) && $_GET["tab"] == $tabid) $active = "_active";
			else if(!isset($_GET["tab"]) && $tabid == "details") $active = "_active";

			echo "<a href=\"$href\"><span class=\"view_tab$active\">$label</span></a>";
		}
		echo "</div>\n";
		echo "<div class=\"view_tab_content\">\n";

		// content
		if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			$this->viewDetailTable();
		}
		else {
			$func = "tab_" . $_GET["tab"];
			$this->$func();
		}
			
		echo "</div>\n";
	}
	
	function viewOptions() {
		if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			parent::viewOptions();
		}
		else {
			$this->backToStart();
			$this->buttonSpace();
			
			switch($_GET["tab"]) {
				case "rehearsals":
					$addReh = new Link($this->modePrefix() . "addRehearsal&id=" . $_GET["id"], "Probe hinzufügen");
					$addReh->addIcon("plus");
					$addReh->write();
					break;
				case "contacts":
					$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], "Kontakt hinzufügen");
					$addContact->addIcon("plus");
					$addContact->write();
					$this->buttonSpace();
					
					$addGroupContacts = new Link($this->modePrefix() . "addMultipleContacts&id=" . $_GET["id"], "Kontakte einer Gruppe hinzufügen");
					$addGroupContacts->addIcon("plus");
					$addGroupContacts->write();
					break;
				case "concerts":
					$addConcert = new Link($this->modePrefix() . "addConcert&id=" . $_GET["id"], "Konzert hinzufügen");
					$addConcert->addIcon("plus");
					$addConcert->write();
					break;
			}
		}
	}
	
	protected function backToViewTab($id, $tab) {
		$back = new Link($this->modePrefix() . "view&id=$id&tab=$tab", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}

	function addRehearsal() {
		$form = new Form("Probe hinzufügen", $this->modePrefix() . "process_addRehearsal&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken einer Probe diese zur Probenphase hinzu.");

		$futRehearsals = $this->getData()->adp()->getFutureRehearsals();
		$gs = new GroupSelector($futRehearsals, array(), "rehearsal");
		$gs->setNameColumn("begin");
		$gs->setCaptionType(FieldType::DATE);
		$form->addElement("kommende Proben", $gs);
		$form->write();
	}
	
	function addRehearsalOptions() {
		$this->backToViewButton($_GET["id"]);
	}

	function process_addRehearsal() {
		$this->getData()->addRehearsals($_GET["id"]);
		new Message("Proben hinzugefügt", "Die ausgewählten Proben wurden der Probenphase hinzugefügt.");
	}
	
	function process_addRehearsalOptions() {
		$this->backToViewTab($_GET["id"], "rehearsals");
	}

	function addConcert() {
		$form = new Form("Konzert hinzufügen", $this->modePrefix() . "process_addConcert&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken eines oder mehrerer Konzerte diese der Probenphase hinzu.");

		$futConcerts = $this->getData()->getFutureConcerts();
		$gs = new GroupSelector($futConcerts, array(), "concert");
		$gs->setNameColumn("title");
		$gs->setCaptionType(FieldType::CHAR);
		$form->addElement("kommende Konzerte", $gs);
		$form->write();
	}
	
	function addConcertOptions() {
		$this->backToViewTab($_GET["id"], "concerts");
	}

	function process_addConcert() {
		$this->getData()->addConcerts($_GET["id"]);
		new Message("Konzerte hinzugefügt", "Die ausgewählten Konzerte wurden der Probenphase hinzugefügt.");
	}
	
	function process_addConcertOptions() {
		$this->backToViewTab($_GET["id"], "concerts");
	}

	function addContact() {
		$form = new Form("Kontakt hinzufügen", $this->modePrefix() . "process_addContact&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken eines oder mehrerer Kontakte diese der Probenphase hinzu.");

		$contacts = $this->getData()->getContacts();
		$gs = new GroupSelector($contacts, array(), "contact");
		$form->addElement("Kontakte", $gs);
		$form->write();
	}
	
	function addContactOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}
	
	function process_addContact() {
		$this->getData()->addContacts($_GET["id"]);
		new Message("Kontakte hinzugefügt", "Die ausgewählten Kontakte wurden der Probenphase hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}
	
	function process_addContactOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}
	
	function addMultipleContacts() {
		$form = new Form("Alle Kontakte einer Gruppe zur Probenphase hinzufügen",
				$this->modePrefix() . "process_addMultipleContacts&id=" . $_GET["id"]);
		
		Writing::p("Fügen durch anklicken einer oder mehrerer Gruppen alle deren Kontakte der Probenphase hinzu.");
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true), array(), "group");
		$form->addElement("Gruppen", $gs);
		$form->write();
	}
	
	function addMultipleContactsOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}

	function process_addMultipleContacts() {
		$this->getData()->addGroupContacts($_GET["id"]);
		new Message("Kontakte hinzugefügt", "Die ausgewählten Kontakte wurden der Probenphase hinzugefügt.");
	}
	
	function process_addMultipleContactsOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	
	function addRemoveColumnToTable($entity, $data) {
		// header
		array_push($data[0], "Löschen");
		
		// body
		foreach($data as $i => $row) {
			if($i == 0) continue;
			$delLink = $this->modePrefix() . "delEntity&entity=$entity&id=" . $_GET["id"] . "&eid=" . $row["id"];
			$lnk = new Link($delLink, "");
			$lnk->addIcon("remove");
			array_push($row, $lnk->toString());
			$row["Löschen"] = $lnk->toString();
			$data[$i] = $row;
		}
		return $data;
	}
	
	function delEntity() {
		$this->getData()->deleteEntity($_GET["entity"], $_GET["id"], $_GET["eid"]);
		$this->view();
	}
}
