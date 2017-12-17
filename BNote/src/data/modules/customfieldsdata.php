<?php

/**
 * Custom Fields Data Access Object.
* @author Matti
*
*/
class CustomFieldsData extends AbstractData {

	public $fieldTypes = array(
		"INT" => "Ganzzahl",
		"DOUBLE" => "Dezimalzahl",
		"STRING" => "Zeichenkette"
	);
	
	public $objectReferenceTypes = array(
		"c" => "Kontakt"
	);
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"techname" => array("Name", FieldType::CHAR),
				"txtdefsingle" => array("Name Singular", FieldType::CHAR),
				"txtdefplural" => array("Name Plural", FieldType::CHAR),
				"fieldtype" => array("Wertebereich", FieldType::ENUM),
				"otype" => array("Objektbezug", FieldType::ENUM)
		);

		$this->references = array(
		);

		$this->table = "customfield";
		$this->init($dir_prefix);
	}
	
	
	
}

?>