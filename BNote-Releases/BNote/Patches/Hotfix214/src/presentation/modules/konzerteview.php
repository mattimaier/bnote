<?php

/**
 * View for concert module.
 * @author matti
 *
 */
class KonzerteView extends CrudRefView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Konzert");
		$this->setJoinedAttributes(array(
			"location" => array("name"),
			"program" => array("name"),
			"contact" => array("surname", "name")
		));
	}
	
	function start() {
		Writing::h1("Konzerte");
		
		// Options
		$lnk = new Link($this->modePrefix() . "wizzard", "Konzert hinzuf&uuml;gen");
		$lnk->write();
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "programs", "Programme verwalten");
		$lnk->write();
		$this->buttonSpace();
		$lnk = new Link($this->modePrefix() . "history", "Konzertchronik");
		$lnk->write();
		$this->verticalSpace();
		
		Writing::p("Um ein Konzert anzuzeigen oder zu bearbeiten, bitte auf das entsprechende Konzert klicken.");
		
		// Next Concert
		$concerts = $this->getData()->getFutureConcerts();
		Writing::h2("N&auml;chstes Konzert");
		if(count($concerts) > 1) {
			$this->writeConcert($concerts[1]);
		}
		
		// More Concerts
		Writing::h2("Geplante Konzerte");
		$this->writeConcerts($concerts);
	}
	
	private function writeConcerts($concerts) {
		for($i = 2; $i < count($concerts); $i++) {
			$this->writeConcert($concerts[$i]);
		}
	}
	
	private function writeConcert($concert) {
		// when? where? who to talk to? notes + program
		$text = "<strong class=\"concert\">" . Data::convertDateFromDb($concert["begin"]);
		$text .= " bis " . substr($concert["end"], strlen($concert["end"])-8, 5);
		$text .= " Uhr im " . $concert["location_name"] . "</strong><br />";
		$text .= "<font size=\"-1\">Adresse: ";
		$text .= $concert["location_street"] . ", " . $concert["location_zip"];
		$text .= " " . $concert["location_city"] . "&nbsp;&nbsp;";
		if($concert["contact_name"] != "") {
			$text .= "Kontaktperson: " . $concert["contact_name"] . " (";

			$ct = 0;
			if($concert["contact_phone"] != "") {
				$text .= "Tel. " . $concert["contact_phone"] . ", ";
				$ct++;
			}
			if($concert["contact_email"] != "") {
				$text .= "E-Mail " . $concert["contact_email"] . ", ";
				$ct++;
			}
			if($concert["contact_web"] != "") {
				$text .= "Web " . $concert["contact_web"] . ", ";
				$ct++;
			}
			
			if($ct == 0) {
				$text = substr($text, 0, strlen($text)-2);
			}
			else {
				$text = substr($text, 0, strlen($text)-2) . ")";
			}
		}
		
		if($concert["program_name"] != "") {
			if($concert["contact_name"] != "") $text .= " - ";
			$text .= "Programm: " . $concert["program_name"];
		}
		$text .= "</font>";
		
		// actually write concert
		echo '<div class="concert"><a class="concert" href="' . $this->modePrefix();
		echo "view&id=" . $concert["id"] . '">';
		Writing::p($text);
		echo "<pre class='concert'>" . $concert["notes"] . "</pre>\n";
		echo "</a></div>";
		$this->verticalSpace();
	}
	
	function history() {
		Writing::h2("Konzertchronik");
		
		$table = new Table($this->getData()->getPastConcerts());
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("location_name", "Auff&uuml;hrungsort");
		$table->renameHeader("location_city", "Stadt");
		$table->renameHeader("contact_name", "Kontaktperson");
		$table->renameHeader("program_name", "Programm");
		$table->setColumnFormat("begin", "DATE");
		$table->setColumnFormat("end", "DATE");
		$table->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	function viewDetailTable() {
		$c = $this->getData()->findByIdNoRef($_GET["id"]);
		$dv = new Dataview();
		$dv->autoAddElements($c);
		$dv->autoRename($this->getData()->getFields());
		
		$dv->removeElement("Ort");
		$loc = $this->getData()->getLocation($c["location"]);
		$lv = (count($loc) > 0) ? $loc["name"] : ""; 
		$dv->addElement("Ort", $lv);		
		
		$dv->removeElement("Programm");
		if($c["program"] != "") {
			$prg = $this->getData()->getProgram($c["program"]);
			$pv = $prg["name"];
		}
		else {
			$pv = "-";
		}
		$dv->addElement("Programm", $pv);
		
		$dv->removeElement("Kontakt");
		if($c["contact"]) {
			$cnt = $this->getData()->getContact($c["contact"]);
			$cv = $cnt["name"];
		}
		else {
			$cv = "-";
		}
		$dv->addElement("Kontakt", $cv);
		
		$dv->write();
	}
	
	function editEntityForm() {
		$form = new Form("Konzert bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$c = $this->getData()->findByIdNoRef($_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), "concert", $_GET["id"]);
		$form->removeElement("id");
		
		$form->setForeign("location", "location", "id", "name", $c["location"]);
		$form->setForeign("program", "program", "id", "name", $c["program"]);
		
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
		
		$form->write();
	}
	
	function additionalViewButtons() {
		$partLink = new Link($this->modePrefix() . "showParticipants&id=" . $_GET["id"], "Teilnehmer anzeigen");
		$partLink->write();
		$this->buttonSpace();
	}
	
	function showParticipants() {
		$this->checkID();
		$parts = $this->getData()->getParticipants($_GET["id"]);
		
		Writing::h2("Konzertteilnehmer");
		
		$table = new Table($parts);
		$table->renameHeader("participate", "Nimmt teil");
		$table->renameHeader("reason", "Grund");
		$table->write();
		$this->verticalSpace();
		
		$this->backToViewButton($_GET["id"]);
	}
	
	/***** CONCERT CREATION PROCESS ***********/
	/**
	 * Definition of the steps.
	 * @var array
	 */
	private $addSteps = array(
		"Stammdaten",
		"Auff&uuml;hrungsort",
		"Kontaktperson",
		"Programm",
		"Fertig"
	);
	
	function showAddTitle() {
		Writing::h2("Konzert hinzuf&uuml;gen");
	}
	
	/**
	 * Displays a progress bar on top of the module.
	 * @param int $progress Current step number (1 to 5)
	 */
	function showProgressBar($progress) {
		echo '<div class="progressbar">' . "\n";
		
		foreach($this->addSteps as $i => $caption) {
			if($i < $progress) $passed = " passed";
			else $passed = "";
			
			echo '<div class="progressbar_step' . $passed . '">' . $caption . '</div>' . "\n";
		}
		echo '</div>' . "\n";
		
		$this->verticalSpace();
		$this->verticalSpace();
	}
	
	function abortButton() {
		$this->verticalSpace();
		$lnk = new Link($this->modePrefix() . "start", "Abbrechen");
		$lnk->write();
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
	
	/**
	 * Ask for basic data.
	 * @param String $action Method to call after modePrefix.
	 */
	function step1($action) {
		$form = new Form("Stammdaten", $this->modePrefix() . $action);
		
		$form->addElement("Beginn", new Field("begin", "", FieldType::DATETIME));
		$form->addElement("Ende", new Field("end", "", FieldType::DATETIME));
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));		
		
		$this->addCollectedData($form);
		$form->write();
	}
	
	/**
	 * Ask for location.
	 * @param String $action Method to call after modePrefix.
	 */
	function step2($action) {
		Writing::p("Bitte w&auml;hle einen Ort aus oder erstelle einen neuen Ort.");
		
		// form 1: choose location
		$form1 = new Form("Ort ausw&auml;hlen", $this->modePrefix() . $action);
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
		
		Writing::p("Wird einer neuer Ort erstellt, so wird er automatisch als Auff&uuml;hrungsort gespeichert.");
	}
	
	/**
	 * Ask for contact.
	 * @param String $action Method to call after modePrefix.
	 */
	function step3($action) {
		Writing::p("Bitte w&auml;hle eine Kontaktperson oder erstelle einen neuen Kontakt.");
		
		// form 1: choose contact
		$form1 = new Form("Kontakt ausw&auml;hlen", $this->modePrefix() . $action);
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
		$form2 = new Form("Kontaktperson hinzuf&uuml;gen", $this->modePrefix() . $action);
		$form2->addElement("Vorname", new Field("contact_name", "", FieldType::CHAR));
		$form2->addElement("Nachname", new Field("contact_surname", "", FieldType::CHAR));
		$form2->addElement("Telefon", new Field("contact_phone", "", FieldType::CHAR));
		$form2->addElement("E-Mail", new Field("contact_email", "", FieldType::CHAR));
		$form2->addElement("Web", new Field("contact_web", "", FieldType::CHAR));	
		$this->addCollectedData($form2);
		$form2->write();
		
		Writing::p("Wird ein neuer Kontakt erstellt, wird er diesem Konzert zugeordnet. Er kann jedoch unter Kontakte/Sonstige Kontakte bearbeitet werden.");
	}
	
	/**
	 * Ask for program.
	 * @param String $action Method to call after modePrefix.
	 */
	function step4($action) {
		$msg = "Bitte w&auml;hle eine Programmvorlage aus ";
		$msg .= "oder fuege ein Programm sp&auml;ter hinzu.<br />";
		$msg .= "Das Programm kann unter Konzerte/Programme sp&auml;ter ";
		$msg .= "bearbeitet werden.";
		Writing::p($msg);
		
		// form 1: choose program template
		$form1 = new Form("Programmvorlage ausw&auml;hlen", $this->modePrefix() . $action);
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
	 * Show saving message.
	 * @param String $action Is not used.
	 */
	function step5($action) {
		$m = "Das Konzert wurde erfolgreich erstellt.";
		$msg = new Message("Konzert erstellt", $m);
		$this->backToStart();
	}
}

?>
