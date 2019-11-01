<?php

/**
 * View for concert module.
 * @author matti
 *
 */
class KonzerteView extends CrudRefLocationView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Auftritt");
		$this->setAddEntityName(Lang::txt("KonzerteView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"location" => array("name"),
			"program" => array("name"),
			"contact" => array("name", "surname"),
			"accommodation" => array("name")
		));
	}
	
	function showOptions() {
		if(isset($_GET["mode"]) && Data::startsWith($_GET["mode"], "step")) {
			if($this->isMode("step1")) {
				$this->backToStart();
			}
		}
		else if(isset($_GET["mode"]) && $_GET["mode"] == "programs") {
			$this->getController()->getProgramView()->showOptions();
		}
		else {
			parent::showOptions();
		}
	}
	
	function start() {
		Writing::p(Lang::txt("KonzerteView_start.Message"));
		
		// Next Concert
		$concerts = $this->getData()->getFutureConcerts();
		Writing::h2(Lang::txt("KonzerteView_start.Next"));
		if(count($concerts) > 1) {
			$this->writeConcert($concerts[1]);
		}
		
		// More Concerts
		Writing::h2(Lang::txt("KonzerteView_start.More"));
		$this->writeConcerts($concerts);
	}
	
	protected function startOptions() {
		parent::startOptions();
		
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "programs", Lang::txt("KonzerteView_startOptions.programs"));
		$lnk->addIcon("setlist");
		$lnk->write();
		
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "history", Lang::txt("KonzerteView_startOptions.history"));
		$lnk->addIcon("timer");
		$lnk->write();
	}
	
	private function writeConcerts($concerts) {
		for($i = 2; $i < count($concerts); $i++) {
			$this->writeConcert($concerts[$i]);
		}
	}
	
	private function writeConcert($concert) {
		// when? where? who to talk to? notes + program
		$text = "<p class=\"concert_title\">" . Data::convertDateFromDb($concert["begin"]);
		$text .= Lang::txt("KonzerteView_writeConcert.title") . "<span class=\"concert_title_name\">" . $concert["title"] . "</span></p>";
		
		// location
		$text .= "<span class=\"concert_location\">";
		$text .= $concert["location_name"] . ", " . $this->formatAddress($concert, FALSE, "location_");
		$text .= "</span>";
		
		// contact
		if($concert["contact_name"] != "") {
			$text .= "<span class=\"concert_contact\">" . $this->formatContact($concert, "NAME_COMM", "contact_") . "</span>";
		}
		
		// notes
		$text .= "<span class=\"concert_notes\">" . $concert["notes"] . "</span>\n";
		
		// actually write concert
		echo '<a class="concert" href="' . $this->modePrefix() . "view&id=" . $concert["id"] . '">';
		echo '<div class="concert">';
		echo $text;
		echo "</div></a>";
	}
	
	function history() {
		// defaults
		$to = date("d.m.Y");
		$from = Data::subtractMonthsFromDate($to, 12);
		if(isset($_POST["from"])) {
			$from = $_POST["from"];
		}
		if(isset($_POST["to"])) {
			$to = $_POST["to"];
		}
		
		// filters
		$filter = new Filterbox($this->modePrefix() . "history");
		$filter->addFilter("from", Lang::txt("KonzerteView_history.from"), FieldType::DATE, $from);
		$filter->addFilter("to", Lang::txt("KonzerteView_history.to"), FieldType::DATE, $to);
		$filter->write();
		
		// data
		$concerts = $this->getData()->getPastConcerts($from, $to);
		
		// table
		$table = new Table($concerts);
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("notes");
		$table->renameHeader("id", Lang::txt("KonzerteView_history.id"));
		$table->renameHeader("location_name", Lang::txt("KonzerteView_history.location_name"));
		$table->renameHeader("location_city", Lang::txt("KonzerteView_history.location_city"));
		$table->renameHeader("contact_name", Lang::txt("KonzerteView_history.contact_name"));
		$table->renameHeader("program_name", Lang::txt("KonzerteView_history.program_name"));
		$table->setColumnFormat("begin", "DATE");
		$table->setColumnFormat("end", "DATE");
		$table->setEdit("id");
		$table->showFilter(false);
		$table->write();
	}
	
	function view() {
		// get data
		$c = $this->getData()->findByIdNoRef($_GET["id"]);
		$custom = $this->getData()->getCustomData($_GET["id"]);
		$loc = $this->getData()->getLocation($c["location"]);
		
		// concert details
		Writing::h1($c["title"]);
		
		Writing::p($c["notes"]);
		?>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading"><?php echo Lang::txt("KonzerteView_view.title"); ?></div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.organizer"); ?></span>
					<span class="concertdetail_value"><?php 
					if($c["organizer"]) {
						echo $c["organizer"];
					}
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.location"); ?></span>
					<span class="concertdetail_value"><?php 
					echo $loc["name"] . "<br/>";
					echo $this->formatAddress($this->getData()->getAddress($loc["address"]));
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.contact"); ?></span>
					<span class="concertdetail_value"><?php 
					if($c["contact"]) {
						$cnt = $this->getData()->getContact($c["contact"]);
						$cv = $this->formatContact($cnt, 'NAME_COMM_LB');
					}
					else {
						$cv = "-";
					}
					echo $cv;
					?></span>
				</div>
			</div>
		</div>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading"><?php echo Lang::txt("KonzerteView_view.periods"); ?></div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.date"); ?></span>
					<span class="concertdetail_value"><?php 
					echo Data::convertDateFromDb($c["begin"]) . " - ";
					echo Data::convertDateFromDb($c["end"]);
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.place"); ?></span>
					<span class="concertdetail_value"><?php echo Data::convertDateFromDb($c["meetingtime"]); ?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.till"); ?></span>
					<span class="concertdetail_value"><?php echo Data::convertDateFromDb($c["approve_until"]); ?></span>
				</div>
			</div>
		</div>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading"><?php echo Lang::txt("KonzerteView_view.organisation"); ?></div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.occupation"); ?></span>
					<span class="concertdetail_value"><?php 
					$groups = $this->getData()->getConcertGroups($c["id"]);
					$groupNames = Database::flattenSelection($groups, "name");
					echo join(", ", $groupNames);
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.program"); ?></span>
					<span class="concertdetail_value"><?php 
					if($c["program"]) {
						$prg = $this->getData()->getProgram($c["program"]);
						echo $prg["name"];
					}
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.Outfit"); ?></span>
					<span class="concertdetail_value"><?php 
					if($c["outfit"]) {
						$outfit = $this->getData()->getOutfit($c["outfit"]);
						echo $outfit["name"];
					}
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.equipment"); ?></span>
					<span class="concertdetail_value">
						<?php 
						$equipment = $this->getData()->getConcertEquipment($c["id"]);
						if(count($equipment) == 0) {
							echo '-';		
						}
						else {
							echo '<ul>';
							for($e = 1; $e < count($equipment); $e++) {
								echo '<li>' . $equipment[$e]["name"] . '</li>';
							}
							echo '</ul>';
						}
						?>						
					</span>
				</div>
			</div>
		</div>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading"><?php echo Lang::txt("KonzerteView_view.details"); ?></div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.accommodation"); ?></span>
					<span class="concertdetail_value"><?php 
					if($c["accommodation"] > 0) {
						$acc = $this->getData()->adp()->getAccommodationLocation($c["accommodation"]);
						echo $acc["name"] . "<br>" . $this->formatAddress($acc);
					}
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.payment"); ?></span>
					<span class="concertdetail_value"><?php echo Data::convertFromDb($c["payment"]); ?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteView_view.conditions"); ?></span>
					<span class="concertdetail_value"><?php echo $c["conditions"]; ?></span>
				</div>
			<?php 
			$customFields = $this->getData()->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE);
			for($i = 1; $i < count($customFields); $i++) {
				$field = $customFields[$i];
				?>
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo $field["txtdefsingle"]; ?></span>
					<span class="concertdetail_value"><?php echo $custom[$field["techname"]]; ?></span>
				</div>
				<?php 
			}
			?>
			</div>
		</div>
		
		<?php
		
		// invitations
		$this->viewInvitations();
		$this->verticalSpace();
		
		// phases
		$this->viewPhases();
	}
	
	private function viewInvitations() {
		// manage members who will play in this concert
		Writing::h2(Lang::txt("KonzerteView_viewInvitations.title"));
		
		$contacts = $this->getData()->getConcertContacts($_GET["id"]);
		$contacts = Table::addDeleteColumn($contacts, $this->modePrefix() . "delConcertContact&id=" . $_GET["id"] . "&contactid=");
		$tab = new Table($contacts);
		$tab->removeColumn("id");
		$tab->renameHeader("fullname", Lang::txt("KonzerteView_viewInvitations.fullname"));
		$tab->renameHeader("nickname", Lang::txt("KonzerteView_viewInvitations.nickname"));
		$tab->renameHeader("phone", Lang::txt("KonzerteView_viewInvitations.phone"));
		$tab->renameHeader("mobile", Lang::txt("KonzerteView_viewInvitations.mobile"));
		$tab->write();
	}
	
	private function viewPhases() {
		// show the rehearsal phases this concert is related to
		Writing::h2(Lang::txt("KonzerteView_viewPhases.title"));
		$phases = $this->getData()->getRehearsalphases($_GET["id"]);
		$tab = new Table($phases);
		$tab->removeColumn("id");
		$tab->renameHeader("Begin", Lang::txt("KonzerteView_viewPhases.Begin"));
		$tab->renameHeader("end", Lang::txt("KonzerteView_viewPhases.end"));
		$tab->renameHeader("notes", Lang::txt("KonzerteView_viewPhases.notes"));
		$tab->write();
	}
	
	function addConcertContact() {
		$this->checkID();
		
		$form = new Form("Kontakt hinzufügen", $this->modePrefix() . "process_addConcertContact&id=" . $_GET["id"]);
		
		// single contacts
		$gs = new GroupSelector($this->getData()->getContacts(), array(), "contact");
		$gs->setNameColumn("fullname");
		$form->addElement("Einzeln", $gs);
		
		// contact groups
		$grp = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$grp->setNameColumn("name_member");
		$form->addElement("Gruppe", $grp);
		
		$form->write();
	}
	
	function addConcertContactOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function process_addConcertContact() {
		$this->checkID();
		
		// single contacts
		$contacts = GroupSelector::getPostSelection($this->getData()->getContacts(), "contact");
		$this->getData()->addConcertContact($_GET["id"], $contacts);
		
		// groups
		$groups = GroupSelector::getPostSelection($this->getData()->adp()->getGroups(), "group");
		$this->getData()->addConcertContactGroup($_GET["id"], $groups);
		
		new Message("Kontakte hinzugefügt", "Der oder die Kontakte wurden dem Auftritt hinzugefügt.");
	}
	
	function process_addConcertContactOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function delConcertContact() {
		$this->checkID();
		$this->getData()->deleteConcertContact($_GET["id"], $_GET["contactid"]);
		$this->view();
	}
	
	function delConcertContactOptions() {
		$this->viewOptions();
	}
	
	function editEntityForm($write=true) {
		// data
		$c = $this->getData()->getConcert($_GET["id"]);
		
		// form init
		$form = new Form("Auftritt bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), "concert", $_GET["id"]);
		$form->removeElement("accommodation");
		$form->removeElement("id");
		
		// location
		$form->setForeign("location", "location", "id", "name", $c["location"]);
		
		// program
		if(!isset($c["program"]) || $c["program"] == "" || $c["program"] == null) {
			$c["program"] = "0";
			$form->addElement("program", new Field("program", $c["program"], FieldType::REFERENCE));
		}
		$form->setForeign("program", "program", "id", "name", $c["program"]);
		$form->addForeignOption("program", "Kein Programm", "0");
		$form->setForeignOptionSelected("program", $c["program"]);
		
		// contact
		$form->removeElement("contact");
		$dd = new Dropdown("contact");
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $contacts[$i]["name"] . " " . $contacts[$i]["surname"];
			$instr = isset($contacts[$i]["instrumentname"]) ? $contacts[$i]["instrumentname"] : '';
			if($instr != "") $label .= " (" . $contacts[$i]["instrumentname"] . ")";
			$dd->addOption($label, $contacts[$i]["id"]);
		}
		$dd->setSelected($c["contact"]);
		$form->addElement("Kontakt", $dd);
		
		// accommodation
		$dd3 = new Dropdown("accommodation");
		$accommodations = $this->getData()->adp()->getLocations(array(3));
		$dd3->addOption("Keine Unterkunft", 0);
		for($a = 1; $a < count($accommodations); $a++) {
			$acc = $accommodations[$a];
			$caption = $acc["name"] . ": " . $this->formatAddress($acc);
			$dd3->addOption($caption, $acc["id"]);
		}
		$dd3->setSelected($c["accommodation"]);
		$form->addElement("Unterkunft", $dd3);
		
		// outfit
		$outfit = $c['outfit'];
		if($outfit == "" || !isset($c["outfit"])) {
			$outfit = 0;
			$form->addElement("outfit", new Field("outfit", $c["outfit"], FieldType::REFERENCE));
		}
		$form->setForeign("outfit", "outfit", "id", array("name"), $outfit);
		$form->addForeignOption("outfit", "Kein Outfit", 0);
		
		// custom data
		$this->appendCustomFieldsToForm($form, KonzerteData::$CUSTOM_DATA_OTYPE, $c);
		
		$form->write();
	}
	
	function additionalViewButtons() {
		$partLink = new Link($this->modePrefix() . "showParticipants&id=" . $_GET["id"], "Teilnehmer anzeigen");
		$partLink->addIcon("user");
		$partLink->write();
		
		// concert contact
		$addContact = new Link($this->modePrefix() . "addConcertContact&id=" . $_GET["id"], "Kontakt hinzufügen");
		$addContact->addIcon("plus");
		$addContact->write();
		
		// gig card (Word export)
		$word = new Link("src/export/gigcard.doc?id=" . $_GET["id"], "Word Export");
		$word->addIcon("save");
		$word->write();
		
		// notifications
		$emLink = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation");
		$emLink .= "&mode=concertMail&preselect=" . $_GET["id"];
		$em = new Link($emLink, "Benachrichtigung senden");
		$em->addIcon("email");
		$em->write();
	}
	
	function showParticipants() {
		$this->checkID();
		$parts = $this->getData()->getParticipants($_GET["id"]);
		
		Writing::h2(Lang::txt("KonzerteView_showParticipants.title_1"));
		$table = new Table($parts);
		$table->renameHeader("participate", Lang::txt("KonzerteView_showParticipants.participate"));
		$table->renameHeader("reason", Lang::txt("KonzerteView_showParticipants.reason"));
		$table->renameHeader("category", Lang::txt("KonzerteView_showParticipants.category"));
		$table->renameHeader("nickname", Lang::txt("KonzerteView_showParticipants.nickname_1"));
		$table->removeColumn("id");
		$table->write();
		$this->verticalSpace();
		
		Writing::h3(Lang::txt("KonzerteView_showParticipants.title_2"));
		$openTab = new Table($this->getData()->getOpenParticipants($_GET["id"]));
		$openTab->removeColumn("id");
		$openTab->renameHeader("nickname", Lang::txt("KonzerteView_showParticipants.nickname_2"));
		$openTab->renameHeader("fullname", Lang::txt("KonzerteView_showParticipants.fullname"));
		$openTab->renameHeader("phone", Lang::txt("KonzerteView_showParticipants.phone"));
		$openTab->renameHeader("mobile", Lang::txt("KonzerteView_showParticipants.mobile"));
		$openTab->write();
	}
	
	protected function showParticipantsOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	protected function addEntityForm() {
		require_once $GLOBALS["DIR_WIDGETS"] . "sectionform.php";
		
		$form = new SectionForm(Lang::txt($this->getaddEntityName()), $this->modePrefix() . "add");
		$this->flash(Lang::txt("KonzerteView_addEntityForm.flash_1"), Lang::txt("KonzerteView_addEntityForm.flash_2"));
		
		// ************* MASTER DATA *************
		$title_field = new Field("title", "", FieldType::CHAR);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.title"), $title_field, true);
		$begin_field = new Field("begin", "", FieldType::DATETIME);
		$begin_field->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.begin"), $begin_field, true);
		$end_field = new Field("end", "", FieldType::DATETIME);
		$end_field->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.copyDateTarget"), $end_field, true);
		$approve_field = new Field("approve_until", "", FieldType::DATETIME);
		$approve_field->setCssClass("copyDateTarget");
		$meetingtime = new Field("meetingtime", "", FieldType::DATETIME);
		$meetingtime->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.meetingtime_from"), $meetingtime, true);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.meetingtime_to"), $approve_field, true);
		$notesField = new Field("notes", "", FieldType::TEXT);
		$notesField->setColsAndRows(5, 40);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.notes"), $notesField);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.title"), array("title", "begin", "end", "approve_until", "meetingtime", "notes"));
		
		// ************* LOCATION AND CONTACT *************
		// choose location
		$dd1 = new Dropdown("location");
		$locs = $this->getData()->getLocations();
		for($i = 1; $i < count($locs); $i++) {
			$loc = $locs[$i];
			$l = $loc["name"] . ": " . $this->formatAddress($loc);
			$dd1->addOption($l, $loc["id"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.location"), $dd1, true);
		
		// choose contact
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.organizer"), new Field("organizer", "", FieldType::CHAR));
		$dd2 = new Dropdown("contact");
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $this->formatContact($contacts[$i], "NAME_COMM");
			$dd2->addOption($label, $contacts[$i]["id"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.contact"), $dd2, true);
		
		// choose accommodation
		$dd3 = new Dropdown("accommodation");
		$accommodations = $this->getData()->adp()->getLocations(array(3));
		$dd3->addOption("Keine Unterkunft", 0);
		for($a = 1; $a < count($accommodations); $a++) {
			$acc = $accommodations[$a];
			$caption = $acc["name"] . ": " . $this->formatAddress($acc);
			$dd3->addOption($caption, $acc["id"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.accommodation"), $dd3);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.title_location"), array("location", "organizer", "contact", "accommodation"));
		
		// ************* ORGANISATION *************
		// choose members
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), array(), "group");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.group"), $gs, true);
		
		// chosse program
		$dd4 = new Dropdown("program");
		$templates = $this->getData()->getTemplates();
		$dd4->addOption("Keine Auswahl", 0);
		for($i = 1; $i < count($templates); $i++) {
			$dd4->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.program"), $dd4);
		
		// choose equipment
		$equipment = $this->getData()->adp()->getEquipment();
		$equipmentSelector = new GroupSelector($equipment, array(), "equipment");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.equipment"), $equipmentSelector);
		
		// outfit
		$form->addElement("outfit", new Field(Lang::txt("KonzerteView_addEntityForm.outfit"), 0, FieldType::REFERENCE));
		$form->setForeign("outfit", "outfit", "id", array("name"), -1);
		$form->addForeignOption("outfit", "Kein Outfit", 0);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.group_title"), array("group", "program", "equipment", "outfit"));
		$form->renameElement("outfit", Lang::txt("KonzerteView_addEntityForm.outfit"));
		
		// ************* DETAILS *************
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.payment"), new Field("payment", "", FieldType::CURRENCY));
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.conditions"), new Field("conditions", "", FieldType::TEXT));
		
		// custom fields
		$customFields = $this->getData()->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE);
		$customFieldNames = Database::flattenSelection($customFields, "techname");
		$this->appendCustomFieldsToForm($form, KonzerteData::$CUSTOM_DATA_OTYPE);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.details_title"), array_merge(array("payment", "conditions"), $customFieldNames));
		
		$form->write();
	}
	
	function exportFormatAddress($address) {
		return $this->formatAddress($address);
	}
	
	function exportFormatContact($contact, $profile) {
		return $this->formatContact($contact, $profile);
	}
}

?>
