<?php


/**
 * Data Access Class for Login.
 * @author matti
 *
 */
class LoginData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array("User ID", FieldType::INTEGER),
				"login" => array("Login", FieldType::CHAR),
				"password" => array("Password", FieldType::PASSWORD),
				"realname" => array("Name", FieldType::CHAR),
				"lastlogin" => array("Last Logged in", FieldType::DATETIME)
		);
	
		$this->references = array();
		$this->table = "user";
	
		$this->init();
	}
	
	function validateLogin() {
		$this->regex->isLogin($_POST["login"]);
		$this->regex->isPassword($_POST["password"]);
	}
	
	function getPasswordForLogin($login) {
		return $this->database->getCell($this->table, "password", "login = '" . $login . "' AND isActive = 1");
	}
	
	function getUserIdForLogin($login) {
		return $this->database->getCell($this->table, "id", "login = '" . $login . "'");
	}
	
	function saveLastLogin() {
		// Save last logged in
		$uid = $_SESSION["user"];
		$this->database->execute("UPDATE " . $this->table . " SET lastlogin = NOW() WHERE id = $uid");
	}
	
	function getStartModuleId() {
		global $system_data;
		return $system_data->getStartModuleId();
	}
	
	function validateEMail($email) {
		$this->regex->isEmail($email);
	}
	
	function getUserIdForEMail($email) {
		// check whether mail-address is unique
		$ct = $this->database->getCell("contact", "count(id)", "email = '$email'");
		if($ct != 1) { return -1; }
		
		// if it's unique return the user's id
		$contact = $this->database->getCell("contact", "id", "email = 'email'"); //TODO FIXME: bug - no contact is found for existing mail address
		if($contact < 1) { return -1; }
		
		// check more than 1 user for this contact
		$ct = $this->database->getCell($this->table, "count(id)", "contact = $contact");
		if($ct != 1) return -1;
		
		return $this->database->getCell($this->table, "id", "contact = $contact"); 
	}
	
	function getUsernameForId($uid) {
		return $this->database->getCell($this->table, "login", "id = $uid");
	}
	
	function saveNewPassword($uid, $pwenc) {
		$query = "UPDATE user SET password = '$pwenc' WHERE id = $uid";
		$this->database->execute($query);
	}
	
	function getInstruments() {
		$query = "SELECT id,name FROM instrument";
		return $this->database->getSelection($query);
	}
	
	function getJSValidationFunctions() {
		return $this->regex->getJSValidationFunctions();
	}
	
	function validateRegistration() {
		$this->regex->isName($_POST["name"]);
		$this->regex->isName($_POST["surname"]);
		if(isset($_POST["phone"]) && $_POST["phone"] != "") $this->regex->isPhone($_POST["phone"]);
		$this->regex->isEmail($_POST["email"]);
		$this->regex->isStreet($_POST["street"]);
		$this->regex->isZip($_POST["zip"]);
		$this->regex->isCity($_POST["city"]);
		$this->regex->isPositiveAmount($_POST["instrument"]);
		$this->regex->isLogin($_POST["login"]);
		$this->regex->isPassword($_POST["pw1"]);
		$this->regex->isPassword($_POST["pw2"]);
	}
	
	function duplicateLoginCheck() {
		$login = $_POST["login"];
		$ct = $this->database->getCell("user", "count(id)", "login = '$login'");
		return ($ct > 0);
	}
	
	function createAddress() {
		$query = "INSERT INTO address (street, city, zip) VALUES (";
		$query .= '"' . $_POST["street"] . '", "' . $_POST["city"] . '", "' . $_POST["zip"] . '"';
		$query .= ")";
		return $db->execute($query);
	}
	
	function createContact($aid) {
		$query = "INSERT INTO contact (surname, name, phone, email, address, status, instrument)";
		$query .= " VALUES (";
		$query .= '"' . $_POST["surname"] . '", ';
		$query .= '"' . $_POST["name"] . '", ';
		$query .= '"' . $_POST["phone"] . '", ';
		$query .= '"' . $_POST["email"] . '", ';
		$query .= "$aid, ";
		$query .= '"MEMBER", ';
		$query .= $_POST["instrument"];
		$query .= ")";
		return $db->execute($query);
	}
	
	function createUser($login, $password, $cid) {
		// create inactive user
		$query = "INSERT INTO user (login, password, isActive, contact)";
		$query .= " VALUES (";
		$query .= '"' . $login . '", ';
		$query .= '"' . $password . '", ';
		$query .= "0, $cid";
		$query .= ")";
		return $db->execute($query);
	}
	
	function createDefaultRights($uid) {
		$privQuery = "INSERT INTO privilege (user, module) VALUES ";
		global $system_data;
		foreach($system_data->getDefaultUserCreatePermissions() as $i => $mod) {
			$privQuery .= "($uid, $mod), ";
		}
		$privQuery = substr($privQuery, 0, strlen($privQuery)-2);
		$db->execute($privQuery);
	}
}

?>