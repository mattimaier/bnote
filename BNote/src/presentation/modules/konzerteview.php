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
		$this->setEntityName(Lang::txt("KonzerteView_construct.concert"));
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
		Writing::h4(Lang::txt("KonzerteView_start.Next"), "mt-2");
		if(count($concerts) > 1) {
			$this->writeConcert($concerts[1]);
		}
		
		// More Concerts
		Writing::h4(Lang::txt("KonzerteView_start.More"), "mt-3");
		$this->writeConcerts($concerts);
	}
	
	protected function startOptions() {
		parent::startOptions();
		
		$lnk = new Link($this->modePrefix() . "overview", Lang::txt("KonzerteView_startOptions.overview"));
		$lnk->addIcon("people");
		$lnk->write();
		
		$lnk = new Link($this->modePrefix() . "programs", Lang::txt("KonzerteView_startOptions.programs"));
		$lnk->addIcon("music-note-list");
		$lnk->write();
		
		$lnk = new Link($this->modePrefix() . "history", Lang::txt("KonzerteView_startOptions.history"));
		$lnk->addIcon("clock-history");
		$lnk->write();
	}
	
	private function writeConcerts($concerts) {
		for($i = 2; $i < count($concerts); $i++) {
			$this->writeConcert($concerts[$i]);
		}
	}
	
	private function writeConcert($concert) {
		$href = $this->modePrefix() . "view&id=" . $concert["id"];
		
		// when? where? who to talk to? notes + program
		?>
		<div class="card concert">
			<div class="card-body">
				<a href="<?php echo $href; ?>" class="concert_link">
					<p class="card-subtitle text-muted"><?php echo Data::convertDateFromDb($concert["begin"]) . " " . Lang::txt("Konzerte_status." . $concert["status"]); ?></p>
					<h5 class="card-title concert_title"><?php echo $concert["title"]; ?></h5>
				</a>
				<p class="card-text">
					<span class="d-block"><?php echo $concert["location_name"] . ", " . $this->formatAddress($concert, FALSE, "location_"); ?></span>
					<?php
					if($concert["contact_name"] != "") {
					?>
					<span class="d-block"><?php echo $this->formatContact($concert, "NAME_COMM", "contact_"); ?></span>
					<?php 
					}
					?>
					<span class="concert_notes d-block"><?php echo $concert["notes"]; ?></span>
				</p>
			</div>
		</div>
		<?php
	}
	
	function history() {
		// defaults
		$to = date("Y-m-d");
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
		$loc = $this->getData()->adp()->getLocation($c["location"]);
		
		// concert details
		Writing::h1($c["title"]);
		?>
		<p class="ml-comment"><?php echo $c["notes"]; ?></p>
		
		<div class="row mb-3">
		<div class="col-md-3 mt-2"> 
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
		
		<div class="col-md-3 mt-2">
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
				<div class="concertdetail_entry">
					<span class="concertdetail_key"><?php echo Lang::txt("KonzerteData_construct.status"); ?></span>
					<span class="concertdetail_value"><?php echo Lang::txt("Konzerte_status." . $c["status"]); ?></span>
				</div>
			</div>
		</div>
		
		<div class="col-md-3 mt-2">
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
						if($prg != NULL && isset($prg["name"])) echo $prg["name"];
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
		
		<div class="col-md-3 mt-2">
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
		Writing::h4(Lang::txt("KonzerteView_viewInvitations.title"));
		
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
		Writing::h4(Lang::txt("KonzerteView_viewPhases.title"));
		$phases = $this->getData()->getRehearsalphases($_GET["id"]);
		$tab = new Table($phases);
		$tab->removeColumn("id");
		$tab->renameHeader("Name", Lang::txt("KonzerteView_viewPhases.name"));
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
	
	function editTitle() {
		$c = $this->getData()->getConcert($_GET["id"]);
		$name = Lang::txt($this->getaddEntityName());
		if($c != NULL) {
			$name = $this->getEntityName() . ": " . $c["title"];
		}
		return $name;
	}
	
	function editEntityForm($write=true) {
		// data
		$c = $this->getData()->getConcert($_GET["id"]);
		$grps = $this->getData()->getConcertGroups($c["id"]);
		$c["groups"] = Database::flattenSelection($grps, "id");
		$equip = $this->getData()->getConcertEquipment($c["id"]);
		$c["equipment"] = Database::flattenSelection($equip, "id");
		$form = $this->concertForm($this->modePrefix() . "edit_process&id=" . $c["id"], $c);
		
		$form->write();
	}
	
	function additionalViewButtons() {
		$concert_id = $_GET["id"];
		
		$partLink = new Link($this->modePrefix() . "showParticipants&id=$concert_id", Lang::txt("KonzerteView_viewButtons.showParticipants"));
		$partLink->addIcon("person-lines-fill");
		$partLink->write();
		
		// concert contact
		$addContact = new Link($this->modePrefix() . "addConcertContact&id=$concert_id", Lang::txt("KonzerteView_viewButtons.addConcertContact"));
		$addContact->addIcon("plus");
		$addContact->write();
		
		// edit program
		$concert = $this->getData()->getConcert($concert_id);
		$program_id = $concert["program"];
		if($program_id != null && $program_id > 0) {
			$program = new Link($this->modePrefix() . "programs&sub=view&id=$program_id", Lang::txt("KonzerteView_viewButtons.editProgram"));
			$program->addIcon("music-note-list");
			$program->write();
		}
		
		// gig card (Word export)
		$word = new Link("src/export/gigcard.doc?id=" . $_GET["id"], "Word Export");
		$word->addIcon("save");
		$word->write();
		
		// notifications
		$emLink = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation");
		$emLink .= "&mode=concertMail&preselect=" . $_GET["id"];
		$em = new Link($emLink, Lang::txt("KonzerteView_viewButtons.Sendnotification"));
		$em->addIcon("envelope");
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
		$table->renameHeader("Name", Lang::txt("KonzerteView_showParticipants.name_1"));
		$table->renameHeader("nickname", Lang::txt("KonzerteView_showParticipants.nickname_1"));
		$table->renameHeader("replyon", Lang::txt("KonzerteView_showParticipants.replyon"));
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
		
		$editParticipation = new Link($this->modePrefix() . "editParticipation&id=" . $_GET["id"], Lang::txt("KonzerteView_editParticipation.button"));
		$editParticipation->addIcon("pen");
		$editParticipation->write();
	}
	
	public function editParticipation() {
		$gig = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h3(Lang::txt("KonzerteView_editParticipation.heading", array($gig["title"])));
		$participation = $this->getData()->getFullParticipation($_GET["id"]);
		?>
		<form action="<?php echo $this->modePrefix() . "editParticipation_process&id=" . $_GET["id"]; ?>" method="POST">
		<?php
		foreach($participation as $i => $part) {
			if($i == 0) continue;
			?><div class="participationEditLine col-md-4">
				<span class="participationEditLine_user">
					<?php echo $this->formatContact($part, "NAME_INST"); ?>
				</span>
				<?php 
				$dd = new Dropdown("user_" . $part["user_id"]);
				$dd->addOption("?", -1);
				$dd->addOption("-", 0);
				$dd->addOption("✓", 1);
				$dd->addOption("~", 2);
				$dd->setSelected($part['participate']);
				$dd->setStyleClass("participationQuickSelector");
				echo $dd->write();
				?>
			</div><?php
		}
		?>
			<input type="submit" value="<?php echo Lang::txt("KonzerteView_editParticipation.saveButton"); ?>" style="margin-top: 1em;" />
		</form>
		<?php
	}
	
	protected function editParticipationOptions() {
		$back = new Link($this->modePrefix() . "showParticipants&id=" . $_GET["id"], Lang::txt("CrudView_backToViewButton.back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	public function editParticipation_process() {
		$this->getData()->saveParticipation($_GET["id"]);
		$this->showParticipants();
	}
	
	protected function editParticipation_processOptions() {
		$this->showParticipantsOptions();
	}
	
	/**
	 * Creates a nice concert form and returns it (no echo)
	 * @param array $c Concert (for edit), NULL by default (for create)
	 */
	private function concertForm($href, $c = NULL) {
		require_once $GLOBALS["DIR_WIDGETS"] . "sectionform.php";
		
		$form = new SectionForm("", $href);
		if($c == NULL) {
			$this->flash(Lang::txt("KonzerteView_addEntityForm.flash_1"), Lang::txt("KonzerteView_addEntityForm.flash_2"));
		}
		
		// ************* MASTER DATA *************
		$title = ($c != NULL) ? $c["title"] : "";
		$title_field = new Field("title", $title, FieldType::CHAR);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.title"), $title_field, true, 9);
		
		$status = ($c != NULL) ? $c["status"] : NULL;
		$status_dd = $this->buildStatusDropdown($this->getData()->getStatusOptions(), $status);
		$form->addElement(Lang::txt("KonzerteData_construct.status"), $status_dd, true, 3);
		
		$begin = ($c != NULL) ? $c["begin"] : "";
		$begin_field = new Field("begin", $begin, FieldType::DATETIME);
		$begin_field->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.begin"), $begin_field, true, 3);
		
		$end = ($c != NULL) ? $c["end"] : "";
		$end_field = new Field("end", $end, FieldType::DATETIME);
		$end_field->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.copyDateTarget"), $end_field, true, 3);
		
		$approve_until = ($c != NULL) ? $c["approve_until"] : "";
		$approve_field = new Field("approve_until", $approve_until, FieldType::DATETIME);
		$approve_field->setCssClass("copyDateTarget");
		$meeting = ($c != NULL) ? $c["meetingtime"] : "";
		$meetingtime = new Field("meetingtime", $meeting, FieldType::DATETIME);
		$meetingtime->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.meetingtime_from"), $meetingtime, true, 3);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.meetingtime_to"), $approve_field, true, 3);
		
		$notes = ($c != NULL) ? $c["notes"] : "";
		$notesField = new Field("notes", $notes, FieldType::TEXT);
		$notesField->setColsAndRows(2, 40);
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.notes"), $notesField, false, 12);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.title"), array("title", "status", "begin", "end", "approve_until", "meetingtime", "notes"));
		
		// ************* LOCATION AND CONTACT *************
		// choose location
		$ddLocation = new Dropdown("location");
		$locs = $this->getData()->getLocations();
		for($i = 1; $i < count($locs); $i++) {
			$loc = $locs[$i];
			$l = $loc["name"] . ": " . $this->formatAddress($loc);
			$ddLocation->addOption($l, $loc["id"]);
		}
		if($c != NULL) {
			$ddLocation->setSelected($c["location"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.location"), $ddLocation, true);
		
		// choose accommodation
		$ddAccommodation = new Dropdown("accommodation");
		$accommodations = $this->getData()->adp()->getLocations(array(3));
		$ddAccommodation->addOption(Lang::txt("KonzerteView_addEntityForm.no_accommodation"), 0);
		for($a = 1; $a < count($accommodations); $a++) {
			$acc = $accommodations[$a];
			$caption = $acc["name"] . ": " . $this->formatAddress($acc);
			$ddAccommodation->addOption($caption, $acc["id"]);
		}
		if($c != NULL) {
			$ddAccommodation->setSelected($c["accommodation"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.accommodation"), $ddAccommodation);
		
		// choose contact
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.organizer"), new Field("organizer", "", FieldType::CHAR));
		$ddContact = new Dropdown("contact");
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $this->formatContact($contacts[$i], "NAME_COMM");
			$ddContact->addOption($label, $contacts[$i]["id"]);
		}
		if($c != NULL) {
			$ddContact->setSelected($c["contact"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.contact"), $ddContact, true);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.title_location"), array("location", "organizer", "contact", "accommodation"));
		
		// ************* ORGANISATION *************
		// choose members
		$selectedGroups = array();
		if($c != NULL && isset($c["groups"])) {
			$selectedGroups = $c["groups"];
		}
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), $selectedGroups, "group");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.group"), $gs, true);
		
		// choose program
		$ddProgram = new Dropdown("program");
		if($c == NULL) {
			$programs = $this->getData()->getTemplates();
		}
		else {
			$programs = $this->getData()->getPrograms();
		}
		$ddProgram->addOption(Lang::txt("KonzerteView_addEntityForm.programNone"), 0);
		$ddProgram->addOption(Lang::txt("KonzerteView_addEntityForm.programNew"), "new");
		for($i = 1; $i < count($programs); $i++) {
			$ddProgram->addOption($programs[$i]["name"], $programs[$i]["id"]);
		}
		if($c != NULL) {
			$ddProgram->setSelected($c["program"]);
		}
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.program"), $ddProgram);
		
		// choose equipment
		$equipment = $this->getData()->adp()->getEquipment();
		$selectedEquipment = array();
		if($c != NULL && isset($c["equipment"])) {
			$selectedEquipment = $c["equipment"];
		}
		$equipmentSelector = new GroupSelector($equipment, $selectedEquipment, "equipment");
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.equipment"), $equipmentSelector);
		
		// outfit
		$form->addElement("outfit", new Field(Lang::txt("KonzerteView_addEntityForm.outfit"), 0, FieldType::REFERENCE));
		$selectedOutfit = -1;
		if($c != NULL) {
			$selectedOutfit = $c["outfit"];
		}
		$form->setForeign("outfit", "outfit", "id", array("name"), $selectedOutfit);
		$form->addForeignOption("outfit", Lang::txt("KonzerteView_addEntityForm.no_outfit"), 0);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.group_title"), array("group", "program", "equipment", "outfit"));
		$form->renameElement("outfit", Lang::txt("KonzerteView_addEntityForm.outfit"));
		
		// ************* DETAILS *************
		$payment = ($c != NULL) ? $c["payment"] : "";
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.payment"), new Field("payment", $payment, FieldType::CURRENCY));
		
		$conditions = ($c != NULL) ? $c["conditions"] : "";
		$form->addElement(Lang::txt("KonzerteView_addEntityForm.conditions"), new Field("conditions", $conditions, FieldType::TEXT));
		
		// custom fields
		$customFields = $this->getData()->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE);
		$customFieldNames = Database::flattenSelection($customFields, "techname");
		$concertCustomData = NULL;
		if($c != NULL) {
			$concertCustomData = $c;
		}
		$this->appendCustomFieldsToForm($form, KonzerteData::$CUSTOM_DATA_OTYPE, $concertCustomData);
		
		$form->setSection(Lang::txt("KonzerteView_addEntityForm.details_title"), array_merge(array("payment", "conditions"), $customFieldNames));
		return $form;
	}
	
	protected function addEntityForm() {
		$form = $this->concertForm($this->modePrefix() . "add");
		$form->write();
	}
	
	function exportFormatAddress($address) {
		return $this->formatAddress($address);
	}
	
	function exportFormatContact($contact, $profile) {
		return $this->formatContact($contact, $profile);
	}
	
	private function buildStatusDropdown($options, $selectedOpt=NULL) {
		$dd = new Dropdown("status");
		foreach($options as $opt) {
			$dd->addOption(Lang::txt("Konzerte_status.$opt"), $opt);
		}
		if($selectedOpt != NULL) {
			$dd->setSelected($selectedOpt);
		}
		return $dd;
	}
	
	function overviewTitle() {
		return Lang::txt("KonzerteView_overviewTitle.title");
	}
	
	function overview() {
		$futureConcerts = $this->getData()->getFutureConcerts();
		$usedInstruments = $this->getData()->getUsedInstruments();
		for($c = 1; $c < count($futureConcerts); $c++) {
			$concert = $futureConcerts[$c];
			?>
			<div class="rehearsal_overview_box">
				<div class="rehearsal_overview_header">
					<?php echo $concert["title"] . " - " . Data::convertDateFromDb($concert["begin"]); ?>
				</div>
				<?php 
				for($i = 1; $i < count($usedInstruments); $i++) {
					$instrument = $usedInstruments[$i];
					?>
					<div class="instrument_box">
						<div class="instrument_box_header"><?php echo $instrument["name"]; ?></div>
						<?php 
						$parts = $this->getData()->getParticipantOverview($concert['id'], $instrument["id"]);
						foreach($parts as $participant) {
							$p = $participant['participate'];
							if(is_null($p) || (is_string($p) && $p == "") || (is_numeric($p) && $p < 0)) {
								$icon = "question-square";
							} else {
								switch($p) {
									case 0:
										$icon = "x-square-fill icon-red";
										break;
									case 2:
										$icon = "question-square-fill icon-yellow";
										break;
									default:
										$icon = "check-square-fill icon-green";
										break;
								}
							}
							?>
							<div class="player_participation_line">
								<i class="bi-<?php echo $icon; ?>"></i>
								<span><?php echo $participant['contactname']; ?></span>
							</div>
						<?php 
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
}

?>
