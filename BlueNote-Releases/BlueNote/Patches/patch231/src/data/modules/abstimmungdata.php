<?php

/**
 * Data Access Class for vote data.
 * @author matti
 *
 */
class AbstimmungData extends AbstractData {

	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array("Abstimmungs-ID", FieldType::INTEGER),
				"name" => array("Titel", FieldType::CHAR),
				"author" => array("Ersteller", FieldType::REFERENCE),
				"end" => array("Abstimmungsende", FieldType::DATETIME),
				"is_date" => array("Datumsabstimmung", FieldType::BOOLEAN),
				"is_multi" => array("Mehrere Optionen möglich", FieldType::BOOLEAN),
				"is_finished" => array("Abstimmung beendet", FieldType::BOOLEAN)
		);

		$this->references = array("user");

		$this->table = "vote";

		$this->init();
	}
	
	function create($values) {
		// validation
		$this->regex->isSubject($_POST["name"]);
		$_POST["end"] = trim($_POST["end"]);	
		$this->regex->isDateTime($_POST["end"]);
		
		$author = $_SESSION["user"];
		if(isset($_POST["is_date"])) { $is_date = 1; } else { $is_date = 0; }
		if(isset($_POST["is_multi"])) { $is_multi = 1; } else { $is_multi = 0; }
		$is_finished = 0;
		
		// insert vote
		$query = "INSERT INTO " . $this->table;
		$query .= " (name, author, end, is_multi, is_date, is_finished)";
		$query .= " VALUES (";
		$query .= '"' . $_POST["name"] . '", ';
		$query .= "$author, ";
		$query .= '"' . Data::convertDateToDb($_POST["end"]) . '", ';
		$query .= "$is_multi, $is_date, $is_finished";
		$query .= " )";
		$vid = $this->database->execute($query);
		
		/*
		 * enhancement #15: add all members and admins by default
		 * - removed adding the current user, since it could be an admin and should
		 *   thus be invisible.
		 */
		$this->addAllMembersAndAdminsToGroup($vid);	
		
		return $vid;
	}
	
	/**
	 * @return Database Selection with the votes for the current user that are not marked as finished.
	 */
	function getUserActiveVotes() {
		$query = "SELECT id, name, end, is_multi, is_date FROM " . $this->table;
		$query .= " WHERE is_finished = 0 AND author = " . $_SESSION["user"];
		$query .= " ORDER BY end ASC";
		return $this->database->getSelection($query);
	}
	
	/**
	 * @return Database Selection with all active votes within the last year that are not marked as finished.
	 */
	function getAllActiveVotes() {
		$query = "SELECT id, name, end, is_multi, is_date FROM " . $this->table;
		$query .= " WHERE is_finished = 0 AND YEAR(end) >= (YEAR(NOW())-1)";
		$query .= " ORDER BY end ASC";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Checks whether the user is admin/author of vote.
	 * @param Integer $uid User ID.
	 * @param Integer $vid Vote ID.
	 * @return True if the user is the author of this vote, otherwise false.
	 */
	function isUserAuthorOfVote($uid, $vid) {
		$author = $this->database->getCell($this->table, "author", "id = $vid");
		return ($author == $uid);
	}
	
	function getOptions($vid) {
		$query = "SELECT vo.* ";
		$query .= "FROM vote_option vo, vote v ";
		$query .= "WHERE vo.vote = v.id AND v.id = $vid";
		if($this->isDateVote($vid)) {
			$query .= " ORDER BY vo.odate ASC";
		}
		return $this->database->getSelection($query);
	}
	
	private function isDateVote($vid) {
		return ($this->database->getCell($this->table, "is_date", "id = $vid") == 1);
	}
	
	function addOption($vid) {
		$is_date = $this->isDateVote($vid); 
		$query = "INSERT INTO vote_option (vote, ";
		if($is_date) { $query .= "odate"; } else { $query .= "name"; }
		$query .= ") VALUES (" . $vid . ", \"";
		if($is_date) {
			$_POST["odate"] = trim($_POST["odate"]);
			$this->regex->isDateTime($_POST["odate"]);
			$query .= Data::convertDateToDb($_POST["odate"]);
		}
		else {
			$this->regex->isSubject($_POST["name"]);
			$query .= $_POST["name"];
		}
		$query .= "\")";
		return $this->database->execute($query);
	}
	
	function deleteOption($oid) {
		$query = "DELETE FROM vote_option WHERE id = $oid";
		$this->database->execute($query);
	}
	
	function delete($id) {
		// do not delete a vote, just set is_finished
		$query = "UPDATE " . $this->table . " SET is_finished = 1 WHERE id = $id";
		$this->database->execute($query);
	}
	
	function update($id, $values) {
		// validation
		$this->regex->isSubject($values["name"]);
		$values["end"] = trim($values["end"]);
		$this->regex->isDateTime($values["end"]);
		
		// update db
		$query = "UPDATE " . $this->table . " SET ";
		$query .= "name = \"" . $values["name"] . "\", ";
		$query .= "end = \"" . Data::convertDateToDb($values["end"]) . "\"";
		$query .= " WHERE id = $id";
		$this->database->execute($query);
	}
	
	function getGroup($vid) {
		$query = "SELECT vg.user as id, c.surname, c.name ";
		$query .= "FROM vote_group vg, user u, contact c ";
		$query .= "WHERE vg.vote = $vid AND vg.user = u.id AND u.contact = c.id ";
		$query .= "ORDER BY c.name, c.surname";
		return $this->database->getSelection($query);
	}
	
	function getUsers() {
		$query = "SELECT u.id, c.surname, c.name ";
		$query .= "FROM user u JOIN contact c ON u.contact = c.id ";
		
		// bug #13: Filter out admins
		global $system_data;
		$superUsers = $system_data->getSuperUserContactIDs();
		if(count($superUsers) > 0) {
			$query .= "WHERE ";
			for($i = 0; $i < count($superUsers); $i++) {
				if($i > 0) $query .= "AND ";
				$query .= "c.id <> " . $superUsers[$i] . " ";
			}
		}
		
		$query .= "ORDER BY c.name, c.surname";
		return $this->database->getSelection($query);
	}
	
	function addToGroup($vid, $uid) {
		$this->regex->isPositiveAmount($uid);
		$query = "INSERT INTO vote_group (vote, user) VALUES ($vid, $uid)";
		return $this->database->execute($query);
	}
	
	function addAllMembersAndAdminsToGroup($vid) {
		// get all admin and member user ids
		$query = "SELECT u.id FROM user u JOIN contact c ON u.contact = c.id WHERE c.status = 'MEMBER' OR c.status = 'ADMIN'";
		$users = $this->database->getSelection($query);
		
		/* bug #13 and #14:
		 * remove admins from being added to the group
		* and don't add people who are already in the group
		*/
		$q1 = "SELECT user FROM vote_group WHERE vote = $vid";
		$contactsInList = $this->database->getSelection($q1);
		$contactList = array();
		// flatten the list and remove header
		for($i = 1; $i < count($contactsInList); $i++) {
			array_push($contactList, $contactsInList[$i]["user"]);
		}
		global $system_data;
		$superUsers = $system_data->getSuperUsers();
		
		// add the user ids to group
		$query = "INSERT INTO vote_group (vote, user) VALUES ";
		$addset = "";
		for($i = 1; $i < count($users); $i++) {
			// exclude users already in the list and admins
			if(in_array($users[$i]["id"], $superUsers) || in_array($users[$i]["id"], $contactList)) {
				continue;
			}
			
			if($addset != "") $addset .= ",";
			$addset .= "($vid, " . $users[$i]["id"] . ")";
		}
		if($addset != "") {
			$this->database->execute($query . $addset);
		}
	}
	
	function deleteFromGroup($vid, $uid) {
		$query = "DELETE FROM vote_group WHERE vote = $vid AND user = $uid";
		$this->database->execute($query);
	}
	
	function getResult($vid) {
		$vote = $this->findByIdNoRef($vid);
		$options = $this->getOptions($vid);
		$result = array();
		
		for($i = 1; $i < count($options); $i++) {
			$c = $this->database->getCell("vote_option_user", "count(user)",
					"vote_option = " . $options[$i]["id"]);
			if($vote["is_date"] == 1) {
				$label = Data::convertDateFromDb($options[$i]["odate"]);
			}
			else {
				$label = $options[$i]["name"];
			}
			if($c == 1) {
				$c .= " Stimme";
			}
			else {
				$c .= " Stimmen";
			}
			$result[$label] = $c;
		}
		
		return $result;
	}
}

?>