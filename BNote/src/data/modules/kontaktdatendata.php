<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";

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
			new Error("Die Passw&ouml;rter stimmen nicht &uuml;berein!");
		}
		
		// encrypt passwords
		$pw = crypt($_POST["pw1"], CRYPT_BLOWFISH);
		
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
		
		$values = $this->update_address($id, $values);
		
		// update info
		AbstractData::update($current["id"], $values); // includes validation
	}
	
	function getPIN($uid) {
		$pin = $this->database->getCell($this->database->getUserTable(), "pin", "id = $uid");
		if($pin == null || $pin == "") {
			// create a pin
			$lower_bound = 100000;
			$upper_bound = 999999;
			$pin = 0;
			$pin_exists = 1;
			
			while($pin_exists > 0) {
				$pin = rand($lower_bound, $upper_bound);
				$pin_exists = $this->database->getCell($this->database->getUserTable(), "count(id)", "pin = $pin");
			}
			
			// save new pin
			$query = "UPDATE " . $this->database->getUserTable() . " SET pin = $pin WHERE id = $uid";
			$this->database->execute($query);
		}
		return $pin; 
	}
	
}

?>