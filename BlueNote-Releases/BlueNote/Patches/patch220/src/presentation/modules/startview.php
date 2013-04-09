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
		$ical = new Link($GLOBALS["DIR_EXPORT"] . "calendar.ics", "Kalender herunterladen");
		$ical->addIcon("arrow_down");
		$ical->write();
		
		Writing::h2("Proben");
		$this->writeRehearsalList($this->getData()->adp()->getAllRehearsals());
		
		Writing::h2("Konzerte");
		$this->writeConcertList($this->getData()->adp()->getFutureConcerts());
		
		Writing::h2("Abstimmungen");
		$this->writeVoteList();
	}
	
	private function writeRehearsalList($data) {
		echo "<ul>\n";
		for($i = 1; $i < count($data); $i++) {
			$row = $data[$i];
			/* PHP > 5.2!!!
			 *
			// calculate day of the week
			$date_begin = new DateTime($row["begin"]);
			$date_end = new DateTime($row["end"]);
			$weekday = Data::convertEnglishWeekday($date_begin->format('D'));

			// check whether they are on the same day -> if so, only write hour as end
			$finish = $date_end->format('H:i');
			*/
			// PHP 5.0 - 5.1
			$date_begin = strtotime($row["begin"]);
			$date_end = strtotime($row["end"]);
			$weekday = Data::convertEnglishWeekday(date("D", $date_begin));
			$finish = date('H:i', $date_end);

			$when = Data::convertDateFromDb($row["begin"]) . " bis " . $finish . " Uhr";
			
			// put the output together
			$out = "<strong>$weekday, $when</strong><br />";
			$out .= "<font size=\"-1\">" . $row["name"];
			$out .= " (" . $row["street"] . ", " . $row["zip"] . " " . $row["city"] .  ")";
			$songs = $this->getData()->getSongsForRehearsal($row["id"]);
			if(count($songs) > 1) {
				$out .= "<br />";
				$out .= "St&uuml;cke zum <u>&uuml;ben</u>: ";
				for($j = 1; $j < count($songs); $j++) {
					$out .= $songs[$j]["title"];
					if($songs[$j]["notes"] != "") $out .= " (" . $songs[$j]["notes"] . ")";
					$out .= ", ";
				}
				$out = substr($out, 0, strlen($out) -2);
			}
			$out .= "</font>";
			$out .= "<pre class=\"concert\">" . $row["notes"] . "</pre>\n";
			
			$rehParticipation = $this->getData()->doesParticipateInRehearsal($row["id"]);
			if($rehParticipation == -1) {
				$participate = new Link($this->modePrefix() . "participate&rid=" . $row["id"] . "&status=yes", "Ich werde anwesend sein.");
				$participate->addIcon("checkmark");
				$out .= $participate->toString();
				$out .= "&nbsp; &nbsp;";
				$dnpart = new Link($this->modePrefix() . "participate&rid=" . $row["id"] . "&status=no", "Ich werde nicht anwesend sein.");
				$dnpart->addIcon("no_entry");
				$out .= $dnpart->toString();
				$out .= "&nbsp; &nbsp;";
				$maypart = new Link($this->modePrefix() . "participate&rid=" . $row["id"] . "&status=maybe", "Ich werde vielleicht anwesend sein.");
				$maypart->addIcon("yield");
				$out .= $maypart->toString();
				$out .= "<br/><br/>";
			}
			else {
				$partMsg = "";
				$partStyle = "font-size: 14px; color: ";
				$linkaddy = $this->modePrefix() . "participate&rid=" . $row["id"] . "&status=";
				$links = array();
				
				if($rehParticipation == 0) {
					$partMsg = "Du nimmst <u>nicht</u> an der Probe teil.";
					$partStyle .= "#A61717";
					$links["zusagen"] = "yes";
					$links["vielleicht"] = "maybe";
				}
				else if($rehParticipation == 1) {
					$partMsg = "Du nimmst an der Probe teil.";
					$partStyle .= "#1EA617";
					$links["absagen"] = "no";
					$links["vielleicht"] = "maybe";
				}
				else if($rehParticipation == 2) {
					$partMsg = "Du nimmst <u>vielleicht</u> an der Probe teil.";
					$partStyle .= "#E6911C";
					$links["zusagen"] = "yes";
					$links["absagen"] = "no";
				}				
				$out .= "<p style=\"$partStyle;\">";
				$out .= $partMsg;
				
				// Add a link to change decision
				foreach($links as $action => $target) {
					$out .= "&nbsp;&nbsp;";
					$out .= "<a href=\"" . $linkaddy . $target . "\">$action</a>";
				}
				$out .= "&nbsp;&nbsp;";
				$out .= '<a href="' . $this->modePrefix() . 'participants&rid=' . $row["id"] . '">Teilnehmer</a>';
				
				$out .= "</p>\n";
			}
			echo " <li>$out</li>\n";
		}
		echo "</ul>\n";
	}
	
	private function writeConcertList($data) {
		echo "<ul>\n";
		for($i = 1; $i < count($data); $i++) {
			$row = $data[$i];
			/* PHP > 5.2!!!
			 *
			// calculate day of the week
			$date_begin = new DateTime($row["begin"]);
			$date_end = new DateTime($row["end"]);
			$weekday = Data::convertEnglishWeekday($date_begin->format('D'));

			// check whether they are on the same day -> if so, only write hour as end
			$finish = $date_end->format('H:i');
			*/
			// PHP 5.0 - 5.1
			$date_begin = strtotime($row["begin"]);
			$date_end = strtotime($row["end"]);
			$weekday = Data::convertEnglishWeekday(date("D", $date_begin));
			$finish = date('H:i', $date_end);

			$when = Data::convertDateFromDb($row["begin"]) . " bis " . $finish . " Uhr";
			
			// put the output together
			$out = "<strong>$weekday, $when</strong><br />";
			$out .= "<font size=\"-1\">" . $row["location_name"];
			$out .= " (" . $row["location_street"] . ", " . $row["location_zip"] . " " . $row["location_city"] .  ")</font>";
			$out .= "<pre class=\"concert\">" . $row["notes"] . "</pre>\n";
			if($this->getData()->doesParticipateInConcert($row["id"]) == -1) {
				$participate = new Link($this->modePrefix() . "participate&cid=" . $row["id"] . "&status=yes", "Ich werde mitspielen.");
				$participate->addIcon("checkmark");
				$out .= $participate->toString();
				$out .= "&nbsp; &nbsp;";
				$dnpart = new Link($this->modePrefix() . "participate&cid=" . $row["id"] . "&status=no", "Ich werde nicht mitspielen.");
				$dnpart->addIcon("no_entry");
				$out .= $dnpart->toString() . "<br /><br />";
			}
			echo " <li>$out</li>\n";
		}
		echo "</ul>\n";
		$this->verticalSpace();
	}
	
	public function participate() {
		if(isset($_GET["status"]) && ($_GET["status"] == "no" || $_GET["status"] == "maybe")
				&& !isset($_POST["rehearsal"])) {
			$target = $this->modePrefix() . "participate&status=" . $_GET["status"];
			$reasonMsg = "Ich werde nicht anwesend sein weil...";
			if($_GET["status"] == "maybe") {
				$reasonMsg = "Ich werde vielleicht nicht anwesend sein weil...";
			}
			
			$form = new Form($reasonMsg, $target);
			$form->addElement("", new Field("explanation", "", FieldType::CHAR));
			if(isset($_GET["rid"])) {
				$form->addHidden("rehearsal", $_GET["rid"]);
			}
			if(isset($_GET["cid"])) {
				$form->addHidden("concert", $_GET["cid"]);
			}
			$form->write();
		}
		else {
			$this->getData()->saveParticipation();
			$this->start();
		}
	}
	
	public function participants() {
		if(isset($_GET["rid"]) && $this->getData()->doesParticipateInRehearsal($_GET["rid"]) >= 0) {
			// show list of participants
			$parts = $this->getData()->getRehearsalParticipants($_GET["rid"]);
			
			Writing::h2("Probenteilnehmer");
			echo "<p>";
			for($i = 0; $i < count($parts); $i++) {
				if($i == 0) continue;
				$p = $parts[$i];
				if($i > 1) echo "<br/>";
				echo $p["name"] . " " . $p["surname"] . " (" . $p["instrument"] . ")";
			}
			echo "</p>\n";
		}
		else {
			new Error("Es wurde keine Proben-ID angegeben.");
		}
		$this->backToStart();
	}
	
	public function writeVoteList() {
		$votes = $this->getData()->getVotesForUser();
		
		echo "<ul>";
		for($i = 1; $i < count($votes); $i++) {
			echo "<li>";
			echo "<b>" . $votes[$i]["name"] . "</b><br/>";
			echo "Abstimmung bis " . Data::convertDateFromDb($votes[$i]["end"]);
			$this->verticalSpace();
			if(!$this->getData()->hasUserVoted($votes[$i]["id"])) {
				$btn = new Link($this->modePrefix() . "voteOptions&id=" . $votes[$i]["id"], "abstimmen");
				$btn->write();
				$this->verticalSpace();
			}
			echo "</li>\n";
		}
		echo "</ul>";
		
		if(count($votes) == 1) {
			Writing::p("<i>Derzeit sind keine Abstimmungen für dich offen.</i>");
		}
		$this->verticalSpace();
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
}

?>
