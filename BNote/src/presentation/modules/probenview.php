<?php

/**
 * View for rehearsal module.
 * @author matti
 *
 */
class ProbenView extends CrudRefView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Probe");
		$this->setJoinedAttributes(array(
			"location" => array("name")
		));
	}
	
	function start() {
		Writing::h1("Proben");
		Writing::p("Bitte auf eine Probe klicken um diese zu bearbeiten.");
		
		Writing::h2("N&auml;chste Probe");
		$nextRehearsal = $this->getData()->getNextRehearsal();
		if($nextRehearsal != null && $nextRehearsal != "" && count($nextRehearsal) > 0) {
			$this->writeRehearsal($nextRehearsal);
		}
		else {
			Writing::p("Keine Probe angesagt.");
		}

		Writing::h2("Weitere Proben");
		$this->writeRehearsalList($this->getData()->adp()->getFutureRehearsals());
	}
	
	function startOptions() {
		parent::startOptions();
		
		$this->buttonSpace();
		$series = new Link($this->modePrefix() . "addSerie", "Probenstrecke hinzufügen");
		$series->addIcon("overtime");
		$series->write();
		
		$this->buttonSpace();
		$history = new Link($this->modePrefix() . "history", "Verganene Proben anzeigen");
		$history->addIcon("timer");
		$history->write();
	}
	
	function addEntity($form_target=null, $tour=null) {
		// check whether a location exists
		if(!$this->getData()->locationsPresent()) {
			$msg = new Message("Keine Location vorhanden", "Bevor du eine Probe anlegen kannst, erstelle bitte eine Location.");
			$this->backToStart();
			return;
		}
		
		// New entity form
		if($form_target == null) {
			$form_target = $this->modePrefix() . "add";
		}
		$form = new Form("Neue Probe", $form_target);
		$form->addElement("Beginn", new Field("begin", date("d.m.Y") . " " . $this->getData()->getDefaultTime(), Field::FIELDTYPE_DATETIME_SELECTOR));
		if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
			$end = Data::addMinutesToDate(date("d.m.Y") . " " . $this->getData()->getDefaultTime() . ":00", $this->getData()->getDefaultDuration());
			$form->addElement("Ende", new Field("end", $end, Field::FIELDTYPE_DATETIME_SELECTOR));
		}
		else {
			$form->addElement("Dauer in min", new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		}
		$form->addElement("location", new Field("location", "", FieldType::REFERENCE));
		$form->setForeign("location", "location", "id", "name", -1);
		$form->renameElement("location", "Ort");
		$form->addElement("Zusagen bis", new Field("approve_until", "", FieldType::DATETIME));
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), array(), "group");
		$form->addElement("Probe für", $gs);
		
		if($tour != null) {
			$form->addHidden("tour", $tour);
		}
		
		$form->write();
	}
	
	function add() {		
		// save rehearsal
		$rid = $this->getData()->create($_POST);
		
		// write success
		new Message($this->getEntityName() . " gespeichert",
				"Die Probe wurde erfolgreich gespeichert.");
		
		// Show link to create a rehearsal information
		$lnk = new Link("?mod=7&mode=rehearsalMail&preselect=$rid", "Probenbenachrichtigung an Mitspieler senden");
		$lnk->write();
	}
	
	function addSerie() {		
		$form = new Form("Probenserie hinzufügen", $this->modePrefix() . "processSerie");
		
		$form->addElement("Erste Probe am", new Field("first_session", "", FieldType::DATE));
		$form->addElement("Letzte Probe am", new Field("last_session", "", FieldType::DATE));
		
		$cycle = new Dropdown("cycle");
		$cycle->addOption("wöchentlich", "1");
		$cycle->addOption("zweiwöchentlich", "2");
		$form->addElement("Zyklus", $cycle);
		
		$form->addElement("Uhrzeit", new Field("default_time", $this->getData()->getDefaultTime(), 96));
		$form->addElement("Dauer in min", new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		
		$form->addElement("Ort", new Field("location", "", FieldType::REFERENCE));
		$form->setForeign("Ort", "location", "id", "name", -1);
		
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(), array(), "group");
		$form->addElement("Proben für", $gs);
		
		$form->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	function processSerie() {
		if($this->getData()->saveSerie()) {
			new Message("Probenstrecke gespeichert", "Alle Proben wurde erfolgreich erstellt.");
			$this->backToStart();
		}
		else {
			new Error("Die Probenserie konnte nicht verarbeitet werden.");
		}
	}
	
	/**
	 * Writes out a list with rehearsals.
	 * @param Array $data Data selection array.
	 */
	private function writeRehearsalList($data) {
		// omit the header and the first row, cause its the next rehearsal
		$count = 0;
		for($i = 2; $i < count($data); $i++) {
			$this->writeRehearsal($data[$i]);
			$count++;
		}
		if($count == 0) {
			Writing::p("Keine weiteren Proben angesagt.");
		}
	}
	
	/**
	 * Writes out a single rehearsal as text.
	 * @param Array $row Row with field ids of rehearsal and location as keys (id column of rehearsal).
	 */
	private function writeRehearsal($row) {
		// prepare data
		$date_begin = strtotime($row["begin"]);
		$date_end = strtotime($row["end"]);
		$weekday = Data::convertEnglishWeekday(date("D", $date_begin));
		$finish = date('H:i', $date_end);

		$when = Data::convertDateFromDb($row["begin"]) . " bis " . $finish . " Uhr";

		// put output together
		$out = "<p class=\"rehearsal_title\">$weekday, $when</p>";
		$out .= "<p class=\"rehearsal_details\">" . $row["name"];
		
		$out .= " (";
		if($row["street"] == "" && $row["zip"] == "") {
			$out .= $row["city"];
		}
		else if ($row["street"] == "") {
			$out .= $row["zip"] . " " . $row["city"];
		}
		else if($row["city"] == "" && $row["zip"] == "") {
			$out .= $row["street"];
		}
		else if($row["city"] == "") {
			$out .= $row["zip"] . " - " . $row["street"];
		}
		else {		
			$out .= $row["street"] . ", " . $row["zip"] . " " . $row["city"];
		}
		$out .= ")</p>";
		
		$out .= "<pre class=\"rehearsal\">" . $row["notes"] . "</pre>\n";
		
		echo "<a class=\"rehearsal\" href=\"" . $this->modePrefix() . "view&id=" . $row["id"] . "\">";
		echo "<div class=\"rehearsal\">$out</div>";
		echo "</a>\n";
	}
	
	protected function editEntityForm() {
		$r = $this->getData()->findByIdNoRef($_GET["id"]);
		
		$form = new Form($this->getEntityName() . " bearbeiten",
							$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
									$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->setForeign("location", "location", "id", "name", $r["location"]);
		
		$form->write();
	}
	
	function invitations() {
		$contacts = $this->getData()->getRehearsalContacts($_GET["id"]);
			
		// add a link to the data to remove the contact from the list
		$contacts[0]["delete"] = "Löschen";
		for($i = 1; $i < count($contacts); $i++) {
			$delLink = $this->modePrefix() . "delContact&id=" . $_GET["id"] . "&cid=" . $contacts[$i]["id"];
			$btn = new Link($delLink, "");
			$btn->addIcon("remove");
			$contacts[$i]["delete"] = $btn->toString();
		}
			
		$table = new Table($contacts);
		$table->removeColumn("id");
		$table->renameHeader("mobile", "Handynummer");
		$table->write();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->renameElement("name", "Ort");
		$details->renameElement("street", "Stra&szlig;e");
		$details->renameElement("zip", "Postleitzahl");
		$details->renameElement("city", "Stadt");
		$details->write();
		
		if(!$this->isReadOnlyView()) {
			// contacts who should participate in rehearsal			
			$phases = $this->getData()->getRehearsalsPhases($_GET["id"]);
			
			if(count($phases) > 1) {
				Writing::h2("Probenphasen");
				$table = new Table($phases);
				$table->removeColumn("id");
				$table->renameHeader("name", "Probenphase");
				$table->renameHeader("begin", "Von");
				$table->renameHeader("end", "bis");
				$table->renameHeader("notes", "Anmerkungen");
				$table->write();
			}
		}
	}
	
	public function addContact() {
		$this->checkID();
		
		$form = new Form("Einladung zu Probe hinzufügen", $this->modePrefix() . "process_addContact&id=" . $_GET["id"]);
		$contacts = $this->getData()->getContacts();
		$gs = new GroupSelector($contacts, array(), "contact");
		$gs->setNameColumn("fullname");
		$form->addElement("Einladung für", $gs);
		$form->write();
		
		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}
	
	public function process_addContact() {
		$this->checkID();
		$this->getData()->addRehearsalContact($_GET["id"]);
		new Message("Kontakt hinzugefügt", "Der Kontakt wurde zu dieser Probe hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}
	
	public function delContact() {
		$this->checkID();
		$this->getData()->deleteRehearsalContact($_GET["id"], $_GET["cid"]);
		$this->view();
	}
	
	function participants() {
		$this->checkID();
		
		Writing::h3("Instrumente");
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->getAttendingInstruments($_GET["id"]));
		$dv->write();
		
		Writing::h3("Teilnahme");
		$table = new Table($this->getData()->getParticipants($_GET["id"]));
		$table->removeColumn("id");
		$table->renameHeader("participate", "Nimmt teil");
		$table->renameHeader("reason", "Grund");
		$table->write();
		echo "<br/>\n";
		
		// remaining calls
		Writing::h3("Ausstehende Zu-/Absagen");
		$openTab = new Table($this->getData()->getOpenParticipation($_GET["id"]));
		$openTab->removeColumn("id");
		$openTab->renameHeader("mobile", "Handy");
		$openTab->write();
		echo "<br/>\n";
		
		Writing::h3("Zusammenfassung");
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->getParticipantStats($_GET["id"]));
		$dv->write();
	}
	
	function participantsOptions() {
		if(!$this->isReadOnlyView()) {
			// back button
			$this->backToViewButton($_GET["id"]);
			$this->verticalSpace();
		}
	}
	
	function practise() {
		$this->checkID();
		Writing::h3("Stückauswahl");
		
		// check if a new song was added
		if(isset($_POST["song"])) {
			$this->getData()->saveSongForRehearsal($_POST["song"], $_GET["id"], $_POST["notes"]);
		}
		
		// show songs
		$songs = $this->getData()->getSongsForRehearsal($_GET["id"]);
		echo "<ul>\n";
		for($i = 1; $i < count($songs); $i++) {
			$s = $songs[$i];
			$href = $this->modePrefix() . "practise&id=" . $_GET["id"];
			$href .= "&song=" . $s["id"];
			$caption = $s["title"];
			echo "<li class=\"practise\"><a href=\"$href\">$caption</a><br />";
			// show options if required
			if(isset($_GET["song"]) && $_GET["song"] == $s["id"]) {
				echo '<form method="POST" action="' . $this->modePrefix();
				echo 'practiseUpdate&id=' . $_GET["id"] . '&song=' . $s["id"] . '">';
				echo ' <input type="text" name="notes" size="30" value="' . $s["notes"] . '" />';
				echo ' <input type="submit" value="speichern" />&nbsp;&nbsp;';
				$del = new Link($this->modePrefix() .
					"practiseDelete&id=" . $_GET["id"] . "&song=" . $s["id"], "l&ouml;schen");
				$del->write();
				echo '</form>';
			}
			else {
				echo $s["notes"] . "<br />";
			}
			echo "</li>\n";
		}
		if(count($songs) == 1) {
			echo "<li>Keine St&uuml;cke ausgew&auml;hlt.</li>\n";
		}
		echo "</ul>\n";
		
		// add a song
		$form = new Form("St&uuml;ck hinzuf&uuml;gen", $this->modePrefix() . "view&tab=practise&id=" . $_GET["id"]);
		$form->addElement("song", new Field("song", "", FieldType::REFERENCE));
		$form->setForeign("song", "song", "id", "title", -1);
		$form->renameElement("song", "St&uuml;ck");
		$form->addElement("Anmerkungen", new Field("notes", "", FieldType::CHAR));
		$form->write();
	}
	
	function practiseOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function practiseUpdate() {
		$this->checkID();
		$this->getData()->updateSongForRehearsal($_GET["song"], $_GET["id"], $_POST["notes"]);
		unset($_GET["song"]);
		$this->practise();
	}
	
	function practiseDelete() {
		$this->checkID();
		$this->getData()->removeSongForRehearsal($_GET["song"], $_GET["id"]);
		unset($_GET["song"]);
		$this->practise();
	}
	
	function printPartlist() {
		require_once($GLOBALS["DIR_PRINT"] . "partlist.php");
		$contacts = $this->getData()->getRehearsalContacts($_GET["id"]);
		$filename = $GLOBALS["DATA_PATHS"]["members"] . "partlist_rehearsal_" . $_GET["id"] . ".pdf";
		new PartlistPDF($filename, $this->getData(), $contacts, $_GET["id"]);
		
		// show report
		echo "<embed src=\"src/data/filehandler.php?mode=module&file=$filename\" width=\"90%\" height=\"700px\" />\n";
		$this->verticalSpace();
		
		// back button
		$this->backToStart();
		$this->verticalSpace();
	}
	
	function history() {
		// presets
		if(isset($_POST["year"])) {
			$year = $_POST["year"];
		}
		else if(isset($_GET["year"])) {
			$year = $_GET["year"];
		}
		else {
			$year = date("Y");
		}
		
		// filtering
		$form = new Form("Filter", $this->modePrefix() . "history");
		$dd = new Dropdown("year");
		$years = $this->getData()->getRehearsalYears();
		for($i = 1; $i < count($years); $i++) {
			$y = $years[$i]["year"];
			$dd->addOption($y, $y);
		}
		$dd->setSelected($year);
		
		$form->addElement("Jahr", $dd);
		$form->changeSubmitButton("Filtern");
		$form->write();
		
		// result
		Writing::p("Klicke auf einen Eintrag um diesen anzuzeigen.");
		
		$data = $this->getData()->getPastRehearsals($year);
		$tab = new Table($data);
		$tab->setEdit("id");
		$tab->changeMode("view&readonly=true&year=" . $year);
		$tab->renameAndAlign($this->getData()->getFields());
		$tab->setColumnFormat("begin", "DATE");
		$tab->setColumnFormat("end", "DATE");
		$tab->renameHeader("street", "Straße");
		$tab->renameHeader("zip", "PLZ");
		$tab->renameHeader("city", "Stadt");
		$tab->write();
	}
	
	function view() {
		if($this->isReadOnlyView()) {
			// history view
			$this->checkID();
			Writing::h2("Probendetails");
			$this->viewDetailTable();
			$this->participants();
			
			Writing::h3("Stücke zum üben");
			$songs = $this->getData()->getSongsForRehearsal($_GET["id"]);
			$tab = new Table($songs);
			$tab->removeColumn("id");
			$tab->renameHeader("title", "Stück");
			$tab->renameHeader("notes", "Aktuelle Notizen");
			$tab->write();
		}
		else {
			// title
			$pdate = $this->getData()->getRehearsalBegin($_GET["id"]);
			Writing::h2("Probe am $pdate Uhr");
			
			// tabs
			$tabs = array(
				"details" => "Details",
				"invitations" => "Einladungen",
				"participants" => "Teilnehmer",
				"practise" => "Stücke zum Üben"
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
			else if($_GET["tab"] == "invitations") {
				$this->invitations();
			}
			else if($_GET["tab"] == "participants") {
				$this->participants();
			}
			else if($_GET["tab"] == "practise") {
				$this->practise();
			}
			
			echo "</div>\n";
		}
	}
	
	function viewOptions() {
		if($this->isReadOnlyView()) {
			$back = new Link($this->modePrefix() . "history&year=" . $_GET["year"], "Zurück");
			$back->addIcon("arrow_left");
			$back->write();
		}
		else if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			parent::viewOptions();
			
			// show a button to send a reminder to all about this rehearsal
			$remHref = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation") . "&mode=rehearsalMail&preselect=" . $_GET["id"];
			
			$reminder = new Link($remHref, "Benachrichtigung senden");
			$reminder->addIcon("email");
			$reminder->write();
		}
		else if(isset($_GET["tab"]) && $_GET["tab"] == "invitations") {
			$this->backToStart();
			$this->buttonSpace();
			
			$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], "Einladung hinzufügen");
			$addContact->addIcon("plus");
			$addContact->write();
			$this->buttonSpace();
				
			$printPartlist = new Link($this->modePrefix() . "printPartlist&id=" . $_GET["id"], "Teilnehmerliste drucken");
			$printPartlist->addIcon("printer");
			$printPartlist->write();
		}
		else {
			$this->backToStart();
		}
	}
	
	private function isReadOnlyView() {
		return (isset($_GET["readonly"]) && $_GET["readonly"] == "true");
	}
}

?>
