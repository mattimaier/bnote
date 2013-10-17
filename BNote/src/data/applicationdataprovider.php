<?php

/**
 * ADP = Application Data Provider
 * A collection of data access methods used in multiple modules
 * accessing database information. This is the central class
 * used to access entity information and can be the superclass of
 * a hierachy which acts as an ORM framework.
 * @author matti
 *
 */
class ApplicationDataProvider {
	
	// GENERAL ATTRIBUTES
	/**
	 * Database Connection.
	 * @var Database
	 */
	private $database;
	
	/**
	 * Regular Expressions
	 * @var Regex
	 */
	private $regex;
	
	/**
	 * System Information Access
	 * @var Systemdata
	 */
	private $sysdata;
	
	
	// APPLICATION SPECIFIC ATTRIBUTES
	/**
	 * Entities referencing certain tables. This array has the format
	 * of [tablename] => {table1, table2, ...} with tableX containing a
	 * reference column to tablename.
	 * @var Array
	 */
	private $references = array(
		"address" => array("location", "contact")
	);
	
	
	// CONSTRUCTOR
	/**
	 * Creates a new Application Data Provider.
	 * @param Database $database Database connection.
	 * @param Regex $regex Regular Expressions.
	 * @param Systemdata $sysdata System Information.
	 */
	function __construct($database, $regex, $sysdata) {
		$this->database = $database;
		$this->regex = $regex;
		$this->sysdata = $sysdata;
	}
	
	
	// GENERAL METHODS
	/**
	 * Checks whether other entities reference to this entity. This function
	 * requires the foreign key column in the referencing entity to have
	 * the same name than the table that is referenced.
	 * @param String $table Name of the table the referenced entity is stored in.
	 * @param int $key Reference key.
	 * @return True if the key is used by another entity, otherwise false.
	 */
	private function isKeyUsed($table, $key) {
		if(isset($this->references[$table])) {
			$totalcount = 0;
			foreach($this->references[$table] as $i => $ref) {
				$e = $this->database->getCell($ref, "count($table)", "$table = $key");
				$totalcount += $e;
			}
			return ($totalcount > 1);
		}
		else {
			return false;
		}
	}
	
