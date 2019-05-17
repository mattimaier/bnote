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
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("User ID", FieldType::INTEGER),
				"login" => array("Login", FieldType::CHAR),
				"password" => array("Password", FieldType::PASSWORD),
				"realname" => array("Name", FieldType::CHAR),
				"lastlogin" => array("Last Logged in", FieldType::DATETIME)
		);
	
		$this->references = array();
		$this->table = "user";
	
		$this->init($dir_prefix);
	}
	
	function validateLogin() {
		if(!$this->regex->isLoginQuiet($_POST["login"])) {
			$this->regex->isEmail($_POST["login"]);
		}
		$this->regex->isPassword($_POST["password"]);
	}
	
	function getPasswordForLogin($login) {
		if(strpos($login, "@") !== false) {
			$cid = $this->database->getCell("contact", "id", "email = \"" . $login . "\"");
			if($cid > 0) {
				return $this->database->getCell($this->table, "password", "contact = $cid AND isActive = 1");
			}
			else {
				return null;
			}
		}
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
		$contact = $this->database->getCell("contact", "id", "email = '$email'");
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
		$query = "SELECT i.id, i.name as instrument, c.id as cat, c.name as category";
		$query .= " FROM instrument i JOIN category c ON i.category = c.id";
		$query .= " ORDER BY c.name, i.name";
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
		$query = "INSERT INTO address (street, city, zip, country) VALUES (";
		$query .= '"' . $_POST["street"] . '", "' . $_POST["city"] . '", "' . $_POST["zip"] . '", "' . $_POST["country"] . '"';
		$query .= ")";
		return $this->database->execute($query);
	}
	
	function createContact($aid) {
		$query = "INSERT INTO contact (surname, name, phone, email, address, instrument)";
		$query .= " VALUES (";
		$query .= '"' . $_POST["surname"] . '", ';
		$query .= '"' . $_POST["name"] . '", ';
		$query .= '"' . $_POST["phone"] . '", ';
		$query .= '"' . $_POST["email"] . '", ';
		$query .= "$aid, ";
		$query .= $_POST["instrument"];
		$query .= ")";
		$cid = $this->database->execute($query);
		
		// get configured default group
		$defaultGroup = $this->getSysdata()->getDynamicConfigParameter("default_contact_group");
		if($defaultGroup == null || $defaultGroup == "") $defaultGroup = 2; // fallback
		
		// add the contact to the members group (gid=2)
		$query = "INSERT INTO contact_group (contact, `group`) VALUES ($cid, $defaultGroup)"; 
		$this->database->execute($query);
		
		return $cid;
	}
	
	function createUser($login, $password, $cid) {
		// create inactive user
		$query = "INSERT INTO user (login, password, isActive, contact)";
		$query .= " VALUES (";
		$query .= '"' . $login . '", ';
		$query .= '"' . $password . '", ';
		$query .= "0, $cid";
		$query .= ")";
		$uid = $this->database->execute($query);
		
		// create user directory
		$dir_prefix = "";
		if(isset($GLOBALS['dir_prefix'])) {
			$dir_prefix = $GLOBALS['dir_prefix'];
		}
		$path = $dir_prefix . $GLOBALS["DATA_PATHS"]["userhome"] . $login;
		mkdir($path);
		
		return $uid;
	}
	
	function createDefaultRights($uid) {
		$privQuery = "INSERT INTO privilege (user, module) VALUES ";
		global $system_data;
		foreach($system_data->getDefaultUserCreatePermissions() as $i => $mod) {
			$privQuery .= "($uid, $mod), ";
		}
		$privQuery = substr($privQuery, 0, strlen($privQuery)-2);
		$this->database->execute($privQuery);
	}
	
	function findContactByCode($code) {
		$this->regex->isSubject($code);
		return $this->database->getRow("SELECT *, a.* FROM contact c RIGHT OUTER JOIN address a ON c.address = a.id WHERE gdpr_code = '$code'");
	}
	
	function gdprOk($code) {
		$this->regex->isSubject($code);
		$this->database->execute("UPDATE contact SET gdpr_ok = 1 WHERE gdpr_code = '$code'");
	}
}

?>