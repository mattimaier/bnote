<?php

/**
 * Data Access Class for member data.
 * @author matti
 *
 */
class MitspielerData extends AbstractLocationData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("MitspielerData_construct.id"), FieldType::INTEGER),
				"surname" => array(Lang::txt("MitspielerData_construct.surname"), FieldType::CHAR),
				"name" => array(Lang::txt("MitspielerData_construct.name"), FieldType::CHAR),
				"nickname" => array(Lang::txt("MitspielerData_construct.nickname"), FieldType::CHAR),
				"company" => array(Lang::txt("MitspielerData_construct.company"), FieldType::CHAR),
				"phone" => array(Lang::txt("MitspielerData_construct.phone"), FieldType::CHAR),
				"fax" => array(Lang::txt("MitspielerData_construct.fax"), FieldType::CHAR),
				"mobile" => array(Lang::txt("MitspielerData_construct.mobile"), FieldType::CHAR),
				"business" => array(Lang::txt("MitspielerData_construct.business"), FieldType::CHAR),
				"email" => array(Lang::txt("MitspielerData_construct.email"), FieldType::EMAIL),
				"address" => array(Lang::txt("MitspielerData_construct.address"), FieldType::REFERENCE),
				"instrument_name" => array(Lang::txt("MitspielerData_construct.instrument_name"), FieldType::REFERENCE),
				"birthday" => array(Lang::txt("MitspielerData_construct.birthday"), FieldType::DATE),
				"status" => array(Lang::txt("MitspielerData_construct.status"), FieldType::ENUM),
				"city" => array(Lang::txt("MitspielerData_construct.city"), FieldType::CHAR),
				"zip" => array(Lang::txt("MitspielerData_construct.zip"), FieldType::CHAR)
		);
		
		$this->init($dir_prefix);
	}
	
	/**
	 * Retrieves all members from the database which are associated with the current user.
	 * @return Array Members of groups and phases the current user is part of.
	 */
	function getMembers() {
		$uid = $this->getUserId();
		
		$fields = array(
				"c.id",
				"c.name",
				"c.surname",
				"CONCAT(c.name, ' ', c.surname) as fullname",
				"nickname",
				"IF(share_email = 1, email, '') as email",
				"web",
				"i.id as instrument",
				"i.name as instrumentname",
				"notes",
				
				// address fields
				"IF(share_address = 1, a.street, '') as street",
				"IF(share_address = 1, a.zip, '') as zip",
				"IF(share_address = 1, a.city, '') as city",
				"IF(share_address = 1, a.state, '') as state",
				"IF(share_address = 1, a.country, '') as country",
				
				// phone fields
				"IF(share_phones = 1, phone, '') as phone",
				"IF(share_phones = 1, mobile, '') as mobile",
				"IF(share_phones = 1, fax, '') as fax",
				"IF(share_phones = 1, business, '') as business",
				
				// birthday field
				"IF(share_birthday = 1, birthday, '') as birthday"
		);
		$fieldsStr = join(",", $fields);
		$order = "ORDER BY fullname, i.rank";
		
		// Super User or Admin
		if($this->getSysdata()->isUserSuperUser($uid) || $this->getSysdata()->isUserMemberGroup(1, $uid)) {
			$query = "SELECT $fieldsStr FROM contact c
					  JOIN instrument i ON c.instrument = i.id
					  LEFT OUTER JOIN address a ON c.address = a.id
					  $order";
			$contacts = $this->database->getSelection($query);
			return $this->appendCustomDataToSelection("c", $contacts);
		}
		
		$contacts = array();
		$currContact = $this->getSysdata()->getUsersContact($uid);
		$cid = $currContact["id"];
		
		// get user's groups
		$query = "SELECT DISTINCT $fieldsStr
					FROM (
					  SELECT `group` as id FROM contact_group WHERE contact = ?
					) as groups JOIN contact_group ON groups.id = contact_group.group
					JOIN contact c ON contact_group.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					LEFT OUTER JOIN address a ON c.address = a.id
					$order";
		$groupContacts = $this->database->getSelection($query, array(array("i", $cid)));
		
		// get user's phases
		$query = "SELECT DISTINCT $fieldsStr
					FROM (
					  SELECT rehearsalphase FROM rehearsalphase_contact WHERE contact = ?
					) as phases JOIN rehearsalphase_contact ON phases.rehearsalphase = rehearsalphase_contact.rehearsalphase
					JOIN contact c ON rehearsalphase_contact.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					LEFT OUTER JOIN address a ON c.address = a.id
					$order";
		$phaseContacts = $this->database->getSelection($query, array(array("i", $cid)));
		
		$contacts[0] = $groupContacts[0];
		for($i = 1; $i < count($groupContacts); $i++) {
			array_push($contacts, $groupContacts[$i]);
		}
		for($i = 1; $i < count($phaseContacts); $i++) {
			if(!$this->isContactInArray($phaseContacts[$i], $contacts)) { 
				array_push($contacts, $phaseContacts[$i]);
			}
		}
		
		return $this->appendCustomDataToSelection('c', $this->filterSuperUsers($contacts));
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
	 * @return Array Selection without super users
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
				WHERE c.id = ?";
			return $this->database->fetchRow($query, array(array($cid)));
		}
		return null;
	}
}
