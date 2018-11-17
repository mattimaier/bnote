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
		$this->message("Updated 'Mitglieder' and 'Externe' Group.");
	}
	
	function getNumberRows($table) {
		return $this->db->getNumberRows($table);
	}
	
	function executeQuery($query) {
		return $this->db->execute($query);
	}
}

$update = new UpdateDb();
?>

<p><b>This script updates BNote's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>

<?php 
/*
 * 3.4.0 UPDATES
 * -------------
 */

// Task 1: Archive flag for songs
$update->addColumnToTable("song", "is_active", "INT(1) DEFAULT 1");

// Task 2: GDPR OK flag and code on contacts
$update->addColumnToTable("contact", "gdpr_ok", "INT(1) DEFAULT 0");
$update->addColumnToTable("contact", "gdpr_code", "VARCHAR(255)");

// Task 3: Rehearsal conductor
$update->addColumnToTable("rehearsal", "conductor", "INT(11)");  # contact
$update->addColumnToTable("contact", "is_conductor", "INT(1) DEFAULT 0");
$update->addDynConfigParam("default_conductor", "");

// Task 4: Custom field extensions
$update->addColumnToTable("customfield", "public_field", "INT(1) DEFAULT 0");
$update->addColumnToTable("customfield_value", "dateval", "DATE");
$update->addColumnToTable("customfield_value", "datetimeval", "DATETIME");

// Task 5: Appointment submodule
$update->addTable("appointment", "CREATE TABLE IF NOT EXISTS `appointment` (
	id int(11) PRIMARY KEY AUTO_INCREMENT,
	begin DATETIME NOT NULL,
	end DATETIME NOT NULL,
	name VARCHAR(100) NOT NULL,
	location INT(11),
	contact INT(11),
	notes TEXT
)");

$update->addTable("appointment_group", "CREATE TABLE IF NOT EXISTS `appointment_group` (
	`appointment` int(11) NOT NULL,
	`group` int(11) NOT NULL
)");

// Task 6: Save rehearsal/concert groups
$update->addTable("rehearsal_group", "CREATE TABLE IF NOT EXISTS `rehearsal_group` (
	`rehearsal` int(11) NOT NULL,
	`group` int(11) NOT NULL
)");

$update->addTable("concert_group", "CREATE TABLE IF NOT EXISTS `concert_group` (
	`concert` int(11) NOT NULL,
	`group` int(11) NOT NULL
)");

// Task 7: Add state field to address
$update->addColumnToTable("address", "state", "VARCHAR(50)");

?>

<div style="font-weight: bold; font-style: italic;">COMPLETE.</div>

</body>
</html>