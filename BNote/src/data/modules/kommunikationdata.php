<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";

/**
 * Data Access Class for communication data.
 * @author matti
 *
 */
class KommunikationData extends KontakteData {
	
	function getRehearsals() {
		return $this->adp()->getAllRehearsals();
	}
	
	function getRehearsal($id) {
		$query = "SELECT begin, name as location, street, city, zip, r.notes ";
		$query .= "FROM rehearsal r, location l, address a ";
		$query .= "WHERE r.location = l.id AND l.address = a.id AND r.id = " . $id;
		return $this->database->getRow($query);
	}
	
	function getUsermail() {
		$cid = $this->database->getCell("user", "contact", "id = " . $_SESSION["user"]);
		return $this->getContactmail($cid);
	}
	
	function getContactmail($id) {
		return $this->database->getCell("contact", "email", "id = $id");
	}
	
	function getMailaddressesFromGroup($prefix) {
		$selectedGroups = GroupSelector::getPostSelection($this->adp()->getGroups(), $prefix);
		
		$query = "SELECT c.email ";
		$query .= "FROM contact c JOIN contact_group cg ON cg.contact = c.id ";
		$query .= "WHERE ";
		foreach($selectedGroups as $i => $group) {
			if($i > 0) $query .= "OR ";
			$query .= "cg.group = $group ";
		}
		
		$mailaddies = $this->database->getSelection($query);
		
		return $this->flattenAddresses($mailaddies);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = $rid";
		return $this->database->getSelection($query);
	}
	
	function getRehearsalContactMail($rid) {
		$query = "SELECT c.email ";
		$query .= "FROM contact c JOIN rehearsal_contact rc ON rc.contact = c.id ";
		$query .= "WHERE rc.rehearsal = $rid";
		$mailaddies = $this->database->getSelection($query);
		
		return $this->flattenAddresses($mailaddies);
	}
	
	private function flattenAddresses($selection) {
		$addresses = array();
		for($i = 1; $i < count($selection); $i++) {
			$addy = $selection[$i]["email"];
			if($addy != "") array_push($addresses, $addy);
		}
		return $addresses;
	}
}

?>