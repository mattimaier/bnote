<?php

/**
 * Data provider for the task module.
 * @author Matti
 *
 */
class AufgabenData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("AufgabenData_construct.id"), FieldType::INTEGER),
				"title" => array(Lang::txt("AufgabenData_construct.title"), FieldType::CHAR),
				"description" => array(Lang::txt("AufgabenData_construct.description"), FieldType::TEXT),
				"created_at" => array(Lang::txt("AufgabenData_construct.created_at"), FieldType::DATETIME),
				"created_by" => array(Lang::txt("AufgabenData_construct.created_by"), FieldType::REFERENCE),
				"due_at" => array(Lang::txt("AufgabenData_construct.due_at"), FieldType::DATETIME),
				"assigned_to" => array(Lang::txt("AufgabenData_construct.assigned_to"), FieldType::REFERENCE),
				"is_complete" => array(Lang::txt("AufgabenData_construct.is_complete"), FieldType::BOOLEAN),
				"completed_at" => array(Lang::txt("AufgabenData_construct.completed_at"), FieldType::DATETIME)
		);
	
		$this->references = array(
				"created_by" => "contact",
				"assigned_to" => "contact"
		);
	
		$this->table = "task";
		$this->init($dir_prefix);
	}
	
	function getTasks($onlyOpen = true) {
		$query = "SELECT t.*, CONCAT(c1.name, ' ', c1.surname) as creator, CONCAT(c2.name, ' ', c2.surname) as assignee ";
		$query .= "FROM task t, contact c1, contact c2 ";
		$query .= "WHERE t.created_by = c1.id AND t.assigned_to = c2.id AND is_complete = ? ";
		$query .= "ORDER BY due_at, assigned_to DESC";
		$onlyOpenQ = $onlyOpen ? 0 : 1;
		return $this->database->getSelection($query, array(array("i", $onlyOpenQ)));
	}
	
	function create($values) {
		// prepare data
		$values["created_at"] = date("Y-m-d H:i:s");
		$values["created_by"] = $this->getSysdata()->getContactFromUser();
		$values["assigned_to"] = $values[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")];
		$values["is_complete"] = "0";
		return parent::create($values);	
	}
	
	function isTaskComplete($tid) {
		$complete = $this->database->colValue("SELECT is_complete FROM task WHERE id = ?", "is_complete", array(array("i", $tid)));
		return ($complete == 1);
	}
	
	function markTask($tid, $is_complete) {
		$remove_date = $is_complete == 0 ? ", completed_at = NULL" : "";
		$query = "UPDATE task SET is_complete = ?" . $remove_date . " WHERE id = ?";
		$this->database->execute($query, array(array("i", $is_complete), array("i", $tid)));
	}
	
	function getContactName($cid) {
		return $this->database->colValue("SELECT CONCAT(name, ' ', surname) as fullname FROM contact WHERE id = ?", "fullname", array(array("i", $cid)));
	}
	
	function update($id, $values) {
		$values["assigned_to"] = $values[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")];
		parent::update($id, $values);
	}
	
	function getContactmail($cid) {
		return $this->database->colValue("SELECT email FROM contact WHERE id = ?", "email", array(array("i", $cid)));
	}
}