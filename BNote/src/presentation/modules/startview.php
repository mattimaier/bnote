<?php

/**
 * View for start module.
 * @author matti
 *
 */
class StartView extends CrudRefLocationView {
	
	/**
	 * During the creation of all rehearsal, concert, vote and task items on the start page
	 * this listing is filled in the format:  [otype] => array( oid1, oid2, ...) .
	 * Then the updates are calculated based on this list.
	 * This trick is necessary, since the recalculation of the possible discussions for the
	 * user would put unnecessary load on the server.
	 * @var array
	 */
	private $objectListing = array(
		"R" => array(),
		"C" => array(),
		"V" => array(),
		"T" => array(),
		"B" => array()  # reservations (B=blocked date)
	);
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function showOptions() {
		if(!isset($_GET["mode"])) {
			$this->startOptions();
		}
		else {
			$optFunc = $_GET["mode"] . "Options";
			if(method_exists($this, $optFunc)) {
				$this->$optFunc();
			}
			else {
				$this->startOptions();
			}
		}
	}
	
	protected function startOptions() {
		// don't show any options when the user has to select GDPR
		if($this->getData()->getSysdata()->gdprOk() == 0) {
			return;
		}
		
		// Calendar Exports
		$userExt = "?user=" . urlencode($this->getData()->adp()->getLogin());
		
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", Lang::txt("StartView_startOptions.calendarExport"));
		$ical->addIcon("save");
		$ical->write();
		
		// WebCal URL creation
		$systemUrl = $this->getData()->getSysdata()->getSystemURL();
		if($systemUrl != "") {
			if(!Data::endsWith($systemUrl, "/")) $systemUrl .= "/";
			if(Data::startsWith($systemUrl, "http://")) $systemUrl = substr($systemUrl, 7);
			else if(Data::startsWith($systemUrl, "https://")) $systemUrl = substr($systemUrl, 8);
		}
		else {
			$systemUrl = $_SERVER["HTTP_HOST"] . str_replace("main.php", "", $_SERVER["SCRIPT_NAME"]);
		}
		$webcal_link = "webcal://" . $systemUrl . "BNote/" .  $GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt";
		if(strpos($webcal_link, "BNote/BNote") !== False) {
			$webcal_link = str_replace("BNote/BNote/", "BNote/", $webcal_link);
		}
		
		$calSubsc = new Link($webcal_link, Lang::txt("StartView_startOptions.calendarSubscribe"));
		$calSubsc->addIcon("calendar");
		$calSubsc->write();
	}
	
	function startTitle() {
		return Lang::txt("StartView_start.Feed");
	}
	
