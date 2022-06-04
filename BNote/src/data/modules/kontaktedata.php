<?php

/**
 * Data Access Class for contact data.
 * @author matti
 *
 */
class KontakteData extends AbstractLocationData {
	
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
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("KontakteData_construct.id"), FieldType::INTEGER),
			"surname" => array(Lang::txt("KontakteData_construct.surname"), FieldType::CHAR),
			"name" => array(Lang::txt("KontakteData_construct.name"), FieldType::CHAR),
			"nickname" => array(Lang::txt("KontakteData_construct.nickname"), FieldType::CHAR),
			"company" => array(Lang::txt("KontakteData_construct.company"), FieldType::CHAR),
			"phone" => array(Lang::txt("KontakteData_construct.phone"), FieldType::CHAR),
			"fax" => array(Lang::txt("KontakteData_construct.fax"), FieldType::CHAR),
			"mobile" => array(Lang::txt("KontakteData_construct.mobile"), FieldType::CHAR),
			"business" => array(Lang::txt("KontakteData_construct.business"), FieldType::CHAR),
			"email" => array(Lang::txt("KontakteData_construct.email"), FieldType::EMAIL),
			"web" => array(Lang::txt("KontakteData_construct.web"), FieldType::CHAR),
			"notes" => array(Lang::txt("KontakteData_construct.notes"), FieldType::TEXT),
			"address" => array(Lang::txt("KontakteData_construct.address"), FieldType::REFERENCE),
			"instrument" => array(Lang::txt("KontakteData_construct.instrument"), FieldType::REFERENCE),
			"is_conductor" => array(Lang::txt("KontakteData_construct.is_conductor"), FieldType::BOOLEAN),
			"birthday" => array(Lang::txt("KontakteData_construct.birthday"), FieldType::DATE),
			"status" => array(Lang::txt("KontakteData_construct.status"), FieldType::ENUM),
			"share_address" => array(Lang::txt("KontakteData_construct.share_address"), FieldType::BOOLEAN),
			"share_phones" => array(Lang::txt("KontakteData_construct.share_phones"), FieldType::BOOLEAN),
			"share_birthday" => array(Lang::txt("KontakteData_construct.share_birthday"), FieldType::BOOLEAN),
			"share_email" => array(Lang::txt("KontakteData_construct.share_email"), FieldType::BOOLEAN)
		);
		
		$this->references = array(
			"address" => "address",
			"instrument" => "instrument"
		);
		
		$this->table = "contact";
		
		require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . 'userdata.php';
		$this->init($dir_prefix);
	}
	
	public function getFields() {
		$allFields = $this->fields;
		$customFields = $this->getCustomFields('c');
		for($i = 1; $i < count($customFields); $i++) {
			$field = $customFields[$i];
			$allFields[$field['techname']] = array(
					$field['txtdefsingle'],
					$this->fieldTypeFromCustom($field['fieldtype'])
			);
		}
		return $allFields;
	}
	
	/**
	 * @return array Members of a group, if "null" then by default just members and admins, if "all" then all contacts.
	 */
	function getMembers($groupFilter=NULL) {
		if($groupFilter == null) {
			return $this->getGroupContacts(KontakteData::$GROUP_MEMBER);
		}
		else if($groupFilter == "all") {
			return $this->getAllContacts();
		}
		else {
			return $this->getGroupContacts($groupFilter);
		}
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
		$query .= "        JOIN `group` ON grp.group = `group`.id ";
		$query .= "        WHERE contact.id = grp.contact AND grp.group = ? AND `group`.is_active = 1";
		$query .= "        ) as c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT OUTER JOIN instrument i ON c2.instrument = i.id ";
		$query .= "ORDER BY c2.name ASC";
		
		$contacts = $this->filterSuperUsers($this->database->getSelection($query, array(array("i", $group))));
		return $this->appendCustomDataToSelection('c', $contacts);
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
	 * Returns the row with the contact, but with readable and modified status values.
	 * @param int $id ID of the contact.
	 */
	function getContact($id) {
		$query = $this->createQuery();
		$query .= "WHERE c2.id = ?";
		$contact = $this->database->fetchRow($query, array(array("i", $id)));
		
		// add custom data
		$custom = $this->getCustomFieldData('c', $id);
		return array_merge($contact, $custom);
	}
	
	private function createQuery() {
		$query = "SELECT c2.*, i.name as instrumentname ";
		$query .= "FROM ";
		$query .= " (SELECT c.*, a.street, a.city, a.zip ";
		$query .= "  FROM contact c ";
		$query .= "  LEFT JOIN address a ";
		$query .= "  ON c.address = a.id) as c2 ";
		$query .= "LEFT OUTER JOIN instrument i ON c2.instrument = i.id ";
		return $query;
	}
	
	/**
	 * Removes all super users from selection.
	 * @param Array $selection Database Selection Array
	 * @return Array Selection without super users
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
		foreach($input as $col => $value) {
			if($value != "") {
				// check that the user has at least one of surname, name, nickname
				if($input["name"] == "" && $input["surname"] == "" && $input["nickname"] == "") {
					new BNoteError(Lang::txt("KontakteData_validate.errorNameRequired"));
				}
				
				if($col == "instrument") {
					$this->regex->isNumber($value);
				}
				else {
					// check all other values for security
					$this->validatePair($col, $value, $this->getTypeOfField($col));
				}
			}
		}
	}
	
	function create($values) {
		// validate contact
		$this->validate($values);
		
		// simply create one address per contact
		$values["address"] = $this->createAddress($values);
		
		// set share_* values to false by default
		$values["share_address"] = isset($values["share_address"]) ? $values["share_address"] : 0;  // false by default
		$values["share_phones"] = isset($values["share_phones"]) ? $values["share_phones"] : 0;  // false by default
		$values["share_birthday"] = isset($values["share_birthday"]) ? $values["share_birthday"] : 0;  // false by default
		$values["share_email"] = isset($values["share_email"]) ? $values["share_email"] : 0;  // false by default
		
		// create contact
		$cid = parent::create($values);
		
		// save custom fields
		$this->createCustomFieldData('c', $cid, $values);
		
		// create group entries
		$this->createContactGroupEntries($cid);
		
		return $cid;
	}
	
	private function createContactGroupEntries($cid) {
		$query = "INSERT INTO contact_group (contact, `group`) VALUES ";
		$tuples = array();
		$params = array();
		$groups = $this->getGroups();
		for($i = 1; $i < count($groups); $i++) {
			$gid = $groups[$i]["id"];
			$fieldId = "group_" . $gid;
			if(isset($_POST[$fieldId])) {
				array_push($tuples, "(?, ?)");
				array_push($params, array("i", $cid));
				array_push($params, array("i", $gid));
			}
		}
		
		if(count($tuples) > 0) {
			$query .= join(",", $tuples);
			$this->database->execute($query, $params);
		}
	}
	
	function update($id, $values, $plainUpdate=false) {
		if(!$plainUpdate) {
			// update address
			$values = $this->update_address($id, $values);
				
			// update groups
			$query = "DELETE FROM contact_group WHERE contact = ?";
			$this->database->execute($query, array(array("i", $id)));
			$this->createContactGroupEntries($id);
			
			// update custom data
			$this->updateCustomFieldData('c', $id, $values);
		}
		parent::update($id, $values);
	}
	
	public function update_address($id, $values) {
		$addressId = $this->getAddressFromId($id);
		$this->updateAddress($addressId, $values);
		$values["address"] = $addressId;
		return $values;
	}
	
	function delete($id) {
		// remove group memberships
		$query = "DELETE FROM contact_group WHERE contact = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		// delete custom data
		$this->deleteCustomFieldData('c', $id);
		
		// remove contact
		parent::delete($id);
	}
	
	/**
	 * @param int $id Contact ID
	 * @return True if a user account with this contact exists, otherwise false.
	 */
	function hasContactUserAccount($id) {
		$ct = $this->database->colValue("SELECT count(id) as cnt FROM user WHERE contact = ?", "cnt", array(array("i", $id)));
		return ($ct > 0);
	}
	
	/**
	 * Create a new user with default privileges.
	 * @param int $cid Contact ID
	 * @param String $username Unique login.
	 * @param String $password Unencrypted password.
	 * @return Integer User ID
	 */
	function createUser($cid, $username, $password) {
		$dao = new UserData();
		$values = array(
			"contact" => $cid,
			"login" => $username,
			"password" => $password,
			"isActive" => ""
		);
		$dao->create($values);
	}
	
	/**
	 * Gets the group name from the database.
	 * @param Integer $groupId ID of the group
	 * @return string Name of the group.
	 */
	function getGroupName($groupId) {
		return $this->database->colValue("SELECT name FROM `group` WHERE id = ?", "name", array(array("i", $groupId)));
	}
	
	function getGroups() {
		return $this->adp()->getGroups();
	}
	
	function getContactGroups($cid) {
		$query = "SELECT GROUP_CONCAT(g.name) as grpConcat ";
		$query .= "FROM `group` g JOIN contact_group cg ON cg.group = g.id ";
		$query .= "WHERE cg.contact = ? ";
		$query .= "GROUP BY cg.contact";
		$grpConcat = $this->database->getSelection($query, array(array("i", $cid)));
		if(count($grpConcat) > 1) {
			$grpString = $grpConcat[1]["grpConcat"];
		}
		else {
			$grpString = "-";
		}
		return $grpString;
	}
	
	function getContactGroupsArray($cid) {
		$query = "SELECT `group` FROM contact_group WHERE contact = ?";
		$res = $this->database->getSelection($query, array(array("i", $cid)));
		return $this->database->flattenSelection($res, "group");
	}
	
	function getContactFullGroups($cid) {
		$query = "SELECT g.id, g.name
			    FROM contact_group cg JOIN `group` g ON cg.group = g.id
				WHERE cg.contact = ? AND g.is_active = 1
		        ORDER BY g.id";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function getPhases() {
		$query = "SELECT * FROM rehearsalphase ORDER BY begin";
		return $this->database->getSelection($query);
	}
	
	function getVotes() {
		$query = "SELECT * FROM vote WHERE end >= now() AND is_finished = 0";
		return $this->database->getSelection($query);
	}
	
	function addContactRelation($otype, $oid, $cid) {
		$tab = $otype . "_contact"; // Security note: $otype is set as a static value in the controller call
		$query = "SELECT count(*) as cnt FROM $tab WHERE $otype = ? AND contact = ?";
		$ct = $this->database->colValue($query, "cnt", array(array("i", $oid), array("i", $cid)));
		if($ct <= 0) {
			$query = "INSERT INTO $tab ($otype, contact) VALUES (?, ?)";
			return $this->database->execute($query, array(array("s", $oid), array("i", $cid)));
		}
		return 0;
	}
	
	function addContactToVote($vid, $cid) {
		$uid = $this->database->colValue("SELECT id FROM user WHERE contact = ?", "id", array(array("i", $cid)));
		if($uid != null && $uid > 0) {
			$query = "SELECT count(*) as cnt FROM vote_group WHERE vote = ? AND user = ?";
			$ct = $this->database->colValue($query, "cnt", array(array("i", $vid), array("i", $uid)));
			if($ct <= 0) {
				$query = "INSERT INTO vote_group (vote, user) VALUES (?, ?)";
				return $this->database->execute($query, array(array("i", $vid), array("i", $uid)));
			}
			return 0;
		}
		return -1;
	}
	
	function saveVCards($cards, $selectedGroups) {
		foreach($cards as $card) {
			$this->create($card);
		}
	}
	
	/**
	 * Concert invitations (all time).
	 * @param integer $cid Contact ID.
	 */
	function getConcertInvitations($cid) {
		$this->regex->isPositiveAmount($cid);
		$query = "SELECT c.* FROM concert_contact cc JOIN concert c ON cc.concert = c.id WHERE cc.contact = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	/**
	 * Concert participation (all time).
	 * @param integer $uid User ID.
	 */
	function getConcertParticipation($uid) {
		$this->regex->isPositiveAmount($uid);
		$query = "SELECT c.* FROM concert_user cu JOIN concert c ON cu.concert = c.id WHERE cu.user = ?";
		return $this->database->getSelection($query, array(array("i", $uid)));
	}
	
	/**
	 * Rehearsal invitations (all time).
	 * @param integer $cid Contact ID.
	 */
	function getRehearsalInvitations($cid) {
		$this->regex->isPositiveAmount($cid);
		$query = "SELECT r.* FROM rehearsal_contact rc JOIN rehearsal r ON rc.rehearsal = r.id WHERE rc.contact = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	/**
	 * Rehearsal participation (all time).
	 * @param integer $uid User ID.
	 */
	function getRehearsalParticipation($uid) {
		$query = "SELECT r.* FROM rehearsal_user ru JOIN rehearsal r ON ru.rehearsal = r.id WHERE ru.user = ?";
		return $this->database->getSelection($query, array(array("i", $uid)));
	}
	
	/**
	 * Rehearsalphase invitations (all time).
	 * @param integer $cid Contact ID.
	 */
	function getRehearsalphaseInvitations($cid) {
		$query = "SELECT p.* FROM rehearsalphase_contact rc JOIN rehearsalphase p ON rc.rehearsalphase = p.id WHERE rc.contact = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	/**
	 * Tour invitations (all time).
	 * @param integer $cid Contact ID.
	 */
	function getTourInvitations($cid) {
		$this->regex->isPositiveAmount($cid);
		$query = "SELECT t.* FROM tour_contact tc JOIN tour t ON tc.tour = t.id WHERE tc.contact = ?";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function getContactGdprStatus($ok = 2) {
		$query = "SELECT c.id as contact_id, u.id as user_id, c.surname, c.name, c.nickname, c.email, c.gdpr_ok, u.login 
				FROM contact c LEFT OUTER JOIN user u ON u.contact = c.id";
		$params = array();
		if($ok != 2) {
			$query .= " WHERE c.gdpr_ok = ?";
			array_push($params, array("i", $ok));
		}
		$query .= "	ORDER BY surname, name";
		return $this->database->getSelection($query, $params);
	}
	
	function generateGdprCodes() {
		// get contacts without codes
		$contacts = $this->getContactGdprStatus("0 AND c.gdpr_code IS NULL");
		foreach($contacts as $i => $contact) {
			if($i == 0) continue;
			$cid = $contact["contact_id"];
			
			// generate code
			$code = uniqid('BN', true);
			
			// update table
			$query = "UPDATE contact SET gdpr_code = ? WHERE id = ?";
			$this->database->execute($query, array(array("s", $code), array("i", $cid)));
		}
	}
	
	function getContactmail($id) {
		return $this->database->colValue("SELECT email FROM contact WHERE id = ?", "email", array(array("i", $id)));
	}
	
	function getUsermail() {
		$cid = $this->getSysdata()->getContactFromUser();
		return $this->getContactmail($cid);
	}
	
	/**
	 * Be careful with duplicate email usages.
	 * @param string $email E-Mail-Address
	 * @return String GDPR Code.
	 */
	function getGdprCode($email) {
		return $this->database->colValue("SELECT gdpr_code FROM contact WHERE email = ?", "gdpr_code", array(array("s", $email)));
	}
	
}