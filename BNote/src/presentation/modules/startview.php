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
		$itemType = isset($_GET["itemType"]) ? $_GET["itemType"] : "news";
		$inboxItems = $this->getData()->getInboxItems();
		
		switch($itemType) {
			case "rehearsal":
			case "concert":
			case "appointment":
			case "reservation":
			case "vote":
			case "task":
				$detailsTitle = $this->findInboxItemTitle($inboxItems, $_GET["itemType"], $_GET["id"]);
				break;
			default:
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
				$newsActive = (!isset($_GET["itemType"]) || !isset($_GET["id"]) || ($_GET["itemType"] == "news"));
				$this->writeCard(Lang::txt("StartView_start_box.heading"), substr($news, 0, 50) . "...", $this->modePrefix() . "start&itemType=news", $newsActive);
				
				$sortedInboxItems = array_column($inboxItems, 'replyUntil');
				array_multisort($sortedInboxItems, SORT_ASC, $inboxItems);
				
				foreach($inboxItems as $item) {
					$href = $this->modePrefix() . "start&itemType=" . $item["itemType"] . "&id=" . $item["id"];
					$active = (isset($_GET["itemType"]) && isset($_GET["id"]) && $_GET["itemType"] == $item["itemType"] && $_GET["id"] == $item["id"]);
					$this->writeCard($item["title"], $item["preview"], $href, $active, $item["due"], isset($item["participation"]) ? $item["participation"] : NULL);
				}
				?>
			</div>
			<div class="col-md-9">
				<!-- CONTENT -->
				<div class="start_box_content_heading p-2 mb-1"><?php echo $detailsTitle; ?></div>
				<div class="p-2">
				<?php 
				$startFunc = "start" . $itemType;
				$this->$startFunc();
				?>
				</div>
			</div>
		</div>
		<?php
	}
	
	private function findInboxItemTitle($inboxItems, $itemType, $id) {
		foreach($inboxItems as $item) {
			if($item["itemType"] == $itemType && $item["id"] == $id) {
				return $item["title"];
			}
		}
	}
	
	private function writeCard($title, $preview, $href, $active, $dueDate=NULL, $userParticipation=NULL) {
		$partClass = "";
		if($userParticipation != NULL) {
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
					<div class="card-title"><?php echo $title; ?></div>
					<span class="fw-light"><?php echo $preview?></span>
				</div>
			</a>
		</div>
		<?php
	}
	
	function startNews() {
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
	
	function startrehearsal() {
		$rehearsal = $this->getData()->getRehearsal($_GET["id"]);
		
		$liCaption = Data::convertDateFromDb($rehearsal["begin"]);
		$liCaption = Data::getWeekdayFromDbDate($rehearsal["begin"]) . ", " . $liCaption;
		if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
			$liCaption .= "<br/>bis " . Data::getWeekdayFromDbDate($rehearsal["end"]) . ", " . Data::convertDateFromDb($rehearsal["end"]);
		}
		$liCaption = "<span class=\"start_rehearsal_title\">" . $liCaption . "</span>";
		
		// create details for each rehearsal
		$dataview = new Dataview();
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.begin"), Data::convertDateFromDb($rehearsal["begin"]));
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.end"), Data::convertDateFromDb($rehearsal["end"]));
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.location"), $this->formatAddress($rehearsal));
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
					$value = $value == 1 ? Lang::txt("StartView_writeRehearsalList.yes") : Lang::txt("StartView_writeRehearsalList.no");
				}
				$dataview->addElement($label, $value);
			}
		}
		
		if($rehearsal["notes"] != "") {
			$dataview->addElement(Lang::txt("StartView_writeRehearsalList.comment"), $rehearsal["notes"]);
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
		
		// add button to show participants
		$participantsButton = new Link($this->modePrefix() . "rehearsalParticipants&id=" . $rehearsal["id"], "Teilnehmer anzeigen");
		$dataview->addElement(Lang::txt("StartView_writeRehearsalList.Participants"), $participantsButton->toString());
		
		// show three buttons to participate/maybe/not in rehearsal
		$partButtonSpace = "<br/><br/>";
		$partButtons = "";
		$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=rehearsal&id=" . $rehearsal["id"] . "&action=";
		
		$partBtn = new Link($partLinkPrefix . "yes", Lang::txt("StartView_writeRehearsalList.yes"));
		$partBtn->addIcon("checkmark");
		$partButtons .= $partBtn->toString() . $partButtonSpace;
		
		if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
			$mayBtn = new Link($partLinkPrefix . "maybe", Lang::txt("StartView_writeRehearsalList.maybe"));
			$mayBtn->addIcon("yield");
			$partButtons .= $mayBtn->toString() . $partButtonSpace;
		}
		
		$noBtn = new Link($partLinkPrefix . "no", Lang::txt("StartView_writeRehearsalList.no"));
		$noBtn->addIcon("cancel");
		$partButtons .= $noBtn->toString();
		
		$userParticipation = $this->getData()->doesParticipateInRehearsal($rehearsal["id"]);
		if($userParticipation < 0) {
			if($rehearsal["approve_until"] == "" || Data::compareDates($rehearsal["approve_until"], Data::getDateNow()) > 0) {
				$this->writeItemContent("R", $rehearsal["id"], "r" . $rehearsal["id"], $liCaption,
						$dataview, $partButtons, Lang::txt("StartView_writeRehearsalList.setParticipation"));
			}
			else {
				$this->writeItemContent("R", $rehearsal["id"], "r" . $rehearsal["id"], $liCaption,
						$dataview, $partButtons, Lang::txt("StartView_writeRehearsalList.participationOver"), "", true);
			}
		}
		else {
			$msg = "";
			if($userParticipation == 1) {
				$msg .= Lang::txt("StartView_writeRehearsalList.Participate");
			}
			else if($userParticipation == 2) {
				$msg .= Lang::txt("StartView_writeRehearsalList.MaybeParticipate");
			}
			else if($userParticipation == 0) {
				$msg .= Lang::txt("StartView_writeRehearsalList.NotParticipate");
			}
			
			$this->writeItemContent("R", $rehearsal["id"], "r" . $rehearsal["id"], $liCaption, $dataview, $partButtons,
					$msg, "", false, $userParticipation);
		}
	}
	
	function startOld() {
		$news = $this->getData()->getNews();
		if($news != "" || $this->getData()->getSysdata()->gdprOk() == 0) { 
			// GDPR
			if($this->getData()->getSysdata()->gdprOk() == 0) {
				$h = Lang::txt("StartView_start.box_heading");
				$c = "";
				$this->box($h, $c);
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
						$yes->addIcon("checkmark");
						$yes->write();
						$no = new Link($this->modePrefix() . "gdprOk&accept=0", Lang::txt("StartView_start.cancel"));
						$no->addIcon("cancel");
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
			<div class="col-md-4 mb-3">
				<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Rehearsal.heading"); ?></div>
				<div class="p-2">
					<?php
					if(isset($_GET["max"]) && $_GET["max"] >= 0) {
						$this->writeRehearsalList($_GET["max"]);
					}
					else {
						$this->writeRehearsalList($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_max"));
					}
					?>
				</div>
			</div>
			
			<div class="col-md-4 mb-3">
				<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Concert.heading"); ?></div>
				<div class="p-2">
					<?php
					if(isset($_GET["concert_max"]) && $_GET["concert_max"] >= 0) {
						$this->writeConcertList($_GET["concert_max"]);
					}
					else {
						$this->writeConcertList($this->getData()->getSysdata()->getDynamicConfigParameter("concert_show_max"));
					}
					?>
				</div>
			</div>
			
			<?php
			if($this->getData()->hasReservations()) {
				?>
				<div class="col-md-4 mb-3">
					<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Reservation.heading"); ?></div>
					<div class="p-2">
					<?php $this->writeReservationList(); ?>
					</div>
				</div>	
				<?php
			}
			?>
				
			<?php
			if($this->getData()->hasAppointments()) {
				?>
				<div class="col-md-4 mb-3">
					<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Appointment.heading"); ?></div>
					<div class="p-2">
					<?php 
					if(isset($_GET["appointments_max"]) && $_GET["appointments_max"] >= 0) {
						$this->writeAppointmentList($_GET["appointments_max"]);
					}
					else {
						$this->writeAppointmentList($this->getData()->getSysdata()->getDynamicConfigParameter("appointments_show_max"));
					}
					?>
					</div>
				</div>	
				<?php
			}
			?>
			
			<div class="col-md-4 mb-3">
				<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Vote.heading"); ?></div>
				<div class="p-2">
					<?php $this->writeVoteList(); ?>
				</div>
			</div>
			
			<div class="col-md-4 mb-3">
				<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Task.heading"); ?></div>
				<div class="p-2">
					<?php $this->writeTaskList(); ?>
				</div>
			</div>
			
			<?php
			/*
			 * The update generation has to be last!
			 * For explanation see comment for $objectListing.
			 */
			if($this->getData()->getSysdata()->getDynamicConfigParameter("discussion_on") == 1) { 
				?>
				<div class="col-md-4 mb-3">
					<div class="p-2 start_box_heading"><?php echo Lang::txt("StartView_start_box_Task.writeUpdateList"); ?></div>
					<div class="p-2">
						<?php $this->writeUpdateList(); ?>
					</div>
				</div>
				<?php 
			}
			?>
		</div>
		<?php
	}
	
	public function askReason($type) {
		$form = new Form(Lang::txt("StartView_askReason.Form"),
				$this->modePrefix() . "saveParticipation&obj=$type&id=" . $_GET["id"] . "&action=" . $_GET["action"]);
		$form->addElement("", new Field("explanation", "", FieldType::CHAR));
		$form->write();
	}
	
	private function writeRehearsalList($max = 0) {
		$data = $this->getData()->getUsersRehearsals();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("StartView_writeRehearsalList.Form") . "</li>\n";
		}
		else {
			// iterate over rehearsals
			for($i = 1; $i < count($data); $i++) {
				
				// add every item to the discussion
				array_push($this->objectListing["R"], $data[$i]["id"]);
				
				// limit the number of rehearsals if necessary
				if($max > 0 && $i > $max) {
					if($i == $max+1) {
						echo "<span style=\"font-style: italic;\">" . Lang::txt("StartView_writeRehearsalList.Rehearsal", array($max)) . "</span>\n";
						echo "<br/><a href=\"" . $this->modePrefix() . "start&max=0" . "\">" . Lang::txt("showAll") . "</a>";
					}					
					continue; // cannot break due to discussion addition of objects
				}
			}
		}
		echo "</ul>\n";
	}
	
	private function writeConcertList($max = 0) {
		$data = $this->getData()->getUsersConcerts();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("StartView_writeConcertList.noConcertsScheduled") . "</li>\n";
		}
		else {
			// iterate over concerts
			foreach($data as $i => $row) {
				if($i == 0) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["C"], $row["id"]);
				
				// limit the number of concerts if necessary
				if($max > 0 && $i > $max) {
					if($i == $max+1) {
						echo "<span style=\"font-style: italic;\">" . Lang::txt("StartView_writeConcertList.Concert", array($max)) . "</span>\n";
						echo "<br/><a href=\"" . $this->modePrefix() . "start&concert_max=0" . "\">" . Lang::txt("showAll") . "</a>";
					}
					continue; // cannot break due to discussion addition of objects
				}
				
				$liCaption = Data::convertDateFromDb($row["begin"]);
				$liCaption = Data::getWeekdayFromDbDate($row["begin"]) . ", " . $liCaption;
				$liCaption = "<span class=\"start_concert_title\">" . $row["title"] . "</span><br/>" . $liCaption . "";
				
				$dataview = new Dataview();
				
				// show three buttons to participate/maybe/not in concert
				$partButtonSpace = "<br/><br/>";
				$partButtons = "";
				$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=concert&id=" . $data[$i]["id"] . "&action=";
				
				$partBtn = new Link($partLinkPrefix . "yes", Lang::txt("StartView_writeConcertList.yes"));
				$partBtn->addIcon("checkmark");
				$partButtons .= $partBtn->toString() . $partButtonSpace;
				
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
					$mayBtn = new Link($partLinkPrefix . "maybe", Lang::txt("StartView_writeConcertList.maybe"));
					$mayBtn->addIcon("yield");
					$partButtons .= $mayBtn->toString() . $partButtonSpace;
				}
				
				$noBtn = new Link($partLinkPrefix . "no", Lang::txt("StartView_writeConcertList.no"));
				$noBtn->addIcon("cancel");
				$partButtons .= $noBtn->toString();
				
				$userParticipation = $this->getData()->doesParticipateInConcert($data[$i]["id"]);
				if($userParticipation < 0) {
					if($data[$i]["approve_until"] == "" || Data::compareDates($data[$i]["approve_until"], Data::getDateNow()) > 0) {
						$this->writeItemContent("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("StartView_writeConcertList.setParticipation"));
					}
					else {
						$this->writeItemContent("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("StartView_writeConcertList.participationOver"), "", true);
					}
				}
				else {
					$msg = "";
					if($userParticipation == 1) {
						$msg .= Lang::txt("StartView_writeConcertList.Participate");
					}
					else if($userParticipation == 2) {
						$msg .= Lang::txt("StartView_writeConcertList.MayParticipate");
					}
					else if($userParticipation == 0) {
						$msg .= Lang::txt("StartView_writeConcertList.DontParticipate");
					}
						
					$this->writeItemContent("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption, $dataview, $partButtons,
							$msg, "", false, $userParticipation);
				}
			}
		}
		echo "</ul>\n";
	}
	
	private function writeTaskList() {
		$data = $this->getData()->adp()->getUserTasks();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("StartView_writeTaskList.noTasks") . "</li>\n";
		}
		else {
			// iterate over tasks
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["T"], $row["id"]);
				
				$liCaption = $row["title"];
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("StartView_writeTaskList.title"), $row["title"]);
				$dataview->addElement(Lang::txt("StartView_writeTaskList.description"), $row["description"]);
				$dataview->addElement(Lang::txt("StartView_writeTaskList.due_at"), Data::convertDateFromDb($row["due_at"]));
				$lnk = $this->modePrefix() . "taskComplete&id=" . $row["id"];
				$this->writeItemContent("T", $row["id"],"t" . $row["id"], $liCaption, $dataview, "", Lang::txt("start_markAsCompleted"), $lnk);
			}
		}
		echo "</ul>\n";
	}
	
	private function writeVoteList() {
		$data = $this->getData()->getVotesForUser();
		
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("StartView_writeVoteList.noVotes") . "</li>\n";
		}
		else {
			// iterate over votes
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["V"], $row["id"]);
				
				$liCaption = $row["name"];
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("StartView_writeVoteList.name"), $row["name"]);
				$dataview->addElement(Lang::txt("StartView_writeVoteList.end"), Data::convertDateFromDb($row["end"]));
				
				$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
				$this->writeItemContent("V", $row["id"], "v" . $row["id"], $liCaption, $dataview, "", Lang::txt("StartView_writeVoteList.vote"), $link);
			}
		}
		echo "</ul>\n";
	}
	
	private function writeReservationList() {
		$data = $this->getData()->getReservations();
	
		echo "<ul>\n";
		// iterate over votes
		foreach($data as $i => $row) {
			if($i < 1) continue;

			// add every item to the discussion
			array_push($this->objectListing["B"], $row["id"]);

			$liCaption =  Data::convertDateFromDb($row["begin"]) . " (" . $row["name"] . ")";
			$dataview = new Dataview();
			$dataview->addElement(Lang::txt("StartView_writeReservationList.name"), $row["name"]);
			
			// custom data
			$customFields = $this->getData()->getCustomFields('v', true);
			$customData = $this->getData()->getCustomData('v', $row["id"]);
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

			$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
			$this->writeItemContent("B", $row["id"], "b" . $row["id"], $liCaption, $dataview, $link, Lang::txt("vote"));
		}
		echo "</ul>\n";
	}

	private function writeAppointmentList($max = 0) {
		$data = $this->getData()->getAppointments();
		echo "<ul>\n";
		// iterate over votes
		foreach($data as $i => $row) {
			if($i < 1 || ($max > 0 && $i > $max)) {
				if($i > $max && $i == count($data)-1) {
					echo "<span style=\"font-style: italic;\"><a href=\"" . $this->modePrefix() . "start&appointments_max=0" . "\">" . Lang::txt("showAll") . "</a></span>";
				}
				continue;
			}
			
			$liCaption =  Data::convertDateFromDb($row["begin"]) . " (" . $row["name"] . ")";
			
			$dataview = new Dataview();
			$dataview->addElement(Lang::txt("StartView_writeAppointmentList.name"), $row["name"]);
			$dataview->addElement(Lang::txt("StartView_writeAppointmentList.locationname"), $row["locationname"]);
			
			// custom data
			$customFields = $this->getData()->getCustomFields('a', true);
			$customData = $this->getData()->getCustomData('a', $row["id"]);
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
			
			$this->writeItemContent('A', $row["id"], "a" . $row["id"], $liCaption, $dataview);
		}
		echo "</ul>\n";
	}
	
	/**
	 * Writes content for an item.
	 * @param String $otype Single character {R = Rehearsal, C = Concert, V = Vote, T = Task}, but T is not supported yet
	 * @param int $oid ID of the discussion object (see $otype).
	 * @param string $popboxid ID of the window.
	 * @param string $liCaption Caption of the Item (writing in blue).
	 * @param string $dataview Content of the popup window.
	 * @param string $participation optional: Buttons/content for the participation window.
	 * @param string $msg optional: Participation message, e.g. "Teilnahme angeben" or "Abstimmen".
	 * @param string $voteLink optional: Link to the voting-screen.
	 * @param boolean $partOver optional: Whether the participation deadline (approve_until) is over, by default false.
	 * @param int $participate -1 not set, 0 not, 1 yes, 2 maybe
	 */
	private function writeItemContent($otype, $oid, $popboxid, $liCaption, $dataview,
			$participation = "", $msg = "", $voteLink = "", $partOver = false, $participate=-1) {
			
			$participate_class = "";
			if($otype == "R" || $otype == "C") {
				$participate_class = "participate_" . $participate; 
			}
			?>
			<div class="<?php echo $participate_class; ?>">
			<?php
						
			if($otype == "C") {
				$href = $this->modePrefix() . "gigcard&id=" . $oid;
				echo "<a href=\"$href\" class=\"start_item_heading $participate_class\">$liCaption</a>";
			}
			else {
				?>	
				<a href="#" class="start_item_heading <?php echo $participate_class; ?>"><?php echo $liCaption; ?></a>
				<?php
			}
			
			if($msg != "" && $participation != "" && !$partOver) {
				echo $msg;
			}
			else if($msg != "" && $voteLink != "") {
				?>
				<a href="<?php echo $voteLink; ?>" class="participation"><?php echo $msg; ?></a>
				<?php
			}
			?>
			
			<?php 
			if($this->getData()->getSysdata()->getDynamicConfigParameter("discussion_on") == 1) {
				$commentCaption = Lang::txt("StartView_writeBoxListItem.discussion_on");
				if(!$this->getData()->hasObjectDiscussion($otype, $oid)) {
					$commentCaption = Lang::txt("StartView_writeBoxListItem.newDiscussion");
				}
				$participate_class = "discussion_" . $participate_class;
				echo '<a href="' . $this->modePrefix() . "discussion&otype=$otype&oid=$oid" . '" class="participation ' . $participate_class . '">';
				echo $commentCaption . '</a>';
			}
			?>
			
			<div id="<?php echo $popboxid; ?>" title="Details">
				<?php $dataview->write(); ?>
			</div>
			<div id="<?php echo $popboxid; ?>_participation" title="<?php echo Lang::txt("StartView_writeBoxListItem.participation")?>">
				<?php echo $participation; ?>
			</div>
			</div>
		<?php
	}
	
	public function voteOptions() {
		$this->checkID();
		if(!$this->getData()->canUserVote($_GET["id"])) {
			new BNoteError(Lang::txt("StartView_voteOptions.error"));
		}
		$vote = $this->getData()->getVote($_GET["id"]);
		Writing::h2($vote["name"]);
		
		echo "<form action=\"" . $this->modePrefix() . "saveVote&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		$options = $this->getData()->getOptionsForVote($_GET["id"]);
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
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMeNot"), 0);
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMe"), 1);
					$dd->addOption(Lang::txt("StartView_voteOptions.worksForMeMaybe"), 2);
					if($selected != -1) {
						$dd->setSelected($selected);
					}		
					else {
						$dd->setSelected(1);
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
			echo '<input type="submit" value="abstimmen" />' . "\n";
		}
		else {
			Writing::p(Lang::txt("StartView_voteOptions.noOptionsYet"));
		}
		echo "</form>\n";
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function saveVote() {
		$this->checkID();
		$this->getData()->saveVote($_GET["id"], $_POST);
		$msg = new Message(Lang::txt("StartView_saveVote.selectionSavedTitle"), Lang::txt("StartView_saveVote.selectionSavedMsg"));
		$msg->write();
		$this->backToStart();
	}
	
	public function taskComplete() {
		$this->checkID();
		$this->getData()->taskComplete($_GET["id"]);
		$msg = new Message(Lang::txt("StartView_taskComplete.taskCompletedTitle"), Lang::txt("StartView_taskComplete.taskCompletedMsg"));
		$msg->write();
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
	
	public function rehearsalParticipants() {
		$rehearsal = $this->getData()->getRehearsal($_GET["id"]);
		Writing::h2(Lang::txt("StartView_rehearsalParticipants.participantsOfRehearsal", array(Data::convertDateFromDb($rehearsal["begin"]))));
		
		$parts = $this->getData()->getRehearsalParticipants($_GET["id"]);
		$table = new Table($parts);
		$table->renameHeader("name", Lang::txt("StartView_rehearsalParticipants.name"));
		$table->renameHeader("surname", Lang::txt("StartView_rehearsalParticipants.surname"));
		$table->renameHeader("nickname", Lang::txt("StartView_rehearsalParticipants.nickname"));
		$table->write();
	}
	
	public function rehearsalParticipantsOptions() {
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
	
	private function writeUpdateList() {
		$maxNumUpdates = $this->getData()->getSysdata()->getDynamicConfigParameter("updates_show_max");
		if($maxNumUpdates <= 0) $maxNumUpdates = 1;
		
		$comments = $this->getData()->getUserUpdates($this->objectListing);
		
		if(count($comments) == 1) {
			echo "<p>" . Lang::txt("StartView_writeUpdateList.noNews") . "</p>\n";
			return;
		}
		
		foreach($comments as $i => $comment) {
			if($i == 0) continue; // header
			
			$objTitle = $this->getData()->getObjectTitle($comment["otype"], $comment["oid"]); 
			$objLink = $this->modePrefix() . "discussion&otype=" . $comment["otype"] . "&oid=" . $comment["oid"];
			
			$contact = $this->getData()->getSysdata()->getUsersContact($comment["author"]);
			$author = $contact["name"] . " " . $contact["surname"] . " - " . Data::convertDateFromDb($comment["created_at"]);
			
			$message = urldecode($comment["message"]);
			if(strlen($message) > 140) {
				$message = substr($message, 0, 140) . "...";
			}
			?>
			<div class="start_update_box">
			<a href="<?php echo $objLink; ?>"><?php echo $objTitle; ?></a><br/>
			<span class="start_update_box_author"><?php echo $author; ?></span><br/>
				<p>
				<?php echo $message; ?>
				</p>
			</div>
			<?php
		}
	}
	
	public function discussion() {
		if($this->getData()->getSysdata()->getDynamicConfigParameter("discussion_on") != 1) {
			new BNoteError(Lang::txt("StartView_discussion.Deactivated"));
		}
		if(!isset($_GET["otype"]) || !isset($_GET["oid"])) {
			new BNoteError(Lang::txt("StartView_discussion.Reason"));
		}
		
		Writing::h2(Lang::txt("StartView_discussion.discussion") . ": " . $this->getData()->getObjectTitle($_GET["otype"], $_GET["oid"]));
		
		// show comments
		$comments = $this->getData()->getDiscussion($_GET["otype"], $_GET["oid"]);
		
		if(count($comments) == 1) {
			new Message(Lang::txt("StartView_discussion.noComments"), Lang::txt("StartView_discussion.noCommentsInDiscussion"));
		}
		else {
			foreach($comments as $i => $comment) {
				if($i == 0) continue; // header
				
				$author = $comment["author"] . " / " . Data::convertDateFromDb($comment["created_at"]);
				?>
				<div class="start_update_box">
					<span class="start_update_box_author"><?php echo $author; ?></span><br/>
					<p>
					<?php echo urldecode($comment["message"]); ?>
					</p>
				</div>
				<?php
			}
		}
		
		// add comment form
		$submitLink = $this->modePrefix() . "addComment&otype=" . $_GET["otype"] . "&oid=" . $_GET["oid"];
		$form = new Form(Lang::txt("StartView_discussion.addComment"), $submitLink);
		$form->addElement("", new Field("message", "", FieldType::TEXT));
		$form->changeSubmitButton(Lang::txt("StartView_discussion.Submit"));
		$form->write();
	}
	
	protected function discussionOptions() {
		$this->backToStart();
	}
	
	public function addComment() {
		// save comment
		$this->getData()->addComment($_GET["otype"], $_GET["oid"]);
		
		// show discussion again
		$this->discussion();
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
		$c = $concertData->findByIdNoRef($_GET["id"]);
		$custom = $concertData->getCustomData($_GET["id"]);
		$loc = $concertData->adp()->getLocation($c["location"]);
		
		// concert details
		Writing::h1($c["title"]);
		
		Writing::p($c["notes"]);
		?>
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
		
		<h2><?php echo Lang::txt("StartView_gigcard.organisation"); ?></h2>
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
			
			Writing::h2(Lang::txt("StartView_gigcard.program") . ": " . $prg["name"]);
			
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
	
}

?>
