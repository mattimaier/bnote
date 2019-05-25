<?php

/**
 * DAO for user module.
 * @author matti
 *
 */
class UserData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("UserData_construct.id"), FieldType::INTEGER),
			"isActive" => array(Lang::txt("UserData_construct.isActive"), FieldType::BOOLEAN),
			"login" => array(Lang::txt("UserData_construct.login"), FieldType::LOGIN),
			"password" => array(Lang::txt("UserData_construct.password"), FieldType::PASSWORD),
			"contact" => array(Lang::txt("UserData_construct.contact"), FieldType::REFERENCE),
			"lastlogin" => array(Lang::txt("UserData_construct.lastlogin"), FieldType::DATETIME)
		);
		
		$this->references = array(
			"contact" => "contact"
		);
		$this->table = "user";
		
		require_once $dir_prefix . $GLOBALS['DIR_LOGIC_MODULES'] . "logincontroller.php";
		$this->init($dir_prefix);
	}
	
	function getUsers() {
		// filter out super-users, in case a non-super-user looks at the table
		$query = "SELECT u.id, u.isActive, u.login, ";
		$query .= "CONCAT_WS(' ', c.name, c.surname) as name, u.lastlogin";
		$query .= " FROM user u LEFT JOIN contact c ON u.contact = c.id";
		
		if(!$this->getSysdata()->isUserSuperUser()
				&& count($this->getSysdata()->getSuperUsers()) > 0) {
			$query .= " WHERE ";
			foreach($this->getSysdata()->getSuperUsers() as $i => $su) {
				if($i > 0) $query .= " AND ";
				$query .= "u.id <> $su";
			}
		}
		$query .= " ORDER BY name, id";
		return $this->database->getSelection($query);
	}
	
	function create($values) { // values and $_POST is the same
		// Do a manual validation
		if(!$this->regex->isLogin($values["login"])) new BNoteError(Lang::txt("UserData_create.error_1"));
		if(!$this->regex->isPassword($values["password"])) new BNoteError(Lang::txt("UserData_create.error_2"));
		if(!isset($values["contact"]) || $values["contact"] == "") new BNoteError(Lang::txt("UserData_create.error_3"));
		
		// check that the login is not taken
		if($this->adp()->doesLoginExist($values["login"])) {
			new BNoteError(Lang::txt("UserData_create.error_4"));
		}
		
		$newUsr = array();
		// encrypt password
		foreach($this->getFields()as $id => $info) {
			if($id == "id" || $id == "lastlogin") continue;
			if($id == "password") {
				// specially validate password for empty passwords
				if($values[$id] == "") new BNoteError(Lang::txt("UserData_create.error_5"));
				$newUsr[$id] = crypt($values[$id], LoginController::ENCRYPTION_HASH);
			}
			else if($id != "isActive") {
				$newUsr[$id] = $values[$id];
			}
		}
		if(!array_key_exists("isActive", $newUsr) || $newUsr["isActive"] == "") {
			$newUsr["isActive"] = "on";
		}
		
		$userId = parent::create($newUsr);

		// add default privileges
		global $system_data;
		$privQuery = "INSERT INTO privilege (user, module) VALUES ";
		
		foreach($system_data->getDefaultUserCreatePermissions() as $i => $mod) {
			$privQuery .= "($userId, $mod), ";
		}
		$privQuery = substr($privQuery, 0, strlen($privQuery)-2);
		$this->database->execute($privQuery);
		
		// create user directory
		mkdir($this->getSysdata()->getUsersHomeDir($userId));
	}
	
	function update($id, $values) { // $values is the same than $_POST		
		// restrict access to super user for non-super-users
		if(!$this->getSysdata()->isUserSuperUser()
				&& $this->getSysdata()->isUserSuperUser($_GET["id"])) {
					new BNoteError(Lang::txt("UserData_update.error"));
		}
		
		$usr = array();
		// encrypt password
		foreach($this->getFields()as $id => $info) {
			if($id == "id" || $id == "lastlogin" || $id == "login") continue;
			else if($id == "password") {
				if($_POST[$id] != "") $usr[$id] = crypt($_POST[$id], LoginController::ENCRYPTION_HASH);
			} else {
				$usr[$id] = $_POST[$id];
			}
			
		}
		
		// check if contact is set, otherwise remove contact
		if(!isset($usr["contact"]) || $usr["contact"] == "") {
			$usr["contact"] = "-1";
		}
		
		parent::update($_GET["id"], $usr);
	}
	
	function delete($id) {
		// restrict access to super user for non-super-users
		if(!$this->getSysdata()->isUserSuperUser()
				&& $this->getSysdata()->isUserSuperUser($id)) {
					new BNoteError(Lang::txt("UserData_delete.error"));
		}
		else {
			parent::delete($id);
		}
		
		// delete also user directories with files
		rmdir($this->getSysdata()->getUsersHomeDir($id));
	}
	
	/**
	 * Looks up the real name of the user.
	 * @param int $id ID of the user.
	 * @return The real name of the user.
	 */
	function getUsername($id) {
		return $this->adp()->getUsername($id);
	}
	
	/**
	 * Looks up the user's mail address.
	 * @param int $id User ID.
	 * @return E-Mail-Address of the user, may be empty or null.
	 */
	function getUsermail($id) {
		$contactid = $this->database->getCell($this->table, "contact", "id = $id");
		return $this->database->getCell("contact", "email", "id = $contactid");
	}
	
	/**
	 * Looks up the modules the user has access to.
	 * @param int $id ID of the user.
	 * @return Array with the ids and names of the modules. 
	 */
	function getPrivileges($id) {
		$query = "SELECT m.id, m.name FROM privilege p, module m WHERE p.module = m.id AND p.user = $id";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Looks up whether the user has access to the given module.
	 * @param int $uid ID of the user.
	 * @param int $mid ID of the module.
	 */
	function hasUserPrivilegeForModule($uid, $mid) {
		$bit = $this->database->getCell("privilege", "id", "user = $uid AND module = $mid");
		if(!isset($bit) || $bit == "") return false;
		else return true;
	}
	
	/**
	 * Updates all user privileges by deleting them first, then reinserting them.
	 * @param int $uid User ID.
	 */
	function updatePrivileges($uid) {
		// restrict access to super user for non-super-users
		if(!$this->getSysdata()->isUserSuperUser()
				&& $this->getSysdata()->isUserSuperUser($uid)) {
					new BNoteError(Lang::txt("UserData_updatePrivileges.error"));
		}
		
		// clear privileges
		$query = "DELETE FROM privilege WHERE user = $uid";
		$this->database->execute($query);
		
		// insert privileges
		// $_POST format: [modid] => [on] , if [modid] not in array = off
		$query = "INSERT INTO privilege (user, module) VALUES ";
		$count = 0;
		
		foreach($_POST as $mid => $status) {
			$query .= "($uid, $mid), ";
			$count++;
		}
		$query = substr($query, 0, strlen($query)-2); // cut last ", "
		
		if($count > 0) {
			$this->database->execute($query);
		}
	}
	
	function isUserActive($id) {
		return ($this->database->getCell($this->table, "isActive", "id=$id") == 1);
	}
	
	/**
	 * Activates the user account in case the user is deactivated,
	 * otherwise deactivates the user.
	 * @param int $id User ID.
	 * @return boolean True when the user was activated, false when the user was deactivated.
	 */
	function changeUserStatus($id) {
		// restrict access to super user for non-super-users
		if(!$this->getSysdata()->isUserSuperUser()
				&& $this->getSysdata()->isUserSuperUser($id)) {
					new BNoteError(Lang::txt("UserData_changeUserStatus.error"));
		}
		
		$query = "UPDATE " . $this->table . " SET isActive =";
		$isActiveNow = false;
		if($this->isUserActive($id)) {
			$query .= "0";
		}
		else {
			$query .= "1";
			$isActiveNow = true;
		}
		$query .= " WHERE id = $id";
		$this->database->execute($query);
		return $isActiveNow;
	}
	
	function getContacts() {
		return $this->adp()->getContacts();
	}
	
	/**
	 * Retrieves users not having used BNote within the last 24 months.
	 */
	function getLongInactiveUsers() {
		$loginTresholdFormatted = Data::subtractMonthsFromDate(date("d.m.Y"), 24);
		$loginTreshold = Data::convertDateToDb($loginTresholdFormatted);
		$query = "SELECT * FROM " . $this->getTable() . " WHERE lastlogin <= '$loginTreshold'";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Deletes the users and their data.
	 * @param array $inactiveUsers DB selection of inactive users, e.g. from method getLongInactiveUsers()
	 */
	function deleteUsersFull($inactiveUsers) {
		for($i = 1; $i < count($inactiveUsers); $i++) {
			// get user ID and contact ID
			$user = $inactiveUsers[$i];
			$uid = $user["id"];
			$cid = $user["contact"];
			
			// remove all vote data
			$query = "DELETE FROM vote_option_user WHERE user = $uid";
			$this->database->execute($query);
			$query = "DELETE FROM vote_group WHERE user = $uid";
			$this->database->execute($query);
			
			// remove all task data
			$query = "DELETE FROM task WHERE created_by = $cid or assigned_to = $cid";
			$this->database->execute($query);
			
			// remove all concert data
			$query = "DELETE FROM concert_user WHERE user = $uid";
			$this->database->execute($query);
			$query = "DELETE FROM concert_contact WHERE contact = $cid";
			$this->database->execute($query);
			
			// remove all rehearsal data
			$query = "DELETE FROM rehearsal_user WHERE user = $uid";
			$this->database->execute($query);
			$query = "DELETE FROM rehearsal_contact WHERE contact = $cid";
			$this->database->execute($query);
			
			// remove all rehearsalphase data
			$query = "DELETE FROM rehearsalphase_contact WHERE contact = $cid";
			$this->database->execute($query);
			
			// remove all tour data
			$query = "DELETE FROM tour_contact WHERE contact = $cid";
			$this->database->execute($query);
			
			// remove all comments from this user
			$query = "DELETE FROM comment WHERE author = $uid";
			$this->database->execute($query);
			
			// remove all group associations of this contact
			$query = "DELETE FROM contact_group WHERE contact = $cid";
			$this->database->execute($query);
			
			// remove contact information
			$this->deleteCustomFieldData('c', $cid);
			$query = "DELETE FROM contact WHERE id = $cid";
			$this->database->execute($query);
			
			// remove user
			$this->delete($uid);
		}
	}
}

?>