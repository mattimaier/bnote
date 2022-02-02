<?php

/**
 * Custom Fields Data Access Object.
* @author Matti
*
*/
class CustomFieldsData extends AbstractData {

	private $fieldTypes = array(
		"BOOLEAN" => "CustomFieldsData_fieldTypes.BOOLEAN",
		"INT" => "CustomFieldsData_fieldTypes.INT",
		"DOUBLE" => "CustomFieldsData_fieldTypes.DOUBLE",
		"DATE" => "CustomFieldsData_fieldTypes.DATE",
		"DATETIME" => "CustomFieldsData_fieldTypes.DATETIME",
		"STRING" => "CustomFieldsData_fieldTypes.STRING"
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
				"id" => array(Lang::txt("CustomFieldsData_construct.id"), FieldType::INTEGER),
				"techname" => array(Lang::txt("CustomFieldsData_construct.techname"), FieldType::CHAR),
				"txtdefsingle" => array(Lang::txt("CustomFieldsData_construct.txtdefsingle"), FieldType::CHAR),
				"txtdefplural" => array(Lang::txt("CustomFieldsData_construct.txtdefplural"), FieldType::CHAR),
				"fieldtype" => array(Lang::txt("CustomFieldsData_construct.fieldtype"), FieldType::ENUM),
				"otype" => array(Lang::txt("CustomFieldsData_construct.otype"), FieldType::ENUM),
				"public_field" => array(Lang::txt("CustomFieldsData_construct.public_field"), FieldType::BOOLEAN)
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
			$cnt = $this->database->colValue("SELECT count(techname) as cnt FROM customfield WHERE techname = ?", "cnt", array(array("s", $techname)));
			if($cnt > 0) {
				new BNoteError(Lang::txt("CustomFieldsData_validate.BNoteError"));
			}
		}
		// do usual security checks
		parent::validate($input);
	}
}

?>