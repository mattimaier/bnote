<?php

/**
 * Data Access Class for concert data.
 * @author matti
 *
 */
class KonzerteData extends AbstractLocationData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("Auftritt ID", FieldType::INTEGER),
			"title" => array("Titel", FieldType::CHAR),
			"begin" => array("Beginn", FieldType::DATETIME),
			"end" => array("Ende", FieldType::DATETIME),
			"approve_until" => array("Zusagen bis", FieldType::DATETIME),
			"meetingtime" => array("Treffpunkt (Zeit)", FieldType::DATETIME),
			"organizer" => array("Veranstalter", FieldType::CHAR),
			"location" => array("Ort", FieldType::REFERENCE),
			"accommodation" => array("Unterkunft", FieldType::REFERENCE),
			"program" => array("Programm", FieldType::REFERENCE),
			"contact" => array("Kontakt", FieldType::REFERENCE),
			"outfit" => array("Outfit", FieldType::REFERENCE),
			"notes" => array("Anmerkungen", FieldType::TEXT), 
			"payment" => array("Gage", FieldType::DECIMAL),
			"conditions" => array("Konditionen", FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location",
			"program" => "program",
			"contact" => "contact",
			"outfit" => "outfit",
			"accommodation" => "location"
		);
		
		$this->table = "concert";
		
		$this->init($dir_prefix);
		$this->init_trigger($dir_prefix);
	}
	
	public function delete($id) {
		$query = "DELETE FROM concert_contact WHERE concert = $id";
		$this->database->execute($query);
		
		$query = "DELETE FROM concert_user WHERE concert = $id";
		$this->database->execute($query);
		
		parent::delete($id);
	}
	
	function getConcert($id) {
		$c = $this->findByIdNoRef($id);
		$custom = $this->getCustomFieldData('g', $id);
		return array_merge($c, $custom);
	}
	
	function getFutureConcerts() {
		return $this->adp()->getFutureConcerts();
	}
	
	function getPastConcerts() {
		/* 
		 * For complexity reasons is this data filtering
		 * done in PHP instead of SQL. Since there are only
		 * very few concerts usually, this shouldn't be a
		 * problem!
		 */
		$result = array();
		
		// add header
		array_push($result, array(
			"id", "title", "begin", "end", "notes", 
			"location_name", "location_city",
			"contact_name", "program_name"
		));
		
		// get all future concerts
		$query = "SELECT * FROM concert WHERE end < NOW() ORDER BY begin ASC";
		$concerts = $this->database->getSelection($query);
		
		// iterate over concerts and replace foreign keys with data
		for($i = 1; $i < count($concerts); $i++) {
			// resolve location -> mandatory!
			$loc_id = $concerts[$i]["location"];
			if($loc_id > 0) {
				$location = $this->getLocation($loc_id);
			}
			else {
				$location = array(
					"name" => "-",
					"address" => "0"
				);
			}
			
			// resolve address -> address id present, because location is mandatory
			$address = $this->getAddress($location["address"]);
			if($address == null || $address == "") {
				$address = array(
					"city" => "-"
				);
			}
			
			// resolve contact
			if($concerts[$i]["contact"] != "") {
				$contact = $this->getContact($concerts[$i]["contact"]);
			}
			else {
				$contact = array(
					"name" => "", "phone" => "", "email" => "", "web" => ""
				);
			}
			
			// resolve program
			if($concerts[$i]["program"] != "") {
				$program = $this->getProgram($concerts[$i]["program"]);
			}
			else {
				$program = array(
					"name" => "", "notes" => ""
				);
			}
			
			// build result for by row
			array_push($result, array(
				"id" => $concerts[$i]["id"],
				"title" => $concerts[$i]["title"],
				"begin" => $concerts[$i]["begin"],
				"end" => $concerts[$i]["end"], 
				"notes" => $concerts[$i]["notes"],
				"location_name" => $location["name"],
				"location_city" => $address["city"],
				"contact_name" => $contact["name"],
				"program_name" => $program["name"],
			));
		}
		return $result;
	}
	
	function getLocation($id) {
		$q1 = "SELECT name, notes, address FROM location ";
		$q1 .= "WHERE id = $id";
		return $this->database->getRow($q1);
	}
	
	function getContact($id) {
		$q3 = "SELECT CONCAT_WS(' ', name, surname) as name, phone, email, web ";
		$q3 .= "FROM contact WHERE id = " . $id;
		return $this->database->getRow($q3);
	}
	
	function getProgram($id) {
		$q4 = "SELECT name, notes FROM program ";
		$q4 .= "WHERE id = " . $id;
		return $this->database->getRow($q4);
	}
	
	function getOutfit($id) {
		$q5 = "SELECT name FROM outfit WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	function getCustomData($cid) {
		return $this->getCustomFieldData('g', $cid);
	}
	
	function getLocations() {
		return $this->adp()->getLocations(array(2, 3, 4, 5));
	}
	
	function getContacts() {
		$contacts = $this->adp()->getContacts();
		
		// add fullname
		$contacts[0]["fullname"] = "Name";
		for($i = 1; $i < count($contacts); $i++) {
			$contacts[$i]["fullname"] = $contacts[$i]["name"] . " " . $contacts[$i]["surname"];
		}
		
		return $contacts;
	}
	
	function getTemplates() {
		return $this->adp()->getTemplatePrograms();
	}
	
	function create($values) {
		// Validation
		$this->validate($values);
		
		// create concert
		$concertId = parent::create($values);
		
		// adds members of the selected group(s), add groups themselves
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$this->addMembersToConcert($groups, $concertId);
		$this->addGroupsToConcert($groups, $concertId);
		
		// add equipment
		$equipmentSelection = GroupSelector::getPostSelection($this->adp()->getEquipment(), "equipment");
		$this->addEquipmentToConcert($equipmentSelection, $concertId);
		
		// add custom data
		$this->createCustomFieldData('g', $concertId, $values);
		
		// create trigger if configured
		if($this->triggerServiceEnabled) {
			$approve_dt = Data::convertDateToDb($values["approve_until"]);
			$this->createTrigger($approve_dt, $this->buildTriggerData("C", $concertId));
		}
		
		return $concertId;
	}
	
	public function addMembersToConcert($groups, $concertId) {
		foreach($groups as $i => $groupId) {
			$contacts = $this->adp()->getGroupContacts($groupId);
			$query = "INSERT INTO concert_contact VALUES ";
			$newEntries = 0;
				
			foreach($contacts as $j => $contact) {
				if($j == 0) continue;
				$cid = $contact["id"];
				if(!$this->isContactInConcert($concertId, $cid) && !$this->getSysdata()->isContactSuperUser($cid)) {
					if($newEntries++ > 0) $query .= ",";
					$query .= "($concertId, $cid)";
				}
			}
				
			if($newEntries > 0) {
				$this->database->execute($query);
			}
		}
	}
	
	function addGroupsToConcert($groups, $concertId) {
		//TODO
	}
	
	function addEquipmentToConcert($equipmentSelection, $concertId) {
		//TODO
	}
	
	function getParticipants($cid) {
		$query = 'SELECT c.id, cat.name as category, i.name as instrument, CONCAT_WS(" ", c.name, c.surname) as name, c.nickname, ';
		$query .= ' CASE cu.participate WHEN 1 THEN "ja" WHEN 2 THEN "vielleicht" ELSE "nein" END as participate, cu.reason';		
		$query .= ' FROM concert_user cu JOIN user u ON cu.user = u.id';
		$query .= '  JOIN contact c ON u.contact = c.id';
		$query .= '  LEFT JOIN instrument i ON c.instrument = i.id';
		$query .= '  LEFT JOIN category cat ON i.category = cat.id';
		$query .= ' WHERE cu.concert = ' . $cid;
		$query .= ' ORDER BY cat.id, i.name, participate, name';
		
		return $this->database->getSelection($query);
	}
	
	function getOpenParticipants($cid) {
		// solve this problem programmatically - easier
		$parts = $this->getParticipants($cid);
		$contacts = $this->getConcertContacts($cid);
		$result = array();
		$result[0] = $contacts[0];
		for($i = 1; $i < count($contacts); $i++) {
			$contactParts = false;
			for($j = 1; $j < count($parts); $j++) {
				if($parts[$j]["id"] == $contacts[$i]["id"]) {
					$contactParts = true;
					break;
				}
			}
			if(!$contactParts) array_push($result, $contacts[$i]);
		}
		return $result;
	}
	
	private function isContactInConcert($concertId, $contactId) {
		$ct = $this->database->getCell("concert_contact", "count(contact)", "concert = $concertId AND contact = $contactId");
		return ($ct > 0);
	}
	
	function getConcertContacts($cid) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as fullname, c.nickname, c.phone, c.mobile, c.email, i.name as instrument ";
		$query .= "FROM concert_contact cc JOIN contact c ON cc.contact = c.id LEFT OUTER JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE cc.concert = $cid ";
		$query .= "ORDER BY fullname";
		return $this->database->getSelection($query);
	}
	
	function deleteConcertContact($concertid, $contactid) {
		$query = "DELETE FROM concert_contact WHERE concert = $concertid AND contact = $contactid";
		$this->database->execute($query);
	}
	
	function addConcertContact($concertid, $contacts) {
		// do not insert duplicates, therefore check which contacts are in it already
		$contactsInConcertDbSel = $this->database->getSelection("SELECT contact FROM concert_contact WHERE concert = $concertid");
		$contactsInConcert = $this->database->flattenSelection($contactsInConcertDbSel, "contact");
		
		$values = array();
		foreach($contacts as $i => $contact) {
			if(!in_array($contact, $contactsInConcert)) {
				array_push($values, "($concertid, $contact)");
			}
		}
		if(count($values) > 0) {
			$query = "INSERT INTO concert_contact VALUES " . join(",", $values); 
			$this->database->execute($query);
		}
	}
	
	function addConcertContactGroup($concertid, $groups) {
		$contactsToAdd = array();
		foreach($groups as $i => $group) {
			$contactSelection = $this->adp()->getGroupContacts($group);
			$grpContactIds = $this->database->flattenSelection($contactSelection, "id");
			$contactsToAdd = array_merge($contactsToAdd, $grpContactIds);
		}
		$this->addConcertContact($concertid, $contactsToAdd);
	}
	
	function getRehearsalphases($concertid) {
		$query = "SELECT p.* ";
		$query .= "FROM rehearsalphase_concert rc JOIN rehearsalphase p ON rc.rehearsalphase = p.id ";
		$query .= "WHERE concert = $concertid ";
		$query .= "ORDER BY p.begin, p.end";
		return $this->database->getSelection($query);
	}
	
	function update($id, $values) {
		// add manual validation to default update method
		if(isset($values["begin"])) {
			$this->regex->isDateTime($values["begin"]);
		}
		if(isset($values["end"])) {
			$this->regex->isDateTime($values["end"]);
		}
		if(isset($values["approve_until"])) {
			$this->regex->isDateTime($values["approve_until"]);
		}
		if(isset($values["location"])) {
			$this->regex->isPositiveAmount($values["location"]);
		}
		if(isset($values["program"]) && $values["program"] > 0) {
			$this->regex->isPositiveAmount($values["program"]);
		}
		if(isset($values["notes"])) {
			$this->regex->isText($values["notes"]);
		}
		if(isset($values["contact"])) {
			$this->regex->isPositiveAmount($values["contact"]);
		}
		
		// default update
		parent::update($id, $values);
		
		// update custom data
		$this->updateCustomFieldData('g', $id, $values);
	}
}

?>