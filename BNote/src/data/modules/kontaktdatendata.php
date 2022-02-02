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
		$cid = $this->getSysdata()->getContactFromUser($uid);
		if($cid <= 0) return -1;
		return $this->getContact($cid);
	}
	
	function updatePassword() {
		// validate
		$this->regex->isPassword($_POST["pw1"]);
		$this->regex->isPassword($_POST["pw2"]);
		
		if($_POST["pw1"] != $_POST["pw2"]) {
			new BNoteError(Lang::txt("KontaktdatenData_updatePassword.BNoteError"));
		}
		
		// encrypt passwords
		$pw = crypt($_POST["pw1"], LoginController::ENCRYPTION_HASH);
		
		// update in db
		$query = "UPDATE user SET password = '$pw' WHERE id = " . $_SESSION["user"];
		$this->database->execute($query);
	}
	
	
	function update($id, $values, $plainUpdate=false) {		
		$current = $this->getContactForUser($id);	
		// modify array
		foreach($current as $col => $v) {
			if(!isset($_POST[$col])) {
				$_POST[$col] = $v;
			}
		}
		$contact_id = $current["id"];
		$values["is_conductor"] = $this->database->colValue("SELECT is_conductor FROM contact WHERE id = ?", "is_conductor", array(array("i", $contact_id)));
		
		$values = $this->update_address($contact_id, $values);
		
		// update custom data
		$this->updateCustomFieldData('c', $contact_id, $values, true);
		
		// update info
		AbstractData::update($contact_id, $values); // includes validation
	}
	
	function getPIN($uid) {
		$pin = $this->database->colValue("SELECT pin FROM user WHERE id = ?", "pin", array(array("i", $uid)));
		if($pin == null || $pin == "") {
			$pin = LoginController::createPin($this->database, $uid);
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