	/**
	 * Searches for the row in the given table.
	 * @param String $table Name of the entity table.
	 * @param int $id ID of a row in the table.
	 * @return Array with information. 
	 */
	public function getEntityForId($table, $id) {
		$query = "SELECT * FROM $table WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	/**
	 * Checks whether the given $id is in the id column of the table.
	 * @param int $id ID to check.
	 * @param String $table Table to check in.
	 * @return True if the ID is present, otherwise false.
	 */
	private function idExists($id, $table) {
		return ($this->database->getCell($table, "count(id)", "id = $id") > 0);
	}
	
	
	// APPLICATION SPECIFIC METHODS
	/**
	 * Manages address entities.
	 * @param int $id ID of the address entity to be updated or removed.
	 * 				If a new address should be added or referenced,
	 * 				insert -1.
	 * @param Array $addy Array in the form of [key] => [value] with the
	 * 				keys street, city and zip. If array is empty or null, address can be removed.
	 * @return ID of the added or updated address or -1 if entity was deleted.
	 */
	public function manageAddress($id, $addy) {
		if($id == -1 || !$this->idExists($id, "address")) {
			// check for address existence
			$where = "street = \"" . $addy["street"] . "\" AND ";
			$where .= "city = \"" . $addy["city"] . "\" AND ";
			$where .= "zip = \"" . $addy["zip"] . "\"";
			$existent = $this->database->getCell("address", "id", $where);
			
			// if not exists, add address
			if($existent != null || $existent != "") {
				return $existent;
			}
			else {
				global $system_data;
				$comp = $system_data->getCompanyInformation();
				
				$query = "INSERT INTO address (street, city, zip, country) VALUES (";
				$query .= '"' . $addy["street"] . '"' . ", " . '"' . $addy["city"] . '"' . ", " . '"' . $addy["zip"] . '"';
				$query .= ", " . '"' . $comp["Country"] . '"';
				$query .= ")";
				
				return $this->database->execute($query);
			}
		}
		else {
			if($addy == null || count($addy) < 1 ||
				(!isset($addy["street"]) && !isset($addy["city"]) && !isset($addy["zip"])) || 
				($addy["street"] == "" && $addy["city"] = "" && $addy["zip"] = "")) {
				// delete address if it isn't referenced by other entities
				if($this->isKeyUsed("address", $id)) {
					return $id;
				}
				else {
					$query = "DELETE FROM address WHERE id = $id";
					$this->database->execute($query);
					return -1;
				}
			}
			else if($id > 0) {
				// update address
				$query = "UPDATE address SET ";
				$updated = false;
				if(isset($addy["street"])) {
					$query .= "street = \"" . $addy["street"] . "\", ";
					$updated = true;
				}
				if(isset($addy["city"])) {
					$query .= "city = \"" . $addy["city"] . "\", ";
					$updated = true;
				}
				if(isset($addy["zip"])) {
					$query .= "zip = \"" . $addy["zip"] . "\", ";
					$updated = true;
				}
				if($updated) {
					$query = substr($query, 0, strlen($query)-2);
					$query .= " WHERE id = $id";
					$this->database->execute($query);
				}
				return $id;
			}
		}
	}
	
	/**
	 * Returns all rehearsals joined with location and address.
	 */
	public function getAllRehearsals() {
		$query = "SELECT r.id as id, begin, end, r.notes as notes, name, street, city, zip";
		$query .= " FROM rehearsal r, location l, address a";
		$query .= " WHERE r.location = l.id AND l.address = a.id";
		$query .= " AND begin > NOW() ORDER BY begin ASC";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns all future concerts with joined attributes.
	 */
	public function getFutureConcerts() {
		/* 
		 * For complexity reasons is this data filtering
		 * done in PHP instead of SQL. Since there are only
		 * very few concerts usually, this shouldn't be a
		 * problem!
		 */
		$result = array();
		
		// add header
		array_push($result, array(
			"id", "begin", "end", "notes", // 0-3
			"location_name", "location_notes", // 4-5
			"location_street", "location_city", "location_zip", // 6-8
			"contact_name", "contact_phone", "contact_email", "contact_web", // 9-12
			"program_name", "program_notes" // 13-14
		));
		
		// get all future concerts
		$query = "SELECT * FROM concert WHERE begin > NOW() ORDER BY begin ASC";
		$concerts = $this->database->getSelection($query);
		
		// iterate over concerts and replace foreign keys with data
		for($i = 1; $i < count($concerts); $i++) {
			// resolve location -> mandatory!
			if(!$this->idExists($concerts[$i]["location"], "location")) {
				$location["name"] = "-";
				$location["notes"] = "";
				$location["address"] = "";
				$address["street"] = "-";
				$address["city"] = "-";
				$address["zip"] = "-";
			}
			else {
				$q1 = "SELECT name, notes, address FROM location ";
				$q1 .= "WHERE id = " . $concerts[$i]["location"];
				$location = $this->database->getRow($q1);
				
				// resolve address -> address id present, because location is mandatory
				$q2 = "SELECT street, city, zip FROM address ";
				$q2 .= "WHERE id = " . $location["address"];
				$address = $this->database->getRow($q2);
			}
			
			// resolve contact
			if($concerts[$i]["contact"] != "") {
				$q3 = "SELECT CONCAT_WS(' ', name, surname) as name, phone, email, web ";
				$q3 .= "FROM contact WHERE id = " . $concerts[$i]["contact"];
				$contact = $this->database->getRow($q3);
			}
			else {
				$contact = array(
					"name" => "", "phone" => "", "email" => "", "web" => ""
				);
			}
			
			// resolve program
			if($concerts[$i]["program"] != "") {
				$q4 = "SELECT name, notes FROM program ";
				$q4 .= "WHERE id = " . $concerts[$i]["program"];
				$program = $this->database->getRow($q4);
			}
			else {
				$program = array(
					"name" => "", "notes" => ""
				);
			}
			
			// build result for by row
			array_push($result, array(
				"id" => $concerts[$i]["id"],
				"begin" => $concerts[$i]["begin"],
				"end" => $concerts[$i]["end"], 
				"notes" => $concerts[$i]["notes"],
				"location_name" => $location["name"],
				"location_notes" => $location["notes"],
				"location_street" => $address["street"],
				"location_city" => $address["city"],
				"location_zip" => $address["zip"],
				"contact_name" => $contact["name"],
				"contact_phone" => $contact["phone"],
				"contact_email" => $contact["email"],
				"contact_web" => $contact["web"],
				"program_name" => $program["name"],
				"program_notes" => $program["notes"]
			));
		}
		return $result;
	}
	
	/**
	 * Returns all contacts with addresses and instruments.
	 */
	public function getContacts() {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM contact c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT JOIN instrument i ";
		$query .= "ON c2.instrument = i.id ";
		
		// filter out super users
		$suContacts = $this->sysdata->getSuperUserContactIDs();
		if(count($suContacts) > 0 && !$this->sysdata->isUserSuperUser()) {
			$query .= "WHERE ";
			foreach($suContacts as $i => $suc) {
				if($i > 0) $query .= " AND ";
				$query .= "c2.id <> $suc";
			}
		}
		
		return $this->database->getSelection($query);
	}
	
	public function getGroupContacts($groupId) {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM contact c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT JOIN instrument i ";
		$query .= "ON c2.instrument = i.id ";
		$query .= "JOIN contact_group cg ON c2.id = cg.contact ";
		$query .= "WHERE cg.group = $groupId";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Retrieves the name of the user.
	 * @param int $id ID of the user.
	 * @return First and last name concatenated.
	 */
	public function getUsername($id) {
		$cid = $this->database->getCell($this->database->getUserTable(),
					"contact", "id = $id");
		$query = "SELECT surname, name FROM contact WHERE id = $cid";
		$cdata = $this->database->getRow($query);
		return $cdata["name"] . " " . $cdata["surname"];
	}
	
	/**
	 * @return All locations with joined address.
	 */
	public function getLocations() {
		$query = "SELECT l.id, name, notes, street, city, zip, country ";
		$query .= "FROM location l, address a ";
		$query .= "WHERE l.address = a.id ";
		$query .= "ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	/**
	 * @return All templated programs.
	 */
	function getTemplatePrograms() {
		$query = "SELECT id, name FROM program WHERE isTemplate = 1";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Checks whether the login is taken
	 * @param String $login User login.
	 * @return True if it exists, otherwise false.
	 */
	function doesLoginExist($login) {
		$ct = $this->database->getCell("user", "count(id)", "login = '$login'");
		return ($ct == 1);
	}
	
	/**
	 * Retrieves the user's tasks.
	 * @param Integer $uid optional: User ID, if not set current user.
	 * @return array DB Selection of user's tasks.
	 */
	function getUserTasks($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		
		$query = "SELECT t.*, CONCAT(c1.name, ' ', c1.surname) as creator ";
		$query .= "FROM user u, task t, contact c1, contact c2 ";
		$query .= "WHERE u.id = $uid AND u.contact = t.assigned_to ";
		$query .= " AND t.created_by = c1.id AND t.assigned_to = c2.id ";
		$query .= " AND is_complete = 0 ";
		$query .= "ORDER BY due_at DESC";
		
		return $this->database->getSelection($query);
	}
	
	/**
	 * Retrieves the contact id for the given user id.
	 * @param Integer $uid optional: User ID, if not set current user.
	 * @return Contact ID of the user. 
	 */
	function getUserContact($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		return $this->database->getCell($this->database->getUserTable(), "contact", "id = $uid");
	}
	
	/**
	 * All available groups.
	 * @param boolean optional: true, false or null accepted.
	 * @return array DB Selection of all groups.
	 */
	function getGroups($active = null) {
		$query = "SELECT * FROM `group`";
		if($active != null) {
			$query .= " WHERE is_active = ";
			$query .= ($active) ? "1" : "0";
		}
		return $this->database->getSelection($query);
	}
	
	/**
	 * Retrieves the name of the group.
	 * @param Integer $groupId Group ID.
	 * @return Name of group.
	 */
	function getGroupName($groupId) {
		return $this->database->getCell("`group`", "name", "id = $groupId");
	}
	
	/**
	 * Checks whether the given user (or current one) is member of the given group.
	 * @param Integer $gid Group ID.
	 * @param Integer $uid optional: User ID, if not set current user.
	 * @return boolean True when the user is a member, otherwise false.
	 */
	function isGroupMember($gid, $uid = 0) {
		$contact = $this->getUserContact($uid);
		$ct = $this->database->getCell("contact_group", "count(*)", "`group` = $gid AND contact = $contact");
		return ($ct > 0);
	}
	
}