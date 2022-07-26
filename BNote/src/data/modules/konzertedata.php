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
			"conditions" => array(Lang::txt("KonzerteData_construct.conditions"), FieldType::TEXT),
			"status" => array(Lang::txt("KonzerteData_construct.status"), FieldType::ENUM)
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
		$query = "DELETE FROM concert_contact WHERE concert = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM concert_user WHERE concert = ?";
		$this->database->execute($query, array(array("i", $id)));
		
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
				CONCAT(k.name, ' ', k.surname) as contact_name, 
				k.share_phones as contact_share_phones, k.phone as contact_phone, k.business as contact_business, k.mobile as contact_mobile, 
				k.share_email as contact_share_email, k.email as contact_email,
				p.name as program_name, c.status
				FROM concert c
				LEFT OUTER JOIN location l ON c.location = l.id
				LEFT OUTER JOIN address a ON l.address = a.id
				LEFT OUTER JOIN contact k ON c.contact = k.id
				LEFT OUTER JOIN program p ON c.program = p.id
				WHERE begin >= ? AND end <= ?
				ORDER BY begin ASC";
		$params = array(array("s", $from), array("s", $to));
		$concerts = $this->database->getSelection($query, $params);
		return $concerts;
	}
	
	function getContact($id) {
		$q3 = "SELECT CONCAT_WS(' ', name, surname) as name, c.* FROM contact c WHERE id = ?";
		return $this->database->fetchRow($q3, array(array("i", $id)));
	}
	
	function getProgram($id) {
		$q4 = "SELECT id, name, notes FROM program WHERE id = ?";
		return $this->database->fetchRow($q4, array(array("i", $id)));
	}
	
	function getOutfit($id) {
		$query = "SELECT name FROM outfit WHERE id = ?";
		return $this->database->fetchRow($query, array(array("i", $id)));
	}
	
	function getCustomData($cid) {
		return $this->getCustomFieldData(KonzerteData::$CUSTOM_DATA_OTYPE, $cid);
	}
	
	function getLocations() {
		return $this->adp()->getLocations();  # just show all for more flexibility (see #444 on GitHub)
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
	
	function getPrograms() {
		return $this->adp()->getPrograms();
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
		
		// manage program
		if($values["program"] == "new") {
			$program_query = "INSERT INTO program (name, isTemplate) VALUES (?, 0)";
			$program_id = $this->database->prepStatement($program_query, array(
					array("s", $values["title"] . " " . Lang::txt("ProgramView_construct.EntityName"))
			));
			$values["program"] = $program_id;
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
			$approve_dt = $values["approve_until"];
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
			
			$tuples = array();
			$params = array();
			foreach($contacts as $j => $contact) {
				if($j == 0) continue;
				$cid = $contact["id"];
				if(!$this->isContactInConcert($concertId, $cid) && !$this->getSysdata()->isContactSuperUser($cid)) {
					array_push($tuples, "(?, ?)");
					array_push($params, array("i", $concertId));
					array_push($params, array("i", $cid));
				}
			}
				
			if(count($tuples) > 0) {
				$query = "INSERT INTO concert_contact VALUES " . join(",", $tuples);
				$this->database->execute($query, $params);
			}
		}
	}
	
	function addGroupsToConcert($groups, $concertId) {
		$s = $this->tupleStmt($concertId, $groups);
		$query = "INSERT INTO concert_group (concert, `group`) VALUES " . $s[0];
		$this->database->execute($query, $s[1]);
	}
	
	function addEquipmentToConcert($equipmentSelection, $concertId) {
		$s = $this->tupleStmt($concertId, $equipmentSelection);
		$query = "INSERT INTO concert_equipment (concert, `equipment`) VALUES " . $s[0];
		$this->database->execute($query, $s[1]);
	}
	
	function getParticipants($cid) {
		$query = 'SELECT c.id, cat.name as category, i.name as instrument, CONCAT_WS(" ", c.name, c.surname) as name, c.nickname, ';
		$query .= ' CASE cu.participate WHEN 1 THEN "' . Lang::txt("KonzerteData_getParticipants.yes") . '" WHEN 2 THEN "' . Lang::txt("KonzerteData_getParticipants.maybe") . '" WHEN -1 THEN "-" ELSE "' . Lang::txt("KonzerteData_getParticipants.no") . '" END as participate, cu.reason, cu.replyon';		
		$query .= ' FROM concert_user cu JOIN user u ON cu.user = u.id';
		$query .= '  JOIN contact c ON u.contact = c.id';
		$query .= '  LEFT JOIN instrument i ON c.instrument = i.id';
		$query .= '  LEFT JOIN category cat ON i.category = cat.id';
		$query .= ' WHERE cu.concert = ?';
		$query .= ' ORDER BY cat.id, i.name, participate, name';
		
		return $this->database->getSelection($query, array(array("i", $cid)));
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
	
	/**
	 * Returns all invitations including their participation status.
	 * @param int $concert_id Concert ID.
	 * @return Array Array Contact-based selection.
	 */
	function getFullParticipation($concert_id) {
		$query = 'SELECT c.id as contact_id, u.id as user_id, cu.participate, cu.reason, c.nickname, CONCAT_WS(" ", c.name, c.surname) as name,
					i.name as instrument
				  FROM concert_contact cc
					JOIN contact c ON cc.contact = c.id
					JOIN user u ON u.contact = c.id
					LEFT OUTER JOIN concert_user cu ON cu.user = u.id AND cu.concert = cc.concert
					LEFT OUTER JOIN instrument i ON c.instrument = i.id
				  WHERE cc.concert = ?
				  ORDER BY c.name, c.surname ASC';
		return $this->database->getSelection($query, array(array("i", $concert_id)));
	}
	
	function saveParticipation($concert_id) {
		// get all participations for this concert
		$query = "SELECT * FROM concert_user cu WHERE concert = ?";
		$old_participation = $this->database->getSelection($query, array(array("i", $concert_id)));
		$old_participate = Database::flattenSelection($old_participation, "user");
		
		// run through posted result and update the database
		foreach($_POST as $user_key => $participate) {
			$user_id = substr($user_key, 5);
			if(in_array($user_id, $old_participate)) {
				// update participation
				$query = "UPDATE concert_user SET participate = ? WHERE user = ? AND concert = ?";				
			}
			else {
				// create participation
				$query = "INSERT INTO concert_user (participate, user, concert, replyon) VALUES (?, ?, ?, NOW())";
			}
			$params = array(array("i", $participate), array("i", $user_id), array("i", $concert_id));
			$this->database->execute($query, $params);
		}
	}
	
	private function isContactInConcert($concertId, $contactId) {
		$query = "SELECT count(contact) as cnt FROM concert_contact WHERE concert = ? AND contact = ?";
		$ct = $this->database->colValue($query, "cnt", array(array("i", $concertId), array("i", $contactId)));
		return ($ct > 0);
	}
	
	function getConcertContacts($cid) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as fullname, c.nickname, c.phone, c.mobile, c.email, i.name as instrument ";
		$query .= "FROM concert_contact cc JOIN contact c ON cc.contact = c.id LEFT OUTER JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE cc.concert = ? ";
		$query .= "ORDER BY fullname";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function getConcertGroups($cid) {
		$query = "SELECT g.* FROM concert_group cg JOIN `group` g ON cg.`group` = g.id WHERE cg.concert = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function getConcertEquipment($cid) {
		$query = "SELECT e.* FROM concert_equipment ce JOIN equipment e ON ce.equipment = e.id WHERE ce.concert = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function deleteConcertContact($concertid, $contactid) {
		$query = "DELETE FROM concert_contact WHERE concert = ? AND contact = ?";
		$this->database->execute($query, array(array("i", $concertid), array("i", $contactid)));
	}
	
	function addConcertContact($concertid, $contacts) {
		// do not insert duplicates, therefore check which contacts are in it already
		$q = "SELECT contact FROM concert_contact WHERE concert = ?";
		$contactsInConcertDbSel = $this->database->getSelection($q, array(array("i", $concertid)));
		$contactsInConcert = $this->database->flattenSelection($contactsInConcertDbSel, "contact");
		
		$values = array();
		$params = array();
		foreach($contacts as $i => $contact) {
			if(!in_array($contact, $contactsInConcert)) {
				array_push($values, "(?, ?)");
				array_push($params, array("i", $concertid));
				array_push($params, array("i", $contact));
			}
		}
		if(count($values) > 0) {
			$query = "INSERT INTO concert_contact VALUES " . join(",", $values); 
			$this->database->execute($query, $params);
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
		$query .= "WHERE concert = ? ";
		$query .= "ORDER BY p.begin, p.end";
		return $this->database->getSelection($query, array(array("i", $concertid)));
	}
	
	function getStatusOptions() {
		return array("planned", "confirmed", "cancelled", "hidden");
	}
	
	function getUsedInstruments() {
		return $this->adp()->getUsedInstruments();
	}
	
	/**
	 * Checks for the given instrument and concert what the status of participation is.
	 * @param Integer $cid Concert ID.
	 * @param Integer $instrumentId Instrument ID, set NULL (default) for all
	 * @param Boolean $stripped Cut first row (true by default)
	 */
	function getParticipantOverview($cid, $instrumentId=NULL, $stripped=TRUE) {
		$this->regex->isPositiveAmount($cid, "concertId");
		$params = array(array("i", $cid), array("i", $cid));
		$instrument = "";
		if($instrumentId != NULL) {
			$this->regex->isPositiveAmount($instrumentId, "instrumentId");
			$instrument = "AND c.instrument = ?";
			array_push($params, array("i", $instrumentId));
		}
		$query = "SELECT i.name as instrument, c.id as contact_id, CONCAT(c.name, ' ', c.surname) as contactname, u.id as user_id, IFNULL(cu.participate, -1) as participate
				FROM concert_contact cc
					JOIN contact c ON cc.contact = c.id
					JOIN user u ON u.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					LEFT OUTER JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
				WHERE cc.concert = ? $instrument
				ORDER BY instrument, contactname";
		
		$participants = $this->database->getSelection($query, $params);
		if($stripped) {
			$participants = array_splice($participants, 1);
		}
		return $participants;
	}
}