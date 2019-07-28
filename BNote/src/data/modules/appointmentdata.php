<?php 

/**
 * DAO for appointments (calendar sub-module).
 * @author matti
 *
 */
class AppointmentData extends AbstractLocationData {
	
	public static $colExchange = array(
			"contact" => array("name", "surname"),
			"location" => array("name")
	);
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("AppointmentData_construct.id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("AppointmentData_construct.begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("AppointmentData_construct.end"), FieldType::DATETIME),
			"name" => array(Lang::txt("AppointmentData_construct.name"), FieldType::CHAR),
			"location" => array(Lang::txt("AppointmentData_construct.location"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("AppointmentData_construct.contact"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("AppointmentData_construct.notes"), FieldType::TEXT)
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
		$address = $this->getAddressFromLocation($this->database->getCell($this->table, "location", "id = $id"));
		$appointment = Data::arrayMergeWithPrefix($appointment, $address, "location");
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