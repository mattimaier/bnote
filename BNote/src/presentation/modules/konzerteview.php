<?php
require_once $GLOBALS["DIR_PRESENTATION"] . "crudreflocationview.php";

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
		$lnk = new Link($this->modePrefix() . "wizzard", "Auftritt hinzufügen");
		$lnk->addIcon("plus");
		$lnk->write();
		
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
	
	/***** CONCERT CREATION PROCESS ***********/
	
	function showAddTitle() {
		Writing::h2("Auftritt hinzufügen");
	}
	
	/**
	 * Displays a progress bar on top of the module.
	 * @param int $progress Current step number (1 to 5)
	 */
	function showProgressBar($progress) {
		echo '<div class="progressbar">' . "\n";
		
		foreach($this->getController()->getSteps() as $i => $caption) {
			if($i < $progress) $passed = " passed";
			else $passed = "";
			
			echo '<div class="progressbar_step' . $passed . '">' . $caption . '</div>' . "\n";
			if($passed != "") $passed .= '_arrow';
			echo '<div class="arrow_right' . $passed . '"></div>' . "\n";
		}
		echo '</div>' . "\n";
		
		$this->verticalSpace();
		$this->verticalSpace();
	}
	
	function abortButton() {
		// don't show abort button below
	}
	
	/**
	 * Adds all values from $_POST to the form as hidden fields.
	 * @param Form $form Form to add the data to.
	 */
	private function addCollectedData($form) {
		foreach($_POST as $k => $v) {
			$form->addHidden($k, $v);
		}
	}
	
	protected function wizzardOptions() {
		if(!isset($_GET["progress"]) || $_GET["progress"] < 6) {
			$abort = new Link("?mod=" . $this->getModId() . "&mode=start", "Abbrechen");
			$abort->addIcon("cancel");
			$abort->write();
		}
	}
	
	/**
	 * Ask for basic data.
	 * @param String $action Method to call after modePrefix.
	 */
	function step1($action) {
		$form = new Form("Stammdaten", $this->modePrefix() . $action);
		
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
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'g');
		
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));		
		
		$this->addCollectedData($form);
		$form->write();
	}
	
	/**
	 * Ask for location.
	 * @param String $action Method to call after modePrefix.
	 */
	function step2($action) {
		Writing::p("Bitte wähle einen Ort aus oder erstelle einen neuen Ort.");
		
		// form 1: choose location
		$form1 = new Form("Ort auswählen", $this->modePrefix() . $action);
		$dd1 = new Dropdown("location");
		$locs = $this->getData()->getLocations();		
		for($i = 1; $i < count($locs); $i++) {
			$loc = $locs[$i];
			$l = $loc["name"] . " (" . $loc["city"] . ")";
			$dd1->addOption($l, $loc["id"]);
		}
		$form1->addElement("Location", $dd1);
		$this->addCollectedData($form1);
		$form1->write();
		
		// form 2: add new location
		$form2 = new Form("Ort erstellen", $this->modePrefix() . $action);
		$form2->addElement("Name", new Field("location_name", "", FieldType::CHAR));
		$form2->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form2->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form2->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		$this->addCollectedData($form2);
		$form2->write();
		
		Writing::p("Wird ein neuer Ort erstellt, so wird er automatisch als Aufführungsort gespeichert.");
	}
	
	/**
	 * Ask for contact.
	 * @param String $action Method to call after modePrefix.
	 */
	function step3($action) {
		Writing::p("Bitte wähle eine Kontaktperson oder erstelle einen neuen Kontakt.");
		
		// form 1: choose contact
		$form1 = new Form("Kontakt auswählen", $this->modePrefix() . $action);
		$dd = new Dropdown("contact");
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $contacts[$i]["name"] . " " . $contacts[$i]["surname"];
			$instr = isset($contacts[$i]["instrumentname"]) ? $contacts[$i]["instrumentname"] : '';
			if($instr != "") $label .= " (" . $contacts[$i]["instrumentname"] . ")";
			$dd->addOption($label, $contacts[$i]["id"]);
		}
		$form1->addElement("Kontakt", $dd);
		$this->addCollectedData($form1);
		$form1->write();
		
		// form 2: add contact
		$form2 = new Form("Kontaktperson hinzufügen", $this->modePrefix() . $action);
		$form2->addElement("Vorname", new Field("contact_name", "", FieldType::CHAR));
		$form2->addElement("Nachname", new Field("contact_surname", "", FieldType::CHAR));
		$form2->addElement("Telefon", new Field("contact_phone", "", FieldType::CHAR));
		$form2->addElement("E-Mail", new Field("contact_email", "", FieldType::CHAR));
		$form2->addElement("Web", new Field("contact_web", "", FieldType::CHAR));	
		$this->addCollectedData($form2);
		$form2->write();
		
		Writing::p("Wird ein neuer Kontakt erstellt, wird er diesem Auftritt zugeordnet. Er kann jedoch unter Kontakte/Sonstige Kontakte bearbeitet werden.");
	}
	
	/**
	 * Ask for program.
	 * @param String $action Method to call after modePrefix.
	 */
	function step4($action) {
		$msg = "Bitte wähle eine Programmvorlage aus ";
		$msg .= "oder fuege ein Programm später hinzu.<br />";
		$msg .= "Das Programm kann unter Auftritte/Programme später ";
		$msg .= "bearbeitet werden.";
		Writing::p($msg);
		
		// form 1: choose program template
		$form1 = new Form("Programmvorlage auswählen", $this->modePrefix() . $action);
		$dd = new Dropdown("program");
		$templates = $this->getData()->getTemplates();
		for($i = 1; $i < count($templates); $i++) {
			$dd->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form1->addElement("Vorlage", $dd);
		$this->addCollectedData($form1);
		$form1->write();
		
		// form 2: don't add program
		$form2 = new Form("Direkt weiter", $this->modePrefix() . $action);
		$form2->addElement("Weiter", new TextWriteable("ohne Programm"));
		$form2->addHidden("program", 0);
		$this->addCollectedData($form2);
		$form2->write();
	}
	
	/**
	 * Ask for members participating in this concert.
	 * @param String $action Method to call after modePrefix.
	 */
	function step5($action) {
		// select the groups (or all) the concert will be for
		Writing::p("Bitte wähle die Mitglieder für diesen Auftritt aus.");
		
		$form = new Form("Mitglieder auswählen", $this->modePrefix() . $action);
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), array(), "group");
		$form->addElement("Gruppen", $gs);
		$this->addCollectedData($form);
		$form->changeSubmitButton("weiter");
		$form->write(); 
	}
	
	/**
	 * Show saving message.
	 * @param String $action Is not used.
	 */
	function step6($action) {
		$m = "Der Auftritt wurde erfolgreich erstellt.";
		$msg = new Message("Auftritt erstellt", $m);
		$this->backToStart();
	}
	
}

?>
