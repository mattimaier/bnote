<?php

class GruppenData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR),
				"is_active" => array("Aktiv", FieldType::BOOLEAN)
		);
	
		$this->references = array(
		);
	
		$this->table = "`group`";
		$this->init();
	}
	
	function getGroups() {
		return $this->adp()->getGroups();
	}
	
	function getGroupMembers($gid) {
		$query = "SELECT CONCAT(c.name, ' ', c.surname) as name, notes as Notizen, i.name as instrument ";
		$query .= "FROM (contact c JOIN contact_group cg ON cg.contact = c.id) LEFT JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE cg.`group` = $gid ";
		$query .= "ORDER BY name, instrument";
		return $this->database->getSelection($query);
	}
	
	function delete($id) {
		// check whether the members of this group still have at least one other group
		$query = "SELECT cg.contact, count(cg.`group`) as numUserGroup
       			  FROM (SELECT * FROM `contact_group`
						WHERE `group`=$id) as grp, contact_group cg
				  WHERE grp.contact = cg.contact
				  GROUP BY cg.contact
				  HAVING numUserGroup < 2";
		$res = $this->database->getSelection($query);
		$numContactsWithNoOtherGroup = count($res) -1;
		if($numContactsWithNoOtherGroup > 0) {
			new Error("In dieser Gruppe sind $numContactsWithNoOtherGroup Kontakte die keiner anderen Gruppe zugeordnet sind.
					   Bitte ändere deren Gruppenzugehörigkeit bevor du die Gruppe löschen kannst.");
		}
		
		// first remove all members from the group
		$query = "DELETE FROM contact_group WHERE `group`=$id";
		$this->database->execute($query);
		
		parent::delete($id);
	}
}

?>