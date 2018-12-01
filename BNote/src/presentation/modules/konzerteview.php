<?php
require_once $GLOBALS["DIR_PRESENTATION"] . "crudreflocationview.php";
require_once $GLOBALS["DIR_WIDGETS"] . "sectionform.php";

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
		$this->setJoinedAttributes(array(
			"location" => array("name"),
			"program" => array("name"),
			"contact" => array("name", "surname")
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
		Writing::p("Um einen Auftritt anzuzeigen oder zu bearbeiten, bitte auf den entsprechenden Auftritt klicken.");
		
		// Next Concert
		$concerts = $this->getData()->getFutureConcerts();
		Writing::h2("Nächster Auftritt");
		if(count($concerts) > 1) {
			$this->writeConcert($concerts[1]);
		}
		
		// More Concerts
		Writing::h2("Geplante Auftritte");
		$this->writeConcerts($concerts);
	}
	
	protected function startOptions() {
		parent::startOptions();
		
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "programs", "Programme verwalten");
		$lnk->addIcon("setlist");
		$lnk->write();
		
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "history", "Chronik");
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
		$text .= " Uhr / <span class=\"concert_title_name\">" . $concert["title"] . "</span></p>";
		
		// location
		$text .= "<span class=\"concert_location\">";
		$text .= $concert["location_name"] . ", " . $this->formatAddress($concert, FALSE, "location_");
		$text .= "</span>";
		
		// contact
		if($concert["contact_name"] != "") {
			$text .= "<span class=\"concert_contact\">" . $concert["contact_name"] . " (";

			$ct = 0;
			if($concert["contact_phone"] != "") {
				$text .= "Tel. " . $concert["contact_phone"] . ", ";
				$ct++;
			}
			if($concert["contact_email"] != "") {
				$text .= "E-Mail " . $concert["contact_email"] . ", ";
				$ct++;
			}
			
			if($ct == 0) {
				$text = substr($text, 0, strlen($text)-2);
			}
			else {
				$text = substr($text, 0, strlen($text)-2) . ")";
			}
			$text .= "</span>";
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
		Writing::h2("Chronik");
		
		$table = new Table($this->getData()->getPastConcerts());
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("location_name", "Aufführungsort");
		$table->renameHeader("location_city", "Stadt");
		$table->renameHeader("contact_name", "Kontaktperson");
		$table->renameHeader("program_name", "Programm");
		$table->setColumnFormat("begin", "DATE");
		$table->setColumnFormat("end", "DATE");
		$table->write();
	}
	
	function view() {
		// get data
		$c = $this->getData()->findByIdNoRef($_GET["id"]);
		$custom = $this->getData()->getCustomData($_GET["id"]);
		$loc = $this->getData()->getLocation($c["location"]);
		
		// concert details
		Writing::h1($c["title"]);
		?>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading">Veranstaltung</div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Ort</span>
					<span class="concertdetail_value"><?php 
					echo $loc["name"] . "<br/>";
					echo $this->formatAddress($this->getData()->getAddress($loc["address"]));
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Kontakt</span>
					<span class="concertdetail_value"><?php 
					if($c["contact"]) {
						$cnt = $this->getData()->getContact($c["contact"]);
						$cv = $cnt["name"];
						$details = array();
						if($cnt["phone"] != "") {
							array_push($details, $cnt["phone"]);
						}
						if($cnt["email"] != "") {
							array_push($details, $cnt["email"]);
						}
						if($cnt["web"] != "") {
							array_push($details, $cnt["web"]);
						}
						if(count($details) > 0) {
							$cv .= "<br/>" . join(", ", $details);
						}
					}
					else {
						$cv = "-";
					}
					echo $cv;
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Programm</span>
					<span class="concertdetail_value"><?php 
					if($c["program"]) {
						$prg = $this->getData()->getProgram($c["program"]);
						$pv = $prg["name"];
					}
					else {
						$pv = "-";
					}
					echo $pv;
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Outfit</span>
					<span class="concertdetail_value"><?php 
					if($c["outfit"]) {
						$outfit = $this->getData()->getOutfit($c["outfit"]);
						echo $outfit["name"];
					}
					else {
						echo "-";
					}
					?></span>
				</div>
			</div>
		</div>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading">Zeiten</div>
			<div class="concertdetail_data">
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Datum/Zeit</span>
					<span class="concertdetail_value"><?php 
					echo Data::convertDateFromDb($c["begin"]) . " - ";
					echo Data::convertDateFromDb($c["end"]);
					?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Treffpunkt</span>
					<span class="concertdetail_value"><?php echo Data::convertDateFromDb($c["meetingtime"]); ?></span>
				</div>
				<div class="concertdetail_entry">
					<span class="concertdetail_key">Zusage bis</span>
					<span class="concertdetail_value"><?php echo Data::convertDateFromDb($c["approve_until"]); ?></span>
				</div>
			</div>
		</div>
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading">Details</div>
			<div class="concertdetail_data">
			<?php 
			$customFields = $this->getData()->getCustomFields('g');
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
		
		<div class="concertdetail_box">
			<div class="concertdetail_heading">Notizen</div>
			<div class="concertdetail_notes"><?php echo $c["notes"]; ?></div>
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
		Writing::h2("Eingeladene Kontakte");
		
		$contacts = $this->getData()->getConcertContacts($_GET["id"]);
		$contacts = Table::addDeleteColumn($contacts, $this->modePrefix() . "delConcertContact&id=" . $_GET["id"] . "&contactid=");
		$tab = new Table($contacts);
		$tab->removeColumn("id");
		$tab->renameHeader("fullname", "Name");
		$tab->renameHeader("nickname", Lang::txt("nickname"));
		$tab->renameHeader("phone", "Telefon");
		$tab->renameHeader("mobile", "Handy");
		$tab->write();
	}
	
	private function viewPhases() {
		// show the rehearsal phases this concert is related to
		Writing::h2("Probenphasen");
		$phases = $this->getData()->getRehearsalphases($_GET["id"]);
		$tab = new Table($phases);
		$tab->removeColumn("id");
		$tab->renameHeader("Begin", "von");
		$tab->renameHeader("end", "bis");
		$tab->renameHeader("notes", "Notizen");
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
		$form = new Form("Auftritt bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"] . "&manualValid=true");
		$form->autoAddElements($this->getData()->getFields(), "concert", $_GET["id"]);
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
		
		// outfit
		$outfit = $c['outfit'];
		if($outfit == "" || !isset($c["outfit"])) {
			$outfit = 0;
			$form->addElement("outfit", new Field("outfit", $c["outfit"], FieldType::REFERENCE));
		}
		$form->setForeign("outfit", "outfit", "id", array("name"), $outfit);
		$form->addForeignOption("outfit", "Kein Outfit", 0);
		
		// custom data
		$this->appendCustomFieldsToForm($form, 'g', $c);
		
		$form->write();
	}
	
	function additionalViewButtons() {
		$partLink = new Link($this->modePrefix() . "showParticipants&id=" . $_GET["id"], "Teilnehmer anzeigen");
		$partLink->addIcon("user");
		$partLink->write();
		$this->buttonSpace();
		
		// concert contact
		$addContact = new Link($this->modePrefix() . "addConcertContact&id=" . $_GET["id"], "Kontakt hinzufügen");
		$addContact->addIcon("plus");
		$addContact->write();
		$this->buttonSpace();
		
		// notifications
		$emLink = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation");
		$emLink .= "&mode=concertMail&preselect=" . $_GET["id"];
		$em = new Link($emLink, "Benachrichtigung senden");
		$em->addIcon("email");
		$em->write();
		$this->buttonSpace();
	}
	
	function showParticipants() {
		$this->checkID();
		$parts = $this->getData()->getParticipants($_GET["id"]);
		
		Writing::h2("Teilnehmer");
		$table = new Table($parts);
		$table->renameHeader("participate", "Nimmt teil");
		$table->renameHeader("reason", "Grund");
		$table->renameHeader("category", "Gruppe");
		$table->renameHeader("nickname", Lang::txt("nickname"));
		$table->removeColumn("id");
		$table->write();
		$this->verticalSpace();
		
		Writing::h3("Ausstehende Zu-/Absagen");
		$openTab = new Table($this->getData()->getOpenParticipants($_GET["id"]));
		$openTab->removeColumn("id");
		$openTab->renameHeader("nickname", Lang::txt("nickname"));
		$openTab->renameHeader("fullname", Lang::txt("fullname"));
		$openTab->renameHeader("phone", Lang::txt("phone"));
		$openTab->renameHeader("mobile", Lang::txt("mobile"));
		$openTab->write();
	}
	
	protected function showParticipantsOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function add() {
		Data::viewArray($_POST);
	}
	
	protected function addEntityForm() {
		$form = new SectionForm("Neuer Auftritt", $this->modePrefix() . "add");
		Writing::p("Bevor ein neuer Auftritt angelegt werden kann, bitte alle Kontaktdaten (Kontakte) und Orte (Locations) anlegen.");
		
		// ************* MASTER DATA *************
		$title_field = new Field("title", "", FieldType::CHAR);
		$form->addElement("Titel", $title_field);
		$begin_field = new Field("begin", "", FieldType::DATETIME);
		$begin_field->setCssClass("copyDateOrigin");
		$form->addElement("Beginn", $begin_field);
		$end_field = new Field("end", "", FieldType::DATETIME);
		$end_field->setCssClass("copyDateTarget");
		$form->addElement("Ende", $end_field);
		$approve_field = new Field("approve_until", "", FieldType::DATETIME);
		$approve_field->setCssClass("copyDateTarget");
		$meetingtime = new Field("meetingtime", "", FieldType::DATETIME);
		$meetingtime->setCssClass("copyDateTarget");
		$form->addElement("Treffpunkt (Zeit)", $meetingtime);
		$form->addElement("Zusagen bis", $approve_field);
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));
		
		$form->setSection("Auftritt", array("title", "begin", "end", "approve_until", "meetingtime", "notes"));
		
		// ************* LOCATION AND CONTACT *************
		// choose location
		$dd1 = new Dropdown("location");
		$locs = $this->getData()->getLocations();
		for($i = 1; $i < count($locs); $i++) {
			$loc = $locs[$i];
			$l = $loc["name"] . ": " . $this->formatAddress($loc);
			$dd1->addOption($l, $loc["id"]);
		}
		$form->addElement("Location", $dd1);
		
		// choose contact
		$form->addElement("Veranstalter", new Field("organizer", "", FieldType::CHAR));
		$dd2 = new Dropdown("contact");
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $contacts[$i]["name"] . " " . $contacts[$i]["surname"];
			
			//TODO add optional company / address and just for internal members the instrument name or group
			$instr = isset($contacts[$i]["instrumentname"]) ? $contacts[$i]["instrumentname"] : '';
			if($instr != "") $label .= " (" . $contacts[$i]["instrumentname"] . ")";
			
			$dd2->addOption($label, $contacts[$i]["id"]);
		}
		$form->addElement("Kontakt", $dd2);
		
		// choose accommodation
		$dd3 = new Dropdown("accommodation");
		$accommodations = $this->getData()->adp()->getLocations(array(3));
		for($a = 1; $a < count($accommodations); $a++) {
			$acc = $accommodations[$a];
			$caption = $acc["name"] . ": " . $this->formatAddress($acc);
			$dd3->addOption($caption, $acc["id"]);
		}
		$form->addElement("Unterkunft", $dd3);
		
		$form->setSection("Ort und Veranstalter", array("location", "organizer", "contact", "accommodation"));
		
		// ************* ORGANISATION *************
		// choose members
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), array(), "group");
		$form->addElement("Besetzung", $gs);
		
		// chosse program
		$dd4 = new Dropdown("program");
		$templates = $this->getData()->getTemplates();
		$dd4->addOption("Keine Auswahl", -1);
		for($i = 1; $i < count($templates); $i++) {
			$dd4->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form->addElement("Programm aus Vorlage", $dd4);
		
		// choose equipment
		$equipment = $this->getData()->adp()->getEquipment();
		$equipmentSelector = new GroupSelector($equipment, array(), "equipment");
		$form->addElement("Equipment", $equipmentSelector);
		
		$form->setSection("Organisation", array("group", "program", "equipment"));
		
		// ************* DETAILS *************
		$form->addElement("Gage", new Field("payment", "0", FieldType::DECIMAL));
		$form->addElement("Konditionen", new Field("conditions", "", FieldType::TEXT));
		
		// custom fields
		$customFields = $this->getData()->getCustomFields('g');
		$customFieldNames = Database::flattenSelection($customFields, "techname");
		$this->appendCustomFieldsToForm($form, 'g');
		
		$form->setSection("Details", array_merge(array("payment", "conditions"), $customFieldNames));
		
		$form->write();
	}
	
}

?>
