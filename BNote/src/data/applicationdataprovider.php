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
	
	/**
	 * Security Manager.
	 * @var SecurityManager
	 */
	private $secManager;
	
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
	 * @param string $dir_prefix Optional: Prefix for include(s).
	 */
	function __construct($database, $regex, $sysdata, $dir_prefix = "") {
		$this->database = $database;
		$this->regex = $regex;
		$this->sysdata = $sysdata;
		
		// includes
		require_once($dir_prefix . $GLOBALS["DIR_LOGIC"] . "securitymanager.php");
		$this->secManager = new SecurityManager($sysdata, $this);
	}
	
	/**
	 * @return SecurityManager
	 */
	function getSecurityManager() {
		return $this->secManager;
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
	
	/**
	 * Retrieves all future rehearsals without participation in ascending order.
	 * @param boolean $withGroups Add "groups" field with the associated groups to the rehearsal objects (by default: false).
	 * @return All rehearsals joined with location and address.
	 */
	public function getFutureRehearsals($withGroups=false) {
		$query = "SELECT r.id as id, begin, end, approve_until, conductor, r.notes as notes, name, street, city, zip, l.id as location";
		$query .= " FROM rehearsal r, location l, address a";
		$query .= " WHERE r.location = l.id AND l.address = a.id";
		$query .= " AND end > NOW() ORDER BY begin ASC";
		$rehearsals = $this->database->getSelection($query);
		
		// find groups for all future rehearsals
		if($withGroups) {
			$groupQuery = "SELECT r.id as rehearsal, g.id as `group`, g.name 
					FROM `group` g 
					JOIN rehearsal_group rg ON rg.`group` = g.id
					JOIN rehearsal r ON rg.rehearsal = r.id
					WHERE r.end > NOW()";
			$groupSelection = $this->database->getSelection($groupQuery);
			$rehearsalGroups = array();
			for($i = 1; $i < count($groupSelection); $i++) {
				$rid = $groupSelection[$i]["rehearsal"];
				if(!isset($rehearsalGroups[$rid])) {
					$rehearsalGroups[$rid] = array();
				}
				array_push($rehearsalGroups[$rid], $groupSelection[$i]);
			}
			for($i = 1; $i < count($rehearsals); $i++) {
				if(isset($rehearsalGroups[$rehearsals[$i]["id"]])) {
					$rehearsals[$i]["groups"] = $rehearsalGroups[$rehearsals[$i]["id"]];
				}
			}
		}
		return $rehearsals;
	}
	
	/**
	 * Retrieves all user specific future concerts.
	 * @param Integer $uid optional: User ID, by default current user.
	 * @return All future concerts with joined attributes.
	 */
	public function getFutureConcerts($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		/* 
		 * For complexity reasons is this data filtering
		 * done in PHP instead of SQL. Since there are only
		 * very few concerts usually, this shouldn't be a
		 * problem!
		 */
		$result = array();
		
		// add header
		array_push($result, array(
			"id", "title", "begin", "end", "approve_until", "notes", // 0-5
			"location_name", "location_notes", // 6,7
			"location_street", "location_city", "location_zip", // 8-10
			"contact_name", "contact_phone", "contact_email", "contact_web", // 11-14
			"program_name", "program_notes", // 15,16
			"outfit" // 17
		));
		
		// get all future concerts
		// super users will see it all
		if($this->sysdata->isUserSuperUser($uid)) {
			$query = "SELECT * FROM concert WHERE end > NOW() ORDER BY begin, end";
			$concerts = $this->database->getSelection($query);
		}
		else {
			// only show concerts of groups and rehearsal phases the user is in
			$phases = $this->getUsersPhases($uid);
			$phasesWhere = "WHERE ";
			if(count($phases) == 0) {
				$phasesWhere .= "0 = 1"; // no phases
			}
			else {
				foreach($phases as $i => $p) {
					if($i > 0) $phasesWhere .= " OR ";
					$phasesWhere .= "rehearsalphase = $p";
				}
			}
			
			$cid = $this->getUserContact($uid);
			
			$query = "SELECT DISTINCT c.*
				FROM concert c
					JOIN (
						(
							SELECT concert
							FROM rehearsalphase_concert
							$phasesWhere
						)
						UNION ALL (
							SELECT concert
							FROM concert_contact
							WHERE contact = $cid
						)
					) AS concerts ON c.id = concerts.concert
				WHERE END > NOW( )
				ORDER BY BEGIN,
				END";
			$concerts = $this->database->getSelection($query);
		}
		
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
				$q3 = "SELECT CONCAT_WS(' ', name, surname) as name, phone, mobile, email, web ";
				$q3 .= "FROM contact WHERE id = " . $concerts[$i]["contact"];
				$contact = $this->database->getRow($q3);
			}
			else {
				$contact = array(
					"name" => "",
					"phone" => "", "mobile" => "",
					"email" => "", "web" => ""
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
			
			// resolve outfit
			if($concerts[$i]["outfit"] != "") {
				$q5 = "SELECT name, description FROM outfit WHERE id = " . $concerts[$i]["outfit"];
				$outfit = $this->database->getRow($q5);
				$outfit_out = $outfit["name"];
				if($outfit["description"] != "") {
					$outfit_out .= ": " . $outfit["description"];
				}
			}
			else {
				$outfit_out = "-";
			}
			
			// build result for by row
			array_push($result, array(
				"id" => $concerts[$i]["id"],
				"title" => $concerts[$i]["title"],
				"begin" => $concerts[$i]["begin"],
				"end" => $concerts[$i]["end"],
				"meetingtime" => $concerts[$i]["meetingtime"],
				"approve_until" => $concerts[$i]["approve_until"],
				"notes" => $concerts[$i]["notes"],
				"location_name" => $location["name"],
				"location_notes" => $location["notes"],
				"location_street" => $address["street"],
				"location_city" => $address["city"],
				"location_zip" => $address["zip"],
				"contact_name" => $contact["name"],
				"contact_phone" => $contact["phone"],
				"contact_mobile" => $contact["mobile"],
				"contact_email" => $contact["email"],
				"contact_web" => $contact["web"],
				"program_id" => $concerts[$i]["program"],
				"program_name" => $program["name"],
				"program_notes" => $program["notes"],
				"outfit" => $outfit_out
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
		
		// order contacts
		$query .= " ORDER BY c2.name, c2.surname";
		
		return $this->database->getSelection($query);
	}
	
	public function getGroupContacts($groupId) {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM contact c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT OUTER JOIN instrument i ON c2.instrument = i.id ";
		$query .= "JOIN contact_group cg ON c2.id = cg.contact ";
		$query .= "WHERE cg.group = $groupId ";
		$query .= "ORDER BY c2.name, c2.surname";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Retrieves the groups for the given/current user.
	 * @param Integer $uid Optional: User ID, by default current user.
	 * @return Flat array of groups.
	 */
	public function getUsersGroups($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		$cid = $this->database->getCell($this->database->getUserTable(), "contact", "id = $uid");
		$query = "SELECT `group` FROM contact_group WHERE contact = $cid";
		$sel = $this->database->getSelection($query);
		return Database::flattenSelection($sel, "group");
	}
	
	/**
	 * Retrieves the rehearsal phases for the given/current user.
	 * @param Integer $uid Optional: User ID, by default current user.
	 * @return Flat array of phases.
	 */
	public function getUsersPhases($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		$cid = $this->database->getCell($this->database->getUserTable(), "contact", "id = $uid");
		$query = "SELECT rehearsalphase FROM rehearsalphase_contact WHERE contact = $cid";
		$sel = $this->database->getSelection($query);
		return Database::flattenSelection($sel, "rehearsalphase");
	}
	
	/**
	 * Retrieves all votes the user has ever participated in.
	 * @param Integer $uid Optional: User ID, by default current user.
	 * @return array of votes as a selection
	 */
	public function getUsersVotesAll($uid = -1) {
		$query = "SELECT v.* FROM vote v JOIN vote_group g ON v.id = g.vote WHERE g.user = $uid";
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
	 * Retrieves the login name of the given user.
	 * @param Integer $uid User ID, by default current user.
	 * @return Login name.
	 */
	public function getLogin($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		return $this->database->getCell($this->database->getUserTable(), "login", "id = $uid");
	}
	
	/**
	 * Retrives all locations for any group by default. 
	 * If groups is set, then only locations of these groups are returned.
	 * @param array $groups Optionally an array of location_type IDs.
	 * @return All locations with joined address.
	 */
	public function getLocations($groups=null) {
		$query = "SELECT l.id, name, notes, street, city, zip, country ";
		$query .= "FROM location l, address a ";
		$query .= "WHERE l.address = a.id ";
		if($groups != null && count($groups) > 0) {
			$query .= "AND (";
			foreach($groups as $i => $locationType) {
				if($i > 0) $query .= " OR ";
				$query .= "location_type = $locationType";
			}
			$query .= ") ";
		}
		$query .= "ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns simple name of location from database.
	 * @param int $locId Location ID.
	 * @return String Name of the location.
	 */
	public function getLocationName($locId) {
		return $this->database->getCell("location", "name", "id = $locId");
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
	 * Tries to find a user for the given contact.
	 * @param Integer $cid Contact ID.
	 * @return array Null or array or user information.
	 */
	function getUserForContact($cid) {
		$uid = $this->database->getCell($this->database->getUserTable(), "id", "contact = $cid");
		if($uid == null) {
			return null;
		}
		return $this->database->getRow("SELECT * FROM user WHERE id = $uid");
	}
	
	/**
	 * All available groups.
	 * @param boolean active optional: show only active (true), false or null accepted.
	 * @param boolean showNumberMembers optional: add number of members to result (true), default false.
	 * @return array DB Selection of all groups.
	 */
	function getGroups($active = null, $showNumberMembers=false) {
		$query = "SELECT * FROM `group`";
		if($showNumberMembers) {
			$memberCaption = Lang::txt("members");
			$query = "SELECT g.*, CONCAT(g.name, ' (', count(cg.contact), ' " . $memberCaption . ")') as name_member " 
					. "FROM `group` g JOIN contact_group cg ON cg.group = g.id";
		}
		$query .= " WHERE is_active = ";
		if($active != null && $active == false) {
			$query .= "0";
		} else {
			$query .= "1";
		}
		if($showNumberMembers) {
			$query .= " GROUP BY g.id";
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
	function isGroupMember($gid, $uid = -1) {
		$contact = $this->getUserContact($uid);
		$ct = $this->database->getCell("contact_group", "count(*)", "`group` = $gid AND contact = $contact");
		return ($ct > 0);
	}
	
	function getTours() {
		return $this->database->getSelection("SELECT * FROM tour ORDER BY start");
	}
	
	function getEquipment() {
		return $this->database->getSelection("SELECT * FROM equipment ORDER BY name");
	}
	
	function getConductors() {
		$query = "SELECT * FROM contact WHERE is_conductor = 1";
		return $this->database->getSelection($query);
	}
	
	function getConductorname($cid) {
		$query = "SELECT CONCAT(name, ' ', surname) as name FROM contact WHERE id = $cid";
		$row = $this->database->getRow($query);
		return $row["name"];
	}
	
	function getDocumentTypes() {
		$query = "SELECT * FROM `doctype`";
		return $this->database->getSelection($query);
	}
	
}