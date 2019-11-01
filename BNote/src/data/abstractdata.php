<?php
if(file_exists($GLOBALS["DIR_DATA"] . "applicationdataprovider.php")) {
	require_once $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";
}

/**
 * Data Access Object (DAO) Template for all entities.
 * @author matti
 *
 */
abstract class AbstractData {
	
	/**
	 * Database Object
	 */
	protected $database;

	/**
	 * Regular Expression Object
	 */
	protected $regex;
	
	/**
	 * Content: [db_field] => [label, Type]<br />
	 * With [label] as the displayed name and [type] as the Type constant.
	 * @var Array
	 */
	protected $fields;
	
	/**
	 * References to other entities.
	 * Content: [foreign_key_column] => [foreign table]
	 * @var Array
	 */
	protected $references;
	
	/**
	 * Associated database table.
	 * @var String
	 */
	protected $table;
	
	/**
	 * ADP = Application Data Provider
	 * A collection of data access methods used in multiple modules
	 * accessing database information.
	 * @var Object
	 */
	private $adp;
	
	/**
	 * System Data: Application core settings and holder of diverse system functions.
	 * @var Systemdata
	 */
	private $sysdata;
	
	/**
	 * True when the trigger service is available.
	 * @var boolean
	 */
	protected $triggerServiceEnabled = false;
	
	/**
	 * BNote.info Trigger service
	 * @var TriggerServiceClient
	 */
	protected $triggerServiceClient = null;
	
	/**
	 * Prefix for includion in exports
	 * @var string
	 */
	protected $dirPrefix = "";
	
	/**
	 * Initialize data provider.
	 * @param string $dir_prefix Optional parameter for include(s) prefix.
	 */
	protected function init($dir_prefix = "") {
		$this->dirPrefix = $dir_prefix;
		
		global $system_data;
		$this->sysdata = $system_data;
		$this->database = $system_data->dbcon;
		$this->regex = $system_data->regex;
		
		$this->adp = new ApplicationDataProvider($this->database, $this->regex, $system_data, $dir_prefix);
	}
	
	protected function init_trigger($dir_prefix) {
		$service_active = $this->getSysdata()->getDynamicConfigParameter("enable_trigger_service");
		if($service_active) {
			require_once($dir_prefix . $GLOBALS['DIR_EXPORT'] . "triggerService.php");
			$this->triggerServiceClient = new TriggerServiceClient();
			$this->triggerServiceEnabled = true;
		}
	}
	
	protected function getNotificationTriggerUrl() {
		// use $_SERVER info over configuration, because it's often mal-configured
		if(isset($_SERVER['REQUEST_SCHEME'])) {
			$proto = $_SERVER['REQUEST_SCHEME'];
		} else if(isset($_SERVER['SERVER_PROTOCOL']) && strpos($_SERVER['SERVER_PROTOCOL'], "HTTPS") !== FALSE) {
			$proto = "https";
		} else {
			$proto = "http";
		}
		
		$bnote_url = $proto . "://" . $_SERVER['HTTP_HOST'];
		$bnote_url .= substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/"));  # bnote path on this server
		$bnote_url .= "/src/export/notify.php";
		return $bnote_url;
	}
	
	protected function buildTriggerData($otype, $oid) {
		return array(
			"oid" => $oid,
			"otype" => $otype,
			"token" => $this->sysdata->getDynamicConfigParameter("trigger_key")
		);
	}
	
	/**
	 * Creates a trigger.
	 * @param String $event_dt Date when the event begins/ends in Format: YYYY-mm-dd HH:ii:ss
	 * @param Array $triggerData Data, usually from buildTriggerData()
	 */
	protected function createTrigger($event_dt, $triggerData) {
		# End of the event
		$limit_dt = DateTime::createFromFormat(TriggerServiceClient::DATE_FORMAT, $event_dt);
		# every n days send a reminder
		$repeatCycle = intval($this->getSysdata()->getDynamicConfigParameter("trigger_cycle_days"));
		# how often should this be repeated
		$repeatCount = intval($this->getSysdata()->getDynamicConfigParameter("trigger_repeat_count"));
		
		# Create triggers
		if($repeatCount > 0) {
			$trigger_on = clone $limit_dt;
			$dtinterval = new DateInterval("P" . strval($repeatCycle) . "D");
			
			for($i = 0; $i < $repeatCount;$i++) {
				date_sub($trigger_on, $dtinterval);  // inplace operation				
				$this->triggerServiceClient->createTrigger(
					date_format($trigger_on, TriggerServiceClient::DATE_FORMAT),
					$this->getNotificationTriggerUrl(),
					$triggerData
				);
			}
		}
	}
	