	function start() {
		$otype = isset($_GET["otype"]) ? $_GET["otype"] : "N";  // N = News
		$inboxItems = $this->getData()->getInboxItems();
		
		if($otype != "N") {
			$detailsTitle = $this->findInboxItemTitle($inboxItems, $otype, $_GET["oid"]);
		}
		else {
			// news
			$detailsTitle = Lang::txt("StartView_start_box.heading");
		}
		$news = $this->getData()->getNews();
		
		if($news != "" || $this->getData()->getSysdata()->gdprOk() == 0) {
			// GDPR
			if($this->getData()->getSysdata()->gdprOk() == 0) {
				?>
				<div class="row">
					<div class="col-md-12 mb-3">
						<span class="warning">
							<?php echo Lang::txt("StartView_start.warning"); ?>
						</span>
						<a href="?mod=terms" target="_blank"><?php echo Lang::txt("StartView_start.terms"); ?></a>
						<br/>
						<?php 
						$yes = new Link($this->modePrefix() . "gdprOk&accept=1", Lang::txt("StartView_start.checkmark"));
						$yes->addIcon("check-square");
						$yes->write();
						$no = new Link($this->modePrefix() . "gdprOk&accept=0", Lang::txt("StartView_start.cancel"));
						$no->addIcon("x-square");
						$no->write();
						?>
					</div>
				</div>
				<?php
				// do not show anything on the start page unless the user has selected (ok)
				return;
			}
		}
		
		?>
		<div class="row">
			<div class="col-md-3">
				<!-- FEED -->
				<div class="start_box_heading p-2 mb-1"><?php echo Lang::txt("StartView_start.Inbox"); ?></div>
				<?php
				$newsActive = (!isset($_GET["otype"]) || !isset($_GET["oid"]) || ($_GET["otype"] == "N"));
				$this->writeCard(Lang::txt("StartView_start_box.heading"), substr($news, 0, 50) . "...", $this->modePrefix() . "start&otype=N", $newsActive);
				
				$sortedInboxItems = array_column($inboxItems, 'replyUntil');
				array_multisort($sortedInboxItems, SORT_ASC, $inboxItems);
				
				foreach($inboxItems as $item) {
					$href = $this->modePrefix() . "start&otype=" . $item["otype"] . "&oid=" . $item["oid"] . "#itemContentScreen";
					$active = (isset($_GET["otype"]) && isset($_GET["oid"]) && $_GET["otype"] == $item["otype"] && $_GET["oid"] == $item["oid"]);
					$part = isset($item["participation"]) ? $item["participation"] : NULL;
					$status = isset($item["status"]) ? $item["status"] : NULL;
					$this->writeCard($item["title"], $item["preview"], $href, $active, $item["due"], $part, $status);
				}
				?>
			</div>
			<div class="col-md-9" id="itemContentScreen">
				<!-- CONTENT -->
				<div class="start_box_content_heading p-2 mb-1"><?php echo $detailsTitle; ?></div>
				<div class="py-2">
				<?php 
				// If discussions are allowed, create column on the right with discussion in chat style
				if($this->getData()->getSysdata()->getDynamicConfigParameter("discussion_on") == 1 && $otype != "N") {
					?>
					<div class="row">
						<div class="col-md-9">
							<?php 
							$startFunc = "startView" . $otype;
							$this->$startFunc();
							?>
						</div>
						<div class="col-md-3">
							<?php 
							// Chat Widget
							$chat = new ChatWidget($otype, $_GET["oid"], $this->getData()->adp(), $this->modePrefix() . "addComment");
							$chat->write();
							?>
						</div>
					</div>
					<?php
				}
				else {
					$startFunc = "startView" . $otype;
					$this->$startFunc();
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}
	
	private function findInboxItemTitle($inboxItems, $itemType, $id) {
		foreach($inboxItems as $item) {
			if($item["otype"] == $itemType && $item["oid"] == $id) {
				return $item["title"];
			}
		}
	}
	
	private function writeCard($title, $preview, $href, $active, $dueDate=NULL, $userParticipation=NULL, $status=NULL) {
		$partClass = "";
		if($userParticipation != NULL || is_int($userParticipation)) {
			switch($userParticipation) {
				case 0:
					$partClass = "start_box_participation_no";
					break;
				case 1:
					$partClass = "start_box_participation_yes";
					break;
				case 2:
					$partClass = "start_box_participation_maybe";
					break;
				default:
					$partClass = "start_box_participation_unknown";
			}
		}
		
		$statusClass = "";
		if($status != NULL) {
			$statusClass = " start_box_status_$status";
		}
		?>
		<div class="card <?php echo $active ? "start_box_active" : ""; echo " " . $partClass; ?> mb-1">
			<a class="start_card" href="<?php echo $href; ?>">
				<div class="start_card_due"><?php
				if($dueDate != NULL && $dueDate != "") {
					echo '<i class="bi-send-exclamation me-1"></i>';
					echo $dueDate; 
				}
				?></div>
				<div class="card-body p-2 ps-3">
					<div class="card-title <?php echo $statusClass; ?>"><?php echo $title; ?></div>
					<span class="fw-light"><?php echo $preview?></span>
				</div>
			</a>
		</div>
		<?php
	}
	
	function startViewN() {
		$news = $this->getData()->getNews();
		echo $news;
		
		// warning
		if(($this->getData()->getSysdata()->isUserSuperUser() || $this->getData()->getSysdata()->isUserAdmin())
		&& $this->getController()->usersToIntegrate()) {
			$this->verticalSpace();
			echo '<span class="warning">' . Lang::txt("StartView_start_box_content.warning_1") . '</span>';
		}
		
		// check whether autologin is active and user is admin
		if($this->getData()->getSysdata()->isUserAdmin() && $this->getData()->getSysdata()->isAutologinActive()) {
			$this->verticalSpace();
			echo '<span class="warning">' . Lang::txt("StartView_start_box_content.warning_2") . '</span>';
		}
	}
	
	function startViewR() {
		$oid = $_GET["oid"];
		$rehearsal = $this->getData()->getRehearsal($oid);
		
		// participation widget
		$partLink = $this->modePrefix() . "saveParticipation&otype=R&oid=$oid";
		$userParticipation = $this->getData()->doesParticipateInRehearsal($oid);
		$partWidget = new ParticipationWidget($partLink, $userParticipation["participate"], $userParticipation["reason"]);
		$partWidget->write();
		
		Writing::h5(Lang::txt("StartView_startViewR.info"), "mt-3");
		
		// create details for each rehearsal
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.begin"), Data::convertDateFromDb($rehearsal["begin"]));
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.end"), Data::convertDateFromDb($rehearsal["end"]));
		$dataview->addElement(Lang::txt("ProbenData_construct.status"), Lang::txt("Proben_status." . $rehearsal["status"]));
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.location"), $rehearsal["name"] . ": " . $this->formatAddress($rehearsal));
		if(isset($rehearsal["conductor"]) && $rehearsal["conductor"] != null) {
			$dataview->addElement(Lang::txt("StartView_writeRehearsalList.conductor"), $this->getData()->adp()->getConductorname($rehearsal["conductor"]));
		}
		if(isset($rehearsal["groups"])) {
			$groupNames = array_column($rehearsal["groups"], "name");
			$dataview->addElement(Lang::txt("StartView_writeRehearsalList.groupNames"), join(", ", $groupNames));
		}
		
		// custom data
		$customFields = $this->getData()->getCustomFields('r', true);
		$customData = $this->getData()->getCustomData('r', $rehearsal["id"]);
		for($j = 1; $j < count($customFields); $j++) {
			$field = $customFields[$j];
			$label = $field["txtdefsingle"];
			if(isset($customData[$field["techname"]])) {
				$value = $customData[$field["techname"]];
				if($field["fieldtype"] == "BOOLEAN") {
					$value = $value == 1 ? Lang::txt("yes") : Lang::txt("no");
				}
				$dataview->addElement($label, $value);
			}
		}
		
		if($rehearsal["notes"] != "") {
			$dataview->addElement(Lang::txt("StartView_writeRehearsalList.comment"), "<p class=\"ml-comment\">" . $rehearsal["notes"] . "</p>");
		}
		
		$songs = $this->getData()->getSongsForRehearsal($rehearsal["id"]);
		if(count($songs) > 1) {
			$strSongs = "";
			for($j = 1; $j < count($songs); $j++) {
				if($j > 1) $strSongs .= ", ";
				$strSongs .= $songs[$j]["title"];
				if($songs[$j]["notes"] != "") $strSongs .= " (" . $songs[$j]["notes"] . ")";
			}
			$dataview->addElement(Lang::txt("StartView_writeRehearsalList.Song"), $strSongs);
		}
		
		$dataview->write();
		
		// participants
		Writing::h5(Lang::txt("StartView_rehearsalParticipants.participantsOfRehearsal", array(Data::convertDateFromDb($rehearsal["begin"]))), "mt-3");
		
		$parts = $this->getData()->getRehearsalParticipants($oid);
		$table = new Table($parts);
		$table->renameHeader("name", Lang::txt("StartView_rehearsalParticipants.name"));
		$table->renameHeader("surname", Lang::txt("StartView_rehearsalParticipants.surname"));
		$table->renameHeader("nickname", Lang::txt("StartView_rehearsalParticipants.nickname"));
		$table->removeColumn("instrumentrank");
		$table->write();
	}
	
	function startViewC() {
		$participation = $this->getData()->doesParticipateInConcert($_GET["oid"]);
		$href = $this->modePrefix() . "saveParticipation&otype=C&oid=" . $_GET["oid"];
		$partWidget = new ParticipationWidget($href, $participation["participate"], $participation["reason"]);
		$partWidget->write();
		$this->gigcard();
	}
	
	function startViewT() {
		?>
		<div class="mb-3">
			<a href="<?php echo $this->modePrefix() . "taskComplete&otype=T&oid=" . $_GET["oid"]; ?>" class="btn btn-primary">
				<i class="bi-check"></i>
				<?php echo Lang::txt("StartView_taskComplete.taskCompletedTitle"); ?>
			</a>
		</div>
		<?php
		$task = $this->getData()->getTask($_GET["oid"]);
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeTaskList.title"), $task["title"]);
		$dataview->addElement(Lang::txt("StartView_writeTaskList.description"), $task["description"]);
		$dataview->addElement(Lang::txt("StartView_writeTaskList.due_at"), Data::convertDateFromDb($task["due_at"]));
		$dataview->write();
	}
	
	function startViewA() {
		$appointment = $this->getData()->getAppointment($_GET["oid"]);
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeAppointmentList.name"), $appointment["name"]);
		$dataview->addElement(Lang::txt("StartView_writeAppointmentList.locationname"), $appointment["locationname"]);
		
		// custom data
		$customFields = $this->getData()->getCustomFields('a', true);
		$customData = $this->getData()->getCustomData('a', $appointment["id"]);
		for($j = 1; $j < count($customFields); $j++) {
			$field = $customFields[$j];
			$label = $field["txtdefsingle"];
			if(isset($customData[$field["techname"]])) {
				$value = $customData[$field["techname"]];
				if($field["fieldtype"] == "BOOLEAN") {
					$value = $value == 1 ? Lang::txt("StartView_writeAppointmentList.yes") : Lang::txt("StartView_writeAppointmentList.no");
				}
				$dataview->addElement($label, $value);
			}
		}
		
		$dataview->write();
	}
	
	function startViewB() {
		$oid = $_GET["oid"];
		$reservation = $this->getData()->getReservation($oid);
		
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeReservationList.name"), $reservation["name"]);
		
		// custom data
		$customFields = $this->getData()->getCustomFields('b', true);
		$customData = $this->getData()->getCustomData('b', $oid);
		for($j = 1; $j < count($customFields); $j++) {
			$field = $customFields[$j];
			$label = $field["txtdefsingle"];
			if(isset($customData[$field["techname"]])) {
				$value = $customData[$field["techname"]];
				if($field["fieldtype"] == "BOOLEAN") {
					$value = $value == 1 ? Lang::txt("StartView_writeReservationList.yes") : Lang::txt("StartView_writeReservationList.no");
				}
				$dataview->addElement($label, $value);
			}
		}
		
		$dataview->write();
	}
	
	function startViewV() {
		$vote = $this->getData()->getVote($_GET["oid"]);
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeVoteList.name"), $vote["name"]);
		$dataview->addElement(Lang::txt("StartView_writeVoteList.end"), Data::convertDateFromDb($vote["end"]));
		$dataview->write();
		
		$this->voteOptions();
	}
	public function voteOptions() {
		$oid = -1; // definitely leads to display the error (which is correct)
		if(isset($_GET["oid"])) {
			$oid = $_GET["oid"];
		}
		else if(isset($_GET["id"])) {
			$oid = $_GET["id"];
		}
		if(!$this->getData()->canUserVote($oid)) {
			new BNoteError(Lang::txt("StartView_voteOptions.error"));
		}
		
		$vote = $this->getData()->getVote($oid);
		Writing::h4(Lang::txt("vote"), "mt-3");
		
		echo "<form action=\"" . $this->modePrefix() . "saveVote&id=$oid\" method=\"POST\" class=\"mb-3\">\n";
		$options = $this->getData()->getOptionsForVote($oid);
		if(count($options) > 1) {
			$dv = new Dataview();
			for($i = 1 ; $i < count($options); $i++) {
				if($vote["is_date"] == 1) {
					$label = substr(Data::getWeekdayFromDbDate($options[$i]["odate"]), 0, 2) . ", ";
					$label .= Data::convertDateFromDb($options[$i]["odate"]);
				}
				else {
					$label = $options[$i]["name"];
				}
				
				$in = '<input type="';
				$selected = $this->getData()->getSelectedOptionsForUser($options[$i]["id"], $this->getUserId());
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == "1"
						&& $vote["is_multi"] == 1) {
					$dd = new Dropdown($options[$i]["id"]);
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMeNot"), "no");
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMe"), "yes");
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMeMaybe"), "maybe");
					if($selected !== -1) {
						$choice = "yes";
						if($selected == 2) $choice = "maybe";
						else if($selected < 1) $choice = "no";
						$dd->setSelected($choice);
					}		
					else {
						$dd->setSelected("yes"); // default
					}			
					$in = $dd->write();
				}
				else if($vote["is_multi"] == 1) {
					if($selected == 1) {
						$checked = "checked";
					}
					else {
						$checked = "";
					}
					$in .= "checkbox";
					$in .= '" name="' . $options[$i]["id"] . '" ' . $checked . '/>';
				}
				else {
					if($selected == 1) {
						$checked = "checked";
					}
					else {
						$checked = "";
					}
					$in .= "radio";
					$in .= '" name="uservote" value="' . $options[$i]["id"] . '" ' . $checked . '/>';
				}
				if(is_numeric($label)) {
					$dv->allowNumericLabels();
				}
				$dv->addElement($label, $in);
			}
			$dv->write();
			echo '<input type="submit" class="btn btn-primary" />' . "\n";
		}
		else {
			Writing::p(Lang::txt("StartView_voteOptions.noOptionsYet"));
		}
		echo "</form>\n";
	}
	
