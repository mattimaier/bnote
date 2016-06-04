<?php

class TourView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("tour"));
		$this->idParameter = "accId";
	}
	
	function view() {
		$this->checkID();
		
		$tabs = array(
				"details" => "Details",
				"rehearsals" => "Proben",
				"contacts" => "Teilnehmer",
				"concerts" => "Konzerte",
				"accommodation" => "Übernachtungen",
				"travel" => "Reisen",
				"checklist" => "Checklist",
				"equipment" => "Equipment"
		);
		echo "<div class=\"view_tabs\">\n";
		foreach($tabs as $tabid => $label) {
			$href = $this->modePrefix() . "view&" . $this->idParameter . "=" . $_GET[$this->idParameter] . "&tab=$tabid";
		
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
		else if($_GET["tab"] != "accommodation" && $_GET["tab"] != "travel") {
			$func = "tab_" . $_GET["tab"];
			$this->$func();
		}
			
		echo "</div>\n";
	}
	
	function viewOptions() {
		if((isset($_GET["func"]) && $_GET["func"] == "start") || !isset($_GET["func"])) {
			parent::viewOptions();
		}
		else {
			$this->additionalViewButtons();
		}
	}
	
	function additionalViewButtons() {
		// show the option of a tour summary sheet
		$summary = new Link($this->modePrefix() . "summarySheet&accId=" . $_GET[$this->idParameter], Lang::txt("tour_summarysheet"));
		$summary->addIcon("printer");
		$summary->write();
		$this->buttonSpace();
		
		// show options based on selected tab
		if(isset($_GET["tab"])) {
			$tab = $_GET["tab"];
			
			switch($tab) {
				case "accommodation": 
					$view = $this->getController()->getAccommodationView();
					$view->subModuleOptions();
					$this->buttonSpace();
					break;
				case "rehearsals":
					$this->addReferenceButton("tour_add_rehearsal", "addRehearsal");
					break;
				case "contacts":
					$this->addReferenceButton("tour_add_contacts", "addContacts");
					break;
				case "concerts":
					$this->addReferenceButton("tour_add_concert", "addConcert");
					break;
				case "travel":
					$view = $this->getController()->getTravelView();
					$view->subModuleOptions();
					$this->buttonSpace();
					break;
			}
		}
	}
	
	private function addReferenceButton($lang_txt, $target) {
		$addRef = new Link($this->modePrefix() . $target . "&accId=" . $_GET[$this->idParameter], Lang::txt($lang_txt));
		$addRef->addIcon("plus");
		$addRef->write();
		$this->buttonSpace();
	}
	
	// --- REHEARSALS ---
	function tab_rehearsals() {
		// show all rehearsals in this tour
		$rehearsals = $this->getData()->getRehearsals($_GET[$this->idParameter]);
		$table = new Table($rehearsals);
		$table->renameHeader("begin", Lang::txt("tour_rehearsal_tab_begin"));
		$table->renameHeader("rehearsal_notes", Lang::txt("tour_rehearsal_tab_notes"));
		$table->renameHeader("name", Lang::txt("name"));
		$table->renameHeader("location_notes", Lang::txt("tour_rehearsal_tab_location_notes"));
		$table->renameHeader("street", Lang::txt("street"));
		$table->renameHeader("city", Lang::txt("city"));
		$table->setEdit("id");
		$table->removeColumn("id");
		$table->setModId(5);
		$table->write();
	}
	
	function addRehearsal() {
		# show form to add a new rehearsal that's automatically assigned to this tour
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$this->getController()->getRehearsalView()->addEntity(
			$this->modePrefix() . "addRehearsalProcess&$idf=$tour&tab=rehearsals", $tour
		);
	}
	
	protected function addXOptionsBack($tab) {
		$idf = $this->idParameter;
		$back = new Link($this->modePrefix() . "view&$idf=" . $_GET[$this->idParameter] . "&tab=$tab", Lang::txt("back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function addRehearsalOptions() {
		$this->addXOptionsBack("rehearsals");
	}
	
	function addRehearsalProcess() {
		$this->getData()->createRehearsal($_POST);
		new Message(Lang::txt("tour_rehearsal_created"), Lang::txt("tour_rehearsal_created_msg"));
	}
	
	function addRehearsalProcessOptions() {
		$this->addRehearsalOptions();
	}
	
	// --- CONTACTS ---
	function tab_contacts() {
		$contacts = $this->getData()->getContacts($_GET[$this->idParameter]);
		$tour_id = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$contacts = Table::addDeleteColumn(
				$contacts,
				$this->modePrefix() . "removeContact&$idf=$tour_id&tab=contacts&id=", 
				"delete",
				Lang::txt("tour_contact_remove_ref"));
		
		$table = new Table($contacts);
		$cols_to_remove = array("id", "fax", "business", "web", "notes", "address", "status", "instrument", "street", "city", "zip");
		foreach($cols_to_remove as $col) {
			$table->removeColumn($col);
		}
		$table->renameHeader("surname", Lang::txt("surname"));
		$table->renameHeader("name", Lang::txt("name"));
		$table->renameHeader("phone", Lang::txt("phone"));
		$table->renameHeader("mobile", Lang::txt("mobile"));
		$table->renameHeader("birthday", Lang::txt("birthday"));
		$table->renameHeader("instrumentname", Lang::txt("instrument"));
		
		$table->write();
	}
	
	function addContacts() {
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$form = new Form(Lang::txt("add_contacts_form_title"), $this->modePrefix() . "addContactsProcess&$idf=$tour&tab=contacts");
		$contacts = $this->getData()->adp()->getContacts();
		$grpSelector = new GroupSelector($contacts, array(), "contact");
		$grpSelector->setNameColumns(array("name", "surname"));
		$form->addElement("", $grpSelector);
		$form->write();
	}
	
	function addContactsOptions() {
		$this->addXOptionsBack("contacts");
	}
	
	function addContactsProcess() {
		$tour_id = $_GET[$this->idParameter];
		$contacts = $this->getData()->adp()->getContacts($tour_id);
		$contactIds = GroupSelector::getPostSelection($contacts, "contact");
		$this->getData()->addContacts($tour_id, $contactIds);
		new Message(Lang::txt("tour_add_contacts_success_title"), Lang::txt("tour_add_contacts_success_msg"));
	}
	
	function addContactsProcessOptions() {
		$this->addContactsOptions();
	}
	
	function removeContact() {
		$this->getData()->removeReference($_GET[$this->idParameter], "contact", $_GET[$this->idField]);
		$this->view();
	}
	
	function removeContactOptions() {
		$this->viewOptions();
	}
	
	// --- CONCERTS ---
	function tab_concerts() {
		$concerts = $this->getData()->getConcerts($_GET[$this->idParameter]);
		
		$table = new Table($concerts);
		$table->removeColumn("id");
		$table->setEdit("id");
		$table->setModId(4);
		$table->write();
	}
	
	function addConcert() {
		$view = $this->getController()->getConcertView();
		$tour_id = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$step = 1;
		if(isset($_GET["step"])) {
			$step = $_GET["step"];
		}
		$nextStep = $step+1;
		$step_func = "step$step";
		if($nextStep == 7) {
			$this->getData()->addConcert($tour_id, $_POST);
		}
		$view->$step_func("addConcert&step=$nextStep&$idf=$tour_id&tab=concerts");
	}

	function addConcertOptions() {
		$this->addXOptionsBack("concerts");
	}
	
	// --- CHECKLIST ---
	function tab_checklist() {
		//TODO add a reference to tasks like to rehearsals and concerts
	}
	
	// --- EQUIPMENT ---
	function tab_equipment() {
		//TODO add a reference to equipment like to contacts --> only remove reference, not the equipment itself
	}
	
	function summarySheet() {
		$tour = $this->getData()->findByIdNoRef($_GET[$this->idParameter]);
		
		// Details
		Writing::h1($tour["name"]);
		Writing::p(Data::convertDateFromDb($tour["start"]) . " - " . Data::convertDateFromDb($tour["end"]));
		Writing::p($tour["notes"]);
		
		// Participants
		
		// Concerts
		
		// Rehearsals
		
		// Travel and Accommodation List
		
		// Task Checklist
		
		// Equipment
		
	}
	
	function summarySheetOptions() {
		$this->backToViewButton($_GET[$this->idParameter]);
		$this->buttonSpace();
		
		$prt = new Link("javascript:window.print()", Lang::txt("print"));
		$prt->addIcon("printer");
		$prt->write();
	}
}


?>