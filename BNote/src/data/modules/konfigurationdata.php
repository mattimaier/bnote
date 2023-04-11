<?php

/**
 * Data Access Class for configuration data.
 * @author matti
 *
 */
class KonfigurationData extends AbstractLocationData {
	
	private $parameterConfig = array();
	
	private $parameterExclude = array();
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"param" => array(Lang::txt("KonfigurationData_construct.param"), FieldType::CHAR),
				"value" => array(Lang::txt("KonfigurationData_construct.value"), FieldType::CHAR),
				"is_active" => array(Lang::txt("KonfigurationData_construct.is_active"), FieldType::BOOLEAN)
		);
	
		$this->references = array(
				"default_contact_group" => "group"
		);
	
		$this->table = "configuration";
	
		$this->parameterConfig = array(
				"rehearsal_start" => array(Lang::txt("KonfigurationData_construct.rehearsal_start"), 96),
				"rehearsal_duration" => array(Lang::txt("KonfigurationData_construct.rehearsal_duration"), FieldType::INTEGER),
				"default_contact_group" => array(Lang::txt("KonfigurationData_construct.default_contact_group"), FieldType::REFERENCE),
				"auto_activation" => array(Lang::txt("KonfigurationData_construct.auto_activation"), FieldType::BOOLEAN),
				"user_registration" => array(Lang::txt("KonfigurationData_construct.user_registration"), FieldType::BOOLEAN),
				"share_nonadmin_viewmode" => array(Lang::txt("KonfigurationData_construct.share_nonadmin_viewmode"), FieldType::BOOLEAN),
				"rehearsal_show_length" => array(Lang::txt("KonfigurationData_construct.rehearsal_show_length"), FieldType::BOOLEAN),
				"allow_participation_maybe" => array(Lang::txt("KonfigurationData_construct.allow_participation_maybe"), FieldType::BOOLEAN),
				"allow_zip_download" => array(Lang::txt("KonfigurationData_construct.allow_zip_download"), FieldType::BOOLEAN),
				"appointments_show_max" => array(Lang::txt("KonfigurationData_construct.appointments_show_max"), FieldType::INTEGER),
				"rehearsal_show_max" => array(Lang::txt("KonfigurationData_construct.rehearsal_show_max"), FieldType::INTEGER),
				"discussion_on" => array(Lang::txt("KonfigurationData_construct.discussion_on"), FieldType::BOOLEAN),
				"updates_show_max" => array(Lang::txt("KonfigurationData_construct.updates_show_max"), FieldType::INTEGER),
				"language" => array(Lang::txt("KonfigurationData_construct.language"), FieldType::CHAR),
				"default_country" => array(Lang::txt("KonfigurationData_construct.default_country"), FieldType::CHAR),
				"google_api_key" => array(Lang::txt("KonfigurationData_construct.google_api_key"), FieldType::CHAR),
				"trigger_key" => array(Lang::txt("KonfigurationData_construct.trigger_key"), FieldType::CHAR),
				"trigger_cycle_days" => array(Lang::txt("KonfigurationData_construct.trigger_cycle_days"), FieldType::INTEGER),
				"trigger_repeat_count" => array(Lang::txt("KonfigurationData_construct.trigger_repeat_count"), FieldType::INTEGER),
				"enable_trigger_service" => array(Lang::txt("KonfigurationData_construct.enable_trigger_service"), FieldType::BOOLEAN),
				"default_conductor" => array(Lang::txt("KonfigurationData_construct.default_conductor"), FieldType::REFERENCE),
				"currency" => array(Lang::txt("KonfigurationData_construct.currency"), FieldType::CHAR),
				"concert_show_max" => array(Lang::txt("KonfigurationData_construct.concert_show_max"), FieldType::INTEGER),
				"export_rehearsal_notes" => array(Lang::txt("KonfigurationData_construct.export_rehearsal_notes"), FieldType::BOOLEAN),
				"export_rehearsalsong_notes" => array(Lang::txt("KonfigurationData_construct.export_rehearsalsong_notes"), FieldType::BOOLEAN),
				"enable_failed_login_log" => array(Lang::txt("KonfigurationData_contruct.enable_failed_login_log"), FieldType::BOOLEAN)
		);
		
		$this->parameterExclude = array(
				"instrument_category_filter"
		);
		
		$this->init();
	}
	
	function getActiveParameter() {
		$query = "SELECT param, value FROM configuration WHERE is_active = 1";
		$res = $this->database->getSelection($query);
		$params = array();
		array_push($params, array("param", "caption", "value"));
		for($i = 1; $i < count($res); $i++) {
			if(in_array($res[$i]["param"], $this->parameterExclude)) continue;
			
			$param = array(
					"param" => $res[$i]["param"],
					"caption" => $this->getParameterCaption($res[$i]["param"]),
					"value" => $this->replaceParameterValue($res[$i]["param"], $res[$i]["value"])
			);
			array_push($params, $param);	
		}
		
		return $params;
	}
	
	/**
	 * Converts the value of a parameter for view purposes.
	 * @param string $param Name of the parameter.
	 * @param string $value Original value of the parameter.
	 * @return string New value, by default the $value input.
	 */
	private function replaceParameterValue($param, $value) {
		if($param == "default_contact_group") {
			return $this->adp()->getGroupName($value);
		}
		else if($param == "default_conductor") {
			if($value == "") {
				return "";
			}
			return $this->database->colValue("SELECT CONCAT(name, ' ', surname) as fullname FROM contact WHERE id = ?", "fullname", array(array("i", $value)));
		}
		else if($this->getParameterType($param) == FieldType::BOOLEAN) {
			return ($value == 1) ? Lang::txt("KonfigurationData_replaceParameterValue.yes") : Lang::txt("KonfigurationData_replaceParameterValue.no");
		}
		else {
			return $value;
		}
	}
	
	function getParameterCaption($param) {
		$caption = $this->parameterConfig[$param][0];
		if($caption == null || $caption == "") return $param;
		else return $caption;
	}
	
	function getParameterType($param) {
		$type = $this->parameterConfig[$param][1];
		if($type == null) return FieldType::CHAR;
		return $type;
	}
	
	function findByIdNoRef($id) {
		$query = "SELECT * FROM configuration WHERE param = ?";
		return $this->database->fetchRow($query, array(array("s", $id)));
	}
	
	function createParameter($id, $defaultValue, $isActive) {
		$active = $isActive ? 1 : 0;
		$query = "INSERT INTO configuration (param, value, is_active) VALUES (?, ?, ?)";
		$this->database->execute($query, array(array("i", $id), array("s", $defaultValue), array("i", $active)));
	}
	
	function update($id, $values) {
		if(!isset($values["value"])) {
			$val = NULL;
		}
		else {
			$val = $values["value"];
		}
		
		// convert values to be saved to database
		if($this->getParameterType($id) == 96) {
			$val = $values["value_hour"] . ":" . $values["value_minute"];
		}
		else if($this->getParameterType($id) == FieldType::BOOLEAN) {
			$val = (isset($_POST["value"])) ? "1" : "0";
		}
		
		// save to db
		$query = "UPDATE configuration SET value = \"$val\" WHERE param = \"$id\"";
		$this->database->execute($query);
	}
	
}

?>