<?php

/**
 * View for rehearsal phase module.
 * @author Matti
 *
 */
class ProbenphasenView extends CrudView {

	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("ProbenphasenView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("ProbenphasenView_construct.addEntityName"));
	}

	function startOptions() {
		parent::startOptions();
		$hist = new Link($this->modePrefix() . "history", Lang::txt("ProbenphasenView_startOptions.timer"));
		$hist->addIcon("archive");
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
		Writing::h2(Lang::txt("ProbenphasenView_history.title"));

		$data = $this->getData()->getPhases(false);
		$this->showPhaseTable($data);
	}
	
	protected function tab_rehearsals() {		
		$rehearsals = $this->getData()->getRehearsalsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("rehearsal", $rehearsals));
		$table->renameAndAlign(array(
				"id" => array(Lang::txt("ProbenphasenView_tab_rehearsals.id"), FieldType::INTEGER),
				"begin" => array(Lang::txt("ProbenphasenView_tab_rehearsals.begin"), FieldType::DATETIME),
				"location" => array(Lang::txt("ProbenphasenView_tab_rehearsals.location"), FieldType::CHAR)
		));
		$table->setColumnFormat("begin", "DATE");
		$table->removeColumn("id");
		$table->write();
	}
	
	protected function tab_concerts() {		
		$concerts = $this->getData()->getConcertsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("concert", $concerts));
		$table->renameAndAlign(array(
				"id" => array(Lang::txt("ProbenphasenView_tab_concerts.id"), FieldType::INTEGER),
				"title" => array(Lang::txt("ProbenphasenView_tab_concerts.title"), FieldType::CHAR),
				"begin" => array(Lang::txt("ProbenphasenView_tab_concerts.begin"), FieldType::DATETIME),
				"location" => array(Lang::txt("ProbenphasenView_tab_concerts.location"), FieldType::CHAR),
				"notes" => array(Lang::txt("ProbenphasenView_tab_concerts.notes"), FieldType::TEXT)
		));
		$table->setColumnFormat("begin", "DATE");
		$table->removeColumn("id");
		$table->write();
	}
	
	protected function tab_contacts() {		
		$contacts = $this->getData()->getContactsForPhase($_GET["id"]);
		$table = new Table($this->addRemoveColumnToTable("contact", $contacts));
		$table->renameAndAlign(array(
				"id" => array(Lang::txt("ProbenphasenView_tab_contacts.id"), FieldType::INTEGER),
				"instrument" => array(Lang::txt("ProbenphasenView_tab_contacts.instrument"), FieldType::CHAR),
				"phone" => array(Lang::txt("ProbenphasenView_tab_contacts.phone"), FieldType::CHAR),
				"mobile" => array(Lang::txt("ProbenphasenView_tab_contacts.mobile"), FieldType::CHAR),
				"email" => array(Lang::txt("ProbenphasenView_tab_contacts.email"), FieldType::CHAR)
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
		
		$tabs = array(
				"details" => Lang::txt("ProbenphasenView_view.details"),
				"rehearsals" => Lang::txt("ProbenphasenView_view.rehearsals"),
				"contacts" => Lang::txt("ProbenphasenView_view.contacts"),
				"concerts" => Lang::txt("ProbenphasenView_view.concerts")
		);
		echo "<div class=\"nav nav-tabs\">\n";
		foreach($tabs as $tabid => $label) {
			$href = $this->modePrefix() . "view&id=" . $_GET["id"] . "&tab=$tabid";

			$active = "";
			if(isset($_GET["tab"]) && $_GET["tab"] == $tabid) $active = "active";
			else if(!isset($_GET["tab"]) && $tabid == "details") $active = "active";

			echo <<<EOS
			<div class="nav-item">
				<a class="nav-link $active" href="$href">$label</a>
			</div>
			EOS;
		}
		echo "</div>\n";
		
		// content
		echo "<div class=\"view_tab_content\">\n";

		if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			$this->viewDetailTable();
		}
		else {
			$func = "tab_" . $_GET["tab"];
			$this->$func();
		}
			
		echo "</div>\n";
	}
	
	function viewTitle() {
		// always write title first
		$pp = $this->getData()->findByIdNoRef($_GET["id"]);
		return $pp["name"];
	}
	
	function viewOptions() {
		if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			parent::viewOptions();
		}
		else {
			$this->backToStart();
			
			switch($_GET["tab"]) {
				case "rehearsals":
					$addReh = new Link($this->modePrefix() . "addRehearsal&id=" . $_GET["id"], Lang::txt("ProbenphasenView_viewOptions.addRehearsal"));
					$addReh->addIcon("plus");
					$addReh->write();
					break;
				case "contacts":
					$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], Lang::txt("ProbenphasenView_viewOptions.addContact"));
					$addContact->addIcon("plus");
					$addContact->write();
					
					$addGroupContacts = new Link($this->modePrefix() . "addMultipleContacts&id=" . $_GET["id"], Lang::txt("ProbenphasenView_viewOptions.addMultipleContacts"));
					$addGroupContacts->addIcon("plus");
					$addGroupContacts->write();
					break;
				case "concerts":
					$addConcert = new Link($this->modePrefix() . "addConcert&id=" . $_GET["id"], Lang::txt("ProbenphasenView_viewOptions.addConcert"));
					$addConcert->addIcon("plus");
					$addConcert->write();
					break;
			}
		}
	}
	
	protected function backToViewTab($id, $tab) {
		$back = new Link($this->modePrefix() . "view&id=$id&tab=$tab", "Zurück");
		$back->addIcon("arrow-left");
		$back->write();
	}

	function addRehearsal() {
		$form = new Form(Lang::txt("ProbenphasenView_addRehearsal.form"), $this->modePrefix() . "process_addRehearsal&id=" . $_GET["id"]);

		Writing::p(Lang::txt("ProbenphasenView_addRehearsal.message"));

		$futRehearsals = $this->getData()->adp()->getFutureRehearsals();
		$gs = new GroupSelector($futRehearsals, array(), "rehearsal");
		$gs->setNameColumn("begin");
		$gs->setCaptionType(FieldType::DATE);
		$form->addElement(Lang::txt("ProbenphasenView_addRehearsal.begin"), $gs);
		$form->write();
	}
	
	function addRehearsalOptions() {
		$this->backToViewButton($_GET["id"]);
	}

	function process_addRehearsal() {
		$this->getData()->addRehearsals($_GET["id"]);
		new Message(Lang::txt("ProbenphasenView_process_addRehearsal.message_1"), Lang::txt("ProbenphasenView_process_addRehearsal.message_2"));
	}
	
	function process_addRehearsalOptions() {
		$this->backToViewTab($_GET["id"], "rehearsals");
	}

	function addConcertTitle() { return Lang::txt("ProbenphasenView_addConcert.form"); }
	
	function addConcert() {
		$form = new Form("", $this->modePrefix() . "process_addConcert&id=" . $_GET["id"]);

		$futConcerts = $this->getData()->getFutureConcerts();
		$gs = new GroupSelector($futConcerts, array(), "concert");
		$gs->setNameColumn("title");
		$gs->setCaptionType(FieldType::CHAR);
		$form->addElement(Lang::txt("ProbenphasenView_addConcert.title"), $gs);
		$form->write();
	}
	
	function addConcertOptions() {
		$this->backToViewTab($_GET["id"], "concerts");
	}

	function process_addConcert() {
		$this->getData()->addConcerts($_GET["id"]);
		new Message(Lang::txt("ProbenphasenView_process_addConcert.message_1"), Lang::txt("ProbenphasenView_process_addConcert.message_2"));
	}
	
	function process_addConcertOptions() {
		$this->backToViewTab($_GET["id"], "concerts");
	}

	function addContact() {
		$form = new Form(Lang::txt("ProbenphasenView_addContact.form"), $this->modePrefix() . "process_addContact&id=" . $_GET["id"]);

		Writing::p(Lang::txt("ProbenphasenView_addContact.message"));

		$contacts = $this->getData()->getContacts();
		$gs = new GroupSelector($contacts, array(), "contact");
		$form->addElement(Lang::txt("ProbenphasenView_addContact.contact"), $gs);
		$form->write();
	}
	
	function addContactOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}
	
	function process_addContact() {
		$this->getData()->addContacts($_GET["id"]);
		new Message(Lang::txt("ProbenphasenView_process_addContact.message_1"), Lang::txt("ProbenphasenView_process_addContact.message_2"));
	}
	
	function process_addContactOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}
	
	function addMultipleContacts() {
		$form = new Form(Lang::txt("ProbenphasenView_addMultipleContacts.form"),
				$this->modePrefix() . "process_addMultipleContacts&id=" . $_GET["id"]);
		
		Writing::p(Lang::txt("ProbenphasenView_addMultipleContacts.message"));
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("ProbenphasenView_addMultipleContacts.name_member"), $gs);
		$form->write();
	}
	
	function addMultipleContactsOptions() {
		$this->backToViewTab($_GET["id"], "contacts");
	}

	function process_addMultipleContacts() {
		$this->getData()->addGroupContacts($_GET["id"]);
		new Message(Lang::txt("ProbenphasenView_process_addMultipleContacts.message_1"), Lang::txt("ProbenphasenView_process_addMultipleContacts.message_2"));
	}
	
	function process_addMultipleContactsOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	
	function addRemoveColumnToTable($entity, $data) {
		// header
		array_push($data[0], Lang::txt("ProbenphasenView_addRemoveColumnToTable.delete"));
		
		// body
		foreach($data as $i => $row) {
			if($i == 0) continue;
			$delLink = $this->modePrefix() . "delEntity&entity=$entity&id=" . $_GET["id"] . "&eid=" . $row["id"];
			$lnk = new Link($delLink, "");
			$lnk->addIcon("trash3");
			array_push($row, $lnk->toString());
			$row[Lang::txt("ProbenphasenView_addRemoveColumnToTable.delete")] = $lnk->toString();
			$data[$i] = $row;
		}
		return $data;
	}
	
	function delEntity() {
		$this->getData()->deleteEntity($_GET["entity"], $_GET["id"], $_GET["eid"]);
		$this->view();
	}
}
