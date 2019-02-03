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
		
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", Lang::txt("start_calendarExport"));
		$ical->addIcon("save");
		$ical->write();
		$this->buttonSpace();
		
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
		
		$calSubsc = new Link($webcal_link, Lang::txt("start_calendarSubscribe"));
		$calSubsc->addIcon("calendar");
		$calSubsc->write();
	}
	
	function start() {
		$news = $this->getData()->getNews();
		if($news != "" || $this->getData()->getSysdata()->gdprOk() == 0) {
			?>
			<div class="start_box_news">
				<?php 
				// GDPR
				if($this->getData()->getSysdata()->gdprOk() == 0) {
					?>
					<div class="start_box_heading"><?php echo Lang::txt("gdprStartHeading"); ?></div>
					<div class="start_box_content">
						<span class="warning">
							<?php echo Lang::txt("gdprOkMessage"); ?>
						</span>
						<a href="?mod=terms" target="_blank"><?php echo Lang::txt("gdprLink"); ?></a>
						<br/>
						<?php 
						$yes = new Link($this->modePrefix() . "gdprOk&accept=1", Lang::txt("gdprOkYes"));
						$yes->addIcon("checkmark");
						$yes->write();
						$no = new Link($this->modePrefix() . "gdprOk&accept=0", Lang::txt("gdprOkNo"));
						$no->addIcon("cancel");
						$no->write();
						?>
					</div>
					<?php
					// do not show anything on the start page unless the user has selected (ok)
					exit(1);
				}
				
				?>
				<div class="start_box_heading"><?php echo Lang::txt("news"); ?></div>
				<div class="start_box_content">
					<?php
					// news
					echo $news;
					
					// warning
					if(($this->getData()->getSysdata()->isUserSuperUser() || $this->getData()->getSysdata()->isUserAdmin())
							&& $this->getController()->usersToIntegrate()) {
						$this->verticalSpace();
						echo '<span class="warning">' . Lang::txt("nonIntegratedUsers") . '</span>';
					}
					
					// check whether autologin is active and user is admin
					if($this->getData()->getSysdata()->isUserAdmin() && $this->getData()->getSysdata()->isAutologinActive()) {
						$this->verticalSpace();
						echo '<span class="warning">' . Lang::txt("autoActivation") . '</span>';
					}
					?>
				</div>
			</div>
			<?php 
		}
		?>
		<div class="start_box_table">
			<div class="start_box_row">
				<div class="start_box" style="padding-right: 10px;">
					<div class="start_box_heading"><?php echo Lang::txt("rehearsals"); ?></div>
					<div class="start_box_content">
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
				
				<div class="start_box">
					<div class="start_box_heading"><?php echo Lang::txt("concerts"); ?></div>
					<div class="start_box_content">
						<?php $this->writeConcertList(); ?>
					</div>
					
					<?php
					if($this->getData()->hasReservations()) {
					?>
					<div class="start_box_heading"><?php echo Lang::txt("reservations"); ?></div>
					<div class="start_box_content">
						<?php $this->writeReservationList(); ?>
					</div>	
					<?php
					}
					?>
					
					<?php
					if($this->getData()->hasAppointments()) {
					?>
					<div class="start_box_heading"><?php echo Lang::txt("appointments"); ?></div>
					<div class="start_box_content">
						<?php $this->writeAppointmentList(); ?>
					</div>	
					<?php
					}
					?>
					
					<div class="start_box_heading"><?php echo Lang::txt("votes"); ?></div>
					<div class="start_box_content">
						<?php $this->writeVoteList(); ?>
					</div>
					
					<div class="start_box_heading"><?php echo Lang::txt("tasks"); ?></div>
					<div class="start_box_content">
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
					<div class="start_box" style="max-width: 250px; padding-left: 10px;">
						<div class="start_box_heading"><?php echo Lang::txt("discussions"); ?></div>
						<div class="start_box_content">
							<?php $this->writeUpdateList(); ?>
						</div>
					</div>
					<?php 
				}
				?>
			</div>
		</div>
		<?php
	}
	
	public function askReason($type) {
		$form = new Form(Lang::txt("start_pleaseGiveReason"),
				$this->modePrefix() . "saveParticipation&obj=$type&id=" . $_GET["id"] . "&action=" . $_GET["action"]);
		$form->addElement("", new Field("explanation", "", FieldType::CHAR));
		$form->write();
	}
	
	private function writeRehearsalList($max = 0) {
		$data = $this->getData()->getUsersRehearsals();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("start_noRehearsalsScheduled") . "</li>\n";
		}
		else {
			// iterate over rehearsals
			for($i = 1; $i < count($data); $i++) {
				
				// add every item to the discussion
				array_push($this->objectListing["R"], $data[$i]["id"]);
				
				// limit the number of rehearsals if necessary
				if($max > 0 && $i > $max) {
					if($i == $max+1) {
						echo "<span style=\"font-style: italic;\">" . Lang::txt("start_showNumRehearsals", array($max)) . "</span>\n";
						echo "<br/><a href=\"" . $this->modePrefix() . "start&max=0" . "\">" . Lang::txt("showAll") . "</a>";
					}					
					continue; // cannot break due to discussion addition of objects
				}
				
				$liCaption = Data::convertDateFromDb($data[$i]["begin"]);
				$liCaption = Data::getWeekdayFromDbDate($data[$i]["begin"]) . ", " . $liCaption;
				if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
					$liCaption .= "<br/>bis " . Data::getWeekdayFromDbDate($data[$i]["end"]) . ", " . Data::convertDateFromDb($data[$i]["end"]);
				}
				$liCaption = "<span class=\"start_rehearsal_title\">" . $liCaption . "</span>";
				
				// create details for each rehearsal
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("begin"), Data::convertDateFromDb($data[$i]["begin"]));
				$dataview->addElement(Lang::txt("end"), Data::convertDateFromDb($data[$i]["end"]));
				$loc = $data[$i]["name"];
				$dataview->addElement(Lang::txt("location"), $this->formatAddress($data[$i]));
				if(isset($data[$i]["conductor"]) && $data[$i]["conductor"] != null) {
					$dataview->addElement(Lang::txt("conductor"), $this->getData()->adp()->getConductorname($data[$i]["conductor"]));
				}
				if(isset($data[$i]["groups"])) {
					$groupNames = array_column($data[$i]["groups"], "name");
					$dataview->addElement(Lang::txt("groups"), join(", ", $groupNames));
				}
				
				// custom data
				$customFields = $this->getData()->getCustomFields('r', true);
				$customData = $this->getData()->getCustomData('r', $data[$i]["id"]);
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
				
				if($data[$i]["notes"] != "") {
					$dataview->addElement(Lang::txt("comment"), $data[$i]["notes"]);
				}
				
				$songs = $this->getData()->getSongsForRehearsal($data[$i]["id"]);
				if(count($songs) > 1) {
					$strSongs = "";
					for($j = 1; $j < count($songs); $j++) {
						if($j > 1) $strSongs .= ", ";
						$strSongs .= $songs[$j]["title"];
						if($songs[$j]["notes"] != "") $strSongs .= " (" . $songs[$j]["notes"] . ")";
					}
					$dataview->addElement(Lang::txt("start_songsToPractise"), $strSongs);
				}
				
				// add button to show participants
				$participantsButton = new Link($this->modePrefix() . "rehearsalParticipants&id=" . $data[$i]["id"], "Teilnehmer anzeigen");
				$dataview->addElement(Lang::txt("participants"), $participantsButton->toString());
				
				// show three buttons to participate/maybe/not in rehearsal
				$partButtonSpace = "<br/><br/>";
				$partButtons = "";
				$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=rehearsal&id=" . $data[$i]["id"] . "&action=";
				
				$partBtn = new Link($partLinkPrefix . "yes", Lang::txt("start_iParticipate"));
				$partBtn->addIcon("checkmark");
				$partButtons .= $partBtn->toString() . $partButtonSpace;
				
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
					$mayBtn = new Link($partLinkPrefix . "maybe", Lang::txt("start_iMightParticipate"));
					$mayBtn->addIcon("yield");
					$partButtons .= $mayBtn->toString() . $partButtonSpace;
				}
				
				$noBtn = new Link($partLinkPrefix . "no", Lang::txt("start_iDoNotParticipate"));
				$noBtn->addIcon("cancel");
				$partButtons .= $noBtn->toString();
				
				$userParticipation = $this->getData()->doesParticipateInRehearsal($data[$i]["id"]);
				if($userParticipation < 0) {
					if($data[$i]["approve_until"] == "" || Data::compareDates($data[$i]["approve_until"], Data::getDateNow()) > 0) {
						$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("start_setParticipation"));
					}
					else {
						$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("start_participationOver"), "", true);
					}
				}
				else {
					$msg = "";
					if($userParticipation == 1) {
						$msg .= Lang::txt("start_rehearsalParticipate");
					}
					else if($userParticipation == 2) {
						$msg .= Lang::txt("start_rehearsalMaybeParticipate");
					}
					else if($userParticipation == 0) {
						$msg .= Lang::txt("start_rehearsalNotParticipate");
					}
					
					$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption, $dataview, $partButtons,
							$msg, "", false, $userParticipation);
				}
			}
		}
		echo "</ul>\n";
	}
	
	private function writeConcertList() {
		$data = $this->getData()->getUsersConcerts();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("start_noConcertsScheduled") . "</li>\n";
		}
		else {
			// iterate over concerts
			foreach($data as $i => $row) {
				if($i == 0) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["C"], $row["id"]);
				
				$liCaption = Data::convertDateFromDb($row["begin"]);
				$liCaption = Data::getWeekdayFromDbDate($row["begin"]) . ", " . $liCaption;
				$liCaption = "<span class=\"start_concert_title\">" . $row["title"] . "</span><br/>" . $liCaption . "";
				
				$dataview = new Dataview();
				
				// show three buttons to participate/maybe/not in concert
				$partButtonSpace = "<br/><br/>";
				$partButtons = "";
				$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=concert&id=" . $data[$i]["id"] . "&action=";
				
				$partBtn = new Link($partLinkPrefix . "yes", Lang::txt("start_iPlay"));
				$partBtn->addIcon("checkmark");
				$partButtons .= $partBtn->toString() . $partButtonSpace;
				
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
					$mayBtn = new Link($partLinkPrefix . "maybe", Lang::txt("start_iMayPlay"));
					$mayBtn->addIcon("yield");
					$partButtons .= $mayBtn->toString() . $partButtonSpace;
				}
				
				$noBtn = new Link($partLinkPrefix . "no", Lang::txt("start_iDontPlay"));
				$noBtn->addIcon("cancel");
				$partButtons .= $noBtn->toString();
				
				$userParticipation = $this->getData()->doesParticipateInConcert($data[$i]["id"]);
				if($userParticipation < 0) {
					if($data[$i]["approve_until"] == "" || Data::compareDates($data[$i]["approve_until"], Data::getDateNow()) > 0) {
						$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("start_setParticipation"));
					}
					else {
						$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, Lang::txt("start_participationOver"), "", true);
					}
				}
				else {
					$msg = "";
					if($userParticipation == 1) {
						$msg .= Lang::txt("start_youParticipate");
					}
					else if($userParticipation == 2) {
						$msg .= Lang::txt("start_youMayParticipate");
					}
					else if($userParticipation == 0) {
						$msg .= Lang::txt("start_youDontParticipate");
					}
						
					$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption, $dataview, $partButtons,
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
			echo "<li>" . Lang::txt("start_noTasks") . "</li>\n";
		}
		else {
			// iterate over tasks
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["T"], $row["id"]);
				
				$liCaption = $row["title"];
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("title"), $row["title"]);
				$dataview->addElement(Lang::txt("description"), $row["description"]);
				$dataview->addElement(Lang::txt("dueAt"), Data::convertDateFromDb($row["due_at"]));
				$lnk = $this->modePrefix() . "taskComplete&id=" . $row["id"];
				$this->writeBoxListItem("T", $row["id"],"t" . $row["id"], $liCaption, $dataview, "", Lang::txt("start_markAsCompleted"), $lnk);
			}
		}
		echo "</ul>\n";
	}
	
	private function writeVoteList() {
		$data = $this->getData()->getVotesForUser();
		
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>" . Lang::txt("start_noVotes") . "</li>\n";
		}
		else {
			// iterate over votes
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["V"], $row["id"]);
				
				$liCaption = $row["name"];
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("name"), $row["name"]);
				$dataview->addElement(Lang::txt("start_endOfVote"), Data::convertDateFromDb($row["end"]));
				
				$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
				$this->writeBoxListItem("V", $row["id"], "v" . $row["id"], $liCaption, $dataview, "", Lang::txt("vote"), $link);
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
			$dataview->addElement(Lang::txt("name"), $row["name"]);
			
			// custom data
			$customFields = $this->getData()->getCustomFields('v', true);
			$customData = $this->getData()->getCustomData('v', $row["id"]);
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

			$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
			$this->writeBoxListItem("B", $row["id"], "b" . $row["id"], $liCaption, $dataview, "", Lang::txt("vote"));
		}
		echo "</ul>\n";
	}

	private function writeAppointmentList() {
		$data = $this->getData()->getAppointments();
		echo "<ul>\n";
		// iterate over votes
		foreach($data as $i => $row) {
			if($i < 1) continue;
			
			$liCaption =  Data::convertDateFromDb($row["begin"]) . " (" . $row["name"] . ")";
			
			$dataview = new Dataview();
			$dataview->addElement(Lang::txt("name"), $row["name"]);
			$dataview->addElement(Lang::txt("location"), $row["locationname"]);
			
			// custom data
			$customFields = $this->getData()->getCustomFields('a', true);
			$customData = $this->getData()->getCustomData('a', $row["id"]);
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
			
			$this->writeBoxListItem('A', $row["id"], "a" . $row["id"], $liCaption, $dataview);
		}
		echo "</ul>\n";
	}
	
	/**
	 * Writes one item to the start page.
	 * @param char $otype {R = Rehearsal, C = Concert, V = Vote, T = Task}, but T is not supported yet
	 * @param int $oid ID of the discussion object (see $otype).
	 * @param string $popboxid ID of the popup window.
	 * @param string $liCaption Caption of the Item (writing in blue).
	 * @param string $dataview Content of the popup window.
	 * @param string $participation optional: Buttons/content for the participation window.
	 * @param string $msg optional: Participation message, e.g. "Teilnahme angeben" or "Abstimmen".
	 * @param string $voteLink optional: Link to the voting-screen.
	 * @param boolean $partOver optional: Whether the participation deadline (approve_until) is over, by default false.
	 * @param int $participate -1 not set, 0 not, 1 yes, 2 maybe
	 */
	private function writeBoxListItem($otype, $oid, $popboxid, $liCaption, $dataview,
			$participation = "", $msg = "", $voteLink = "", $partOver = false, $participate=-1) {
			
			$participate_class = "";
			if($otype == "R" || $otype == "C") {
				$participate_class = "participate_" . $participate;
				echo "<li>";
				echo "<div class=\"" . $participate_class . "\">"; 
			} else {
				echo "<li>";
				echo "<div>";
			}
						
			if($otype == "C") {
				$href = $this->modePrefix() . "gigcard&id=" . $oid;
				echo "<a href=\"$href\" class=\"start_item_heading $participate_class\">$liCaption</a>";
			}
			else {
				?>	
				<a href="#" class="start_item_heading <?php echo $participate_class; ?>" onClick="$(function() { 
					$('#<?php echo $popboxid; ?>').dialog({ 
						width: 500,
						 modal: true,
						 resizable: false,
						 draggable: false,
						 }); 
					});
	
					"><?php echo $liCaption; ?></a>
				<?php
			}
			
			if($msg != "" && $participation != "" && !$partOver) {
				?>
				<br/>
				<a href="#"
				   class="participation <?php echo $participate_class; ?>"
				   onClick="$(function() {
					    $('#<?php echo $popboxid; ?>_participation').dialog({
							 	width: 500,
								 modal: true,
								resizable: false,
								 draggable: false,
							  }); 
						});"><?php echo $msg; ?></a>
				<?php
			}
			else if($msg != "" && $participation != "" && $partOver) {
				?>
				<br/>
				<span><?php echo $msg; ?></span>
				<?php
			}
			else if($msg != "" && $voteLink != "") {
				?>
				<br/>
				<a href="<?php echo $voteLink; ?>" class="participation"><?php echo $msg; ?></a>
				<?php
			}
			?>
			
			<?php 
			if($this->getData()->getSysdata()->getDynamicConfigParameter("discussion_on") == 1) {
				$commentCaption = Lang::txt("discussion");
				if(!$this->getData()->hasObjectDiscussion($otype, $oid)) {
					$commentCaption = Lang::txt("start_newDiscussion");
				}
				$participate_class = "discussion_" . $participate_class;
				echo '<br/><a href="' . $this->modePrefix() . "discussion&otype=$otype&oid=$oid" . '" class="participation ' . $participate_class . '">';
				echo $commentCaption . '</a>';
			}
			?>
			
			<div id="<?php echo $popboxid; ?>" title="Details" style="display: none;">
				<?php $dataview->write(); ?>
			</div>
			<div id="<?php echo $popboxid; ?>_participation" title="<?php echo Lang::txt("start_participation")?>" style="display: none;">
				<?php echo $participation; ?>
			</div>
			</div>
			</li>
		<?php
	}
	
	public function voteOptions() {
		$this->checkID();
		if(!$this->getData()->canUserVote($_GET["id"])) {
			new BNoteError(Lang::txt("start_youCannotParticipateVote"));
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
				$selected = $this->getData()->getSelectedOptionsForUser($options[$i]["id"], $_SESSION["user"]);
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == "1"
						&& $vote["is_multi"] == 1) {
					$dd = new Dropdown($options[$i]["id"]);
					$dd->addOption(Lang::txt("start_worksForMeNot"), 0);
					$dd->addOption(Lang::txt("start_worksForMe"), 1);
					$dd->addOption(Lang::txt("start_worksForMeMaybe"), 2);
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
			Writing::p(Lang::txt("start_noOptionsYet"));
		}
		echo "</form>\n";
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function saveVote() {
		$this->checkID();
		$this->getData()->saveVote($_GET["id"], $_POST);
		$msg = new Message(Lang::txt("start_selectionSavedTitle"), Lang::txt("start_selectionSavedMsg"));
		$msg->write();
		$this->backToStart();
	}
	
	public function taskComplete() {
		$this->checkID();
		$this->getData()->taskComplete($_GET["id"]);
		$msg = new Message(Lang::txt("start_taskCompletedTitle"), Lang::txt("start_taskCompletedMsg"));
		$msg->write();
		$this->backToStart();
	}
	
	public function viewProgram() {
		$this->checkID();
		$titles = $this->getData()->getProgramTitles($_GET["id"]);
		
		Writing::h2("Programm");
		
		// Enhancement #127
		$konzertMod = $this->getData()->getSysdata()->getModuleId(Lang::txt("concerts"));
		if($this->getData()->getSysdata()->userHasPermission($konzertMod)) {
			$editLink = new Link("?mod=" . $konzertMod . "&mode=programs&sub=view&id=" . $_GET["id"], Lang::txt("start_editProgram"));
			$editLink->addIcon("edit");
			$editLink->write();
			$this->verticalSpace();
		}
		
		$table = new Table($titles);		
		$table->renameHeader("rank", Lang::txt("start_rank"));
		$table->renameHeader("title", Lang::txt("start_title"));
		$table->renameHeader("composer", Lang::txt("start_composer"));
		$table->renameHeader("notes", Lang::txt("start_notes"));
		
		$table->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function rehearsalParticipants() {
		$rehearsal = $this->getData()->getRehearsal($_GET["id"]);
		Writing::h2(Lang::txt("start_participantsOfRehearsal", array(Data::convertDateFromDb($rehearsal["begin"]))));
		
		$parts = $this->getData()->getRehearsalParticipants($_GET["id"]);
		$table = new Table($parts);
		$table->renameHeader("name", Lang::txt("firstname"));
		$table->renameHeader("surname", Lang::txt("surname"));
		$table->renameHeader("nickname", Lang::txt("nickname"));
		$table->write();
	}
	
	public function rehearsalParticipantsOptions() {
		$this->backToStart();
	}
	
	public function concertParticipants() {
		$concert = $this->getData()->getConcert($_GET["id"]);
		Writing::h2(Lang::txt("start_participantsOfConcert", array(Data::convertDateFromDb($concert["begin"]))));
		
		$parts = $this->getData()->getConcertParticipants($_GET["id"]);
		$table = new Table($parts);
		$table->renameHeader("name", Lang::txt("firstname"));
		$table->renameHeader("surname", Lang::txt("surname"));
		$table->renameHeader("nickname", Lang::txt("nickname"));
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
			echo "<p>" . Lang::txt("start_noNews") . "</p>\n";
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
			new BNoteError(Lang::txt("start_discussionsDeactivated"));
		}
		if(!isset($_GET["otype"]) || !isset($_GET["oid"])) {
			new BNoteError(Lang::txt("start_giveDiscussionReason"));
		}
		
		Writing::h2(Lang::txt("discussion") . ": " . $this->getData()->getObjectTitle($_GET["otype"], $_GET["oid"]));
		
		// show comments
		$comments = $this->getData()->getDiscussion($_GET["otype"], $_GET["oid"]);
		
		if(count($comments) == 1) {
			new Message(Lang::txt("start_noComments"), Lang::txt("start_noCommentsInDiscussion"));
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
		$form = new Form(Lang::txt("start_addComment"), $submitLink);
		$form->addElement("", new Field("message", "", FieldType::TEXT));
		$form->changeSubmitButton(Lang::txt("start_sendComment"));
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
		$loc = $concertData->getLocation($c["location"]);
		
		// concert details
		Writing::h1($c["title"]);
		
		Writing::p($c["notes"]);
		?>
		<table>
			<tbody>
				<tr>
					<td>Datum/Zeit</td>
					<td><?php 
					echo Data::convertDateFromDb($c["begin"]) . " - ";
					echo Data::convertDateFromDb($c["end"]);
					?></td>
				</tr>
				<tr>
					<td>Treffpunkt</td>
					<td><?php echo Data::convertDateFromDb($c["meetingtime"]); ?></td>
				</tr>
				<tr>
					<td>Zusage bis</td>
					<td><?php echo Data::convertDateFromDb($c["approve_until"]); ?></td>
				</tr>
				<tr>
					<td>Veranstalter</td>
					<td><?php 
					if($c["organizer"]) {
						echo $c["organizer"];
					}
					?></td>
				</tr>
				<tr>
					<td>Ort</td>
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
					<td>Kontakt</td>
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
					<td>Unterkunft</td>
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
		
		<h2>Organisation</h2>
		<table>
			<tbody>
				<tr>
					<td>Besetzung</td>
					<td><?php 
					$groups = $concertData->getConcertGroups($c["id"]);
					$groupNames = Database::flattenSelection($groups, "name");
					echo join(", ", $groupNames);
					?></td>
				</tr>
				<tr>
					<td>Programm</td>
					<td><?php 
					if($c["program"]) {
						$prg = $concertData->getProgram($c["program"]);
						echo $prg["name"];
					}
					?></td>
				</tr>
				<tr>
					<td>Outfit</td>
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
	}
	
	function gigcardOptions() {
		$this->backToStart();
		
		$prt = new Link("javascript:print()", Lang::txt("print"));
		$prt->addIcon("printer");
		$prt->write();
		
		// show participants
		$participantsButton = new Link($this->modePrefix() . "concertParticipants&id=" . $_GET["id"], Lang::txt("participants"));
		$participantsButton->addIcon("mitspieler");
		$participantsButton->write();
		
	}
	
}

?>
