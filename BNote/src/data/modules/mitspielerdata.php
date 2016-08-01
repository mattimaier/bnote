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
				"nickname" => array("Spitzname", FieldType::CHAR),
				"phone" => array("Telefon", FieldType::CHAR),
				"fax" => array("Fax", FieldType::CHAR),
				"mobile" => array("Mobil", FieldType::CHAR),
				"business" => array("Geschäftlich", FieldType::CHAR),
				"email" => array("E-Mail", FieldType::EMAIL),
				"address" => array("Adresse", FieldType::REFERENCE),
				"instrument_name" => array("Instrument", FieldType::REFERENCE),
				"birthday" => array("Geburtstag", FieldType::DATE),
				"status" => array("Status", FieldType::ENUM),
				"city" => array("Stadt", FieldType::CHAR),
				"zip" => array("PLZ", FieldType::CHAR)
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
		if($uid == -1) {
			$uid = $_SESSION["user"];
		}
		
		$single = ", c.id";
		if($singleInfo) $single .= ", c.name, c.surname";
		$fields = "c.id, CONCAT(c.name, ' ', c.surname) as fullname, nickname, phone, mobile, email, web, fax, business, 
				notes,  a.street, a.zip, a.city, i.id as instrument, i.name as instrumentname, birthday" . $single;
		$order = "ORDER BY fullname, instrument";
		
		// Super User or Admin
		if($this->getSysdata()->isUserSuperUser($uid) || $this->getSysdata()->isUserMemberGroup(1, $uid)) {
			$query = "SELECT $fields FROM contact c
					  JOIN instrument i ON c.instrument = i.id
					  LEFT JOIN address a ON c.address = a.id
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
					LEFT JOIN address a ON c.address = a.id
					
					$order";
		$groupContacts = $this->database->getSelection($query);
		
		// get user's phases
		$query = "SELECT DISTINCT $fields
					FROM (
					  SELECT rehearsalphase FROM rehearsalphase_contact WHERE contact = $cid
					) as phases JOIN rehearsalphase_contact ON phases.rehearsalphase = rehearsalphase_contact.rehearsalphase
					JOIN contact c ON rehearsalphase_contact.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					LEFT JOIN address a ON c.address = a.id
					
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
			if($i > 0 && $c["id"] == $contact["id"]) return true;
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
	
	public function getContact($cid) {
		// check access
		$members = $this->getMembers();
		$found = false;
		for($i = 1; $i < count($members); $i++) {
			if($members[$i]["id"] == $cid) {
				$found = true;
				break;
			}
		}
		if($found) {
			$query = "SELECT c.*, i.name as instrument 
				FROM contact c JOIN instrument i ON c.instrument = i.id 
				WHERE c.id = $cid";
			return $this->database->getRow($query);
		}
		return null;
	}
}

?>