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
	function __construct($dir_prefix = "") {
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

		$this->init($dir_prefix);
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
		
		// resolve groups and add members
		$grps = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$this->registerVoters($vid, $grps);
		
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
	 * Returns all votes the user is part of.
	 * @param Boolean $active optionl: Whether to show only active (true) or only inactive (false).
	 * @param Integer $uid optional: User ID, by default current user.
	 */
	function getVotesForUser($active = true, $uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		
		// in case the system admin look at the votes, show all of them
		if($this->getSysdata()->isUserSuperUser() || $this->getSysdata()->isUserMemberGroup(1)) {
			$query = "SELECT id, name, end, is_multi, is_date, is_finished ";
			$query .= "FROM " . $this->table;
			if(!$active) {
				$query .= " WHERE is_finished = 1";
			}
			else {
				$query .= " WHERE is_finished = 0";
			}
			$query .= " ORDER BY is_finished = 0, end ASC";
		}
		else {
			$query = "SELECT v.id, v.name, v.end, v.is_multi, v.is_date ";
			$query .= " FROM vote v JOIN vote_group vg ON vg.vote = v.id";
			$query .= " WHERE vg.user = $uid AND v.is_finished = 0 AND YEAR(v.end) >= (YEAR(NOW())-1)";
			if(!$active) {
				$query .= " AND is_finished = 1";
			}
			else {
				$query .= " AND is_finished = 0";
			}
			$query .= " ORDER BY v.end ASC";
		}
		
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
	
	/**
	 * Checks whether the vote is still on (time before end date and isFinished false).
	 * @param Integer $vid Vote ID.
	 * @return boolean True if the vote is active, otherwise false.
	 */
	function isVoteActive($vid) {
		$vote = $this->findByIdNoRef($vid);
		// check is finished
		if($vote["is_finished"] == 1) {
			return false;
		}
		
		// check time
		$time = strtotime($vote["end"]);
		$now = date("U");
		return ($time > $now);
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
	
	function addOptions($vid, $from, $to) {
		$options = array();
		$current = Data::convertDateToDb($from);
		$to = Data::convertDateToDb($to);
		$infPrevention = 0; // max. 1 year, every day
		while(Data::compareDates($current, $to) < 1 && $infPrevention < 365) {
			if(strlen($current) <= 10) { // only date, no time
				$current .= substr($from, 10);
			}
			array_push($options, $current);
			$current = Data::addDaysToDate(substr(Data::convertDateFromDb($current), 0, 10), 1);
			$current = Data::convertDateToDb($current);
			$infPrevention++;
		}
		
		foreach($options as $i => $option) {
			$_POST["odate"] = Data::convertDateFromDb($option);
			$this->addOption($vid);
		}
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
	
	/**
	 * Takes the groups and extracts the users for a group. Then adds these users
	 * to the given vote.
	 * @param Integer $vid Vote ID.
	 * @param array $groups Flat array with group IDs of users to add.
	 */
	function registerVoters($vid, $groups) {
		if($groups == null || count($groups) == 0) return;
		
		// get all users for the given groups
		$query = "SELECT u.id
				  FROM `contact_group` cg JOIN user u ON u.contact = cg.contact
				  WHERE ";
		
		foreach($groups as $i => $group) {
			if($i > 0) $query .= " OR ";
			$query .= "cg.group = " . $group;
		}
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
		$maybeOn = ($this->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == 1);
		
		if($vote["is_date"] == 1 && $vote["is_multi"] == 1 && $maybeOn) {
			/* 
			 * compile result for date-multi-maybe votes
			 * 
			 * target table look:
			 * OPTION  STIMMEN        WÄHLER
			 * =================================================
			 * <Date>  Ja: 4		  Hans, Hektor, Oskar, Heidi
			 *         Nein: 2        Josef, Viktor
			 *         Vielleicht: 1  Marta
			 * -------------------------------------------------
			 */
			$result = array();
			
			$options = $this->getOptions($vid);			
			foreach($options as $i => $row) {
				if($i == 0) {
					// header
					array_push($result, array(
						"id", "Option", "votes", "voters"
					));
				}
				else {
					// body of result table
					$optionId = $row["id"];
					$name = substr(Data::getWeekdayFromDbDate($row["odate"]), 0, 2) . ", ";
					$name .= Data::convertDateFromDb($row["odate"]) . " Uhr";
					
					$choiceY = $this->getOptionVotes($optionId, 1);
					$yes = $choiceY["count"];
					$yesNames = $choiceY["names"];
					
					$choiceN = $this->getOptionVotes($optionId, 0);
					$no = $choiceN["count"];
					$noNames = $choiceN["names"];
					
					$choiceM = $this->getOptionVotes($optionId, 2);
					$may = $choiceM["count"];
					$mayNames = $choiceM["names"];
					
					if($yes == 0 && $no == 0 && $may == 0) {
						$name = "<span style=\"font-style: italic;\">$name</span>";
						$votesOut = "-";
						$votersOut = "-";
					}
					else {
						$name = "<span style=\"font-weight: bold;\">$name</span>";
						$votesOut = "$yes Ja<br/>$no Nein</br>$may Vielleicht";
						$votersOut = "$yesNames<br/>$noNames<br/>$mayNames";
					}
					
					$resRow = array(
						"id" => $optionId,
						"Option" => $name,
						"votes" => $votesOut,
						"voters" => $votersOut
					);
					array_push($result, $resRow);
				}
			}
			
			return $result;
		}
		else {
			// result for all types of votes, but date-multi-maybe votes
			$query = 'SELECT vo.id,
	       					 IF(v.is_date=1, vo.odate, vo.name) as `option`,
	       					 count(vo.id) as votes,
	       					 GROUP_CONCAT(CONCAT(c.name, \' \', c.surname, " (", i.name, ")" ) SEPARATOR \', \') as voters
	  					FROM vote v, vote_option vo, vote_option_user vou, user u, contact c, instrument i
	 				   WHERE v.id = vo.vote AND vou.vote_option = vo.id AND vou.user = u.id AND u.contact = c.id AND c.instrument = i.id
	 				     AND vote = ' . $vid . ' AND vou.choice > 0
	 				GROUP BY vo.id
			        ORDER BY vo.id ASC';
			return $this->database->getSelection($query);
		}
	}
	
	private function getOptionVotes($optionId, $choice) {
		$result = array( "count" => 0, "names" => "" );
		
		$query = 'SELECT CONCAT(c.name, \' \', c.surname, " (", i.name, ")" ) as voter ';
		$query .= 'FROM vote_option_user vou JOIN user u ON vou.user = u.id ';
		$query .= '     JOIN contact c ON u.contact = c.id ';
		$query .= '     JOIN instrument i ON c.instrument = i.id ';
		$query .= 'WHERE vou.vote_option = ' . $optionId . ' AND vou.choice = ' . $choice . ' ';
		$query .= 'ORDER BY voter';
		
		$voters = $this->database->getSelection($query);
		foreach($voters as $i => $voter) {
			if($i == 0) continue;
			if($result["names"] != "") $result["names"] .= ", ";
			$result["names"] .= $voter["voter"];
			$result["count"]++;
		}
		
		return $result;
	}
	
	function validate($input, $groupRequired = false) {
		parent::validate($input);
		
		// additionally validate whether a group is set -> otherwise the vote "disappears"
		if($groupRequired) {
			$grps = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
			if(count($grps) == 0) {
				new Error("Bitte wähle eine Gruppe für die Abstimmung.");
			}
		}
	}
}

?>