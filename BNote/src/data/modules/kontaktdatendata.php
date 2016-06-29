<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";
require_once $GLOBALS['DIR_LOGIC_MODULES'] . "logincontroller.php";

/**
 * Data access object for personal data management module.
 * @author matti
 *
 */
class KontaktdatenData extends KontakteData {
	
	function getContactForUser($uid) {
		if($uid == null || $uid <= 0 || $uid == "") return -1;
		$cid = $this->database->getCell("user", "contact", "id = $uid");
		if($cid <= 0) return -1;
		return $this->getContact($cid);
	}
	
	function updatePassword() {
		// validate
		$this->regex->isPassword($_POST["pw1"]);
		$this->regex->isPassword($_POST["pw2"]);
		
		if($_POST["pw1"] != $_POST["pw2"]) {
			new Error("Die Passwörter stimmen nicht überein!");
		}
		
		// encrypt passwords
		$pw = crypt($_POST["pw1"], LoginController::ENCRYPTION_HASH);
		
		// update in db
		$query = "UPDATE user SET password = '$pw' WHERE id = " . $_SESSION["user"];
		$this->database->execute($query);
	}
	
	
	function update($id, $values) {		
		$current = $this->getContactForUser($id);
		// modify array
		foreach($current as $col => $v) {
			if(!isset($_POST[$col])) {
				$_POST[$col] = $v;
			}
		}
		
		$values = $this->update_address($current["id"], $values);
		
		// update info
		AbstractData::update($current["id"], $values); // includes validation
	}
	
	function getPIN($uid) {
		$pin = $this->database->getCell($this->database->getUserTable(), "pin", "id = $uid");
		if($pin == null || $pin == "") {
			$pid = LoginController::createPin($this->database, $uid);
		}
		return $pin; 
	}
	
	function saveSettings($uid) {
		// prepare data
		$emn = ($_POST["email_notification"] == "") ? "0" : "1";
		
		// update settings
		$query = "UPDATE " . $this->database->getUserTable() . " SET ";
		$query .= "email_notification = " . $emn . " ";
		$query .= "WHERE id = $uid";
		$this->database->execute($query);
	}
}

?>