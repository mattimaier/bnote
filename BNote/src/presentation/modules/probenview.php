<?php

/**
 * View for rehearsal module.
 * @author matti
 *
 */
class ProbenView extends CrudRefLocationView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("ProbenView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("ProbenView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"location" => array("name"),
			"serie" => array("name"),
			"conductor" => array("name", "surname")
		));
	}
	
	function start() {
		Writing::p(Lang::txt("ProbenView_start.text"));
		
		Writing::h2(Lang::txt("ProbenView_start.title"));
		$nextRehearsal = $this->getData()->getNextRehearsal();
		if($nextRehearsal != null && $nextRehearsal != "" && count($nextRehearsal) > 0) {
			$this->writeRehearsal($nextRehearsal);
		}
		else {
			Writing::p(Lang::txt("ProbenView_start.norehearsal"));
		}

		Writing::h2(Lang::txt("ProbenView_start.title_2"));
		$this->writeRehearsalList($this->getData()->adp()->getFutureRehearsals());
	}
	
	function startOptions() {
		parent::startOptions();
		
		$series = new Link($this->modePrefix() . "addSerie", Lang::txt("ProbenView_startOptions.overtime"));
		$series->addIcon("overtime");
		$series->write();
		
		$seriesEdit = new Link($this->modePrefix() . "editSerie", Lang::txt("ProbenView_startOptions.edit"));
		$seriesEdit->addIcon("edit");
		$seriesEdit->write();
		
		$overview = new Link($this->modePrefix() . "overview", Lang::txt("ProbenView_startOptions.mitspieler"));
		$overview->addIcon("mitspieler");
		$overview->write();
		
		$history = new Link($this->modePrefix() . "history", Lang::txt("ProbenView_startOptions.timer"));
		$history->addIcon("timer");
		$history->write();
	}
	
	function addEntity($form_target=null, $tour=null) {
		// check whether a location exists
		if(!$this->getData()->locationsPresent()) {
			$msg = new Message(Lang::txt("ProbenView_addEntity.message_1"), Lang::txt("ProbenView_addEntity.message_2"));
			$this->backToStart();
			return;
		}
		
		// New entity form
		if($form_target == null) {
			$form_target = $this->modePrefix() . "add";
		}
		$form = new Form(Lang::txt($this->getaddEntityName()), $form_target);
		
		// begin
		$beginField = new Field("begin", date("d.m.Y") . " " . $this->getData()->getDefaultTime(), Field::FIELDTYPE_DATETIME_SELECTOR);
		$beginField->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("ProbenView_addEntity.begin"), $beginField);
		
		// end
		if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
			$end = Data::addMinutesToDate(date("d.m.Y") . " " . $this->getData()->getDefaultTime() . ":00", $this->getData()->getDefaultDuration());
			$form->addElement(Lang::txt("ProbenView_addEntity.end"), new Field("end", $end, Field::FIELDTYPE_DATETIME_SELECTOR));
		}
		else {
			$form->addElement(Lang::txt("ProbenView_addEntity.duration"), new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		}
		
		// location
		$form->addElement("location", new Field("location", "", FieldType::REFERENCE));
		$form->setForeign("location", "location", "id", "name", -1);
		$form->renameElement("location", Lang::txt("ProbenView_addEntity.location"));
		
		// approve until
		$approve_until_field = new Field("approve_until", "", FieldType::DATETIME);
		$approve_until_field->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("ProbenView_addEntity.approve_until"), $approve_until_field);
		
		// conductor
		$form->addElement(Lang::txt("ProbenView_addEntity.conductor"), $this->buildConductorDropdown());
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'r');
		
		// notes
		$form->addElement(Lang::txt("ProbenView_addEntity.notes"), new Field("notes", "", FieldType::TEXT));
		
		// groups
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("ProbenView_addEntity.groups"), $gs);
		
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
		new Message($this->getEntityName() . Lang::txt("ProbenView_add.message_1"),
				Lang::txt("ProbenView_add.message_2"));
		
		// Show link to create a rehearsal information
		$lnk = new Link("?mod=7&mode=rehearsalMail&preselect=$rid", Lang::txt("ProbenView_add.rehearsalMail"));
		$lnk->write();
	}
	
	function addSerie() {		
		$form = new Form(Lang::txt("ProbenView_addSerie.Form"), $this->modePrefix() . "processSerie");
		
		$form->addElement(Lang::txt("ProbenView_addSerie.name"), new Field("name", "", FieldType::CHAR));
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.name"));
		
		$first_session = new Field("first_session", "", FieldType::DATE);
		$first_session->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("ProbenView_addSerie.first_session"), $first_session);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.first_session"));
		
		$last_session = new Field("last_session", "", FieldType::DATE);
		$last_session->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("ProbenView_addSerie.last_session"), $last_session);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.last_session"));
		
		$cycle = new Dropdown("cycle");
		$cycle->addOption(Lang::txt("ProbenView_addSerie.cycle_1"), "1");
		$cycle->addOption(Lang::txt("ProbenView_addSerie.cycle_2"), "2");
		$form->addElement(Lang::txt("ProbenView_addSerie.cycle"), $cycle);
		
		$form->addElement(Lang::txt("ProbenView_addSerie.default_time"), new Field("default_time", $this->getData()->getDefaultTime(), 96));
		$form->addElement(Lang::txt("ProbenView_addSerie.duration"), new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER));
		
		$form->addElement(Lang::txt("ProbenView_addSerie.location"), new Field("location", "", FieldType::REFERENCE));
		$form->setForeign(Lang::txt("ProbenView_addSerie.location"), "location", "id", "name", -1);
		
		$form->addElement(Lang::txt("ProbenView_addSerie.Conductor"), $this->buildConductorDropdown());
		
		$this->appendCustomFieldsToForm($form, 'r');
		
		$form->addElement(Lang::txt("ProbenView_addSerie.notes"), new Field("notes", "", FieldType::TEXT));
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("ProbenView_addSerie.group"), $gs);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.group"));
		
		$form->write();
	}
	
	function processSerie() {
		if($this->getData()->saveSerie()) {
			new Message(Lang::txt("ProbenView_processSerie.message_1"), Lang::txt("ProbenView_processSerie.message_2"));
			$this->backToStart();
		}
		else {
			new BNoteError(Lang::txt("ProbenView_processSerie.error"));
		}
	}
	
	function editSerie() {
		$form = new Form(Lang::txt("ProbenView_editSerie.Form"), $this->modePrefix() . "processEditSerie");
		
		// select series
		$series = $this->getData()->getCurrentSeries();
		$serieSelector = new Dropdown("id");
		for($i = 1; $i < count($series); $i++) {
			$s = $series[$i];
			$serieSelector->addOption($s["name"], $s["id"]);
		}
		$form->addElement(Lang::txt("ProbenView_editSerie.serieSelector"), $serieSelector);
		
		// Change rehearsal beginning
		$form->addElement(Lang::txt("ProbenView_editSerie.update_begin"), new Field("update_begin", false, FieldType::BOOLEAN));
		$form->addElement(Lang::txt("ProbenView_editSerie.begin"), new Field("begin", $this->getData()->getDefaultTime(), 96));
		
		// Change location
		$form->addElement(Lang::txt("ProbenView_editSerie.update_location"), new Field("update_location", false, FieldType::BOOLEAN));
		$locations = $this->getData()->adp()->getLocations(array(1,2,5));  // band rooms, gig venues, others
		$locationSelector = new Dropdown("location");
		for($i = 1; $i < count($locations); $i++) {
			$l = $locations[$i];
			$locationSelector->addOption($l["name"], $l["id"]);
		}
		$form->addElement(Lang::txt("ProbenView_editSerie.locationSelector"), $locationSelector);
		
		// Delete all rehearsals in series
		$form->addElement(Lang::txt("ProbenView_editSerie.delete"), new Field("delete", false, FieldType::BOOLEAN));
		
		$form->write();
	}
	
	function processEditSerie() {
		$this->getData()->updateSerie();
		new Message(Lang::txt("ProbenView_processEditSerie.message_1"), Lang::txt("ProbenView_processEditSerie.message_2"));
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
			Writing::p(Lang::txt("ProbenView_writeRehearsalList.message"));
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

		$when = Data::convertDateFromDb($row["begin"]) . " - " . $finish . Lang::txt("ProbenView_writeRehearsal.begin");
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
		$out .= " (" . $this->formatAddress($row) . ")</p>";
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
		$table->renameHeader("mobile", Lang::txt("ProbenView_invitations.mobile"));
		$table->write();
	}
	
	protected function viewDetailTable() {
		// data
		$entity = $this->getData()->getRehearsal($_GET["id"]);
		$serie = $this->getData()->getRehearsalSerie($_GET["id"]);
		
		?>
		<div class="probendetail_block">
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.period"); ?></div>
				<div class="probendetail_value"><?php 
				echo $this->formatFromToDateShort($entity["begin"], $entity["end"]);
				?></div>
				<div class="probendetail_value"><?php echo Lang::txt("ProbenView_viewDetailTable.approve_until"); ?><?php 
				echo Data::convertDateFromDb($entity["approve_until"]);
				?></div>
			</div>
			
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.location"); ?></div>
				<div class="probendetail_value"><?php
				echo $entity["name"]; 
				?></div>
				<div class="probendetail_value"><?php
				echo $this->formatAddress($entity); 
				?></div>
			</div>
		</div>
		
		<div class="probendetail_block">
			<?php 
			if($entity["conductor"]) {
				?>
				<div class="probendetail_set">
						<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.conductor"); ?></div>
						<div class="probendetail_value"><?php
						echo $this->getData()->adp()->getConductorname($entity["conductor"]); 
						?></div>
					</div>
				<?php 
			}
			
			if($serie != null && count($serie) > 0) {
				?>
				<div class="probendetail_set">
					<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.serie_name"); ?></div>
					<div class="probendetail_value"><?php
					echo $serie["name"]; 
					?></div>
				</div>
				<?php 
			}
			?>
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.groups"); ?></div>
				<div class="probendetail_value"><?php
				$groupNames = Database::flattenSelection($entity["groups"], "name");
				echo join(", ", $groupNames);
				?></div>
			</div>
			
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo Lang::txt("ProbenView_viewDetailTable.notes"); ?></div>
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
					echo $entity[$techName] == 1 ? Lang::txt("ProbenView_viewDetailTable.yes") : Lang::txt("ProbenView_viewDetailTable.no");
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
				Writing::h2(Lang::txt("ProbenView_viewDetailTable.phases_title"));
				$table = new Table($phases);
				$table->removeColumn("id");
				$table->renameHeader("name", Lang::txt("ProbenView_viewDetailTable.phases_name"));
				$table->renameHeader("begin", Lang::txt("ProbenView_viewDetailTable.phases_begin"));
				$table->renameHeader("end", Lang::txt("ProbenView_viewDetailTable.phases_end"));
				$table->renameHeader("notes", Lang::txt("ProbenView_viewDetailTable.phases_notes"));
				$table->write();
			}
		}
	}
	
	public function addContact() {
		$this->checkID();
		
		$form = new Form(Lang::txt("ProbenView_addContact.Form"), $this->modePrefix() . "process_addContact&id=" . $_GET["id"]);
		$contacts = $this->getData()->getContacts();
		$gs = new GroupSelector($contacts, array(), "contact");
		$gs->setNameColumn("fullname");
		$form->addElement(Lang::txt("ProbenView_ProbenView_addContact.fullname"), $gs);
		$form->write();
	}
	
	public function addContactOptions() {
		$this->backToViewButton($_GET["id"] . "&tab=invitations");
	}
	
	public function process_addContact() {
		$this->checkID();
		$this->getData()->addRehearsalContact($_GET["id"]);
		new Message(Lang::txt("ProbenView_process_addContact.message_1"), Lang::txt("ProbenView_process_addContact.message_2"));
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
		
		Writing::h3(Lang::txt("ProbenView_participants.title_1"));
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->getAttendingInstruments($_GET["id"]));
		$dv->write();
		
		Writing::h3(Lang::txt("ProbenView_participants.title_2"));
		$table = new Table($this->getData()->getParticipants($_GET["id"]));
		$table->removeColumn("id");
		$table->removeColumn("instrumentid");
		$table->renameHeader("nickname", Lang::txt("ProbenView_participants.nickname_1"));
		$table->renameHeader("participate", Lang::txt("ProbenView_participants.participate"));
		$table->renameHeader("reason", Lang::txt("ProbenView_participants.reason"));
		$table->write();
		
		// remaining calls
		Writing::h3(Lang::txt("ProbenView_participants.title_3"));
		$openTab = new Table($this->getData()->getOpenParticipation($_GET["id"]));
		$openTab->showFilter();
		$openTab->removeColumn("id");
		$openTab->removeColumn("instrumentid");
		$openTab->renameHeader("nickname", Lang::txt("ProbenView_participants.nickname_2"));
		$openTab->renameHeader("mobile", Lang::txt("ProbenView_participants.mobile"));
		$openTab->write();
		
		Writing::h3(Lang::txt("ProbenView_participants.title_4"));
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
		Writing::h3(Lang::txt("ProbenView_practise.title"));
		
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
				echo ' <input type="submit" value=" . Lang::txt("ProbenView_practise.save") . " />&nbsp;&nbsp;';
				$del = new Link($this->modePrefix() .
					"practiseDelete&id=" . $_GET["id"] . "&song=" . $s["id"] . "&tab=practise", Lang::txt("ProbenView_practise.delete"));
				$del->write();
				echo '</form>';
			}
			else {
				echo $s["notes"] . "<br />";
			}
			echo "</li>\n";
		}
		if(count($songs) == 1) {
			echo "<li>" . Lang::txt("ProbenView_practise.no_song") . "</li>\n";
		}
		echo "</ul>\n";
		
		// add a song
		$form = new Form(Lang::txt("ProbenView_practise.Form"), $this->modePrefix() . "view&tab=practise&id=" . $_GET["id"]);
		$form->addElement("song", new Field("song", "", FieldType::REFERENCE));
		$form->setForeign("song", "song", "id", "title", -1, true);
		$form->renameElement("song", Lang::txt("ProbenView_practise.song"));
		$form->addElement(Lang::txt("ProbenView_practise.notes"), new Field("notes", "", FieldType::CHAR));
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
		// title
		$pdate = $this->getData()->getRehearsalBegin($_GET["id"]);
		Writing::h2(Lang::txt("ProbenView_view.message_1") . "$pdate" . Lang::txt("ProbenView_view.message_2"));
		Writing::h3(Lang::txt("ProbenView_printPartlist.subtitle"));
		
		// participation list
		$parts = $this->getData()->getParticipantOverview($_GET["id"], NULL, FALSE);
		// add signature column
		$parts[0][] = "signature"; 
		for($i = 1; $i < count($parts); $i++) {
			$parts[$i]["signature"] = "<div style=\"width: 300px;\">&nbsp;</div>";
		}
		
		$table = new Table($parts);
		$table->showFilter(false);
		$table->renameHeader("instrument", Lang::txt("ProbenView_printPartlist.printcol_instrument"));
		$table->removeColumn("contact_id");
		$table->renameHeader("contactname", Lang::txt("ProbenView_printPartlist.printcol_contact"));
		$table->removeColumn("user_id");
		$table->renameHeader("participate", Lang::txt("ProbenView_printPartlist.printcol_participate"));
		$table->renameHeader("signature", Lang::txt("ProbenView_printPartlist.printcol_signature"));
		$table->write();
	}
	
	function printPartlistOptions() {
		$this->backToViewButton($_GET["id"] . "&tab=participants");
		
		$print = new Link("javascript:print()", Lang::txt("ProbenView_viewOptions.printPartlist"));
		$print->addIcon("printer");
		$print->write();
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
		$filters = new Filterbox($this->modePrefix() . "history");
		$rehearsalYears = $this->getData()->getRehearsalYears();
		$filters->addFilter("year", Lang::txt("ProbenView_history.year"), FieldType::SET, Filterbox::dbSelectionPreparation($rehearsalYears, "year", "year"));
		$filters->setShowAllOption("year", FALSE);
		$filters->write();
		
		// result
		Writing::p(Lang::txt("ProbenView_history.message"));
		
		$data = $this->getData()->getPastRehearsals($year);
		$tab = new Table($data);
		$tab->setEdit("id");
		$tab->changeMode("view&readonly=true&year=" . $year);
		$tab->renameAndAlign($this->getData()->getFields());
		$tab->setColumnFormat("begin", "DATE");
		$tab->setColumnFormat("end", "DATE");
		$tab->renameHeader("street", Lang::txt("ProbenView_history.street"));
		$tab->renameHeader("zip", Lang::txt("ProbenView_history.zip"));
		$tab->renameHeader("city", Lang::txt("ProbenView_history.city"));
		$tab->showFilter(FALSE);
		$tab->write();
	}
	
	function view() {
		if($this->isReadOnlyView()) {
			// history view
			$this->checkID();
			Writing::h2(Lang::txt("ProbenView_view.details"));
			$this->viewDetailTable();
			$this->participants();
			
			Writing::h3(Lang::txt("ProbenView_view.program"));
			$songs = $this->getData()->getSongsForRehearsal($_GET["id"]);
			$tab = new Table($songs);
			$tab->removeColumn("id");
			$tab->renameHeader("title", Lang::txt("ProbenView_view.title"));
			$tab->renameHeader("notes", Lang::txt("ProbenView_view.notes"));
			$tab->write();
		}
		else {
			// title
			$pdate = $this->getData()->getRehearsalBegin($_GET["id"]);
			Writing::h2(Lang::txt("ProbenView_view.message_1") . "$pdate" . Lang::txt("ProbenView_view.message_2"));
			
			// tabs
			$tabs = array(
				"details" => Lang::txt("ProbenView_view.details"),
				"invitations" => Lang::txt("ProbenView_view.invitations"),
				"participants" => Lang::txt("ProbenView_view.participants"),
				"practise" => Lang::txt("ProbenView_view.practise")
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
			$back = new Link($this->modePrefix() . "history&year=" . $_GET["year"], Lang::txt("ProbenView_viewOptions.back"));
			$back->addIcon("arrow_left");
			$back->write();
		}
		else if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			parent::viewOptions();
			
			// show a button to send a reminder to all about this rehearsal
			$remHref = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation") . "&mode=rehearsalMail&preselect=" . $_GET["id"];
			
			$reminder = new Link($remHref, Lang::txt("ProbenView_viewOptions.remHref"));
			$reminder->addIcon("email");
			$reminder->write();
		}
		else if(isset($_GET["tab"]) && ($_GET["tab"] == "invitations" || $_GET["tab"] == "participants")) {
			$this->backToStart();
			$this->buttonSpace();
			
			$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], Lang::txt("ProbenView_viewOptions.addContact"));
			$addContact->addIcon("plus");
			$addContact->write();
			$this->buttonSpace();
				
			$printPartlist = new Link($this->modePrefix() . "printPartlist&id=" . $_GET["id"], Lang::txt("ProbenView_viewOptions.printPartlist"));
			$printPartlist->addIcon("printer");
			$printPartlist->write();
		}
		else {
			$this->backToStart();
		}
	}
	
	function overview() {
		if(isset($_GET["edit"])) {
			if($_GET["edit"] == "save") {
				$this->getData()->updateParticipations();
			}
			elseif($_GET["edit"] == "true") {
				echo '<form method="POST" action="' . $this->modePrefix() . "overview&edit=save" . '" style="margin-top: 0px;">';
				
				$save = new Link($this->modePrefix() . "overview&edit=save", Lang::txt("ProbenView_overviewEdit.save"));
				$save->isSubmitButton();
				$save->write();
				
				Writing::p(Lang::txt("ProbenView_overview.edit_legend_message"));
			}
		}
		
		$futureRehearsals = $this->getData()->adp()->getFutureRehearsals();
		$usedInstruments = $this->getData()->getUsedInstruments();
		
		if(count($futureRehearsals) <= 1) {
			Writing::p(Lang::txt("ProbenView_overview.FutureRehearsals"));
		}
		
		for($reh = 1; $reh < count($futureRehearsals); $reh++) {
			$rehearsal = $futureRehearsals[$reh];
			?>
			<div class="rehearsal_overview_box">
				<div class="rehearsal_overview_header"><?php echo Lang::txt("ProbenView_overview.header_1") . Data::convertDateFromDb($rehearsal['begin']) . Lang::txt("ProbenView_overview.header_2"); ?></div>
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
									$alt = Lang::txt("ProbenView_overview.unspecified");
									$icon = "unspecified";
									break;
								case 0: 
									$alt = Lang::txt("ProbenView_overview.cancel");
									$icon = "cancel";
									break;
								case 2: 
									$alt = Lang::txt("ProbenView_overview.yield");
									$icon = "yield";
									break;
								default:
									$alt = Lang::txt("ProbenView_overview.checked");
									$icon = "checked";
									break;
							}
							?>
							<div class="player_participation_line">
								<?php 
								if(isset($_GET["edit"]) && $_GET["edit"] == "true") {
									if(in_array("contact", $participant)) {
										$cid = $participant["contact"];
									}
									else {
										$cid = $participant["contact_id"];
									}
									$dd = new Dropdown("part_r" . $rehearsal['id'] . "_c" . $cid);
									$dd->addOption("?", -1);
									$dd->addOption("-", 0);
									$dd->addOption("✓", 1);
									if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
										$dd->addOption("~", 2);
									}
									$dd->setSelected($participant['participate']);
									$dd->setStyleClass("participationQuickSelector");
									echo $dd->write();
								}
								else {
								?>
								<img src="style/icons/<?php echo $icon; ?>.png" alt="<?php echo $alt; ?>" style="height: 14px" />
								<?php 
								}
								?>
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
		
		if(isset($_GET["edit"]) && $_GET["edit"] == "true") {
			echo '</form>';
		}
	}
	
	public function overviewOptions() {
		if(isset($_GET["edit"]) && $_GET["edit"] == "true") {
			$overview = new Link($this->modePrefix() . "overview", Lang::txt("ProbenView_startOptions.mitspieler"));
			$overview->addIcon("mitspieler");
			$overview->write();
		}
		else {
			$this->backToStart();
			$edit = new Link($this->modePrefix() . "overview&edit=true", Lang::txt("ProbenView_overviewEdit.buttonLabel"));
			$edit->addIcon("edit");
			$edit->write();
		}
			
	}
	
	private function isReadOnlyView() {
		return (isset($_GET["readonly"]) && $_GET["readonly"] == "true");
	}
}

?>
