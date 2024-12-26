<?php
global $dir_prefix;
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "abstractlocationdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php";

/**
 * Data Access Class for Login.
 * @author matti
 *
 */
class LoginData extends AbstractLocationData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("LoginData_construct.id"), FieldType::INTEGER),
				"login" => array(Lang::txt("LoginData_construct.login"), FieldType::CHAR),
				"password" => array(Lang::txt("LoginData_construct.password"), FieldType::PASSWORD),
				"realname" => array(Lang::txt("LoginData_construct.realname"), FieldType::CHAR),
				"lastlogin" => array(Lang::txt("LoginData_construct.lastlogin"), FieldType::DATETIME)
		);
	
		$this->references = array();
		$this->table = "user";
	
		$this->init($dir_prefix);
	}
	
	function validateLogin() {
		$this->regex->isPassword($_POST["password"]);
	}
	
	function getPasswordForLogin($login) {
		if(strpos($login, "@") !== false) {
			$query = "SELECT password FROM user u JOIN contact c ON u.contact = c.id WHERE c.email = ?";
			return $this->database->colValue($query, "password", array(array("s", $login)));
		}
		return $this->database->colValue("SELECT password FROM user WHERE login = ? AND isActive = 1", "password", array(array("s", $login)));
	}
	
	function saveLastLogin() {
		// Save last logged in
		$this->database->execute("UPDATE user SET lastlogin = NOW() WHERE id = ?", array(array("i", $this->getUserId())));
	}
	
	function getStartModuleId() {
		global $system_data;
		return $system_data->getStartModuleId();
	}
	
	function validateEMail($email) {
		$this->regex->isEmail($email);
	}
	
	function getUserIdForLogin($login) {
		return $this->database->colValue("SELECT id FROM `user` WHERE login = ?", "id", array(array("s", $login)));
	}
	
	function getUserIdForEMail($email) {
		// check whether mail-address is unique
		$ct = $this->database->colValue("SELECT count(id) as cnt FROM contact WHERE lower(email) = ?", "cnt", array(array("s", strtolower($email))));
		if($ct != 1) { return -1; }
		
		// if it's unique return the user's id
		$contact = $this->database->colValue("SELECT id FROM contact WHERE lower(email) = ?", "id", array(array("s", strtolower($email))));
		if($contact < 1) { return -1; }
		
		// check more than 1 user for this contact
		$ct = $this->database->colValue("SELECT count(id) as cnt FROM user WHERE contact = ?", "cnt", array(array("i", $contact)));
		if($ct != 1) return -1;
		
		return $this->database->colValue("SELECT id FROM user WHERE contact = ?", "id", array(array("i", $contact))); 
	}
	
	function getUsernameForId($uid) {
		return $this->database->colValue("SELECT login FROM user WHERE id = ?", "login", array(array("i", $uid)));
	}
	
	function saveNewPassword($uid, $pwenc) {
		$query = "UPDATE user SET password = ? WHERE id = ?";
		$this->database->execute($query, array(array("s", $pwenc), array("i", $uid)));
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
		$this->regex->isPassword($_POST["pw1"]);
		$this->regex->isPassword($_POST["pw2"]);
		if(isset($_POST["birthday"]) && $_POST["birthday"] != "") $this->regex->isDate($_POST["birthday"]);
	}
	
	function duplicateLoginCheck() {
		$email = $_POST["email"];
		$query = "SELECT count(u.id) as cnt FROM user u JOIN contact c ON u.contact = c.id WHERE c.email = ?";
		$ct = $this->database->colValue($query, "cnt", array(array("s", $email)));
		return ($ct > 0);
	}
	
	function createContact($aid) {
		$query = "INSERT INTO contact (surname, name, nickname, phone, mobile, email, address, instrument, birthday) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$bd = $_POST["birthday"] ? isset($_POST["birthday"]) && $_POST["birthday"] != "" : NULL;
		$cid = $this->database->prepStatement($query, array(
				array("s", $_POST["surname"]),
				array("s", $_POST["name"]),
				array("s", $_POST["nickname"]),
				array("s", $_POST["phone"]),
				array("s", $_POST["mobile"]),
				array("s", $_POST["email"]),
				array("i", $aid),
				array("i", $_POST["instrument"]),
				array("s", $bd)
		));
		
		// get configured default group
		$defaultGroup = $this->getSysdata()->getDynamicConfigParameter("default_contact_group");
		if($defaultGroup == null || $defaultGroup == "") $defaultGroup = 2; // fallback
		
		// add the contact to the members group (gid=2)
		$query = "INSERT INTO contact_group (contact, `group`) VALUES (?, ?)"; 
		$this->database->execute($query, array(array("i", $cid), array("i", $defaultGroup)));
		
		return $cid;
	}
	
	function createUser($login, $password, $cid) {
		// create inactive user
		$query = "INSERT INTO user (login, password, isActive, contact) VALUES (?, ?, 0, ?)";
		$uid = $this->database->prepStatement($query, array(
				array("s", $login),
				array("s", $password),
				array("i", $cid)
		));
		
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
		$s = $this->tupleStmt($uid, $this->getSysdata()->getDefaultUserCreatePermissions());
		$privQuery = "INSERT INTO privilege (user, module) VALUES " . $s[0];
		$this->database->execute($privQuery, $s[1]);
	}
	
	function findContactByCode($code) {
		$q = "SELECT *, a.* FROM contact c RIGHT OUTER JOIN address a ON c.address = a.id WHERE gdpr_code = ?";
		return $this->database->fetchRow($q, array(array("s", $code)));
	}
	
	function gdprOk($code) {
		$this->regex->isSubject($code);
		$this->database->execute("UPDATE contact SET gdpr_ok = 1 WHERE gdpr_code = ?", array(array("s", $code)));
	}
	
	function isUserActive($uid) {
		if($uid == NULL) {
			return FALSE;
		}
		// same as in UserData->isUserActive() but copied so user module must not be imported just for this function 
		return ($this->database->colValue("SELECT isActive FROM user WHERE id = ?", "isActive", array(array("i", $uid))) == 1);
	}
}