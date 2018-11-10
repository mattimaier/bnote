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
			"location" => array("name"),
			"serie" => array("name"),
			"conductor" => array("name", "surname")
		));
	}
	
	function start() {
		Writing::p("Bitte auf eine Probe klicken um diese zu bearbeiten.");
		
		Writing::h2("Nächste Probe");
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
		
		$series = new Link($this->modePrefix() . "addSerie", "Probenstrecke hinzufügen");
		$series->addIcon("overtime");
		$series->write();
		
		$seriesEdit = new Link($this->modePrefix() . "editSerie", "Probenstrecke bearbeiten");
		$seriesEdit->addIcon("edit");
		$seriesEdit->write();
		
		$overview = new Link($this->modePrefix() . "overview", "Teilnehmerübersicht");
		$overview->addIcon("mitspieler");
		$overview->write();
		
		$history = new Link($this->modePrefix() . "history", "Frühere Proben anzeigen");
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
		
		// begin
		$beginField = new Field("begin", date("d.m.Y") . " " . $this->getData()->getDefaultTime(), Field::FIELDTYPE_DATETIME_SELECTOR);
		$beginField->setCssClass("copyDateOrigin");
		$form->addElement("Beginn", $beginField);
		
		// end
		if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
			$end = Data::addMinutesToDate(date("d.m.Y") . " " . $this->getData()->getDefaultTime() . ":00", $this->getData()->getDefaultDuration());
			$form->addElement("Ende", new Field("end", $end, Field::FIELDTYPE_DATETIME_SELECTOR));
		}
		else {
			$form->addElement("Dauer in min", new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		}
		
		// location
		$form->addElement("location", new Field("location", "", FieldType::REFERENCE));
		$form->setForeign("location", "location", "id", "name", -1);
		$form->renameElement("location", "Ort");
		
		// approve until
		$approve_until_field = new Field("approve_until", "", FieldType::DATETIME);
		$approve_until_field->setCssClass("copyDateTarget");
		$form->addElement("Zusagen bis", $approve_until_field);
		
		// conductor
		$form->addElement("Dirigent", $this->buildConductorDropdown());
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'r');
		
		// notes
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));
		
		// groups
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement("Probe für", $gs);
		
		if($tour != null) {
			$form->addHidden("tour", $tour);
		}
		
		$form->write();
	}
	
	private function buildConductorDropdown($selected = null) {
		// data
		$conductors = $this->getData()->adp()->getConductors();
		
		// dropdown
		$conductorsDd = new Dropdown("conductor");
		for($i = 1; $i < count($conductors); $i++) {
			$conductor = $conductors[$i];
			$conductorsDd->addOption($conductor["name"] . " " . $conductor["surname"], $conductor["id"]);
		}
		$conductorsDd->addOption("-", 0);
		
		// selection
		if($selected == null) {
			$defaultConductor = $this->getData()->getSysdata()->getDynamicConfigParameter("default_conductor");
			$conductorsDd->setSelected($defaultConductor);
		}
		else {
			$conductorsDd->setSelected($selected);
		}
		return $conductorsDd;
	}
	
	function add() {		
		// save rehearsal
		$rid = $this->getData()->create($_POST);
		
		// write success
		new Message($this->getEntityName() . " gespeichert",
				"Die Probe wurde erfolgreich gespeichert.");
		
		// Show link to create a rehearsal information
		$lnk = new Link("?mod=7&mode=rehearsalMail&preselect=$rid", "Probenbenachrichtigung an Mitglieder senden");
		$lnk->write();
	}
	
	function addSerie() {		
		$form = new Form("Probenserie hinzufügen", $this->modePrefix() . "processSerie");
		
		$form->addElement("Probenstrecke", new Field("name", "", FieldType::CHAR));
		$form->setFieldRequired("Serienname");
		
		$first_session = new Field("first_session", "", FieldType::DATE);
		$first_session->setCssClass("copyDateOrigin");
		$form->addElement("Erste Probe am", $first_session);
		$form->setFieldRequired("Erste Probe am");
		
		$last_session = new Field("last_session", "", FieldType::DATE);
		$last_session->setCssClass("copyDateTarget");
		$form->addElement("Letzte Probe am", $last_session);
		$form->setFieldRequired("Letzte Probe am");
		
		$cycle = new Dropdown("cycle");
		$cycle->addOption("wöchentlich", "1");
		$cycle->addOption("zweiwöchentlich", "2");
		$form->addElement("Zyklus", $cycle);
		
		$form->addElement("Uhrzeit", new Field("default_time", $this->getData()->getDefaultTime(), 96));
		$form->addElement("Dauer in min", new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		
		$form->addElement("Ort", new Field("location", "", FieldType::REFERENCE));
		$form->setForeign("Ort", "location", "id", "name", -1);
		
		$form->addElement("Dirigent", $this->buildConductorDropdown());
		
		$this->appendCustomFieldsToForm($form, 'r');
		
		$form->addElement("Notizen", new Field("notes", "", FieldType::TEXT));
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement("Proben für", $gs);
		$form->setFieldRequired("Proben für");
		
		$form->write();
	}
	
	function processSerie() {
		if($this->getData()->saveSerie()) {
			new Message("Probenstrecke gespeichert", "Alle Proben wurde erfolgreich erstellt.");
			$this->backToStart();
		}
		else {
			new BNoteError("Die Probenserie konnte nicht verarbeitet werden.");
		}
	}
	
	function editSerie() {
		$form = new Form("Probenstrecke bearbeiten", $this->modePrefix() . "processEditSerie");
		
		// select series
		$series = $this->getData()->getCurrentSeries();
		$serieSelector = new Dropdown("id");
		for($i = 1; $i < count($series); $i++) {
			$s = $series[$i];
			$serieSelector->addOption($s["name"], $s["id"]);
		}
		$form->addElement("Serie", $serieSelector);
		
		// Change rehearsal beginning
		$form->addElement("Beginn aktualisieren", new Field("update_begin", false, FieldType::BOOLEAN));
		$form->addElement("Beginn", new Field("begin", $this->getData()->getDefaultTime(), 96));
		
		// Change location
		$form->addElement("Location aktualisieren", new Field("update_location", false, FieldType::BOOLEAN));
		$locations = $this->getData()->adp()->getLocations(array(1,2,5));  // band rooms, gig venues, others
		$locationSelector = new Dropdown("location");
		for($i = 1; $i < count($locations); $i++) {
			$l = $locations[$i];
			$locationSelector->addOption($l["name"], $l["id"]);
		}
		$form->addElement("Location", $locationSelector);
		
		// Delete all rehearsals in series
		$form->addElement("Strecke und Proben <u>löschen</u>", new Field("delete", false, FieldType::BOOLEAN));
		
		$form->write();
	}
	
	function processEditSerie() {
		$this->getData()->updateSerie();
		new Message("Probenstrecke aktualisiert", "Die Probenstrecke wurde erfolgreich aktualisiert.");
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

		$when = Data::convertDateFromDb($row["begin"]) . " - " . $finish . " Uhr";
		$conductor = "";
		if(isset($row["conductor"]) && $row["conductor"] != 0) {
			$conductor .= "Dirigent: ";
			$conductor .= $this->getData()->adp()->getConductorname($row["conductor"]);
		}

		// put output together
		$out = "<p class=\"rehearsal_title\">";
		$out .= "<span class=\"rehearsal_weekday\">$weekday</span>";
		$out .= "<span class=\"rehearsal_when\">$when</span>";
		$out .= "<span class=\"rehearsal_conductor\">$conductor</span>";
		$out .= "</p>";
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
	
	protected function editEntityForm($write=true) {
		$r = $this->getData()->getRehearsal($_GET["id"]);
		
		$form = new Form($this->getEntityName() . " bearbeiten",
							$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
									$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->removeElement("serie");
		$form->addHidden("serie", $r["serie"]);
		$form->setForeign("location", "location", "id", "name", $r["location"]);
		
		// conductor
		$form->addElement("conductor", $this->buildConductorDropdown($r["conductor"]));
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'r', $r);
		
		$form->write();
	}
	
	function invitations() {
		$contacts = $this->getData()->getRehearsalContacts($_GET["id"]);
			
		// add a link to the data to remove the contact from the list
		$contacts[0]["delete"] = "Löschen";
		for($i = 1; $i < count($contacts); $i++) {
			$delLink = $this->modePrefix() . "delContact&id=" . $_GET["id"] . "&cid=" . $contacts[$i]["id"] . "&tab=invitations";
			$btn = new Link($delLink, "");
			$btn->addIcon("remove");
			$contacts[$i]["delete"] = $btn->toString();
		}
			
		$table = new Table($contacts);
		$table->removeColumn("id");
		$table->removeColumn("instrumentid");
		$table->renameHeader("mobile", "Handynummer");
		$table->write();
	}
	
	protected function viewDetailTable() {
		// data
		$entity = $this->getData()->getRehearsal($_GET["id"]);
		$serie = $this->getData()->getRehearsalSerie($_GET["id"]);
		
		?>
		<div class="probendetail_block">
			<div class="probendetail_set">
				<div class="probendetail_heading">Zeit</div>
				<div class="probendetail_value"><?php 
				echo $this->formatFromToDateShort($entity["begin"], $entity["end"]);
				?></div>
				<div class="probendetail_value">Zusagen bis: <?php 
				echo Data::convertDateFromDb($entity["approve_until"]);
				?></div>
			</div>
			
			<div class="probendetail_set">
				<div class="probendetail_heading">Ort</div>
				<div class="probendetail_value"><?php
				echo $entity["name"]; 
				?></div>
				<div class="probendetail_value"><?php
				echo $this->buildAddress($entity); 
				?></div>
			</div>
		</div>
		
		<div class="probendetail_block">
			<?php 
			if($entity["conductor"]) {
				?>
				<div class="probendetail_set">
						<div class="probendetail_heading">Dirigent</div>
						<div class="probendetail_value"><?php
						echo $this->getData()->adp()->getConductorname($entity["conductor"]); 
						?></div>
					</div>
				<?php 
			}
			
			if($serie != null && count($serie) > 0) {
				?>
				<div class="probendetail_set">
					<div class="probendetail_heading">Probenstrecke</div>
					<div class="probendetail_value"><?php
					echo $serie["name"]; 
					?></div>
				</div>
				<?php 
			}
			?>
			<div class="probendetail_set">
				<div class="probendetail_heading">Eingeladene Gruppen</div>
				<div class="probendetail_value"><?php
				$groupNames = Database::flattenSelection($entity["groups"], "name");
				echo join(", ", $groupNames);
				?></div>
			</div>
			
			<div class="probendetail_set">
				<div class="probendetail_heading">Notizen</div>
				<div class="probendetail_value"><?php
				echo $entity["notes"]; 
				?></div>
			</div>
		</div>
		
		<div class="probendetail_block">
		<?php 
		$customFields = $this->getData()->getCustomFields('r');
		for($i = 1; $i < count($customFields); $i++) {
			$field = $customFields[$i];
			$techName = $field["techname"];
			$caption = $field["txtdefsingle"];
			?>
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo $caption; ?></div>
				<div class="probendetail_value"><?php
				if($field["fieldtype"] == "BOOLEAN") {
					echo $entity[$techName] == 1 ? "ja" : "nein";
				}
				else {
					echo $entity[$techName];
				}
				?></div>
			</div>
			<?php
		}
		?>
		</div>
		
		<?php
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
	}
	
	public function addContactOptions() {
		$this->backToViewButton($_GET["id"] . "&tab=invitations");
	}
	
	public function process_addContact() {
		$this->checkID();
		$this->getData()->addRehearsalContact($_GET["id"]);
		new Message("Kontakt hinzugefügt", "Der Kontakt wurde zu dieser Probe hinzugefügt.");
	}
	
	public function process_addContactOptions() {
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
		$table->removeColumn("instrumentid");
		$table->renameHeader("nickname", Lang::txt("nickname"));
		$table->renameHeader("participate", "Nimmt teil");
		$table->renameHeader("reason", "Grund");
		$table->write();
		
		// remaining calls
		Writing::h3("Ausstehende Zu-/Absagen");
		$openTab = new Table($this->getData()->getOpenParticipation($_GET["id"]));
		$openTab->showFilter();
		$openTab->removeColumn("id");
		$openTab->removeColumn("instrumentid");
		$openTab->renameHeader("nickname", Lang::txt("nickname"));
		$openTab->renameHeader("mobile", "Handy");
		$openTab->write();
		
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
					"practiseDelete&id=" . $_GET["id"] . "&song=" . $s["id"] . "&tab=practise", "löschen");
				$del->write();
				echo '</form>';
			}
			else {
				echo $s["notes"] . "<br />";
			}
			echo "</li>\n";
		}
		if(count($songs) == 1) {
			echo "<li>Keine Stücke ausgewählt.</li>\n";
		}
		echo "</ul>\n";
		
		// add a song
		$form = new Form("Stück hinzufügen", $this->modePrefix() . "view&tab=practise&id=" . $_GET["id"]);
		$form->addElement("song", new Field("song", "", FieldType::REFERENCE));
		$form->setForeign("song", "song", "id", "title", -1);
		$form->renameElement("song", "Stück");
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
			$back = new Link($this->modePrefix() . "history&year=" . $_GET["year"], Lang::txt("back"));
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
		else if(isset($_GET["tab"]) && ($_GET["tab"] == "invitations" || $_GET["tab"] == "participants")) {
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
	
	function overview() {
		$futureRehearsals = $this->getData()->adp()->getFutureRehearsals();
		$usedInstruments = $this->getData()->getUsedInstruments();
		
		for($reh = 1; $reh < count($futureRehearsals); $reh++) {
			$rehearsal = $futureRehearsals[$reh];
			?>
			<div class="rehearsal_overview_box">
				<div class="rehearsal_overview_header">Probe am <?php echo Data::convertDateFromDb($rehearsal['begin']); ?> Uhr</div>
				<?php 
				for($i = 1; $i < count($usedInstruments); $i++) {
					$instrument = $usedInstruments[$i];
					?>
					<div class="instrument_box">
						<div class="instrument_box_header"><?php echo $instrument['name'];?></div>
						<?php 
						$parts = $this->getData()->getParticipantOverview($rehearsal['id'], $instrument['id']);
						foreach($parts as $j => $participant) {
							switch($participant['participate']) {
								case -1: 
									$alt = "Keine Angabe";
									$icon = "unspecified";
									break;
								case 0: 
									$alt = "Nimmt nicht teil";
									$icon = "cancel";
									break;
								case 2: 
									$alt = "Nimmt vielleicht teil";
									$icon = "yield";
									break;
								default:
									$alt = "Nimmt teil";
									$icon = "checked";
									break;
							}
							?>
							<div class="player_participation_line">
								<img src="style/icons/<?php echo $icon; ?>.png" alt="<?php echo $alt; ?>" style="height: 14px" />
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
	
	private function isReadOnlyView() {
		return (isset($_GET["readonly"]) && $_GET["readonly"] == "true");
	}
}

?>
