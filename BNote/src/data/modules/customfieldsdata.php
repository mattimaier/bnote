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
		"DATE" => "customfield_date",
		"DATETIME" => "customfield_datetime",
		"STRING" => "customfield_string"
	);
	
	private $objectReferenceTypes = array(
		"c" => "contact",
		"r" => "rehearsal",
		"g" => "concert",  # g = gig
		"s" => "song",
		"v" => "reservation",  # v = vacancy
		"a" => "appointment",
		"l" => "location",
		"e" => "equipment"
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
				"otype" => array("Objektbezug", FieldType::ENUM),
				"public_field" => array("Freigegeben", FieldType::BOOLEAN)
		);

		$this->references = array(
		);

		$this->table = "customfield";
		$this->init($dir_prefix);
	}
	
	function getAllCustomFields() {
		$fields = $this->findAllNoRef();
		
		// replace enum reference values
		$selection = array();
		for($i = 0; $i < count($fields); $i++) {
			if($i == 0) {
				array_push($selection, $fields[$i]);
			}
			else {
				$row = $fields[$i];
				$row["fieldtype"] = Lang::txt($this->fieldTypes[$row["fieldtype"]]);
				$row["otype"] = Lang::txt($this->objectReferenceTypes[$row["otype"]]);
				array_push($selection, $row);
			}
		}
		return $selection;
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
		if($_GET["sub"] == "add_process") {
			$techname = $input["techname"];
			$cnt = $this->database->getCell($this->table, "count(techname)", "techname = '$techname'");
			if($cnt > 0) {
				new BNoteError(Lang::txt("customfield_notunique_error"));
			}
		}
		// do usual security checks
		parent::validate($input);
	}
}

?>