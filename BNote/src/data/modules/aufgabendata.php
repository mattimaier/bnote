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
		$query .= "WHERE t.created_by = c1.id AND t.assigned_to = c2.id ";
		if($onlyOpen) {
			$query .= "AND is_complete = 0 ";
		}
		else {
			$query .= "AND is_complete = 1 ";
		}
		$query .= "ORDER BY due_at, assigned_to DESC";
		return $this->database->getSelection($query);
	}
	
	function getUserContactId($user = null) {
		if($user == null) {
			$user = $_SESSION["user"];
		}
		return $this->database->getCell("user", "contact", "id = " . $user);
	}
	
	function create($values) {
		// prepare data
		$values["created_at"] = date("d.m.Y H:i:s");
		$values["created_by"] = $this->getUserContactId();
		$values["assigned_to"] = $values["Verantwortlicher"];
		$values["is_complete"] = "0";
		return parent::create($values);	
	}
	
	function isTaskComplete($tid) {
		$complete = $this->database->getCell($this->table, "is_complete", "id = $tid");
		return ($complete == 1);
	}
	
	function markTask($tid, $is_complete) {
		$remove_date = "";
		if($is_complete == 0) {
			$remove_date = ", completed_at = NULL";
		}
		$query = "UPDATE task SET is_complete = $is_complete" . $remove_date . " WHERE id = $tid";
		$this->database->execute($query);
	}
	
	function getContactName($cid) {
		return $this->database->getCell("contact", "CONCAT(name, ' ', surname)", "id = $cid");
	}
	
	function update($id, $values) {
		$values["assigned_to"] = $values["Verantwortlicher"];
		parent::update($id, $values);
	}
	
	function getContactmail($cid) {
		return $this->database->getCell("contact", "email", "id = $cid");
	}
}

?>