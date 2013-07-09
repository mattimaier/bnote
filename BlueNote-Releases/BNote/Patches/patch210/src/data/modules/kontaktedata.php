<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . 'userdata.php';

/**
 * Data Access Class for contact data.
 * @author matti
 *
 */
class KontakteData extends AbstractData {
	
	/**
	 * Contact Status Option for Admins.
	 */
	public static $STATUS_ADMIN = "ADMIN";
	
	/**
	 * Contact Status Option for Membesr.
	 */
	public static $STATUS_MEMBER = "MEMBER";
	
	/**
	 * Contact Status Option for Externals.
	 */
	public static $STATUS_EXTERNAL = "EXTERNAL";
	
	/**
	 * Contact Status Option for Applicants.
	 */
	public static $STATUS_APPLICANT = "APPLICANT";
	
	/**
	 * Contact Status Option for Others.
	 */
	public static $STATUS_OTHER = "OTHER";
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"surname" => array("Name", FieldType::CHAR),
			"name" => array("Vorname", FieldType::CHAR),
			"phone" => array("Telefon", FieldType::CHAR),
			"fax" => array("Fax", FieldType::CHAR),
			"mobile" => array("Mobil", FieldType::CHAR),
			"business" => array("Geschäftlich", FieldType::CHAR),
			"email" => array("E-Mail", FieldType::EMAIL),
			"web" => array("Web", FieldType::CHAR),
			"notes" => array("Anmerkungen", FieldType::TEXT),
			"address" => array("Adresse", FieldType::REFERENCE),
			"instrument" => array("Instrument", FieldType::REFERENCE),
			"status" => array("Status", FieldType::ENUM)
		);
		
		$this->references = array(
			"address" => "address",
			"instrument" => "instrument"
		);
		
		$this->table = "contact";
		
		$this->init();
	}
	
	/**
	 * Returns all members and admins.
	 */
	function getMembers() {
		$query = $this->createQuery();
		$query .= "WHERE c2.status = '" . KontakteData::$STATUS_MEMBER . "'";
		$query .= " OR c2.status = '" . KontakteData::$STATUS_ADMIN . "' ";
		$query .= "ORDER BY c2.name ASC";
		return $this->database->getSelection($query);
	}
	
	function getExternals() {
		return $this->getContactsWithStatus(KontakteData::$STATUS_EXTERNAL);
	}
	
	function getOthers() {
		return $this->getContactsWithStatus(KontakteData::$STATUS_OTHER);
	}
	
	function getApplicants() {
		return $this->getContactsWithStatus(KontakteData::$STATUS_APPLICANT);
	}
	
	function getAdmins() {
		return $this->getContactsWithStatus(KontakteData::$STATUS_ADMIN);
	}
	
	private function getContactsWithStatus($status) {
		$query = $this->createQuery();
		$query .= "WHERE c2.status = '$status'";
		$query .= "ORDER BY c2.name ASC";
		return $this->database->getSelection($query);
	}
	
	function getAllContacts() {
		$query = $this->createQuery();
		$query .= "ORDER BY c2.name";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns the row with the contact, but with readable and modified
	 * status values.
	 * @param int $id ID of the contact.
	 */
	function getContact($id) {
		$query = $this->createQuery();
		$query .= "WHERE c2.id = $id";
		$res = $this->database->getRow($query);
		
		// modify status
		$res["status"] = $this->statusCaption($res["status"]);
		
		return $res;
	}
	
	private function createQuery() {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM contact c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT JOIN instrument i ";
		$query .= "ON c2.instrument = i.id ";
		return $query;
	}
	
	function validate($input) {
		// trim the checks only to the ones which were filled out.
		$values = array();
		foreach($input as $col => $value) {
			if($value != "") {
				$values[$col] = $value;
			}
		}
		if($values["instrument"] == 0) {
			unset($values["instrument"]);
		}
		
		parent::validate($values);
	}
	
	function create($values) {
		$addy["street"] = $values["street"];
		$addy["city"] = $values["city"];
		$addy["zip"] = $values["zip"];
		
		// simply create one address per contact
		$query = "INSERT INTO address (street, city, zip) VALUES (";
		$query .= " \"" . $values["street"] . "\", \"" . $values["city"] . "\", \"" . $values["zip"] . "\")";
		$values["address"] = $this->database->execute($query);
		
		parent::create($values);
	}
	
	function update($id, $values) {
		$this->validate($values);
		
		// update address
		$user = $this->findByIdNoRef($id);
		$query = "UPDATE address SET ";
		$query .= "street = \"" . $values["street"] . "\", ";
		$query .= "city = \"" . $values["city"] . "\", ";
		$query .= "zip = \"" . $values["zip"] . "\" ";
		$query .= "WHERE id = " . $user["address"];
		$this->database->execute($query);
		$values["address"] = $user["address"];
		
		/* Old Implementation (deprecated by Oct 15, 2012)
		if($values["street"] != "" && $values["city"] != "" && $values["zip"] != "") {
			$addy["street"] = $values["street"];
			$addy["city"] = $values["city"];
			$addy["zip"] = $values["zip"];
			$values["address"] = $this->adp()->manageAddress($user["address"], $addy);
		}
		else {
			$values["address"] = $user["address"];
		}
		*/
		
		parent::update($id, $values);
	}
	
	function getAddress($id) {
		return $this->adp()->getEntityForId("address", $id);
	}
	
	/**
	 * @param int $id Contact ID
	 * @return True if a user account with this contact exists, otherwise false.
	 */
	function hasContactUserAccount($id) {
		$ct = $this->database->getCell("user", "count(id)", "contact = $id");
		return ($ct > 0);
	}
	
	/**
	 * Create a new user with default privileges.
	 * @param int $cid Contact ID
	 * @param String $username Unique login.
	 * @param String $password Unencrypted password.
	 * @return User ID.
	 */
	function createUser($cid, $username, $password) {
		$dao = new UserData();
		$values = array(
			"contact" => $cid,
			"login" => $username,
			"password" => $password
		);
		$dao->create($values);
	}
	
	/**
	 * Sets the captions of each status.
	 * @param Const String $status One of the constant strings of this class.
	 */
	function statusCaption($status) {
		switch($status) {
			case KontakteData::$STATUS_ADMIN: return "Administrator";
			case KontakteData::$STATUS_MEMBER: return "Mitglied";
			case KontakteData::$STATUS_EXTERNAL: return "Externer Mitspieler";
			case KontakteData::$STATUS_APPLICANT: return "Bewerber";
			default: return "Sonstiger Kontakt";
		}
	}
}