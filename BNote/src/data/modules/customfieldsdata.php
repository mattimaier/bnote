<?php

/**
 * Custom Fields Data Access Object.
* @author Matti
*
*/
class CustomFieldsData extends AbstractData {

	private $fieldTypes = array(
		"BOOLEAN" => "customfield_bool",
		"INT" => "customfield_int",
		"DOUBLE" => "customfield_double",
		"STRING" => "customfield_string"
	);
	
	private $objectReferenceTypes = array(
		"c" => "contact"
	);
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"techname" => array("Technischer Name", FieldType::CHAR),
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
	
	function getFieldTypes() {
		$out = array();
		foreach($this->fieldTypes as $t => $txt) {
			$out[$t] = Lang::txt($txt);
		}
		return $out;
	}
	
	function getObjectTypes() {
		$out = array();
		foreach($this->objectReferenceTypes as $t => $txt) {
			$out[$t] = Lang::txt($txt);
		}
		return $out;
	}
	
	function validate($input) {
		// check uniqueness of techname
		$techname = $input["techname"];
		$cnt = $this->database->getCell($this->table, "count(techname)", "techname = '$techname'");
		if($cnt > 0) {
			new BNoteError(Lang::txt("customfield_notunique_error"));
		}
		// do usual security checks
		parent::validate($input);
	}
}

?>