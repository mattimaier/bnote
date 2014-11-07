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
		"T" => array()	
	);
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function showOptions() {
		if(!isset($_GET["sub"])) {
			$this->startOptions();
		}
		else {
			$optFunc = $_GET["sub"] . "Options";
			$this->$optFunc();
		}
	}
	
	private function startOptions() {
		// Calendar Exports
		$userExt = "?user=" . urlencode($this->getData()->adp()->getLogin());
		
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", "Kalender Export");
		$ical->addIcon("arrow_down");
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
		$calSubsc = new Link("webcal://" . $systemUrl . $GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", "Kalender abonnieren");
		$calSubsc->addIcon("arrow_right");
		$calSubsc->write();
	}
	
	function start() {
		$news = $this->getData()->getNews();
		if($news != "") {
			?>
			<div class="start_box_news">
				<div class="start_box_heading">Nachrichten</div>
				<div class="start_box_content">
					<?php
					// news
					echo $news;
					
					// warning
					if(($this->getData()->getSysdata()->isUserSuperUser() || $this->getData()->getSysdata()->isUserMemberGroup(1))
							&& $this->getController()->usersToIntegrate()) {
						$this->verticalSpace();
						echo '<span class="warning">';
						echo 'Es gibt inaktive oder nicht integrierte Nutzer. Bitte gehe auf Kontakte/Einphasung und kümmere dich darum.';
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
					<div class="start_box_heading">Proben</div>
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
					<div class="start_box_heading">Konzerte</div>
					<div class="start_box_content">
						<?php $this->writeConcertList(); ?>
					</div>
					
					<div class="start_box_heading">Abstimmungen</div>
					<div class="start_box_content">
						<?php $this->writeVoteList(); ?>
					</div>
					
					<div class="start_box_heading">Aufgaben</div>
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
						<div class="start_box_heading">Diskussionen</div>
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
		$form = new Form("Bitte gebe einen Grund an.",
				$this->modePrefix() . "saveParticipation&obj=$type&id=" . $_GET["id"] . "&action=" . $_GET["action"]);
		$form->addElement("", new Field("explanation", "", FieldType::CHAR));
		$form->write();
	}
	
	private function writeRehearsalList($max = 0) {
		$data = $this->getData()->getUsersRehearsals();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>Keine Proben angesagt.</li>\n";
		}
		else {
			// iterate over rehearsals
			for($i = 1; $i < count($data); $i++) {
				
				// add every item to the discussion
				array_push($this->objectListing["R"], $data[$i]["id"]);
				
				// limit the number of rehearsals if necessary
				if($max > 0 && $i > $max) {
					if($i == $max+1) {
						echo "<span style=\"font-style: italic;\">Es werden nur die ersten $max Proben angezeigt.</span>\n";
						echo "<br/><a href=\"" . $this->modePrefix() . "start&max=0" . "\">Alle anzeigen</a>";
					}					
					continue; // cannot break due to discussion addition of objects
				}
				
				$liCaption = Data::convertDateFromDb($data[$i]["begin"]) . " Uhr";
				$liCaption = Data::getWeekdayFromDbDate($data[$i]["begin"]) . ", " . $liCaption;
				if($this->getData()->getSysdata()->getDynamicConfigParameter("rehearsal_show_length") == 0) {
					$liCaption .= "<br/>bis " . Data::getWeekdayFromDbDate($data[$i]["end"]) . ", " . Data::convertDateFromDb($data[$i]["end"]) . " Uhr";
				}
				
				// create details for each rehearsal
				$dataview = new Dataview();
				$dataview->addElement("Beginn", Data::convertDateFromDb($data[$i]["begin"]) . " Uhr");
				$dataview->addElement("Ende", Data::convertDateFromDb($data[$i]["end"]) . " Uhr");
				$loc = $data[$i]["name"];
				$dataview->addElement("Ort", $this->buildAddress($data[$i]));
				
				if($data[$i]["notes"] != "") {
					$dataview->addElement("Anmerkung", $data[$i]["notes"]);
				}
				
				$songs = $this->getData()->getSongsForRehearsal($data[$i]["id"]);
				if(count($songs) > 2) {
					$strSongs = "";
					for($j = 1; $j < count($songs); $j++) {
						if($j > 1) $strSongs .= ", ";
						$strSongs .= $songs[$j]["title"];
						if($songs[$j]["notes"] != "") $strSongs .= " (" . $songs[$j]["notes"] . ")";
					}
					$dataview->addElement("Stücke zum üben", $strSongs);
				}
				
				// add button to show participants
				$participantsButton = new Link($this->modePrefix() . "rehearsalParticipants&id=" . $data[$i]["id"], "Teilnehmer anzeigen");
				$dataview->addElement("Teilnehmer", $participantsButton->toString());
				
				// show three buttons to participate/maybe/not in rehearsal
				$partButtonSpace = "<br/><br/>";
				$partButtons = "";
				$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=rehearsal&id=" . $data[$i]["id"] . "&action=";
				
				$partBtn = new Link($partLinkPrefix . "yes", "Ich nehme teil.");
				$partBtn->addIcon("checkmark");
				$partButtons .= $partBtn->toString() . $partButtonSpace;
				
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
					$mayBtn = new Link($partLinkPrefix . "maybe", "Ich nehme vielleicht teil.");
					$mayBtn->addIcon("yield");
					$partButtons .= $mayBtn->toString() . $partButtonSpace;
				}
				
				$noBtn = new Link($partLinkPrefix . "no", "Ich kann leider nicht.");
				$noBtn->addIcon("remove");
				$partButtons .= $noBtn->toString();
				
				$userParticipation = $this->getData()->doesParticipateInRehearsal($data[$i]["id"]);
				if($userParticipation < 0) {
					if($data[$i]["approve_until"] == "" || Data::compareDates($data[$i]["approve_until"], Data::getDateNow()) > 0) {
						$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, "Teilnahme angeben");
					}
					else {
						$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, "Teilnahmefrist abgelaufen", "", true);
					}
				}
				else {
					$msg = "";
					if($userParticipation == 1) {
						$msg .= "Du nimmst an der Probe teil.";
					}
					else if($userParticipation == 2) {
						$msg .= "Du nimmst an der Probe vielleicht teil.";
					}
					else if($userParticipation == 0) {
						$msg .= "Du nimmst an der Probe nicht teil.";
					}
					
					$this->writeBoxListItem("R", $data[$i]["id"], "r" . $data[$i]["id"], $liCaption, $dataview, $partButtons, $msg);
				}
			}
		}
		echo "</ul>\n";
	}
	
	private function writeConcertList() {
		$data = $this->getData()->getUsersConcerts();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>Keine Konzerte angesagt.</li>\n";
		}
		else {
			// iterate over concerts
			foreach($data as $i => $row) {
				if($i == 0) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["C"], $data[$i]["id"]);
				
				$liCaption = Data::convertDateFromDb($row["begin"]) . " Uhr";
				$liCaption = Data::getWeekdayFromDbDate($row["begin"]) . ", " . $liCaption;
				
				// concert details
				$dataview = new Dataview();
				$dataview->addElement("Beginn", Data::convertDateFromDb($row["begin"]) . " Uhr");
				$dataview->addElement("Ende", Data::convertDateFromDb($row["end"]) . " Uhr");
				$loc = $this->buildAddress($row);
				if($loc != "") $loc = $row["location_name"] . " - " . $loc;
				else $loc = $row["location_name"];
				$dataview->addElement("Ort", $loc);
				$contact = $row["contact_name"];
				if($row["contact_phone"] != "") $contact .= "<br/>" . $row["contact_phone"];
				if($contact != "" && $row["contact_email"] != "") $contact .= "<br/>" . $row["contact_email"];
				if($contact != "" && $row["contact_web"] != "") $contact .= "<br/>" . $row["contact_web"];
				$dataview->addElement("Kontakt", $contact);
				if($row["program_name"] != "") {
					$program = $row["program_name"];
					if($row["program_notes"] != "") $program .= " (" . $row["program_notes"] . ")";
					$viewProg = new Link($this->modePrefix() . "viewProgram&id=" . $row["program_id"], "Programm ansehen");
					$program .= "<br/><br/>" . $viewProg->toString();
					$dataview->addElement("Programm", $program);
				}
				
				// show three buttons to participate/maybe/not in concert
				$partButtonSpace = "<br/><br/>";
				$partButtons = "";
				$partLinkPrefix = $this->modePrefix() . "saveParticipation&obj=concert&id=" . $data[$i]["id"] . "&action=";
				
				$partBtn = new Link($partLinkPrefix . "yes", "Ich werde mitspielen.");
				$partBtn->addIcon("checkmark");
				$partButtons .= $partBtn->toString() . $partButtonSpace;
				
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") != 0) {
					$mayBtn = new Link($partLinkPrefix . "maybe", "Ich werde vielleicht mitspielen.");
					$mayBtn->addIcon("yield");
					$partButtons .= $mayBtn->toString() . $partButtonSpace;
				}
				
				$noBtn = new Link($partLinkPrefix . "no", "Ich kann nicht mitspielen.");
				$noBtn->addIcon("remove");
				$partButtons .= $noBtn->toString();
				
				$userParticipation = $this->getData()->doesParticipateInConcert($data[$i]["id"]);
				if($userParticipation < 0) {
					if($data[$i]["approve_until"] == "" || Data::compareDates($data[$i]["approve_until"], Data::getDateNow()) > 0) {
						$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, "Teilnahme angeben");
					}
					else {
						$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption,
								$dataview, $partButtons, "Teilnahmefrist abgelaufen", "", true);
					}
				}
				else {
					$msg = "";
					if($userParticipation == 1) {
						$msg .= "Du nimmst am Konzert teil.";
					}
					else if($userParticipation == 2) {
						$msg .= "Du nimmst am Konzert vielleicht teil.";
					}
					else if($userParticipation == 0) {
						$msg .= "Du nimmst am Konzert nicht teil.";
					}
						
					$this->writeBoxListItem("C", $data[$i]["id"], "c" . $data[$i]["id"], $liCaption, $dataview, $partButtons, $msg);
				}
			}
		}
		echo "</ul>\n";
	}
	
	private function writeTaskList() {
		$data = $this->getData()->adp()->getUserTasks();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>Keine Aufgaben vorhanden.</li>\n";
		}
		else {
			// iterate over tasks
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["T"], $row["id"]);
				
				$liCaption = $row["title"];
				$dataview = new Dataview();
				$dataview->addElement("Titel", $row["title"]);
				$dataview->addElement("Beschreibung", $row["description"]);
				$dataview->addElement("Fällig am", Data::convertDateFromDb($row["due_at"]));
				$lnk = $this->modePrefix() . "taskComplete&id=" . $row["id"];
				$this->writeBoxListItem("T", $row["id"],"t" + $row["id"], $liCaption, $dataview, "", "Als abgeschlossen markieren", $lnk);
			}
		}
		echo "</ul>\n";
	}
	
	private function writeVoteList() {
		$data = $this->getData()->getVotesForUser();
		
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>Keine Abstimmungen offen.</li>\n";
		}
		else {
			// iterate over votes
			foreach($data as $i => $row) {
				if($i < 1) continue;
				
				// add every item to the discussion
				array_push($this->objectListing["V"], $row["id"]);
				
				$liCaption = $row["name"];
				$dataview = new Dataview();
				$dataview->addElement("Name", $row["name"]);
				$dataview->addElement("Abstimmungsende", Data::convertDateFromDb($row["end"]) . " Uhr");
				
				$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
				$this->writeBoxListItem("V", $row["id"], "v" + $row["id"], $liCaption, $dataview, "", "Abstimmen", $link);
			}
		}
		echo "</ul>\n";
	}
	
	/**
	 * Writes one item to the start page.
	 * @param char $otype {R = Rehearsal, C = Concert, V = Vote, T = Task}, but T is not supported in 2.5.0
	 * @param int $oid ID of the discussion object (see $otype).
	 * @param string $popboxid ID of the popup window.
	 * @param string $liCaption Caption of the Item (writing in blue).
	 * @param string $dataview Content of the popup window.
	 * @param string $participation optional: Buttons/content for the participation window.
	 * @param string $msg optional: Participation message, e.g. "Teilnahme angeben" or "Abstimmen".
	 * @param string $voteLink optional: Link to the voting-screen.
	 * @param boolean $partOver optional: Whether the participation deadline (approve_until) is over, by default false.
	 */
	private function writeBoxListItem($otype, $oid, $popboxid, $liCaption, $dataview,
			$participation = "", $msg = "", $voteLink = "", $partOver = false) {
		?>
		<li>
			<a href="#" onClick="$(function() { $('#<?php echo $popboxid; ?>').dialog({ width: 400 }); });"><?php echo $liCaption; ?></a>
			<?php
			if($msg != "" && $participation != "" && !$partOver) {
				?>
				<br/>
				<a href="#"
				   class="participation"
				   onClick="$(function() { $('#<?php echo $popboxid; ?>_participation').dialog({ width: 400 }); });"><?php echo $msg; ?></a>
				<?php
			}
			else if($msg != "" && participation != "" && $partOver) {
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
				$commentCaption = "Diskussion";
				if(!$this->getData()->hasObjectDiscussion($otype, $oid)) {
					$commentCaption = "Neue Diskussion";
				}
				echo '&nbsp;<a href="' . $this->modePrefix() . "discussion&otype=$otype&oid=$oid" . '" class="participation">';
				echo $commentCaption . '</a>';
			}
			?>
			
			<div id="<?php echo $popboxid; ?>" title="Details" style="display: none;">
				<?php $dataview->write(); ?>
			</div>
			<div id="<?php echo $popboxid; ?>_participation" title="Teilnahme" style="display: none;">
				<?php echo $participation; ?>
			</div>
			<?php $this->verticalSpace(); ?>
		</li>
		<?php
	}
	
	public function voteOptions() {
		$this->checkID();
		if(!$this->getData()->canUserVote($_GET["id"])) {
			new Error("Sie können in dieser Abstimmung nicht teilnehmen.");
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
					$label .= Data::convertDateFromDb($options[$i]["odate"]) . " Uhr";
				}
				else {
					$label = $options[$i]["name"];
				}
				
				$in = '<input type="';
				$selected = $this->getData()->getSelectedOptionsForUser($options[$i]["id"], $_SESSION["user"]);
				if($this->getData()->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == "1"
						&& $vote["is_multi"] == 1) {
					$dd = new Dropdown($options[$i]["id"]);
					$dd->addOption("Geht nicht", 0);
					$dd->addOption("Geht", 1);
					$dd->addOption("Vielleicht", 2);
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
				
				$dv->addElement($label, $in);
			}
			$dv->write();
			echo '<input type="submit" value="abstimmen" />' . "\n";
		}
		else {
			Writing::p("Es wurden noch keine Optionen angegeben. Schau später noch einmal nach.");
		}
		echo "</form>\n";
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function saveVote() {
		$this->checkID();
		$this->getData()->saveVote($_GET["id"], $_POST);
		$msg = new Message("Auswahl gespeichert", "Deine Auswahl wurde gespeichert.");
		$msg->write();
		$this->backToStart();
	}
	
	public function taskComplete() {
		$this->checkID();
		$this->getData()->taskComplete($_GET["id"]);
		$msg = new Message("Aufgabe abgeschlossen", "Die Aufgabe wurde als abgeschlossen markiert.");
		$msg->write();
		$this->backToStart();
	}
	
	public function viewProgram() {
		$this->checkID();
		$titles = $this->getData()->getProgramTitles($_GET["id"]);
		
		Writing::h2("Programm");
		
		// Enhancement #127
		$konzertMod = $this->getData()->getSysdata()->getModuleId("Konzerte");
		if($this->getData()->getSysdata()->userHasPermission($konzertMod)) {
			$editLink = new Link("?mod=" . $konzertMod . "&mode=programs&sub=view&id=" . $_GET["id"], "Programm bearbeiten");
			$editLink->addIcon("edit");
			$editLink->write();
			$this->verticalSpace();
		}
		
		$table = new Table($titles);		
		$table->renameHeader("rank", "Nr.");
		$table->renameHeader("title", "Titel");
		$table->renameHeader("composer", "Komponist/Arrangeuer");
		$table->renameHeader("notes", "Notizen");
		
		$table->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	public function rehearsalParticipants() {
		$rehearsal = $this->getData()->getRehearsal($_GET["id"]);
		Writing::h2("Teilnehmer der Probe am " . Data::convertDateFromDb($rehearsal["begin"])) . " Uhr";
		
		$parts = $this->getData()->getRehearsalParticipants($_GET["id"]);
		$table = new Table($parts);
		$table->renameHeader("name", "Vorname");
		$table->renameHeader("surname", "Nachname");
		$table->write();
		
		$this->backToStart();
	}
	
	private function writeUpdateList() {
		$maxNumUpdates = $this->getData()->getSysdata()->getDynamicConfigParameter("updates_show_max");
		if($maxNumUpdates <= 0) $maxNumUpdates = 1;
		
		$comments = $this->getData()->getUserUpdates($this->objectListing);
		
		if(count($comments) == 1) {
			echo "<p>Keine Neuigkeiten</p>\n";
			return;
		}
		
		foreach($comments as $i => $comment) {
			if($i == 0) continue; // header
			
			$objTitle = $this->getData()->getObjectTitle($comment["otype"], $comment["oid"]); 
			$objLink = $this->modePrefix() . "discussion&otype=" . $comment["otype"] . "&oid=" . $comment["oid"];
			
			$contact = $this->getData()->getSysdata()->getUsersContact($comment["author"]);
			$author = $contact["name"] . " " . $contact["surname"] . " am " . Data::convertDateFromDb($comment["created_at"]) . " Uhr";
			
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
			new Error("Diskussionen sind in dieser Anwendung deaktiviert.");
		}
		if(!isset($_GET["otype"]) || !isset($_GET["oid"])) {
			new Error("Bitte geben Sie den Dikussionsgegenstand an.");
		}
		
		Writing::h2("Diskussion: " . $this->getData()->getObjectTitle($_GET["otype"], $_GET["oid"]));
		
		// show comments
		$comments = $this->getData()->getDiscussion($_GET["otype"], $_GET["oid"]);
		
		if(count($comments) == 1) {
			echo "Keine Kommentare in dieser Diskussion.";
		}
		else {
			foreach($comments as $i => $comment) {
				if($i == 0) continue; // header
				
				$author = $comment["author"] . " am " . Data::convertDateFromDb($comment["created_at"]) . " Uhr";
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
		$form = new Form("Kommentar hinzufügen", $submitLink);
		$form->addElement("", new Field("message", "", FieldType::TEXT));
		$form->changeSubmitButton("Kommentar senden");
		$form->write();
		$this->verticalSpace();
		
		$this->backToStart();
	}
	
	public function addComment() {
		// save comment
		$this->getData()->addComment($_GET["otype"], $_GET["oid"]);
		
		// show discussion again
		$this->discussion();
	}
}

?>
