<?php

/**
 * Special controller for contact module.
 * @author matti
 *
 */
class KontakteController extends DefaultController {
	
	/**
	 * DAO for group sub-module.
	 * @var GruppenData
	 */
	private $groupData;
	
	public function start() {
		if(isset($_GET['mode'])) {
			if($_GET['mode'] == "createUserAccount") {
				$this->createUserAccount();
			}
			else if($_GET["mode"] == "groups") {
				$this->groups();
			}
			else {
				$this->getView()->$_GET['mode']();
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
		$username = $contact["name"] . $contact["surname"];
		$username = strtolower($username);
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
		$chars = "abcdefghijkmnpqrstuvwxyz123456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '';
		while ($i <= $length) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	
	private function groups() {
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "gruppenview.php";
		require_once $GLOBALS["DIR_DATA_MODULES"] . "gruppendata.php";
		$this->groupData = new GruppenData();
		
		$view = new GruppenView($this);
		

		if(!isset($_GET["func"])) {
			$view->start();
		}
		else {
			$view->$_GET["func"]();
		}
	}
	
	function getData() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "groups") {
			return $this->groupData;
		}
		else {
			return parent::getData();
		}
	}
}