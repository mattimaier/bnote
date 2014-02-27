<?php

/**
 * Data Access Class for member data.
 * @author matti
 *
 */
class MitspielerData extends AbstractData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"surname" => array("Name", FieldType::CHAR),
				"name" => array("Vorname", FieldType::CHAR),
				"phone" => array("Telefon", FieldType::CHAR),
				"fax" => array("Fax", FieldType::CHAR),
				"mobile" => array("Mobil", FieldType::CHAR),
				"business" => array("Geschäftlich", FieldType::CHAR),
				"email" => array("E-Mail", FieldType::EMAIL),
				"address" => array("Adresse", FieldType::REFERENCE),
				"instrument_name" => array("Instrument", FieldType::REFERENCE),
				"status" => array("Status", FieldType::ENUM)	
		);
		
		$this->init($dir_prefix);
	}
	
	/**
	 * Retrieves all members from the database which are associated with given or current user.
	 * @param Integer $uid optional: User ID, by default current user.
	 * @param Boolean $singleInfo optional: Display Name, Surname and ID in single field
	 * @return Members of groups and phases the current user is part of.
	 */
	function getMembers($uid = -1, $singleInfo = true) {
		$single = "";
		if($singleInfo) $single = ", c.name, c.surname, c.id";
		$fields = "CONCAT(c.name, ' ', c.surname) as fullname, phone, mobile, email, i.name as instrument" . $single;
		$order = "ORDER BY fullname, instrument";
		
		if($this->getSysdata()->isUserSuperUser()) {
			$query = "SELECT $fields FROM contact c
					  JOIN instrument i ON c.instrument = i.id
					  $order";
			return $this->database->getSelection($query);
		}
		
		$contacts = array();
		$currContact = $this->getSysdata()->getUsersContact($uid);
		$cid = $currContact["id"];
		
		// get user's groups
		$query = "SELECT DISTINCT $fields
					FROM (
					  SELECT `group` as id FROM contact_group WHERE contact = $cid
					) as groups JOIN contact_group ON groups.id = contact_group.group
					JOIN contact c ON contact_group.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					$order";
		$groupContacts = $this->database->getSelection($query);
		
		// get user's phases
		$query = "SELECT DISTINCT $fields
					FROM (
					  SELECT rehearsalphase FROM rehearsalphase_contact WHERE contact = $cid
					) as phases JOIN rehearsalphase_contact ON phases.rehearsalphase = rehearsalphase_contact.rehearsalphase
					JOIN contact c ON rehearsalphase_contact.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					$order";
		$phaseContacts = $this->database->getSelection($query);
		
		$contacts[0] = $groupContacts[0];
		for($i = 1; $i < count($groupContacts); $i++) {
			array_push($contacts, $groupContacts[$i]);
		}
		for($i = 1; $i < count($phaseContacts); $i++) {
			if(!$this->isContactInArray($phaseContacts[$i], $contacts)) { 
				array_push($contacts, $phaseContacts[$i]);
			}
		}
		
		return $this->filterSuperUsers($contacts);
	}
	
	private function isContactInArray($contact, $contacts) {
		foreach($contacts as $i => $c) {
			if($c["id"] == $contact["id"]) return true;
		}
		return false;
	}
	
	/**
	 * Removes all super users from selection.
	 * @param Array $selection Database Selection Array
	 * @return Selection array without super users.
	 */
	private function filterSuperUsers($selection) {
		$filtered = array();
		$superUsers = $this->getSysdata()->getSuperUserContactIDs();
		$filtered[0] = $selection[0];
		$count_f = 1;
		for($i = 1; $i < count($selection); $i++) {
			if(!in_array($selection[$i]["id"], $superUsers)) {
				$filtered[$count_f++] = $selection[$i];
			}
		}
		return $filtered;
	}
	
}

?>