	/**
	 * <strong>init() must have been called first!</strong>
	 * @return ApplicationDataProvider The application data provider reference.
	 */
	public function adp() {
		if($this->adp == null) echo "<strong>" . Lang::txt("AbstractData_adp.message") . "</strong>";
		return $this->adp;
	}
	
	/**
	 * <strong>init() must have been called first!</strong>
	 * @return Systemdata Reference to application core settings and diverse system functions.
	 */
	public function getSysdata() {
		return $this->sysdata;
	}
	
	/**
	 * Sets the SystemData reference.
	 * @param Systemdata $sysdata System Data Reference.
	 */
	public function setSysdata($sysdata) {
		$this->sysdata = $sysdata;
	}
	
	/**
	 * Returns the name of the database fields.
	 * @return One dimensional array with db_fields as values.
	 */
	public function getDatabaseFields() {
		return array_keys($this->fields);
	}
	
	/**
	 * Returns the complete field information array.
	 * @return Array with the content: [db_field] => [label, Type]<br />
	 * With [label] as the displayed name and [type] as the Type constant.
	 */
	public function getFields() {
		return $this->fields;
	}
	
	/**
	 * @return The name of the database table.
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Returns the label of the given field name.
	 * @param String $field Database field name.
	 * @return String label of field.
	 */
	public function getLabelOfField($field) {
		if(in_array($field, $this->fields)) return "";
		return $this->fields[$field][0];
	}
	
	/**
	 * Returns the type of the given field name.
	 * @param String $field Database field name.
	 * @return Type Type of the field. If field not existent, TEXT is returned.
	 */
	public function getTypeOfField($field) {
		if(!key_exists($field, $this->fields)) {
			return FieldType::TEXT;
		}
		return $this->fields[$field][1];
	}
	
	/**
	 * Optional handling of fields.
	 * @param boolean $field True if the field has a third parameter set to true/optional, otherwise false.
	 */
	public function isFieldOptional($field) {
		if(!key_exists($field, $this->fields) || count($this->fields[$field]) < 3) {
			return false;
		}
		return $this->fields[$field][2];
	}
	
	/**
	 * Returns the table references by the given column.
	 * @param String $column Name of the referencing column.
	 * @return Name of the table the column references to.
	 */
	public function getReferencedTable($column) {
		if(isset($this->references[$column])) {
			return $this->references[$column];
		}
		return $this->getTable();
	}
	
	/**
	 * Returns all entities, without exchanging the foreign key columns with something.<br />
	 * <strong>Don't use this function for entities with possible large contents!</strong>
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllNoRef() {
		$query = "SELECT * FROM $this->table";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns all entities, without exchanging the foreign key columns with something.<br />
	 * @param String $limit Limit Expression in SQL without the "LIMIT" identifier.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllNoRefLimit($limit) {
		$query = "SELECT * FROM " . $this->table . " LIMIT $limit";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns all entities, without exchanging the foreign key columns with something.<br />
	 * @param String $where Where clause in SQL without the "WHERE" identifier.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllNoRefWhere($where) {
		$query = "SELECT * FROM " . $this->table . " WHERE $where";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns all entities with the foreign key columns exchanged for the given exchange columns.
	 * @param Array $colExchange The columns that will be exchanged for the foreign key column.<br/>
	 * 		Format: [foreign_key_column] => [col1, col2, ...] with colX in the referred table.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllJoined($colExchange) {
		return $this->database->getSelection($this->createJoinedQuery($colExchange));
	}
	
	/**
	 * Returns all entities with the foreign key columns exchanged for the given exchange columns.
	 * @param Array $colExchange The columns that will be exchanged for the foreign key column.<br/>
	 * 		Format: [foreign_key_column] => [col1, col2, ...] with colX in the referred table.
	 * @param String $limit Limit Expression in SQL without the "LIMIT" identifier.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllJoinedLimit($colExchange, $limit) {
		if(strlen($limit) > 0) {
			$q = $this->createJoinedQuery($colExchange) . " LIMIT $limit";
			return $this->database->getSelection($q);
		}
		else {
			return $this->findAllJoined($colExchange);
		}
	}
	
	/**
	 * Returns all entities with the foreign key columns exchanged for the given exchange columns.
	 * @param Array $colExchange The columns that will be exchanged for the foreign key column.<br/>
	 * 		Format: [foreign_key_column] => [col1, col2, ...] with colX in the referred table.
	 * @param String $where Where clause in SQL without the "WHERE" identifier.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllJoinedWhere($colExchange, $where) {
		if(strlen($where) > 0) {
			$q = $this->createJoinedQuery($colExchange) . " AND $where";
			return $this->database->getSelection($q);
		}
		else {
			return $this->findAllJoined($colExchange);
		}
	}
	
	/**
	 * Returns all entities with the foreign key columns exchanged for the given exchange columns.
	 * @param Array $colExchange The columns that will be exchanged for the foreign key column.<br/>
	 * 		Format: [foreign_key_column] => [col1, col2, ...] with colX in the referred table.
	 * @param String $orderByCol Order By clause in SQL without the "ORDER BY" identifier, so basically just the column name and eventually ASC/DESC.
	 * @return Returns an database getSelection(...) result array.
	 */
	public function findAllJoinedOrdered($colExchange, $orderByCol) {
		if(strlen($orderByCol) > 0) {
			$q = $this->createJoinedQuery($colExchange) . " ORDER BY $orderByCol";
			return $this->database->getSelection($q);
		}
		else {
			return $this->findAllJoined($colExchange);
		}
	}
	
