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

	function showAdditionStartButtons() {
		$this->buttonSpace();
		$hist = new Link($this->modePrefix() . "history", "Verganene Probenphasen");
		$hist->addIcon("clock");
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

		$this->backToStart();

		$data = $this->getData()->getPhases(false);
		$this->showPhaseTable($data);
	}

	function viewDetailTable() {
		// stem data
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdNoRef($_GET["id"]));
		$dv->removeElement("id");
		$dv->autoRename($this->getData()->getFields());
		$dv->write();

		// rehearsals
		Writing::h3("Proben");
		$addReh = new Link($this->modePrefix() . "addRehearsal&id=" . $_GET["id"], "Probe hinzufügen");
		$addReh->addIcon("add");
		$addReh->write();

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

		// concerts
		Writing::h3("Konzerte");
		$addConcert = new Link($this->modePrefix() . "addConcert&id=" . $_GET["id"], "Konzert hinzufügen");
		$addConcert->addIcon("add");
		$addConcert->write();

		$concerts = $this->getData()->getConcertsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("concert", $concerts));
		$table->renameAndAlign(array(
				"id" => array("ID", FieldType::INTEGER),
				"begin" => array("Beginn", FieldType::DATETIME),
				"location" => array("Ort", FieldType::CHAR),
				"notes" => array("Notizen", FieldType::TEXT)
		));
		$table->setColumnFormat("begin", "DATE");
		$table->removeColumn("id");
		$table->write();

		// contacts
		Writing::h3("Kontakte");
		$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], "Kontakt hinzufügen");
		$addContact->addIcon("add");
		$addContact->write();
		$this->buttonSpace();
		
		$addGroupContacts = new Link($this->modePrefix() . "addMultipleContacts&id=" . $_GET["id"], "Kontakte einer Gruppe hinzufügen");
		$addGroupContacts->addIcon("add");
		$addGroupContacts->write();

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

		$this->verticalSpace();
	}

	function addRehearsal() {
		$form = new Form("Probe hinzufügen", $this->modePrefix() . "process_addRehearsal&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken einer Probe diese zur Probenphase hinzu.");

		$futRehearsals = $this->getData()->getFutureRehearsals();
		$gs = new GroupSelector($futRehearsals, array(), "rehearsal");
		$gs->setNameColumn("begin");
		$gs->setCaptionType(FieldType::DATE);
		$form->addElement("kommende Proben", $gs);
		$form->write();

		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}

	function process_addRehearsal() {
		$this->getData()->addRehearsals($_GET["id"]);
		new Message("Proben hinzugefügt", "Die ausgewählten Proben wurden der Probenphase hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}

	function addConcert() {
		$form = new Form("Konzert hinzufügen", $this->modePrefix() . "process_addConcert&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken eines oder mehrerer Konzerte diese der Probenphase hinzu.");

		$futConcerts = $this->getData()->getFutureConcerts();
		$gs = new GroupSelector($futConcerts, array(), "concert");
		$gs->setNameColumn("begin");
		$gs->setCaptionType(FieldType::DATE);
		$form->addElement("kommende Konzerte", $gs);
		$form->write();

		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}

	function process_addConcert() {
		$this->getData()->addConcerts($_GET["id"]);
		new Message("Konzerte hinzugefügt", "Die ausgewählten Konzerte wurden der Probenphase hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}

	function addContact() {
		$form = new Form("Kontakt hinzufügen", $this->modePrefix() . "process_addContact&id=" . $_GET["id"]);

		Writing::p("Füge durch anklicken eines oder mehrerer Kontakte diese der Probenphase hinzu.");

		$contacts = $this->getData()->getContacts();
		$gs = new GroupSelector($contacts, array(), "contact");
		$form->addElement("Kontakte", $gs);
		$form->write();

		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}
	
	function process_addContact() {
		$this->getData()->addContacts($_GET["id"]);
		new Message("Kontakte hinzugefügt", "Die ausgewählten Kontakte wurden der Probenphase hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}
	
	function addMultipleContacts() {
		$form = new Form("Alle Kontakte einer Gruppe zur Probenphase hinzufügen",
				$this->modePrefix() . "process_addMultipleContacts&id=" . $_GET["id"]);
		
		Writing::p("Fügen durch anklicken einer oder mehrerer Gruppen alle deren Kontakte der Probenphase hinzu.");
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true), array(), "group");
		$form->addElement("Gruppen", $gs);
		$form->write();
		
		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}

	function process_addMultipleContacts() {
		$this->getData()->addGroupContacts($_GET["id"]);
		new Message("Kontakte hinzugefügt", "Die ausgewählten Kontakte wurden der Probenphase hinzugefügt.");
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
