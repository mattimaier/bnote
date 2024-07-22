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
		
		Writing::h4(Lang::txt("ProbenView_start.title"));
		$nextRehearsal = $this->getData()->getNextRehearsal();
		if($nextRehearsal != null && $nextRehearsal != "" && count($nextRehearsal) > 0) {
			$this->writeRehearsal($nextRehearsal);
		}
		else {
			Writing::p(Lang::txt("ProbenView_start.norehearsal"));
		}

		Writing::h4(Lang::txt("ProbenView_start.title_2"), "mt-3");
		$this->writeRehearsalList($this->getData()->adp()->getFutureRehearsals());
	}
	
	function startOptions() {
		parent::startOptions();
		
		$series = new Link($this->modePrefix() . "addSerie", Lang::txt("ProbenView_startOptions.overtime"));
		$series->addIcon("calendar2-plus");
		$series->write();
		
		$seriesEdit = new Link($this->modePrefix() . "editSerie", Lang::txt("ProbenView_startOptions.edit"));
		$seriesEdit->addIcon("pen");
		$seriesEdit->write();
		
		$overview = new Link($this->modePrefix() . "overview", Lang::txt("ProbenView_startOptions.mitspieler"));
		$overview->addIcon("people");
		$overview->write();
		
		$history = new Link($this->modePrefix() . "history", Lang::txt("ProbenView_startOptions.timer"));
		$history->addIcon("clock");
		$history->write();
	}
	
	function addEntity($form_target=null, $tour=null) {
		// check whether a location exists
		if(!$this->getData()->locationsPresent()) {
			new Message(Lang::txt("ProbenView_addEntity.message_1"), Lang::txt("ProbenView_addEntity.message_2"));
			$this->backToStart();
			return;
		}
		
		// New entity form
		if($form_target == null) {
			$form_target = $this->modePrefix() . "add";
		}
		$form = new Form(Lang::txt($this->getaddEntityName()), $form_target);
		
		// begin
		$beginField = new Field("begin", date("Y-m-d") . " " . $this->getData()->getDefaultTime(), FieldType::DATETIME);
		$beginField->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("ProbenView_addEntity.begin"), $beginField, true, 4);
		
		// end
		if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
			$end = Data::addMinutesToDate(date("Y-m-d") . " " . $this->getData()->getDefaultTime() . ":00", $this->getData()->getDefaultDuration());
			$form->addElement(Lang::txt("ProbenView_addEntity.end"), new Field("end", $end, FieldType::DATETIME), true, 4);
		}
		else {
			$form->addElement(Lang::txt("ProbenView_addEntity.duration"), new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER), true, 4);
		}
		
		// approve until
		$approve_until_field = new Field("approve_until", "", FieldType::DATETIME);
		$approve_until_field->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("ProbenView_addEntity.approve_until"), $approve_until_field, true, 4);
		
		$form->addElement(Lang::txt("ProbenData_construct.status"), $this->buildStatusDropdown($this->getData()->getStatusOptions()), true, 4);
		
		// location
		$form->addElement("location", new Field("location", "", FieldType::REFERENCE), true, 4);
		$form->setForeign("location", "location", "id", "name", -1);
		$form->renameElement("location", Lang::txt("ProbenView_addEntity.location"));
		
		// conductor
		$form->addElement(Lang::txt("ProbenView_addEntity.conductor"), $this->buildConductorDropdown(), false, 4);
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'r');
		
		// notes
		$form->addElement(Lang::txt("ProbenView_addEntity.notes"), new Field("notes", "", FieldType::TEXT), false, 12);
		
		// groups
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("ProbenView_addEntity.groups"), $gs);
		
		if($tour != null) {
			$form->addHidden("tour", $tour);
		}
		
		$form->write();
	}
	
	private function buildStatusDropdown($options, $selectedOpt=NULL) {
		$dd = new Dropdown("status");
		foreach($options as $opt) {
			$dd->addOption(Lang::txt("Proben_status.$opt"), $opt);
		}
		if($selectedOpt != NULL) {
			$dd->setSelected($selectedOpt);
		}
		return $dd;
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
		
		$cycle = new Dropdown("cycle");
		$cycle->addOption(Lang::txt("ProbenView_addSerie.cycle_1"), "1");
		$cycle->addOption(Lang::txt("ProbenView_addSerie.cycle_2"), "2");
		$form->addElement(Lang::txt("ProbenView_addSerie.cycle"), $cycle);
		
		$first_session = new Field("first_session", "", FieldType::DATE);
		$first_session->setCssClass("copyDateOrigin");
		$form->addElement(Lang::txt("ProbenView_addSerie.first_session"), $first_session, true, 3);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.first_session"));
		
		$last_session = new Field("last_session", "", FieldType::DATE);
		$last_session->setCssClass("copyDateTarget");
		$form->addElement(Lang::txt("ProbenView_addSerie.last_session"), $last_session, true, 3);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.last_session"));
		
		
		$form->addElement(Lang::txt("ProbenView_addSerie.default_time"), new Field("default_time", $this->getData()->getDefaultTime(), FieldType::TIME), true, 3);
		$form->addElement(Lang::txt("ProbenView_addSerie.duration"), new Field("duration", $this->getData()->getDefaultDuration(), FieldType::INTEGER), true, 3);
		
		$form->addElement(Lang::txt("ProbenView_addSerie.location"), new Field("location", "", FieldType::REFERENCE));
		$form->setForeign(Lang::txt("ProbenView_addSerie.location"), "location", "id", "name", -1);
		
		$form->addElement(Lang::txt("ProbenView_addSerie.Conductor"), $this->buildConductorDropdown());
		
		$notesField = new Field("notes", "", FieldType::TEXT);
		$notesField->setColsAndRows(3, 50);
		$form->addElement(Lang::txt("ProbenView_addSerie.notes"), $notesField, false, 12);
		
		$gs = new GroupSelector($this->getData()->adp()->getGroups(true, true), array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("ProbenView_addSerie.group"), $gs);
		$form->setFieldRequired(Lang::txt("ProbenView_addSerie.group"));
		
		$this->appendCustomFieldsToForm($form, 'r');
		
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
		if(count($data) <= 1) {
			Writing::p(Lang::txt("ProbenView_writeRehearsalList.message"));
			return;
		}
		
		// combine data in a smart way
		$displayData = array();
		array_push($displayData, array(
			"id", "when", "approve_until", "status", "conductor", "Location"
		));
		for($i = 1; $i < count($data); $i++) {
			array_push($displayData, array(
				"id" => $data[$i]["id"],
				"when" => $this->buildWhen($data[$i]["begin"], $data[$i]["end"]),
				"approve_until" => $data[$i]["approve_until"],
				"status" => Lang::txt("Proben_status." . $data[$i]["status"]),
				"conductor" => $data[$i]["conductorname"],
				"location" => $data[$i]["name"] . "<br>" . $this->formatAddress($data[$i])
			));
		}
		
		$tab = new Table($displayData);
		$tab->renameHeader("when", Lang::txt("ProbenView_writeRehearsal.begin"));
		$tab->renameHeader("approve_until", Lang::txt("ProbenView_addEntity.approve_until"));
		$tab->renameHeader("status", Lang::txt("ProbenData_construct.status"));
		$tab->renameHeader("conductor", Lang::txt("ProbenView_addEntity.conductor"));
		$tab->renameHeader("location", Lang::txt("ProbenView_addEntity.location"));
		$tab->setEdit("id");
		$tab->hideColumn("id");
		$tab->write();
	}
	
	private function buildWhen($begin, $end) {
		$date_end = strtotime($end);
		$finish = date('H:i', $date_end);
		return Data::convertDateFromDb($begin) . " - " . $finish;
	}
	
	/**
	 * Writes out a single rehearsal as text.
	 * @param Array $row Row with field ids of rehearsal and location as keys (id column of rehearsal).
	 */
	private function writeRehearsal($row) {
		// prepare data
		$date_begin = strtotime($row["begin"]);
		$weekday = Data::convertEnglishWeekday(date("D", $date_begin));
		$when = $this->buildWhen($row["begin"], $row["end"]);
		
		$conductor = "";
		if(isset($row["conductor"]) && $row["conductor"] != 0) {
			$conductor .= "Dirigent: ";
			$conductor .= $this->getData()->adp()->getConductorname($row["conductor"]);
		}

		$href = $this->modePrefix() . "view&id=" . $row["id"];
		
		// put output together
		?>
		<div class="card mb-2 p-2">
			<div class="">
				<a href="<?php echo $href; ?>"><?php echo $weekday; ?> <?php echo $when; ?></a>
				<span class=""><?php echo $conductor; ?></span>
				<span class="text-muted"><?php echo Lang::txt("Proben_status." . $row["status"]); ?></span>
			</div>
			<div class="">
				<span class="fw-bold"><?php echo $row["name"]; ?></span>
				<span class="text-muted"><?php echo $this->formatAddress($row); ?></span>
				<span class="fw-italic"><?php echo $row["notes"]; ?></span>
			</div>
		</div>
		<?php
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
		
		$form->setFieldColSize("begin", 4);
		$form->setFieldColSize("end", 4);
		$form->setFieldColSize("approve_until", 4);
		$form->setFieldColSize("location", 4);
		$form->setFieldColSize("notes", 12);
		
		$form->addElement("conductor", $this->buildConductorDropdown($r["conductor"]), false, 4);
		$form->addElement("status", $this->buildStatusDropdown($this->getData()->getStatusOptions(), $r["status"]), true, 4);
		
		
		// custom fields
		$this->appendCustomFieldsToForm($form, 'r', $r);
		
		$form->write();
	}
	
	function invitations() {
		$contacts = $this->getData()->getRehearsalContacts($_GET["id"]);
			
		// add a link to the data to remove the contact from the list
		$contacts[0]["delete"] = Lang::txt("ProbenView_invitations.Delete");
		for($i = 1; $i < count($contacts); $i++) {
			$delLink = $this->modePrefix() . "delContact&id=" . $_GET["id"] . "&cid=" . $contacts[$i]["id"] . "&tab=invitations";
			$btn = new Link($delLink, "");
			$btn->addIcon("trash3");
			$contacts[$i]["delete"] = $btn->toString();
		}
			
		$table = new Table($contacts);
		$table->removeColumn("id");
		$table->removeColumn("instrumentid");
		$table->renameHeader("Name", Lang::txt("ProbenView_invitations.Name"));
		$table->renameHeader("Nickname", Lang::txt("ProbenView_invitations.Nickname"));
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
			
			<div class="probendetail_set">
				<div class="probendetail_heading"><?php echo Lang::txt("ProbenData_construct.status"); ?></div>
				<div class="probendetail_value"><?php echo Lang::txt("Proben_status." . $entity["status"]); ?></div>
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
				<div class="probendetail_value"><pre><?php
				echo $entity["notes"]; 
				?></pre></div>
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
		
		Writing::h4(Lang::txt("ProbenView_participants.title_1"), "mt-3");
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->getAttendingInstruments($_GET["id"]));
		$dv->write();
		
		Writing::h4(Lang::txt("ProbenView_participants.title_2"), "mt-3");
		$table = new Table($this->getData()->getParticipants($_GET["id"]));
		$table->removeColumn("id");
		$table->removeColumn("instrumentid");
		$table->renameHeader("Name", Lang::txt("ProbenView_participants.Name_1"));																			
		$table->renameHeader("nickname", Lang::txt("ProbenView_participants.nickname_1"));
		$table->renameHeader("participate", Lang::txt("ProbenView_participants.participate"));
		$table->renameHeader("reason", Lang::txt("ProbenView_participants.reason"));
		$table->renameHeader("replyon", Lang::txt("ProbenView_participants.replyon"));
		$table->write();
		
		// remaining calls
		Writing::h4(Lang::txt("ProbenView_participants.title_3"), "mt-3");
		$openTab = new Table($this->getData()->getOpenParticipation($_GET["id"]));
		$openTab->showFilter(false);
		$openTab->removeColumn("id");
		$openTab->removeColumn("instrumentid");
		$openTab->renameHeader("Name", Lang::txt("ProbenView_participants.Name_2"));																			  
		$openTab->renameHeader("nickname", Lang::txt("ProbenView_participants.nickname_2"));
		$openTab->renameHeader("mobile", Lang::txt("ProbenView_participants.mobile"));
		$openTab->write();
		
		Writing::h4(Lang::txt("ProbenView_participants.title_4"), "mt-3");
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
		Writing::h4(Lang::txt("ProbenView_practise.title"), "mt-3");
		
		// check if a new song was added
		if(isset($_POST["song"])) {
			$this->getData()->saveSongForRehearsal($_POST["song"], $_GET["id"], $_POST["notes"]);
		}
		
		// show songs
		$songs = $this->getData()->getSongsForRehearsal($_GET["id"]);
		echo "<div>\n";
		for($i = 1; $i < count($songs); $i++) {
			$s = $songs[$i];
			$href = $this->modePrefix() . "practise&id=" . $_GET["id"];
			$href .= "&song=" . $s["id"];
			$caption = $s["title"];
			echo "<div class=\"practise card p-2 mb-2\"><a href=\"$href\" class=\"card-title\">$caption</a>";
			// show options if required
			if(isset($_GET["song"]) && $_GET["song"] == $s["id"]) {
				echo '<form method="POST" action="' . $this->modePrefix();
				echo 'practiseUpdate&id=' . $_GET["id"] . '&song=' . $s["id"] . '">';
				echo ' <input type="text" name="notes" size="30" value="' . $s["notes"] . '" />';
				echo ' <input type="submit" value="' . Lang::txt("ProbenView_practise.save") . '" />';
				$del = new Link($this->modePrefix() .
					"practiseDelete&id=" . $_GET["id"] . "&song=" . $s["id"] . "&tab=practise", Lang::txt("ProbenView_practise.delete"));
				$del->write();
				echo '</form>';
			}
			else {
				echo $s["notes"];
			}
			echo "</div>\n";
		}
		if(count($songs) == 1) {
			echo "<div>" . Lang::txt("ProbenView_practise.no_song") . "</div>\n";
		}
		echo "</div>\n";
		
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
	
	function viewTitle() {
		// title
		$pdate = $this->getData()->getRehearsalBegin($_GET["id"]);
		return Lang::txt("ProbenView_view.message_1") . "$pdate" . Lang::txt("ProbenView_view.message_2");
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
			// tabs
			$tabs = array(
				"details" => Lang::txt("ProbenView_view.details"),
				"invitations" => Lang::txt("ProbenView_view.invitations"),
				"participants" => Lang::txt("ProbenView_view.participants"),
				"practise" => Lang::txt("ProbenView_view.practise")
			);
			echo "<div class=\"nav nav-tabs\">\n";
			foreach($tabs as $tabid => $label) {
				$href = $this->modePrefix() . "view&id=" . $_GET["id"] . "&tab=$tabid";
				
				$active = "";
				if(isset($_GET["tab"]) && $_GET["tab"] == $tabid) $active = "active";
				else if(!isset($_GET["tab"]) && $tabid == "details") $active = "active";
			
				echo "<div class=\"nav-item\"><a href=\"$href\" class=\"nav-link $active\">$label</a></div>";
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
			$back->addIcon("arrow-left");
			$back->write();
			
			$editParticipation = new Link($this->modePrefix() . "overview&id=" . $_GET["id"] . "&edit=true", Lang::txt("ProbenView_viewOptions.editParticipation"));
			$editParticipation->addIcon("card-checklist");
			$editParticipation->write();
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
			
			$addContact = new Link($this->modePrefix() . "addContact&id=" . $_GET["id"], Lang::txt("ProbenView_viewOptions.addContact"));
			$addContact->addIcon("plus");
			$addContact->write();
			
			$editParticipation = new Link($this->modePrefix() . "overview&id=" . $_GET["id"] . "&edit=true", Lang::txt("ProbenView_viewOptions.editParticipation"));
			$editParticipation->addIcon("card-checklist");
			$editParticipation->write();
				
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
				$single = "";
				if(isset($_GET["id"])) {
					$single = "&id=" . $_GET["id"];
				}
				
				echo '<form method="POST" action="' . $this->modePrefix() . "overview$single&edit=save" . '" style="margin-top: 0px;">';
				
				$save = new Link($this->modePrefix() . "overview&edit=save$single", Lang::txt("ProbenView_overviewEdit.save"));
				$save->isSubmitButton();
				$save->write();
				
				Writing::p(Lang::txt("ProbenView_overview.edit_legend_message"));
			}
		}
		
		if(isset($_GET["id"])) {
			$rehearsals = array(array(), $this->getData()->findByIdNoRef($_GET["id"]));
		}
		if(isset($_GET["rehearsals"]) && $_GET["rehearsals"] == "history") {
			$rehearsals = $this->getData()->getPastRehearsalsWithLimit(10);
		}
		else {
			$rehearsals = $this->getData()->adp()->getFutureRehearsals();
		}
		$usedInstruments = $this->getData()->getUsedInstruments();
		
		if(count($rehearsals) <= 1) {
			Writing::p(Lang::txt("ProbenView_overview.FutureRehearsals"));
		}
		
		for($reh = 1; $reh < count($rehearsals); $reh++) {
			$rehearsal = $rehearsals[$reh];
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
								<?php 
								if(isset($_GET["edit"]) && $_GET["edit"] == "true") {
									if(isset($participant["contact"])) {
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
								<i class="bi-<?php echo $icon; ?>"></i>
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
		$single = "";
		if(isset($_GET["id"])) {
			$single = "&id=" . $_GET["id"];
		}
		$rehearsalMode = "";
		if(isset($_GET["rehearsals"])) {
			$rehearsalMode = "&rehearsals=" . $_GET["rehearsals"];
		}
		
		if(isset($_GET["edit"]) && $_GET["edit"] == "true") {
			$overview = new Link($this->modePrefix() . "overview$single", Lang::txt("ProbenView_startOptions.mitspieler"));
			$overview->addIcon("mitspieler");
			$overview->write();
		}
		else {
			$this->backToStart();
			$edit = new Link($this->modePrefix() . "overview$single$rehearsalMode&edit=true", Lang::txt("ProbenView_overviewEdit.buttonLabel"));
			$edit->addIcon("pen");
			$edit->write();
			
			$pastRehearsals = new Link($this->modePrefix() . "overview&rehearsals=history", Lang::txt("ProbenView_startOptions.timer"));
			$pastRehearsals->addIcon("clock");
			$pastRehearsals->write();
		}
	}
	
	private function isReadOnlyView() {
		return (isset($_GET["readonly"]) && $_GET["readonly"] == "true");
	}
}

?>