	/**
	 * Helper function to build the complex query.
	 * @param Array $colExchange see findAllJoined(...)
	 */
	private function createJoinedQuery($colExchange) {
		// build query
		$query = "SELECT ";
		$join = "";
		$tables = array();
		array_push($tables, $this->table);
		
		// all native fields
		foreach($this->fields as $field => $info) {
			if(isset($colExchange[$field])) continue;
			$query .= $this->table . "." . $field . ", ";
		}
		
		// all exchanged fields
		if(count($colExchange) != 0) {
			foreach($colExchange as $fcol => $tcols) {
				if(!isset($this->references[$fcol])) continue;
				$foreign_table = $this->references[$fcol];
				if(is_array($tcols)) {
					foreach($tcols as $cid => $col) {
						$query .= $foreign_table . "." . $col . " as $foreign_table$col, "; // foreign_table.foreign_col
					}
				}
				// add table to required tables
				if(!in_array($foreign_table, $tables)) array_push($tables, $foreign_table);
				
				// add join clause: fcol_id = foreigntable.id
				$join .= $this->table . "." . $fcol . " = " . $foreign_table . ".id AND ";
			}
		}
		$query = substr($query, 0, strlen($query)-2); // cut last ", "
		
		// From tables
		$query .= " FROM ";
		foreach($tables as $table) {
			$query .= $table . ", ";
		}
		if(count($tables) > 1) $query = substr($query, 0, strlen($query)-2); // cut last ", "
		
		// join statement
		if(strlen($join) > 5) $join = substr($join, 0, strlen($join)-5); // cut last " AND "
		$query .= " WHERE " . $join;

		return $query;
	}
	
