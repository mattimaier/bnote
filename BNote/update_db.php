<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 *************************/

// path to src/ folder
$PATH_TO_SRC = "src/";

?>

<html>
<head>
	<title>Database Update / Check</title>
</head>
<body>

<?php 

// include necessary libs
require_once "dirs.php";
require_once $PATH_TO_SRC . "data/systemdata.php";
require_once "lang.php";
require_once $PATH_TO_SRC . "presentation/widgets/error.php";

class UpdateDb {
	
	private $sysdata;
	private $db;
	private $regex;
	
	private $tabs; // existing tables
	private $mods; // existing modules
	
	function __construct() {
		// build DB connection
		$this->sysdata = new Systemdata();
		$this->db = $this->sysdata->dbcon;
		$this->regex = $this->sysdata->regex;
		
		$this->loadTabs();
		$this->loadMods();
	}
	
	private function loadTabs() {
		$tabs = $this->db->getSelection("SHOW TABLES");
		$tables = array();
		for($i = 1; $i < count($tabs); $i++) {
			array_push($tables, $tabs[$i][0]);
		}
		$this->tabs = $tables;
	}
	
	private function loadMods() {
		$this->mods = $this->sysdata->getInnerModuleArray();
	}

	function message($msg) {
		echo "<i>$msg</i><br/>\n";
	}
	
	function addColumnToTable($table, $column, $type, $options = "") {
		$fields = $this->db->getFieldsOfTable($table);
		if(!in_array($column, $fields)) {
			$query = "ALTER TABLE $table ADD $column $type $options";
			$this->db->execute($query);
			$this->message("Column $column added to table $table.");
		}
		else {
			$this->message("Column $column already exists in table $table.");
		}
	}
	
	function addTable($table, $definition) {
		if(!in_array($table, $this->tabs)) {
			$this->db->execute($definition);
			$this->message("Table $table created.");
		}
		else {
			$this->message("Table $table already exists.");
		}
	}
	
	function addDynConfigParam($param, $default, $active = 1) {
		$confParams = $this->db->getSelection("SELECT param FROM configuration");
		$containsParam = false;
		foreach($confParams as $i => $row) {
			if(isset($row["param"]) && $row["param"] == $param) {
				$containsParam = true;
				break;
			}
		}
		if(!$containsParam) {
			$query = "INSERT INTO configuration (param, value, is_active) VALUES ";
			$query .= "('$param', '$default', $active)";
			$this->db->execute($query);
			$this->message("Added configuration parameter $param.");
		}
		else {
			$this->message("<i>Configuration parameter $param exists.");
		}
	}
	
	function addModule($modname) {
		if(!in_array($modname, $this->mods)) {
			// add new module
			$query = 'INSERT INTO module (name) VALUES ("' . $modname . '")';
			$modId = $this->db->execute($query);
		
			$this->message("New module $modname (ID $modId) added.");
		
			// add privileges for super user
			$users = $this->sysdata->getSuperUsers();
		
			$query = "INSERT INTO privilege (user, module) VALUES ";
			for($i = 0; $i < count($users); $i++) {
				if($i > 0) $query .= ",";
				$query .= "(" . $users[$i] . ", " . $modId . ")";
			}
			if(count($users) > 0) {
				$this->db->execute($query);
				$this->message("Privileges for module $modId added for all super users.");
			}
			else {
				$this->message("Please add privileges yourself, since no super users are configured.");
			}
			return $modId;
		}
		else {
			$this->message("Module $modname already exists.");
			return $this->sysdata->getModuleId($modname);
		}
	}
	
	function createFolder($path) {
		if(file_exists($path)) {
			$this->message("Folder $path already exists.");
		}
		else {
			if(mkdir($path)) {
				$this->message("Folder $path was created.");
			}
			else {
				$this->message("Failed to create folder $path.");
			}
		}
	}
	
	function addPrivilegeForAllUsers($module_id) {
		if($module_id <= 0) {
			$this->message("Cannot insert privileges. Invalid module ID.");
			return;
		}
		
		// remove all privileges for this module first
		$this->db->execute("DELETE FROM privilege WHERE module = $module_id");
		
		// insert privilege for all
		$users_db = $this->db->getSelection("SELECT id FROM user");
		
		$query = "INSERT INTO privilege (user, module) VALUES ";
		for($i = 1; $i < count($users_db); $i++) {
			if($i > 1) $query .= ",";
			$uid = $users_db[$i]["id"];
			$query .= "($uid, $module_id)";
		}
		
		$this->db->execute($query);
		$this->message($this->mods[$module_id] . " privileges for all users added.");
	}
	
	function getPrimaryKeys($table) {
		$key_query = "SHOW KEYS FROM $table WHERE key_name = 'PRIMARY'";
		$selection = $this->db->getSelection($key_query);
		return Database::flattenSelection($selection, "Column_name");
	}
	
	function removePrimaryKey($table) {
		$key_query = "SHOW KEYS FROM $table WHERE key_name = 'PRIMARY'";
		$selection = $this->db->getSelection($key_query);
		if(count($selection) > 1) {
			$query = "ALTER TABLE $table DROP PRIMARY KEY";
			$this->db->execute($query);
			$this->message("Primary key was removed from $table.");
		}
		else {
			$this->message("Primary key not existent in $table.");
		}
	}
	
	function updateValue($table, $column, $strValue, $where) {
		$query = "UPDATE `$table` SET $column = '$strValue' WHERE $where";
		$res = $this->db->execute($query);
		$this->message("Updated $table.$column with new value $strValue.");
	}
	
	function getNumberRows($table) {
		return $this->db->getNumberRows($table);
	}
	
	function executeQuery($query) {
		return $this->db->execute($query);
	}
	
	function processGdprOk() {
		$query = "SELECT id FROM contact WHERE gdpr_ok = 1";
		$gdprOkContacts = $this->db->getSelection($query);
		if(count($gdprOkContacts) > 1) {
			$this->message("There is at least one contact with GDPR flag set - ignoring GDPR presetting.");
			return;
		}
		$updateQuery = "UPDATE contact c, user u SET gdpr_ok = 1 WHERE u.contact = c.id";
		$this->db->execute($updateQuery);
		$this->message("<b>All contacts with an active user have approved the GDPR requirements so the login is not blocked.</b>");
	}
}

$update = new UpdateDb();
?>

<p><b>This script updates BNote's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>

<?php 
/*
 * 3.5.0 UPDATES
 * -------------
 */

// Task 1: Config for number of gigs on start page
$update->addDynConfigParam("appointments_show_max", 5);

?>

<div style="font-weight: bold; font-style: italic;">COMPLETE.</div>

</body>
</html>