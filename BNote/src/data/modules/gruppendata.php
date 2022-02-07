<?php

class GruppenData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array(Lang::txt("GruppenData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("GruppenData_construct.name"), FieldType::CHAR),
				"is_active" => array(Lang::txt("GruppenData_construct.is_active"), FieldType::BOOLEAN)
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
		$query = "SELECT CONCAT(c.name, ' ', c.surname) as name, notes, i.name as instrument ";
		$query .= "FROM (contact c JOIN contact_group cg ON cg.contact = c.id) LEFT JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE cg.`group` = ? ";
		$query .= "ORDER BY name, instrument";
		return $this->database->getSelection($query, array(array("i", $gid)));
	}
	
	function create($values) {
		$gid = parent::create($values);
		
		// create group directory
		mkdir($this->getSysdata()->getGroupHomeDir($gid));
		
		return $gid;
	}
	
	function update($id, $values) {
		if(isset($values["is_active"]) && $values["is_active"] == "on") {
			$values["is_active"] = 1;
		}
		else {
			$values["is_active"] = 0;
		}
		
		parent::update($id, $values);
	}
	
	function delete($id) {
		// check whether the members of this group still have at least one other group
		$query = "SELECT cg.contact, count(cg.`group`) as numUserGroup
       			  FROM (SELECT * FROM `contact_group`
						WHERE `group`= ? ) as grp, contact_group cg
				  WHERE grp.contact = cg.contact
				  GROUP BY cg.contact
				  HAVING numUserGroup < 2";
		$res = $this->database->getSelection($query, array(array("i", $id)));
		$numContactsWithNoOtherGroup = count($res) -1;
		if($numContactsWithNoOtherGroup > 0) {
			new BNoteError(Lang::txt("GruppenData_delete.BNoteError_1"), "$numContactsWithNoOtherGroup", Lang::txt("GruppenData_delete.BNoteError_2"));
		}
		// check if there are files in the folder -> cancel removal
		if(!$this->isDirEmpty($this->getSysdata()->getGroupHomeDir($id))) {
			new BNoteError(Lang::txt("GruppenData_delete.BNoteError_3"));
		}
		
		// first remove all members from the group
		$query = "DELETE FROM contact_group WHERE `group`=?";
		$this->database->execute($query, array(array("i", $id)));
		
		// remove files from share
		rmdir($this->getSysdata()->getGroupHomeDir($id));
		
		parent::delete($id);
	}
	
	private function isDirEmpty($dir) {
		if (!is_readable($dir)) return NULL;
		return (count(scandir($dir)) == 2);
	}
}

?>