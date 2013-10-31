<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . 'userdata.php';

/**
 * Data Access Class for contact data.
 * @author matti
 *
 */
class KontakteData extends AbstractData {
	
	/**
	 * Group ID for administrator group.
	 * @var Integer
	 */
	public static $GROUP_ADMIN = 1;
	
	/**
	 * Group ID for member group.
	 * @var Integer
	 */
	public static $GROUP_MEMBER = 2;
	
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
		return $this->getGroupContacts(KontakteData::$GROUP_MEMBER);
	}
	
	function getAdmins() {
		return $this->getGroupContacts(KontakteData::$GROUP_ADMIN);
	}
	
	function getGroupContacts($group) {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM (SELECT contact.* ";
		$query .= "        FROM contact, contact_group grp ";
		$query .= "        WHERE contact.id = grp.contact AND grp.group = $group";
		$query .= "        ) as c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT JOIN instrument i ";
		$query .= "ON c2.instrument = i.id ";
		$query .= "ORDER BY c2.name ASC";
		return $this->filterSuperUsers($this->database->getSelection($query));
	}
	
	function getAllContacts() {
		$query = $this->createQuery();
		$query .= "ORDER BY c2.name";
		$sel = $this->database->getSelection($query);
		if($this->getSysdata()->isUserSuperUser()) {
			return $sel;
		}
		else {
			return $this->filterSuperUsers($sel);
		}
	}
	
	/**
	 * Returns the row with the contact, but with readable and modified
	 * status values.
	 * @param int $id ID of the contact.
	 */
	function getContact($id) {
		$query = $this->createQuery();
		$query .= "WHERE c2.id = $id";
		$contact = $this->database->getRow($query);
		
		// add user groups
		$query = "SELECT g.id, g.name ";
		$query .= "FROM `group` g, contact_group cg ";
		$query .= "WHERE g.id = cg.group AND cg.contact = $id";
		$groups = $this->database->getSelection($query);
		for($i = 1; $i < count($groups); $i++) {
			$contact["group_" + $groups[$i]["id"]] = $groups[$i]["name"];
		}
		
		return $contact;
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
	
	/**
	 * Removes all super users from selection.
	 * @param Array $selection Database Selection Array
	 * @return Selection array without super users.
	 */
	private function filterSuperUsers($selection) {
		$filtered = array();
		$superUsers = $GLOBALS["system_data"]->getSuperUserContactIDs();
		$filtered[0] = $selection[0];
		$count_f = 1;		
		for($i = 1; $i < count($selection); $i++) {
			if(!in_array($selection[$i]["id"], $superUsers)) {
				$filtered[$count_f++] = $selection[$i];
			}
		}
		return $filtered;
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
		
		$cid = parent::create($values);
		
		// create group entries
		$this->createContactGroupEntries($cid);
		
		return $cid;
	}
	
	private function createContactGroupEntries($cid) {
		$groups = $this->getGroups();
		$query = "INSERT INTO contact_group (contact, `group`) VALUES ";
		$grpCount = 0;
		for($i = 1; $i < count($groups); $i++) {
			$gid = $groups[$i]["id"];
			$fieldId = "group_" . $gid;
			if(isset($_POST[$fieldId])) {
				if($grpCount > 0) $query .= ", ";
				$query .= "($cid, $gid)";
				$grpCount++;
			}
		}
		
		if($grpCount > 0) {
			$this->database->execute($query);
		}
	}
	
	function update($id, $values) {	
		// update address
		$values = $this->update_address($id, $values);
			
		// update groups
		$query = "DELETE FROM contact_group WHERE contact = $id";
		$this->database->execute($query);
		$this->createContactGroupEntries($id);
		
		parent::update($id, $values);
	}
	
	protected function update_address($id, $values) {
		$addressId = $this->database->getCell("contact", "address", "id = $id");
		$query = "UPDATE address SET ";
		$query .= "street = \"" . $values["street"] . "\", ";
		$query .= "city = \"" . $values["city"] . "\", ";
		$query .= "zip = \"" . $values["zip"] . "\" ";
		$query .= "WHERE id = " . $addressId;
		$this->database->execute($query);
		$values["address"] = $addressId;
		return $values;
	}
	
	function delete($id) {
		// remove group memberships
		$query = "DELETE FROM contact_group WHERE contact = $id";
		$this->database->execute($query);
		
		// remove contact
		parent::delete($id);
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
	 * Gets the group name from the database.
	 * @param Integer $groupId ID of the group
	 * @return string Name of the group.
	 */
	function getGroupName($groupId) {
		return $this->database->getCell("`group`", "name", "id = $groupId");
	}
	
	function getGroups() {
		return $this->adp()->getGroups();
	}
	
	function getContactGroups($cid) {
		$query = "SELECT GROUP_CONCAT(g.name) as grpConcat ";
		$query .= "FROM `group` g JOIN contact_group cg ON cg.group = g.id ";
		$query .= "WHERE cg.contact = $cid ";
		$query .= "GROUP BY cg.contact";
		$grpConcat = $this->database->getSelection($query);
		$grpString = $grpConcat[1]["grpConcat"];
		return $grpString;
	}
	
	function getContactGroupsArray($cid) {
		$query = "SELECT `group` FROM contact_group WHERE contact = $cid";
		$res = $this->database->getSelection($query);
		$groups = array();
		for($i = 1; $i < count($res); $i++) {
			array_push($groups, $res[$i]["group"]);
		}
		return $groups;
	}
}