<?php

/**
 * Data Access Class for configuration data.
 * @author matti
 *
 */
class KonfigurationData extends AbstractData {
	
	private $parameterConfig = array();
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"param" => array("Parameter", FieldType::CHAR),
				"value" => array("Wert", FieldType::CHAR),
				"is_active" => array("Aktiv", FieldType::BOOLEAN)
		);
	
		$this->references = array();
	
		$this->table = "configuration";
	
		$this->parameterConfig = array(
				"rehearsal_start" => array("Probenbeginn", 96),
				"rehearsal_duration" => array("Probendauer in min", FieldType::INTEGER)
		);
		
		$this->init();
	}
	
	function getActiveParameter() {
		$query = "SELECT param, value FROM " . $this->table . " WHERE is_active = 1";
		$res = $this->database->getSelection($query);
		$params = array();
		array_push($params, array("param", "caption", "value"));
		for($i = 1; $i < count($res); $i++) {
			$param = array(
					"param" => $res[$i]["param"],
					"caption" => $this->getParameterCaption($res[$i]["param"]),
					"value" => $res[$i]["value"]
			);
			array_push($params, $param);			
		}
		
		return $params;
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
		$query = "SELECT * FROM " . $this->table . " WHERE param = \"$id\"";
		return $this->database->getRow($query);
	}
	
	function update($id, $values) {
		$val = $values["value"];
		if($this->getParameterType($id) == 96) {
			$val = $values["value_hour"] . ":" . $values["value_minute"];
		}
		$query = "UPDATE configuration SET value = \"$val\" WHERE param = \"$id\"";
		$this->database->execute($query);
	}
}

?>