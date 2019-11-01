<?php

/**
 * Data Access Class for concert data.
 * @author matti
 *
 */
class KonzerteData extends AbstractLocationData {
	
	public static $CUSTOM_DATA_OTYPE = 'g';
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("KonzerteData_construct.id"), FieldType::INTEGER),
			"title" => array(Lang::txt("KonzerteData_construct.title"), FieldType::CHAR, true),
			"begin" => array(Lang::txt("KonzerteData_construct.begin"), FieldType::DATETIME, true),
			"end" => array(Lang::txt("KonzerteData_construct.end"), FieldType::DATETIME, true),
			"approve_until" => array(Lang::txt("KonzerteData_construct.approve_until"), FieldType::DATETIME, true),
			"meetingtime" => array(Lang::txt("KonzerteData_construct.meetingtime"), FieldType::DATETIME, true),
			"organizer" => array(Lang::txt("KonzerteData_construct.organizer"), FieldType::CHAR),
			"location" => array(Lang::txt("KonzerteData_construct.location"), FieldType::REFERENCE),
			"accommodation" => array(Lang::txt("KonzerteData_construct.accommodation"), FieldType::REFERENCE),
			"program" => array(Lang::txt("KonzerteData_construct.program"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("KonzerteData_construct.contact"), FieldType::REFERENCE),
			"outfit" => array(Lang::txt("KonzerteData_construct.outfit"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("KonzerteData_construct.notes"), FieldType::TEXT), 
			"payment" => array(Lang::txt("KonzerteData_construct.payment"), FieldType::CURRENCY),
			"conditions" => array(Lang::txt("KonzerteData_construct.conditions"), FieldType::TEXT)
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
		$custom = $this->getCustomFieldData(KonzerteData::$CUSTOM_DATA_OTYPE, $id);
		return array_merge($c, $custom);
	}
	
	function getFutureConcerts() {
		return $this->adp()->getFutureConcerts();
	}
	
	function getPastConcerts($from, $to) {
		$this->regex->isDate($from);
		$this->regex->isDate($to);
		
		$query = "SELECT c.id, c.begin, c.title, c.organizer, l.name as location_name, a.city as location_city, 
				CONCAT(k.name, ' ', k.surname) as contact_name, p.name as program_name
				FROM concert c
				LEFT OUTER JOIN location l ON c.location = l.id
				LEFT OUTER JOIN address a ON l.address = a.id
				LEFT OUTER JOIN contact k ON c.contact = k.id
				LEFT OUTER JOIN program p ON c.program = p.id
				WHERE begin >= '" . Data::convertDateToDb($from) . "' AND end <= '" . Data::convertDateToDb($to) . "'
				ORDER BY begin ASC";
		$concerts = $this->database->getSelection($query);
		return $concerts;
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
		$q4 = "SELECT id, name, notes FROM program ";
		$q4 .= "WHERE id = " . $id;
		return $this->database->getRow($q4);
	}
	
	function getOutfit($id) {
		$query = "SELECT name FROM outfit WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	function getCustomData($cid) {
		return $this->getCustomFieldData(KonzerteData::$CUSTOM_DATA_OTYPE, $cid);
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
	
	function validate($values) {
		// Manual Validation
		$this->regex->isSubject($values["title"], "title");
		$this->regex->isDateTime($values["begin"], "begin");
		$this->regex->isDateTime($values["end"], "end");
		$this->regex->isDateTime($values["approve_until"], "approve_until");
		$this->regex->isDateTime($values["meetingtime"], "meetingtime");
		if(isset($values["notes"]) && $values["notes"] != "") {
			$this->regex->isText($values["notes"], "notes");
		}
		$this->regex->isPositiveAmount($values["location"]);  // location ID
		if(isset($values["organizer"]) && $values["organizer"] != "") {
			$this->regex->isSubject($values["organizer"], "organizer");
		}
		$this->regex->isPositiveAmount($values["contact"]);  // contact ID
		if(isset($values["accommodation"]) && $values["accommodation"] > 0) {
			$this->regex->isPositiveAmount($values["accommodation"], "accommodation");
		}
		if(isset($values["program"]) && $values["program"] > 0) {
			$this->regex->isPositiveAmount($values["program"], "program");
		}
		if(isset($values["outfit"]) && $values["outfit"] > 0) {
			$this->regex->isPositiveAmount($values["outfit"], "outfit");
		}
		if(isset($values["payment"]) && $values["payment"] != "") {
			$this->regex->isMoney($values["payment"], "payment");
		}
		if(isset($values["conditions"]) && $values["conditions"] != "") {
			$this->regex->isText($values["conditions"], "conditions");
		}
		$this->validateCustomData($values, $this->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE));
	}
	
	function create($values) {
		// at least one group must be selected
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		if(count($groups) == 0) {
			new BNoteError(Lang::txt("KonzerteData_create.error"));
		}
		
		// default values
		if(!isset($values["payment"]) || $values["payment"] == "") {
			$values["payment"] = 0;
		}
				
		// create concert
		$concertId = parent::create($values);
		
		// adds members of the selected group(s), add groups themselves
		$this->addMembersToConcert($groups, $concertId);
		$this->addGroupsToConcert($groups, $concertId);
		
		// add equipment
		$equipmentSelection = GroupSelector::getPostSelection($this->adp()->getEquipment(), "equipment");
		if(count($equipmentSelection) > 0) {
			$this->addEquipmentToConcert($equipmentSelection, $concertId);
		}
		
		// add custom data
		$this->createCustomFieldData(KonzerteData::$CUSTOM_DATA_OTYPE, $concertId, $values);
		
		// create trigger if configured
		if($this->triggerServiceEnabled) {
			$approve_dt = Data::convertDateToDb($values["approve_until"]);
			$this->createTrigger($approve_dt, $this->buildTriggerData("C", $concertId));
		}
		
		return $concertId;
	}
	
	function update($id, $values) {
		// default update
		parent::update($id, $values);
	
		// update custom data
		$this->updateCustomFieldData(KonzerteData::$CUSTOM_DATA_OTYPE, $id, $values);
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
		$query = "INSERT INTO concert_group (concert, `group`) VALUES ($concertId,";
		$query .= join("), ($concertId,", $groups) . ")";
		$this->database->execute($query);
	}
	
	function addEquipmentToConcert($equipmentSelection, $concertId) {
		$query = "INSERT INTO concert_equipment (concert, `equipment`) VALUES ($concertId,";
		$query .= join("), ($concertId,", $equipmentSelection) . ")";
		$this->database->execute($query);
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
	
	function getConcertGroups($cid) {
		$query = "SELECT g.* FROM concert_group cg JOIN `group` g ON cg.`group` = g.id WHERE cg.concert = $cid";
		return $this->database->getSelection($query);
	}
	
	function getConcertEquipment($cid) {
		$query = "SELECT e.* FROM concert_equipment ce JOIN equipment e ON ce.equipment = e.id WHERE ce.concert = $cid";
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
}

?>