	public function saveVote() {
		$this->checkID();
		$this->getData()->saveVote($_GET["id"], $_POST);
		new Message(Lang::txt("StartView_saveVote.selectionSavedTitle"), Lang::txt("StartView_saveVote.selectionSavedMsg"));
	}
	
	function saveVoteOptions() {
		$back = new Link($this->modePrefix() . "start&otype=V&oid=" . $_GET["id"], Lang::txt("back"));
		$back->addIcon("arrow-left");
		$back->write();
	}
	
	public function taskComplete() {
		$this->getData()->taskComplete($_GET["oid"]);
		new Message(Lang::txt("StartView_taskComplete.taskCompletedTitle"), Lang::txt("StartView_taskComplete.taskCompletedMsg"));
	}
	
	public function taskCompleteOptions() {
		$this->backToStart();
	}
	
	public function viewProgram() {
		$this->checkID();
		$titles = $this->getData()->getProgramTitles($_GET["id"]);
		
		Writing::h2(Lang::txt("StartView_viewProgram.ProgramTitles"));
		
		// Enhancement #127
		$konzertMod = $this->getData()->getSysdata()->getModuleId(Lang::txt("concerts"));
		if($this->getData()->getSysdata()->userHasPermission($konzertMod)) {
			$editLink = new Link("?mod=" . $konzertMod . "&mode=programs&sub=view&id=" . $_GET["id"], Lang::txt("start_editProgram"));
			$editLink->addIcon("edit");
			$editLink->write();
			$this->verticalSpace();
		}
		
		$table = new Table($titles);		
		$table->renameHeader("rank", Lang::txt("StartView_viewProgram.rank"));
		$table->renameHeader("title", Lang::txt("StartView_viewProgram.title"));
		$table->renameHeader("composer", Lang::txt("StartView_viewProgram.composer"));
		$table->renameHeader("notes", Lang::txt("StartView_viewProgram.notes"));
		
		$table->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function concertParticipants() {
		$concert = $this->getData()->getConcert($_GET["id"]);
		Writing::h2(Lang::txt("StartView_concertParticipants.participantsOfConcert", array(Data::convertDateFromDb($concert["begin"]))));
		
		$parts = $this->getData()->getConcertParticipants($_GET["id"]);
		$table = new Table($parts);
		$table->renameHeader("name", Lang::txt("StartView_concertParticipants.name"));
		$table->renameHeader("surname", Lang::txt("StartView_concertParticipants.surname"));
		$table->renameHeader("nickname", Lang::txt("StartView_concertParticipants.nickname"));
		$table->write();
	}
	
	public function concertParticipantsOptions() {
		$this->backToStart();
	}
	
	function saveParticipationOptions() {
		$this->backToStart();
	}
	
	function gigcard() {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php";
		require_once $GLOBALS["DIR_LOGIC_MODULES"] . "konzertecontroller.php";
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "konzerteview.php";
		$concertData = new KonzerteData();
		$concertCtrl = new KonzerteController();
		$concertCtrl->setData($concertData);
		$concertView = new KonzerteView($concertCtrl);
		
		// get concert data
		$oid = $_GET["oid"];
		$c = $concertData->findByIdNoRef($oid);
		$custom = $concertData->getCustomData($oid);
		$loc = $concertData->adp()->getLocation($c["location"]);
		
		// concert details
		Writing::h4($c["title"], "mt-3");
		?>
		<p class="ml-comment"><?php echo $c["notes"]; ?></p>
		<table>
			<tbody>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.date"); ?></td>
					<td><?php 
					echo Data::convertDateFromDb($c["begin"]) . " - ";
					echo Data::convertDateFromDb($c["end"]);
					?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("KonzerteData_construct.status"); ?></td>
					<td><?php echo Lang::txt("Konzerte_status." . $c["status"]); ?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.meetingtime"); ?></td>
					<td><?php echo Data::convertDateFromDb($c["meetingtime"]); ?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.approve_until"); ?></td>
					<td><?php echo Data::convertDateFromDb($c["approve_until"]); ?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.organizer"); ?></td>
					<td><?php 
					if($c["organizer"]) {
						echo $c["organizer"];
					}
					?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.address"); ?></td>
					<td><?php 
					$a = $concertData->getAddress($loc["address"]);
					$gigAddress = $concertView->exportFormatAddress($a);
					echo $loc["name"] . "<br/>$gigAddress<br/>";
					
					// show static map if Google key is set
					$google_api_key = $this->getData()->getSysdata()->getDynamicConfigParameter("google_api_key");
					if($google_api_key != "") {
						$addy = urlencode($gigAddress);
						$src = "https://maps.googleapis.com/maps/api/staticmap?center=$addy&size=350x250&markers=color:red|$addy&key=$google_api_key";
						echo "<img src=\"$src\" alt=\"map\" />";
					}
					
					?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.contact"); ?></td>
					<td><?php 
					if($c["contact"]) {
						$cnt = $concertData->getContact($c["contact"]);
						$cv = $concertView->exportFormatContact($cnt, 'NAME_COMM_LB');
					}
					else {
						$cv = "-";
					}
					echo $cv;
					?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.accommodation"); ?></td>
					<td><?php 
					if($c["accommodation"] > 0) {
						$acc = $concertData->adp()->getAccommodationLocation($c["accommodation"]);
						$addy = $this->formatAddress($acc);
						echo $acc["name"] . "<br>$addy";
					}
					?></td>
				</tr>
				
				<?php 
				// custom data
				$customFields = $concertData->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE);
				for($i = 1; $i < count($customFields); $i++) {
					$field = $customFields[$i];
					?>
					<tr>
						<td><?php echo $field["txtdefsingle"]; ?></td>
						<td><?php echo $custom[$field["techname"]]; ?></td>
					</tr>
					<?php 
				}
				?>
			</tbody>
		</table>
		
		<h5 class="mt-3"><?php echo Lang::txt("StartView_gigcard.organisation"); ?></h5>
		<table>
			<tbody>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.groups"); ?></td>
					<td><?php 
					$groups = $concertData->getConcertGroups($c["id"]);
					$groupNames = Database::flattenSelection($groups, "name");
					echo join(", ", $groupNames);
					?></td>
				</tr>
				<tr>
					<td><?php echo Lang::txt("StartView_gigcard.outfit"); ?></td>
					<td><?php 
					if($c["outfit"]) {
						$outfit = $concertData->getOutfit($c["outfit"]);
						echo $outfit["name"];
					}
					?></td>
				</tr>
			</tbody>
		</table>
		
		<?php
		if($c["program"]) {
			$prg = $concertData->getProgram($c["program"]);
			$titles = $this->getData()->getProgramTitles($prg["id"]);
			
			Writing::h5(Lang::txt("StartView_gigcard.program") . ": " . $prg["name"], "mt-3");
			
			$table = new Table($titles);
			$table->renameHeader("rank", Lang::txt("StartView_viewProgram.rank"));
			$table->renameHeader("title", Lang::txt("StartView_viewProgram.title"));
			$table->renameHeader("composer", Lang::txt("StartView_viewProgram.composer"));
			$table->renameHeader("notes", Lang::txt("StartView_viewProgram.notes"));
			$table->showFilter(false);
			
			$table->write();
		}
	}
	
	function gigcardOptions() {
		$this->backToStart();
		
		$prt = new Link("javascript:print()", Lang::txt("StartView_gigcardOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
		
		// show participants
		$participantsButton = new Link($this->modePrefix() . "concertParticipants&id=" . $_GET["id"], Lang::txt("StartView_gigcardOptions.concertParticipants"));
		$participantsButton->addIcon("mitspieler");
		$participantsButton->write();
		
	}
	
	function addComment() {
		$this->getData()->adp()->addComment($_GET["otype"], $_GET["oid"]);
		$_GET["itemType"] = $_GET["otype"];
		$_GET["id"] = $_GET["oid"];
		$this->start();
	}
	
	function addCommentOptions() {
		$this->startOptions();
	}
}

?>
