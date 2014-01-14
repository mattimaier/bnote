<?php

/**
 * View for start module.
 * @author matti
 *
 */
class StartView extends AbstractView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Willkommen");
		Writing::p("Hier befindest du dich auf der Startseite des internen Bereichs der Website.");
		
		// Calendar Exports
		$userExt = "?user=" . urlencode($this->getData()->adp()->getLogin());
		
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", "Kalender Export");
		$ical->addIcon("arrow_down");
		$ical->write();
		$this->buttonSpace();
		
		$systemUrl = $this->getData()->getSysdata()->getSystemURL();
		if(!Data::endsWith($systemUrl, "/")) $systemUrl .= "/";
		if(Data::startsWith($systemUrl, "http://")) $systemUrl = substr($systemUrl, 7);
		else if(Data::startsWith($systemUrl, "https://")) $systemUrl = substr($systemUrl, 8);
				
		$calSubsc = new Link("webcal://" . $systemUrl . $GLOBALS["DIR_EXPORT"] . "calendar.ics$userExt", "Kalender abonnieren");
		$calSubsc->addIcon("arrow_right");
		$calSubsc->write();
		
		$this->verticalSpace();
		
		$news = $this->getData()->getNews();
		
		if($news != "") {
			?>
			<div class="start_box_news">
				<div class="start_box_heading">Nachrichten</div>
				<div class="start_box_content">
					<?php echo $news; ?>
				</div>
			</div>
			<?php 
		}
		?>
		
		<div class="start_box_table">
			<div class="start_box_row">
				<div class="start_box">
					<div class="start_box_heading">Proben</div>
					<div class="start_box_content">
						<?php $this->writeRehearsalList(); ?>
					</div>
				</div>
				
				<div class="start_box">
					<div class="start_box_heading">Konzerte</div>
					<div class="start_box_content">
						<?php $this->writeConcertList(); ?>
					</div>
				</div>
			</div>
			<div class="start_box_row">
				<div class="start_box">
					<div class="start_box_heading">Aufgaben</div>
					<div class="start_box_content">
						<?php $this->writeTaskList(); ?>
					</div>
				</div>
				
				<div class="start_box">
					<div class="start_box_heading">Abstimmungen</div>
					<div class="start_box_content">
						<?php $this->writeVoteList(); ?>
					</div>
				</div>
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
	
	private function writeRehearsalList() {
		$data = $this->getData()->getUsersRehearsals();
		echo "<ul>\n";
		if($data == null || count($data) < 2) {
			echo "<li>Keine Proben angesagt.</li>\n";
		}
		else {
			// iterate over rehearsals
			for($i = 1; $i < count($data); $i++) {
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
					$this->writeBoxListItem("r" . $data[$i]["id"], $liCaption, $dataview, $partButtons, "Teilnahme angeben");
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
					
					$this->writeBoxListItem("r" . $data[$i]["id"], $liCaption, $dataview, $partButtons, $msg);
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
					$this->writeBoxListItem("r" . $data[$i]["id"], $liCaption, $dataview, $partButtons, "Teilnahme angeben");
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
						
					$this->writeBoxListItem("r" . $data[$i]["id"], $liCaption, $dataview, $partButtons, $msg);
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
				$liCaption = $row["title"];
				$dataview = new Dataview();
				$dataview->addElement("Titel", $row["title"]);
				$dataview->addElement("Beschreibung", $row["description"]);
				$dataview->addElement("Fällig am", Data::convertDateFromDb($row["due_at"]));
				$lnk = $this->modePrefix() . "taskComplete&id=" . $row["id"];
				$this->writeBoxListItem("t" + $row["id"], $liCaption, $dataview, "", "Als abgeschlossen markieren", $lnk);
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
				$liCaption = $row["name"];
				$dataview = new Dataview();
				$dataview->addElement("Name", $row["name"]);
				$dataview->addElement("Abstimmungsende", Data::convertDateFromDb($row["end"]) . " Uhr");
				
				$link = $this->modePrefix() . "voteOptions&id=" . $row["id"];
				$this->writeBoxListItem("v" + $row["id"], $liCaption, $dataview, "", "Abstimmen", $link);
			}
		}
		echo "</ul>\n";
	}
	
	private function writeBoxListItem($popboxid, $liCaption, $dataview, $participation = "", $msg = "", $voteLink = "") {
		?>
		<li>
			<a href="#" onClick="$(function() { $('#<?php echo $popboxid; ?>').dialog({ width: 400 }); });"><?php echo $liCaption; ?></a>
			<?php
			if($msg != "" && $participation != "") {
				?>
				<br/>
				<a href="#"
				   class="participation"
				   onClick="$(function() { $('#<?php echo $popboxid; ?>_participation').dialog({ width: 400 }); });"><?php echo $msg; ?></a>
				<?php
			}
			else if($msg != "" && $voteLink != "") {
				?>
				<br/>
				<a href="<?php echo $voteLink; ?>" class="participation"><?php echo $msg; ?></a>
				<?php
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
					$label = Data::convertDateFromDb($options[$i]["odate"]);
				}
				else {
					$label = $options[$i]["name"];
				}
				$in = '<input type="';
				if($vote["is_multi"] == 1) {
					$in .= "checkbox";
					$in .= '" name="' . $options[$i]["id"] . '" />';
				}
				else {
					$in .= "radio";
					$in .= '" name="uservote" value="' . $options[$i]["id"] . '" />';
				}
				
				$dv->addElement($label, $in);
			}
			$dv->addElement('<input type="submit" value="abstimmen" />', "&nbsp;");
			$dv->write();
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
}

?>
