<?php

class TourView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("TourView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("TourView_construct.addEntityName"));
		$this->idParameter = "accId";
	}
	
	function view() {
		$this->checkID();
		
		$tabs = array(
				"details" => Lang::txt("TourView_view.details"),
				"rehearsals" => Lang::txt("TourView_view.rehearsals"),
				"contacts" => Lang::txt("TourView_view.contacts"),
				"concerts" => Lang::txt("TourView_view.concerts"),
				"accommodation" => Lang::txt("TourView_view.accommodation"),
				"travel" => Lang::txt("TourView_view.travel"),
				"checklist" => Lang::txt("TourView_view.checklist"),
				"equipment" => Lang::txt("TourView_view.equipment")
		);
		echo "<div class=\"nav nav-tabs\">\n";
		foreach($tabs as $tabid => $label) {
			$href = $this->modePrefix() . "view&" . $this->idParameter . "=" . $_GET[$this->idParameter] . "&tab=$tabid";
		
			$active = "";
			if(isset($_GET["tab"]) && $_GET["tab"] == $tabid) $active = "active";
			else if(!isset($_GET["tab"]) && $tabid == "details") $active = "active";
		
			echo "<div class=\"nav-item\"><a class=\"nav-link $active\" href=\"$href\">$label</a></div>";
		}
		echo "</div>\n";
		echo "<div class=\"view_tab_content pt-2\">\n";
		
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
		$summary = new Link($this->modePrefix() . "summarySheet&accId=" . $_GET[$this->idParameter], Lang::txt("TourView_additionalViewButtons.summarysheet"));
		$summary->addIcon("printer");
		$summary->write();
		
		// show options based on selected tab
		if(isset($_GET["tab"])) {
			$tab = $_GET["tab"];
			
			switch($tab) {
				case "accommodation": 
					$view = $this->getController()->getAccommodationView();
					$view->subModuleOptions();
					break;
				case "rehearsals":
					$this->addReferenceButton(Lang::txt("TourView_additionalViewButtons.addRehearsal"), "addRehearsal");
					break;
				case "contacts":
					$this->addReferenceButton(Lang::txt("TourView_additionalViewButtons.addContacts"), "addContacts");
					break;
				case "concerts":
					$this->addReferenceButton(Lang::txt("TourView_additionalViewButtons.addConcert"), "addConcert");
					break;
				case "travel":
					$view = $this->getController()->getTravelView();
					$view->subModuleOptions();
					break;
				case "checklist":
					$this->addReferenceButton(Lang::txt("TourView_additionalViewButtons.addTask"), "addTask");
					break;
			}
		}
	}
	
	private function addReferenceButton($lang_txt, $target) {
		$addRef = new Link($this->modePrefix() . $target . "&accId=" . $_GET[$this->idParameter], Lang::txt($lang_txt));
		$addRef->addIcon("plus");
		$addRef->write();
	}
	
	// --- REHEARSALS ---
	function tab_rehearsals() {
		// show all rehearsals in this tour
		$rehearsals = $this->getData()->getRehearsals($_GET[$this->idParameter]);
		$table = new Table($rehearsals);
		$table->renameHeader("begin", Lang::txt("TourView_tab_rehearsals.begin"));
		$table->renameHeader("rehearsal_notes", Lang::txt("TourView_tab_rehearsals.rehearsal_notes"));
		$table->renameHeader("name", Lang::txt("TourView_tab_rehearsals.name"));
		$table->renameHeader("location_notes", Lang::txt("TourView_tab_rehearsals.location_notes"));
		$table->renameHeader("street", Lang::txt("TourView_tab_rehearsals.street"));
		$table->renameHeader("city", Lang::txt("TourView_tab_rehearsals.city"));
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
		$back->addIcon("arrow-left");
		$back->write();
	}
	
	function addRehearsalOptions() {
		$this->addXOptionsBack("rehearsals");
	}
	
	function addRehearsalProcess() {
		$this->getData()->createRehearsal($_POST);
		new Message(Lang::txt("TourView_addRehearsalProcess.message_1"), Lang::txt("TourView_addRehearsalProcess.message_2"));
	}
	
	function addRehearsalProcessOptions() {
		$this->addRehearsalOptions();
	}
	
	// --- CONTACTS ---
	function tab_contacts($sheetMode=false) {
		$contacts = $this->getData()->getContacts($_GET[$this->idParameter]);
		$tour_id = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		
		if(!$sheetMode) {
			$contacts = Table::addDeleteColumn(
					$contacts,
					$this->modePrefix() . "removeContact&$idf=$tour_id&tab=contacts&id=", 
					"delete",
					Lang::txt("tour_contact_remove_ref"));
		}
		$table = new Table($contacts);
		$cols_to_remove = array("id", "fax", "business", "web", "notes", "address", "status", "instrument", "street", "city", "zip");
		foreach($cols_to_remove as $col) {
			$table->removeColumn($col);
		}
		$table->renameHeader("surname", Lang::txt("TourView_tab_contacts.surname"));
		$table->renameHeader("name", Lang::txt("TourView_tab_contacts.name"));
		$table->renameHeader("Nickname", Lang::txt("TourView_tab_contacts.Nickname"));
		$table->renameHeader("phone", Lang::txt("TourView_tab_contacts.phone"));
		$table->renameHeader("mobile", Lang::txt("TourView_tab_contacts.mobile"));
		$table->renameHeader("birthday", Lang::txt("TourView_tab_contacts.birthday"));
		$table->renameHeader("Gdpr_ok", Lang::txt("TourView_tab_contacts.Gdpr_ok"));
		$table->renameHeader("Gdpr_code", Lang::txt("TourView_tab_contacts.Gdpr_code"));
		$table->renameHeader("Is_conductor", Lang::txt("TourView_tab_contacts.Is_conductor"));
		$table->renameHeader("Company", Lang::txt("TourView_tab_contacts.Company"));
		$table->renameHeader("Share_address", Lang::txt("TourView_tab_contacts.Share_address"));
		$table->renameHeader("Share_phones", Lang::txt("TourView_tab_contacts.Share_phones"));
		$table->renameHeader("Share_birthday", Lang::txt("TourView_tab_contacts.Share_birthday"));
		$table->renameHeader("Share_email", Lang::txt("TourView_tab_contacts.Share_email"));
		$table->renameHeader("State", Lang::txt("TourView_tab_contacts.State"));
		$table->renameHeader("Country", Lang::txt("TourView_tab_contacts.Country"));
		$table->renameHeader("instrumentname", Lang::txt("TourView_tab_contacts.instrumentname"));
		$table->renameHeader("tour_contact_remove_ref", Lang::txt("TourView_tab_contacts.instrumentname"));
		
		$table->write();
	}
	
	function addContacts() {
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$form = new Form(Lang::txt("TourView_addContacts.form"), $this->modePrefix() . "addContactsProcess&$idf=$tour&tab=contacts");
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
		new Message(Lang::txt("TourView_addContactsProcess.message_1"), Lang::txt("TourView_addContactsProcess.message_2"));
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
		$table->renameHeader("title", Lang::txt("TourView_tab_concerts.title"));
		$table->renameHeader("begin", Lang::txt("TourView_tab_concerts.begin"));
		$table->renameHeader("end", Lang::txt("TourView_tab_concerts.end"));
		$table->renameHeader("notes", Lang::txt("TourView_tab_concerts.notes"));
		$table->renameHeader("locationname", Lang::txt("TourView_tab_concerts.locationname"));
		$table->renameHeader("program", Lang::txt("TourView_tab_concerts.program"));
		$table->renameHeader("approve_until", Lang::txt("TourView_tab_concerts.approve_until"));
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
		// add a reference to tasks like to rehearsals and concerts
		$tour_id = $_GET[$this->idParameter];
		
		$todos = $this->getData()->getTasks($tour_id, false);
		Writing::h4(Lang::txt("TourView_tab_checklist.todos"));
		$this->checklist_table($todos);
		
		$completed_tasks = $this->getData()->getTasks($tour_id, true);
		Writing::h4(Lang::txt("TourView_tab_checklist.completed_tasks"));
		$this->checklist_table($completed_tasks);
	}
	
	protected function checklist_table($tasks) {
		$table = new Table($tasks);
		$table->removeColumn("id");
		$table->removeColumn("is_complete");
		$table->renameHeader("title", Lang::txt("TourView_tab_checklist.title"));
		$table->renameHeader("description", Lang::txt("TourView_tab_checklist.description"));
		$table->renameHeader("assigned_to", Lang::txt("TourView_tab_checklist.assigned_to"));
		$table->renameHeader("due_at", Lang::txt("TourView_tab_checklist.due_at"));
		$table->setColumnFormat("is_complete", "BOOLEAN");
		$table->setEdit("id");
		$table->setModId(16);
		$table->write();
	}
	
	function addTask() {
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$this->getController()->getChecklistView()->addEntity(
			$this->modePrefix() . "addTaskProcess&$idf=$tour&tab=checklist", $tour
		);
	}
	
	function addTaskOptions() {
		$this->addXOptionsBack("checklist");
	}
	
	function addTaskProcess() {
		$this->getData()->createTask($_GET[$this->idParameter], $_POST);
		$this->view();
	}
	
	function addTaskProcessOptions() {
		$this->viewOptions();
	}
	
	// --- EQUIPMENT ---
	function tab_equipment() {
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$form = new Form("", $this->modePrefix() . "addEquipmentProcess&$idf=$tour&tab=equipment");
		
		// Building an editable table --> replace quantity with an input field and add tour notes for each equipment
		$equipment = $this->getData()->getEquipment($tour);
		for($i = 1; $i < count($equipment); $i++) {
			$eq = $equipment[$i];
			$field_name_q = "e_q_" . $eq["id"];
			$field_name_t = "e_t_" . $eq["id"];
			$q_val = 0;
			$t_val = "";
			if(isset($eq["tour_quantity"])) {
				$q_val = $eq["tour_quantity"];
			}
			if(isset($eq["eq_tour_notes"])) {
				$t_val = $eq["eq_tour_notes"];
			}
			$equipment[$i]["tour_quantity"] = '<input type="number" name="' . $field_name_q . '" value="' . $q_val . '" min="0" style="width: 30px;" />';
			$equipment[$i]["eq_tour_notes"] = '<input type="text" name="' . $field_name_t . '" size="30" value="' . $t_val . '" />';
		}
		
		$table = new Table($equipment);
		$table->removeColumn("id");
		$table->removeColumn("purchase_price");
		$table->removeColumn("current_value");
		$table->renameHeader("model", Lang::txt("TourView_tab_equipment.model"));
		$table->renameHeader("make", Lang::txt("TourView_tab_equipment.make"));
		$table->renameHeader("tour_quantity", Lang::txt("TourView_tab_equipment.tour_quantity"));
		$table->renameHeader("equipment_notes", Lang::txt("TourView_tab_equipment.equipment_notes"));
		$table->renameHeader("eq_tour_notes", Lang::txt("TourView_tab_equipment.eq_tour_notes"));
		
		$form->addElement("", $table);
		$form->write();
	}
	
	function equipmentPrint() {
		$tour = $_GET[$this->idParameter];
		$equipment = $this->getData()->getEquipment($tour, false);
		
		$table = new Table($equipment);
		$table->removeColumn("id");
		$table->removeColumn("purchase_price");
		$table->removeColumn("current_value");
		$table->renameHeader("model", Lang::txt("TourView_tab_equipment.model"));
		$table->renameHeader("make", Lang::txt("TourView_tab_equipment.make"));
		$table->setColumnFormat("tour_quantity", "INT");
		$table->renameHeader("tour_quantity", Lang::txt("TourView_tab_equipment.tour_quantity"));
		$table->renameHeader("equipment_notes", Lang::txt("TourView_tab_equipment.equipment_notes"));
		$table->renameHeader("eq_tour_notes", Lang::txt("TourView_tab_equipment.eq_tour_notes"));
		$table->write();
	}
	
	function addEquipmentOptions() {
		$this->addXOptionsBack("equipment");
	}
	
	function addEquipmentProcess() {
		$tour = $_GET[$this->idParameter];
		$idf = $this->idParameter;
		$this->getData()->saveEquipment($tour, $_POST);
		new Message(Lang::txt("TourView_addEquipmentProcess.message_1"), Lang::txt("TourView_addEquipmentProcess.message_2"));
	}
	
	function addEquipmentProcessOptions() {
		$this->addEquipmentOptions();
	}
	
	function summarySheet() {
		$tour = $this->getData()->findByIdNoRef($_GET[$this->idParameter]);
		
		// insert a div for custom print control
		echo '<div id="tourSummarySheet">';
		
		// Details
		Writing::h1($tour["name"]);
		Writing::p(Data::convertDateFromDb($tour["start"]) . " - " . Data::convertDateFromDb($tour["end"]));
		Writing::p($tour["notes"]);
		
		// Participants
		Writing::h2(Lang::txt("TourView_summarySheet.tab_contacts"));
		$this->tab_contacts(true);
		
		// Concerts
		Writing::h2(Lang::txt("TourView_summarySheet.tab_concerts"), "pagebreak");
		$this->tab_concerts();
		
		// Rehearsals
		Writing::h2(Lang::txt("TourView_summarySheet.tab_rehearsals"), "pagebreak");
		$this->tab_rehearsals();
		
		// Travel and Accommodation List
		Writing::h2(Lang::txt("TourView_summarySheet.TravelView"), "pagebreak");
		$this->getController()->getTravelView()->showAllTable();
		
		Writing::h2(Lang::txt("TourView_summarySheet.AccommodationView"));
		$this->getController()->getAccommodationView()->showAllTable();
		
		// Task Checklist
		if(isset($_GET["checklist"]) && $_GET["checklist"] == "1") {
			Writing::h2(Lang::txt("tasks"), "pagebreak");
			$this->tab_checklist();
		}
		
		// Equipment
		Writing::h2(Lang::txt("TourView_summarySheet.equipment"), "pagebreak");
		$this->equipmentPrint();
		
		echo '</div>'; 
	}
	
	function summarySheetOptions() {
		$this->backToViewButton($_GET[$this->idParameter]);
		
		$prt = new Link("javascript:window.print()", Lang::txt("TourView_summarySheetOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
		
		$checklist = 1;
		$checklist_action = "show";
		$checklist_icon = "plus"; 
		if(isset($_GET["checklist"]) && $_GET["checklist"] == "1") {
			$checklist = 0;
			$checklist_action = "hide";
			$checklist_icon = "cancel";
		}
		$checklist = new Link(
			$this->modePrefix() . "summarySheet&" . $this->idParameter . "=" . $_GET[$this->idParameter] . "&checklist=$checklist",
			Lang::txt("tour_summary_" . $checklist_action . "_checklist")
		);
		$checklist->addIcon($checklist_icon);
		$checklist->write();
	}
}


?>