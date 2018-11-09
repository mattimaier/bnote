<?php 

/**
 * DAO for appointments (calendar sub-module).
 * @author matti
 *
 */
class AppointmentData extends AbstractData {
	
	public static $colExchange = array(
			"contact" => array("name", "surname"),
			"location" => array("name")
	);
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("end"), FieldType::DATETIME),
			"name" => array(Lang::txt("name"), FieldType::CHAR),
			"location" => array(Lang::txt("location"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("contact"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("notes"), FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location",
			"contact" => "contact"
		);
		
		$this->table = "appointment";
		
		$this->init($dir_prefix);
	}
	
	function getAppointment($id) {
		$appointment = $this->findByIdJoined($id, AppointmentData::$colExchange);
		$customData = $this->getCustomFieldData('a', $id);
		$appointment["groups"] = $this->getGroupsForAppointment($id);
		return array_merge($appointment, $customData);
	}
	
	function getGroupsForAppointment($id) {
		$query = "SELECT g.* FROM `appointment_group` ag JOIN `group` g ON ag.`group` = g.id WHERE ag.appointment = $id";
		return $this->database->getSelection($query);
	}
	
	public function getJoinedAttributes() {
		return $this->colExchange;
	}
	
	public function create($values) {
		$id = parent::create($values);
		$this->createCustomFieldData('a', $id, $values);
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$this->updateGroups($id, $groups);
	}
	
	/**
	 * Overwrite groups
	 * @param int $id Appointment ID.
	 * @param array $groups Group IDs to set.
	 */
	private function updateGroups($id, $groups) {
		$delQuery = "DELETE FROM appointment_group WHERE appointment = $id";
		$this->database->execute($delQuery);
		
		$insQuery = "INSERT INTO appointment_group (appointment, `group`) VALUES ($id,";
		$insQuery .= join("), ($id,", $groups) . ")";
		$this->database->execute($insQuery);
	}
	
	public function update($id, $values) {
		parent::update($id, $values);
		$this->updateCustomFieldData('a', $id, $values);
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$this->updateGroups($id, $groups);
	}
	
	public function delete($id) {
		$this->deleteCustomFieldData('a', $id);
		$delGroupsQuery = "DELETE FROM appointment_group WHERE appointment = $id";
		$this->database->execute($delGroupsQuery);
		parent::delete($id);
	}
}

?>