<?php

/**
 * View for start module.
 * @author matti
 *
 */
class StartView extends AbstractView {
	
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
	
	private function startOptions() {
		// Calendar Exports
		$userExt = "?user=" . urlencode($this->getData()->adp()->getLogin());
		
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", Lang::txt("start_calendarExport"));
		$ical->addIcon("save");
		$ical->write();
		$this->buttonSpace();
		
		$systemUrl = $this->getData()->getSysdata()->getSystemURL();
		if($systemUrl != "") {
			if(!Data::endsWith($systemUrl, "/")) $systemUrl .= "/";
			if(Data::startsWith($systemUrl, "http://")) $systemUrl = substr($systemUrl, 7);
			else if(Data::startsWith($systemUrl, "https://")) $systemUrl = substr($systemUrl, 8);
		}
		else {
			$systemUrl = $_SERVER["HTTP_HOST"] . str_replace("main.php", "", $_SERVER["SCRIPT_NAME"]);
		}
		$calSubsc = new Link("webcal://" . $systemUrl . $GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", Lang::txt("start_calendarSubscribe"));
		$calSubsc->addIcon("calendar");
		$calSubsc->write();
	}
	
	function start() {
		$news = $this->getData()->getNews();
		if($news != "") {
			?>
			<div class="start_box_news">
				<div class="start_box_heading"><?php echo Lang::txt("news"); ?></div>
				<div class="start_box_content">
					<?php
					// news
					echo $news;
					
					// warning
					if(($this->getData()->getSysdata()->isUserSuperUser() || $this->getData()->getSysdata()->isUserMemberGroup(1))
							&& $this->getController()->usersToIntegrate()) {
						$this->verticalSpace();
						echo '<span class="warning">';
						echo Lang::txt("nonIntegratedUsers");
						echo '</span>';
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
				$dataview->addElement(Lang::txt("location"), $this->buildAddress($data[$i]));
				
				if($data[$i]["notes"] != "") {
					$dataview->addElement(Lang::txt("comment"), $data[$i]["notes"]);
				}
				
				$songs = $this->getData()->getSongsForRehearsal($data[$i]["id"]);
				if(count($songs) > 2) {
					$strSongs = "";
					for($j = 1; $j < count($songs); $j++) {
						if($j > 1) $strSongs .= ", ";
						$strSongs .= $songs[$j]["title"];
						if($songs[$j]["notes"] != "") $strSongs .= " (" . $songs[$j]["notes"] . ")";
					}
					$dataview->addElement("start_songsToPractise", $strSongs);
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
				array_push($this->objectListing["C"], $data[$i]["id"]);
				
				$liCaption = Data::convertDateFromDb($row["begin"]);
				$liCaption = Data::getWeekdayFromDbDate($row["begin"]) . ", " . $liCaption;
				$liCaption = "<span class=\"start_concert_title\">" . $row["title"] . "</span><br/>" . $liCaption . "";
				
				// concert details
				$dataview = new Dataview();
				$dataview->addElement(Lang::txt("title"), $row["title"]);
				$dataview->addElement(Lang::txt("begin"), Data::convertDateFromDb($row["begin"]));
				$dataview->addElement(Lang::txt("end"), Data::convertDateFromDb($row["end"]));
				$loc = $this->buildAddress($row);
				if($loc != "") $loc = $row["location_name"] . " - " . $loc;
				else $loc = $row["location_name"];
				$dataview->addElement(Lang::txt("location"), $loc);
				$contact = $row["contact_name"];
				if($row["contact_phone"] != "") $contact .= "<br/>" . $row["contact_phone"];
				if($contact != "" && $row["contact_email"] != "") $contact .= "<br/>" . $row["contact_email"];
				if($contact != "" && $row["contact_web"] != "") $contact .= "<br/>" . $row["contact_web"];
				$dataview->addElement(Lang::txt("contact"), $contact);
				if($row["program_name"] != "") {
					$program = $row["program_name"];
					if($row["program_notes"] != "") $program .= " (" . $row["program_notes"] . ")";
					$viewProg = new Link($this->modePrefix() . "viewProgram&id=" . $row["program_id"], Lang::txt("start_viewProgram"));
					$program .= "<br/><br/>" . $viewProg->toString();
					$dataview->addElement(Lang::txt("program"), $program);
				}
				
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
				$this->writeBoxListItem("T", $row["id"],"t" + $row["id"], $liCaption, $dataview, "", Lang::txt("start_markAsCompleted"), $lnk);
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
				$this->writeBoxListItem("V", $row["id"], "v" + $row["id"], $liCaption, $dataview, "", Lang::txt("vote"), $link);
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

			$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
			$this->writeBoxListItem("B", $row["id"], "b" + $row["id"], $liCaption, $dataview, "", Lang::txt("vote"));
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
			$participation = "", $msg = "", $voteLink = "", $partOver = false, $participate=9) {
		
			$participate_class = "";
			if($otype == "R" || $otype == "C") {
				$participate_class = "participate_" . $participate;
			}
		?>
		<li>
			<a href="#" class="start_item_heading <?php echo $participate_class; ?>" onClick="$(function() { $('#<?php echo $popboxid; ?>').dialog({ width: 400 }); });"><?php echo $liCaption; ?></a>
			<?php
			if($msg != "" && $participation != "" && !$partOver) {
				?>
				<br/>
				<a href="#"
				   class="participation <?php echo $participate_class; ?>"
				   onClick="$(function() { $('#<?php echo $popboxid; ?>_participation').dialog({ width: 400 }); });"><?php echo $msg; ?></a>
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
			<?php $this->verticalSpace(); ?>
		</li>
		<?php
	}
	
	public function voteOptions() {
		$this->checkID();
		if(!$this->getData()->canUserVote($_GET["id"])) {
			new Error(Lang::txt("start_youCannotParticipateVote"));
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
		$table->write();
	}
	
	public function rehearsalParticipantsOptions() {
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
			new Error(Lang::txt("start_discussionsDeactivated"));
		}
		if(!isset($_GET["otype"]) || !isset($_GET["oid"])) {
			new Error(Lang::txt("start_giveDiscussionReason"));
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
}

?>
