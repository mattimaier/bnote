<?php
require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "gruppenview.php";
require_once $GLOBALS["DIR_DATA_MODULES"] . "gruppendata.php";
require_once $GLOBALS["DIR_DATA_MODULES"] . "userdata.php";
require_once $GLOBALS["DIR_LOGIC_MODULES"] . "kommunikationcontroller.php";

/**
 * Special controller for contact module.
 * @author matti
 *
 */
class KontakteController extends DefaultController {
	
	/**
	 * DAO for group submodule.
	 * @var GruppenData
	 */
	private $groupData;
	
	/**
	 * View for submodule.
	 * @var GruppenView
	 */
	private $groupView;
	
	public function start() {
		if(isset($_GET['mode'])) {
			if($_GET['mode'] == "createUserAccount") {
				$this->createUserAccount();
			}
			else if($_GET["mode"] == "groups") {
				$this->groups();
			}
			else if($_GET["mode"] == "integration_process") {
				$this->integrate();
			}
			else if($_GET["mode"] == "contactImportProcess") {
				$this->importVCard();
			}
			else if($_GET["mode"] == "getGdprOk") {
				$this->getData()->generateGdprCodes();
				$this->getView()->getGdprOk();
			}
			else if($_GET["mode"] == "gdprSendMail") {
				$this->gdprSendMail();
			}
			else if($_GET["mode"] == "gdprNOK") {
				$this->gdprNOK();
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
	
	private function createUserAccount() {
		// create credentials
		$contact = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// find a not taken username
		if($contact['nickname'] != "") {
			$username = $contact['nickname'];
		}
		else {
			$username = $contact["name"] . $contact["surname"];
		}
		$username = strtolower($username);
		
		// fix #173: only allow lower-case letters and numbers (alphanum)
		$username = preg_replace("/[^a-z0-9]/", '', $username);
		
		$i = 2;
		$un = $username;
		while($this->getData()->adp()->doesLoginExist($un)) {
			 $un = $username . $i++;
		}
		$username = $un;
		$password = $this->createRandomPassword(6);
		
		// create user account
		$this->getData()->createUser($_GET["id"], $username, $password);
		
		// check for mail address availibility
		if(isset($contact["email"]) && $contact["email"] != "") {
			// send email
			global $system_data;
			$subject = "Anmeldeinformationen " . $system_data->getCompany();
			
			$body = "Du kannst dich nun unter ";
			$body .= $system_data->getSystemURL() . " anmelden.\n\n";
			$body .= "Dein Benutzername ist " . $username . " und dein ";
			$body .= "Kennwort ist " . $password . " .\n";
			
			// notify user about result
			require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
			$mail = new Mailing($contact["email"], $subject, $body);
			$mail->setFrom($username . '<' . $contact["email"] . '>');
				
			if(!$mail->sendMail()) {
				$this->getView()->userCredentials($username , $password);
			}
			else {
				$this->getView()->userCreatedAndMailed($username, $contact["email"]);
			}
		}
		else {
			// show credentials & creation success
			$this->getView()->userCredentials($username, $password);
		}
	}
	
	/**
	 * Creates a random password with the given length.
	 * @param int $length Length of password.
	 */
	private function createRandomPassword($length) {
		$chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '';
		while ($i <= $length) {
			$num = rand() % strlen($chars);
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	
	private function initGroup() {
		if($this->groupData == null || $this->groupView == null) {
			$this->groupData = new GruppenData();
			$this->groupView = new GruppenView($this);
		}
	}
	
	private function groups() {
		$this->initGroup();
		if(!isset($_GET["func"])) {
			$this->groupView->start();
		}
		else {
			$func = $_GET["func"];
			$this->groupView->$func();
		}
	}
	
	function groupOptions() {
		$this->initGroup();
		$this->groupView->showOptions();
	}
	
	function getData() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "groups") {
			return $this->groupData;
		}
		else {
			return parent::getData();
		}
	}
	
	function integrate() {
		$grpFilter = null;
		if(isset($_POST["group"])) {
			$grpFilter = $_POST["group"];
		}
		$memberSelection = $this->getData()->getMembers($grpFilter);
		$members = GroupSelector::getPostSelection($memberSelection, "member");
		$rehearsals = GroupSelector::getPostSelection($this->getData()->adp()->getFutureRehearsals(), "rehearsal");
		$phases = GroupSelector::getPostSelection($this->getData()->getPhases(), "rehearsalphase");
		$concerts = GroupSelector::getPostSelection($this->getData()->adp()->getFutureConcerts(), "concert");
		$votes = GroupSelector::getPostSelection($this->getData()->getVotes(), "vote");
		
		foreach($members as $cid) {
			foreach($rehearsals as $rid) {
				$res = $this->getData()->addContactRelation("rehearsal", $rid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation R$rid - $cid kann nicht gesetzt werden.");
				} 
			}
			foreach($phases as $pid) {
				$res =$this->getData()->addContactRelation("rehearsalphase", $pid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation RP$pid - $cid kann nicht gesetzt werden.");
				}
			}
			foreach($concerts as $conid) {
				$res =$this->getData()->addContactRelation("concert", $conid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation C$conid - $cid kann nicht gesetzt werden.");
				}
			}
			foreach($votes as $vid) {
				$res =$this->getData()->addContactToVote($vid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation V$vid - $cid kann nicht gesetzt werden.");
				}
			}
		}
		
		new Message("Zuordnungen gespeichert", "Die Zuordnungen wurden gespeichert.");
	}
	
	private function importVCard() {
		$vcd = file_get_contents($_FILES['vcdfile']['tmp_name']);
		$cards = $this->parseVCard($vcd);
		$selectedGroups = GroupSelector::getPostSelection($this->getData()->getGroups(), "group");
		
		$this->getData()->saveVCards($cards, $selectedGroups);
		
		// show success
		$message = count($cards) . " Einträge wurden importiert.";
		$this->getView()->importVCardSuccess($message);
	}
	
	private function parseVCard($vcd) {
		$lines = explode("\n", $vcd);
		$cards = array();
		$card = null;
		foreach($lines as $lineIdx => $line) {
			$sepPos = strpos($line, ":");
			if($sepPos <= 0) continue;
			$field = strtoupper(substr($line, 0, $sepPos));
			$val = trim(substr($line, $sepPos+1));
			if($field == "BEGIN" && $val == "VCARD") {
				$card = array();
			}
			if($field == "VERSION" || $field == "REV") continue;
			if(Data::startsWith($field, "EMAIL")) {
				if(!isset($card['email']) || strpos($field, "PREF") !== false) {
					$card["email"] = $val;
				}
			}
			if(Data::startsWith($field, "TEL") && strpos($field, "HOME") !== false) {
				$card["phone"] = $val;
			}
			if(Data::startsWith($field, "TEL") && strpos($field, "CELL") !== false) {
				$card["mobile"] = $val;
			}
			if($field == "N") {
				$names = explode(";", $val);
				$card['name'] = $names[1];
				$card['surname'] = $names[0];
			}
			if($field == "BDAY") {
				$card['birthday'] = Data::convertDateFromDb($val);
			}
			if(Data::startsWith($field, "ADR")) {
				if(strpos($field, "HOME") !== false || !isset($card['street'])) {
					$addy = explode(";", $val);
					$card['street'] = $addy[count($addy)-5];
					$card['city'] = $addy[count($addy)-4];
					$card['zip'] = $addy[count($addy)-2];
				}
			}
			if($field == "END" && $val == "VCARD") {
				array_push($cards, $card);
			}
		}
		return $cards;
	}
	
	private function gdprSendMail() {
		// compile addresses
		$contacts = $this->getData()->getContactGdprStatus(0);
		$addresses = Database::flattenSelection($contacts, "email");
		$addresses = array_unique($addresses);
		
		// compile mail
		$_POST["subject"] = "Einverständniserklärung DSGVO";
		$templateContent = file_get_contents("data/gdpr_mail.php");
		$templateContent .= $this->getData()->getSysdata()->getCompany() . "<br><br>";
		$approveUrl = $this->getData()->getSysdata()->getSystemURL() . "main.php?mod=extGdpr&code=";
		
		$successful = 0;
		foreach($addresses as $address) {
			$approveUrl .= $this->getData()->getGdprCode($address);
			$templateContent .= '<a href="' . $approveUrl . '">Überprüfen und zustimmen</a>';
			$_POST["message"] = $templateContent;
			
			// send mail with template
			$commCtrl = new KommunikationController();
			$commCtrl->setData($this->getData());
			$res = $commCtrl->sendMail($addresses, true);
			if($res) {
				$successful++;
			}
		}
		
		// processing
		if($successful == 0) {
			new BNoteError("Die Nachricht(en) konnte(n) nicht versandt werden. Bitte kontaktiere den Administrator.");
		}
		new Message("Mails versandt", "$successful Nachrichten wurden den Kontakten zugestellt.");
	}
	
	private function gdprNOK() {
		$contacts = $this->getData()->getContactGdprStatus(0);
		
		// check for each if it has a user and eventually remove the user completely
		// otherwise remove the contact details
		$userFullRemoval = array(array("id", "contact"));
		for($i = 1; $i < count($contacts); $i++) {
			$contact = $contacts[$i];
			if($contact["login"] != null && $contact["login"] != "") {
				array_push($userFullRemoval, array("id" => $contact["user_id"], "contact" => $contact["contact_id"]));
			}
			else {
				$this->getData()->delete($contact["contact_id"]);
			}
		}
		
		$userData = new UserData();
		$userData->deleteUsersFull($userFullRemoval);
		
		$this->getView()->gdprNOK();
	}
}