	/**
	 * Finds one row result by its id.
	 * @param int $id ID of the row.
	 * @return Returns a database getRow(...) result. 
	 */
	public function findByIdNoRef($id) {
		$query = "SELECT * FROM $this->table WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	/**
	 * Finds a row results by its id and includes exchanged columns.
	 * @param int $id ID of the row.
	 * @param Array $colExchange
	 * @return Returns a database getRow(...) result.
	 */
	public function findByIdJoined($id, $colExchange) {
		$table = $this->table;
		$query = $this->createJoinedQuery($colExchange) . " AND $table.id = $id";
		return $this->database->getRow($query);
	}
	
	/**
	 * Creates a new row with the given values.
	 * @param Array $values Value array in the format [db_field] => [value].
	 * @return ID of the insert statement / new entity.
	 */
	public function create($values) {
		if(count($values) > 0) {
			// build query
			$cols = "";
			$vals = "";
			
			foreach($values as $field => $value) {
				if(!in_array($field, array_keys($this->fields))) continue;
				$cols .= $field . ", ";
				$t = $this->getTypeOfField($field);
				
				if($t == FieldType::DATE || $t == FieldType::DATETIME) {
					$value = Data::convertDateToDb($value);
				}
				else if($t == FieldType::DECIMAL) {
					$value = Data::convertToDb($value);
				}
				else if($t == FieldType::BOOLEAN) {
					$value = ($value == "on") ? 1 : 0; 
				}
				
				if($t == FieldType::TEXT || $t == FieldType::CHAR || $t == FieldType::PASSWORD
					|| $t == FieldType::DATETIME || $t == FieldType::TIME || $t == FieldType::ENUM
					|| $t == FieldType::DATE || $t == FieldType::EMAIL || $t == FieldType::LOGIN) $vals .= '"' . $value . '", ';
				else $vals .= $value . ", ";
			}
			$cols = substr($cols, 0, strlen($cols)-2); // cut last ", "
			$vals = substr($vals, 0, strlen($vals)-2); // cut last ", "
			
			$query = "INSERT INTO $this->table (";
			$query .= $cols;
			$query .= ") VALUES (";
			$query .= $vals;
			$query .= ")";
		}

		return $this->database->execute($query);
	}
	
	/**
	 * Updates the row with the given $id with the values.
	 * @param String $id ID of the row to update.
	 * @param Array $values Array in the format [db_field] => [value]
	 */
	public function update($id, $values) {
		$query = "UPDATE " . $this->table . " SET ";
		
		foreach($values as $field => $val) {
			if(!array_key_exists($field, $this->fields)) continue;
			else {
				$query .= $field . " = ";
				$t = $this->getTypeOfField($field);
				
				if($t == FieldType::DATE || $t == FieldType::DATETIME) {
					$val = Data::convertDateToDb($val);
				}
				else if($t == FieldType::DECIMAL) {
					$val = Data::convertToDb($val);
				}
				else if($t == FieldType::BOOLEAN && $val === "on") {
					$val = 1;
				}
					
				if($t == FieldType::TEXT || $t == FieldType::CHAR || $t == FieldType::PASSWORD
					|| $t == FieldType::DATETIME || $t == FieldType::TIME || $t == FieldType::ENUM
					|| $t == FieldType::DATE || $t == FieldType::EMAIL || $t == FieldType::LOGIN) {
						$query .= '"' . $val . '", ';
				}
				else {
					$query .= $val . ", ";
				}
			}
		}
		
		// update unset boolean values
		foreach($this->fields as $fieldName => $field) {
			$fieldType = $field[1];
			if($fieldType == FieldType::BOOLEAN && !array_key_exists($fieldName, $values)) {
				$query .= $fieldName . " = 0, ";
			}
		}
		
		$query = substr($query, 0, strlen($query)-2);
		$query .= " WHERE id = $id";
		$this->database->execute($query);
	}
	
	/**
	 * Removes the row with the given id.
	 * @param int $id Id of the row.
	 */
	public function delete($id) {
		$query = "DELETE FROM " . $this->table . " WHERE id = $id";
		$this->database->execute($query);
	}
	
	/**
	 * Validate user input based on fieldtype information. If function passes,
	 * then the values are ok.
	 * @param Array input Input array in the form of [id] => [value].
	 */
	public function validate($input) {
		if(count($input) == 0) {
			new BNoteError(Lang::txt("AbstractData_validate.error"));
		}
		foreach($input as $id => $value) {
			$this->validatePair($id, $value, $this->getTypeOfField($id));
		}
	}
	
	protected function validatePair($k, $value, $type) {
		// check if a field has a third parameter -> optional
		if($this->isFieldOptional($k)) {
			if($value == "") return;
		}
		switch($type) {
			case FieldType::INTEGER: $this->regex->isPositiveAmount($value, $k); break;
			case FieldType::DECIMAL: $this->regex->isMoney($value, $k); break;
			case FieldType::CHAR: $this->regex->isName($value, $k); break;
			case FieldType::DATE: $this->regex->isDate(trim($value), $k); break;
			case FieldType::TIME: $this->regex->isTime(trim($value), $k); break;
			case FieldType::DATETIME: $this->regex->isDateTime(trim($value), $k); break;
			case FieldType::REFERENCE: if($value != "null") $this->regex->isPositiveAmount($value, $k); break;
			case FieldType::EMAIL: $this->regex->isEmail($value, $k); break;
			case FieldType::PASSWORD: // only check if password is not empty.
				if(isset($value) && $value != "") $this->regex->isPassword($value, $k);
				break;
			case FieldType::LOGIN: $this->regex->isLogin($value, $k); break;
			default: $this->regex->isText($value, $k); break;
		}
	}
	
	/**
	 * Retrieves the custom fields for this object type.
	 * @param String $objectType Character referring to CustomFieldsData::objectReferenceTypes
	 * @param boolean $publicOnly If only public fields should be exported, false by default.
	 * @return Selection of custom fields
	 */
	public function getCustomFields($objectType, $publicOnly = false) {
		$query = "SELECT * FROM customfield WHERE otype = '$objectType'";
		if($publicOnly) {
			$query .= " AND public_field = 1";
		}
		return $this->database->getSelection($query);
	}
	
	/**
	 * Converts the custom field type into a FieldType ENUM value.
	 * @param String $customType Custom field type string.
	 * @return FieldType
	 */
	public function fieldTypeFromCustom($customType) {
		switch($customType) {
			case "BOOLEAN": return FieldType::BOOLEAN;
			case "INT": return FieldType::INTEGER;
			case "DOUBLE": return FieldType::DECIMAL;
			case "DATE": return FieldType::DATE;
			case "DATETIME": return FieldType::DATETIME;
			default: return FieldType::CHAR;
		}
	}
	
	/**
	 * Convenience method for custom field type info access.
	 * @param Selection $selection Usually from getCustomFields($objectype)
	 * @return Array Format: techname => [txtdefsingle, fieldtype, id]
	 */
	public function compileCustomFieldInfo($selection) {
		$info = array();
		for($i = 1; $i < count($selection); $i++) {
			$info[$selection[$i]['techname']] = array(
					$selection[$i]['txtdefsingle'],
					$selection[$i]['fieldtype'],
					$selection[$i]['id'],
			);
		}
		return $info;
	}
	
	/**
	 * Adds custom field information to the result of getFields() and returns the full package.
	 * @param String $otype Object type for custom data.
	 */
	public function getFieldsWithCustomFields($otype) {
		$customFields = $this->getCustomFields($otype);
		for($i = 1; $i < count($customFields); $i++) {
			$techName = $customFields[$i]['techname'];
			$fieldName = $customFields[$i]['txtdefsingle'];
			$customFieldType = $customFields[$i]['fieldtype'];
			
			$this->fields[$techName] = array($fieldName, $this->fieldTypeFromCustom($customFieldType));
		}
		return $this->fields;
	}
	
	protected function createCustomFieldData($otype, $oid, $values) {
		$valueSet = array();
		
		// get custom fields for otype
		$customFieldSelection = $this->getCustomFields($otype);
		$fields = $this->compileCustomFieldInfo($customFieldSelection);
		
		foreach($fields as $techname => $info) {
			$vtype = $info[1];
			$fieldid = $info[2];
		
			$intval = 0;
			$dblval = 0.0;
			$strval = "";
			$dateval = "";
			$datetimeval = "";
				
			if(isset($values[$techname])) {
				$val = $values[$techname];
		
				switch($vtype) {
					case "BOOLEAN":
						$intval = $val ? 1 : 0;
						break;
					case "INT":
						$intval = $val;
						if($intval == "") {
							$intval = 0;
						}
						break;
					case "DOUBLE":
						$dblval = Data::convertToDb($val);
						if($dblval == "") {
							$dblval = 0;
						}
						break;
					case "DATE":
						$dateval = Data::convertDateToDb($val);
						break;
					case "DATETIME":
						$datetimeval = Data::convertDateToDb($val);
						break;
					default:
						$strval = $val;
				}
			}
			array_push($valueSet, "($fieldid, '$otype', $oid, $intval, $dblval, '$strval', '$dateval', '$datetimeval')");
		}
		if(count($valueSet) == 0) {
			return;
		}
		$query = "INSERT INTO customfield_value (customfield, otype, oid, intval, dblval, strval, dateval, datetimeval) VALUES ";
		$query .= join(",", $valueSet);
		$this->database->execute($query);
	}
	
	protected function updateCustomFieldData($otype, $oid, $values, $public_mode = false) {
		// in case the public_mode is on, add the non-public field values to the value set
		if($public_mode) {
			$currentData = $this->getCustomFieldData($otype, $oid);
			
			$customFields = $this->getCustomFields($otype);
			for($i = 1; $i < count($customFields); $i++) {
				$field = $customFields[$i];
				$techName = $field["techname"];
				if($field["public_field"] == 0) {
					$values[$techName] = $currentData[$techName];
				}
			}
		}
		
		// for update compatibility of custom fields, remove all custom data before inserting new ones
		$delQuery = "DELETE FROM customfield_value WHERE otype = '$otype' AND oid = $oid";
		$this->database->execute($delQuery);
		
		// create data again
		$this->createCustomFieldData($otype, $oid, $values);
	}
	
	protected function deleteCustomFieldData($otype, $oid) {
		$query = "DELETE FROM customfield_value WHERE otype = '$otype' AND oid = $oid";
		$this->database->execute($query);
	}
	
	/**
	 * Compiles a flat array of custom field data for the given entity.
	 * @param Character $otype Object Type, e.g. 'c' for a contact.
	 * @param int $oid Object ID
	 * @return flat array in format: techname => value
	 */
	protected function getCustomFieldData($otype, $oid) {
		// get data
		$query = "SELECT f.techname, v.intval, v.dblval, v.strval, v.dateval, v.datetimeval ";
		$query .= "FROM customfield_value v JOIN customfield f ON v.customfield = f.id ";
		$query .= "WHERE f.otype = '$otype' AND oid = $oid";
		$select = $this->database->getSelection($query);
		
		// get custom fields for otype
		$customFieldSelection = $this->getCustomFields($otype);
		$fields = $this->compileCustomFieldInfo($customFieldSelection);
		
		// process and flatten data
		$out = array();
		for($i = 1; $i < count($select); $i++) {
			$techname = $select[$i]['techname'];
			$out[$techname] = $this->customFieldValueMapper($select[$i], $fields);
		}
		
		// in case there is no custom data for this object yet
		if(count($out) < count($fields)) {
			foreach($fields as $techname => $info) {
				if(!isset($out[$techname])) {
					$out[$techname] = "";
				}
			}
		}
		return $out;
	}
	
	protected function customFieldValueMapper($customValueRow, $fields) {
		$techname = $customValueRow['techname'];
		$vtype = $fields[$techname][1];
		switch($vtype) {
			case "BOOLEAN": 
				$val = ($customValueRow['intval'] == 1); 
				break;
			case "INT": $val = $customValueRow['intval']; break;
			case "DOUBLE": 
				$val = $customValueRow['dblval'];
				$val = Data::convertFromDb($val);
				break;
			case "DATE": 
				$val = $customValueRow['dateval'];
				$val = $val == null ? "" : Data::convertDateFromDb($val);
				break;
			case "DATETIME": 
				$val = $customValueRow["datetimeval"];
				$val = Data::convertDateFromDb($val);
				break;
			default: $val = $customValueRow['strval'];
		}
		return $val;
	}
	
	protected function appendCustomDataToSelection($otype, $selection, $idcol="id") {
		// get all OIDs from selection
		$oids = Database::flattenSelection($selection, "id");
		if(count($oids) == 0) {
			// no data to append to
			return $selection;
		}
		$query = "SELECT oid, techname, intval, dblval, strval, dateval, datetimeval "
				. "FROM customfield_value v JOIN customfield f ON v.customfield = f.id " 
				. "WHERE f.otype = '$otype' AND (oid = "
				. join(" OR v.oid = ", $oids)
				. ")";
		$fieldValueSelect = $this->database->getSelection($query);
		
		// get custom fields for otype
		$customFieldSelection = $this->getCustomFields($otype);
		$fields = $this->compileCustomFieldInfo($customFieldSelection);
		
		// iterate over data and append to each row
		$result = array();
		for($i = 0; $i < count($selection); $i++) {
			$row = $selection[$i];			
			if($i == 0) {
				// header: add tech names
				foreach($fields as $techname => $info) {
					array_push($row, $techname);
				}
			}
			else {
				// body: match ID and OID, then add custom data
				$row_oid = $row[$idcol];
				$customValueRows = $this->filterCustomValueRows($fieldValueSelect, $row_oid);
				for($y = 0; $y < count($customValueRows); $y++) {
					$valRow = $customValueRows[$y];
					$row[$valRow["techname"]] = $this->customFieldValueMapper($valRow, $fields);
				}
			}
			array_push($result, $row);
		}
		return $result;
	}
	
	protected function filterCustomValueRows($fieldValueSelect, $oid) {
		$result_rows = array();
		for($i = 1; $i < count($fieldValueSelect); $i++) {
			if($fieldValueSelect[$i]['oid'] == $oid) {
				array_push($result_rows, $fieldValueSelect[$i]);
			}
		}
		return $result_rows;
	}
	
	protected function validateCustomData($values, $fields) {
		for($i = 1; $i < count($fields); $i++) {
			$field = $fields[$i];
			$type = $this->fieldTypeFromCustom($field["fieldtype"]);
			if(isset($values[$field["techname"]])) {
				// validate according to type
				$this->validatePair($field["techname"], $values[$field["techname"]], $type);
			}
		}
	}
}