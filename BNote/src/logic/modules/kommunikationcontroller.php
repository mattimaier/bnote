<?php

/**
 * Controller for emails.
 * @author matti
 *
 */
class KommunikationController extends DefaultController {
	
	function start() {
		if(isset($_GET['mode'])) {
			if(isset($_GET['sub']) && $_GET['sub'] = "send") {
				$this->prepareMail();
				$this->sendMail();
			}
			else {
				$mode = $_GET['mode'];
				$this->getView()->$mode();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	/**
	 * Prepares the data for the mail.
	 */
	public function prepareMail() {
		// adjust subject & body
		if(isset($_POST["rehearsal"])) {
			// adjust subject
			$reh = $this->getData()->getRehearsal($_POST["rehearsal"]);
			$text = "Probe am " . Data::getWeekdayFromDbDate($reh["begin"]);
			$text .= ", " . Data::convertDateFromDb($reh["begin"]) . Lang::txt("KommunikationController_prepareMail.begin");
			$_POST["subject"] = $text;
			
			// adjust body: append songs to practise
			$songs = $this->getData()->getSongsForRehearsal($_POST["rehearsal"]);
			if(count($songs) > 1) {
				$ext = Lang::txt("KommunikationController_prepareMail.songs");
				for($i = 1; $i < count($songs); $i++) {
					$ext .= "<li>" . urldecode($songs[$i]["title"]) . " (" . urldecode($songs[$i]["notes"]) . ")</li>\n";
				}
				$ext .= "</ul>\n";
				$_POST["message"] .= "\n$ext";
			}
			
			// add direct link to rehearsal on start
			$href = $this->getData()->getSysdata()->getSystemURL();
			if(!Data::startsWith(strtolower($href), "http")) {
				$href = "https://" . $href;
			}
			$href .= "main.php?mod=1&mode=start&otype=R&oid=" . $_POST["rehearsal"];
			$_POST["message"] .= "\n<a href=\"$href\">" . Lang::txt("KommunikationController_prepareMail.rehearsalLink") . "</a>";
			
		}
		else if(isset($_POST["rehearsalSerie"])) {
			$rs = $this->getData()->getRehearsalSerie($_POST["rehearsalSerie"]);
			$_POST["subject"] = Lang::txt("KommunikationController_prepareMail.rehearsalSerie") . $rs["name"];
		}
		else if(isset($_POST["concert"])) {
			$concert = $this->getData()->getConcert($_POST["concert"]);
			
			// subject
			$subj = Lang::txt("KommunikationController_prepareMail.concert") . Data::getWeekdayFromDbDate($concert["begin"]);
			$subj .= ", " . Data::convertDateFromDb($concert["begin"]) . Lang::txt("KommunikationController_prepareMail.begin");
			$_POST["subject"] = $subj;
			
			// body
			if($_POST["message"] == "") {
				$body = Lang::txt("KommunikationController_prepareMail.message_1") . Data::getWeekdayFromDbDate($concert["begin"]) . Lang::txt("KommunikationController_prepareMail.message_2");
				$body .= Data::convertDateFromDb($concert["begin"]) . Lang::txt("KommunikationController_prepareMail.message_3");
				$body .= Lang::txt("KommunikationController_prepareMail.message_4") . $this->getData()->getSysdata()->getCompany() . Lang::txt("KommunikationController_prepareMail.message_5");
				$body .= Lang::txt("KommunikationController_prepareMail.message_6");
				$_POST["message"] = $body;
			}
		}
		else if(isset($_POST["vote"])) {
			$vote = $this->getData()->getVote($_POST["vote"]);
			
			// subject
			$_POST["subject"] = Lang::txt("KommunikationController_prepareMail.subject") . $vote["name"];
			
			// body
			if($_POST["message"] == "") {
				$_POST["message"] = Lang::txt("KommunikationController_prepareMail.vote_message");
			}
		}
		else if($_POST["subject"] == "") {
			global $system_data;
			$_POST["subject"] = $system_data->getCompany(); // band name
		}
		if(isset($_POST["inclreceivers"]) && $_POST["inclreceivers"] == "on") {
			$groups = $this->getData()->adp()->getGroups(true, false);
			$groupIds = GroupSelector::getPostSelection($groups, "group");
			$groupNames = array();
			// doing this in memory is faster than selecting from DB again
			foreach($groupIds as $id) {
				for($g = 1; $g < count($groups); $g++) {
					if($groups[$g]["id"] == $id) {
						array_push($groupNames, $groups[$g]["name"]);
						break;
					}
				}
			}
			$inclrev = Lang::txt("KommunikationController_prepareMail.inclreceivers") . ": " . join(", ", $groupNames);
			$_POST["message"] = $inclrev . "\n\n" . $_POST["message"];
		}
	}
	
	/**
	 * Please make sure that the $_POST array has a subject and message attribute.
	 * @param array $addresses Optionally provide the addresses to send the mail to.
	 * @param bool $silent If set to true does not call the view. Default false. 
	 */
	public function sendMail($addresses = array(), $silent = false) {
		$subject = $_POST["subject"];
		$body = $_POST["message"];
		
		// determine email adresses
		if(isset($_POST["rehearsal"])) {
			// get mail addresses for a rehearsal
			$addresses = $this->getData()->getRehearsalContactMail($_POST["rehearsal"]);
		}
		else if(isset($_POST["rehearsalSerie"])) {
			$addresses = $this->getData()->getRehearsalSerieContactMail($_POST["rehearsalSerie"]);
		}
		else if(isset($_POST["concert"])) {
			$addresses = $this->getData()->getConcertContactMail($_POST["concert"]);
		}
		else if(isset($_POST["vote"])) {
			$addresses = $this->getData()->getVoteContactMail($_POST["vote"]);
		}
		else if(count($addresses) == 0) {
			// get all mail addresses from selected groups
			$addresses = $this->getData()->getMailaddressesFromGroup("group");
		}
		
		if($addresses == null || count($addresses) == 0) {
			new BNoteError(Lang::txt("KommunikationController_sendMail.error"));
		}
		
		global $system_data;
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($subject, "");
		$mail->setBodyInHtml($body);
		$mail->setFromUser($system_data->getUserId());
		
		// place receiver addresses into the bcc field
		$mail->setBcc($addresses);
		
		// handle attachments
		if(isset($_FILES["attachments"])) {
			for($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
				$tmpFilename = $_FILES['attachments']['tmp_name'][$i];
				$filename = $_FILES['attachments']['name'][$i];
				if($tmpFilename != "") {
					$mail->addAttachment($tmpFilename, $filename);
				}
			}
		}
		
		if(!$mail->sendMail()) {
			if(!$silent) {
				$this->getView()->reportMailError($addresses);
			}
			return false;
		}
		else if(!$silent) {
			$this->getView()->messageSent();
		}
		return true;
	}
}