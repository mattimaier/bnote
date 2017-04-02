<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 3.1.5 to 3.2.0 *
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
<p>
<?php 

/*
 * 3.2.0 UPDATES
 * -------------
 */

// Task 1: rename Mitspieler to Mitglieder
$update->updateValue("group", "name", "Mitglieder", "id = 2");
$update->updateValue("group", "name", "Externe", "id = 3");

// Task 2a: location types
$update->addTable("location_type", "CREATE TABLE location_type (
			id INT(11) PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(50) NOT NULL
			)");

// Task 2b: add default location types
if($update->getNumberRows("location_type") == 0) {
	$query = "INSERT INTO location_type (name) 
		VALUES ('Probenräume'), ('Veranstaltungsorte'), ('Übernachtungsmöglichkeiten'), ('Studios'), ('Sonstige')";
	$update->executeQuery($query);
	$update->message("Create location type values in database.");
}

// Task 2c: add reference column to location
$update->addColumnToTable("location", "location_type", "INT(11)", "DEFAULT 1");

// Task 3: old fix
$update->addColumnToTable("concert", "title", "VARCHAR(150)");

// Task 4: add repository field for setting
$update->addColumnToTable("song", "setting", "VARCHAR(300)");

// Task 5a: Outfit module
$update->addModule("Outfits");

// Task 5b: Outfit table
$update->addTable("outfit", "CREATE TABLE outfit (
				id INT(11) PRIMARY KEY AUTO_INCREMENT,
				name VARCHAR(50) NOT NULL,
				description TEXT
				)");
$update->addColumnToTable("concert", "outfit", "int(11)");


/*
 * All updates from 3.1.0 onwards
 * ------------------------------
 */
// Task 2: Add Google API Key
$update->addDynConfigParam("google_api_key", "");
// Task 3: Add trigger Key
require_once $PATH_TO_SRC . "logic/defaultcontroller.php";
require_once $PATH_TO_SRC . "logic/modules/logincontroller.php";
$random_key = LoginController::generatePassword(12);
$update->addDynConfigParam("trigger_key", $random_key);
$update->addDynConfigParam("enable_trigger_service", "1");
// Task 4: Reminder Configuration
$update->addDynConfigParam("trigger_cycle_days", "3");
$update->addDynConfigParam("trigger_repeat_count", "3");
// Task 5: Associate Songs and Files
$update->addTable("song_files", "CREATE TABLE IF NOT EXISTS song_files (
	id INT(11) PRIMARY KEY AUTO_INCREMENT,
	song INT(11) NOT NULL,
	filepath VARCHAR(255) NOT NULL,
	notes TEXT
)");
// Task 6: Add nickname to contact
$update->addColumnToTable("contact", "nickname", "VARCHAR(20)");

// Task 7: manage primary keys of table program_song
$program_song_pkeys = $update->getPrimaryKeys("program_song");
if(count($program_song_pkeys) > 1) {
	// Task 1a: remove primary key from program_song (#217)
	$update->removePrimaryKey("program_song");
	// Task 1b: add ID as the primary key
	$update->addColumnToTable("program_song", "id", "int(11)", "PRIMARY KEY AUTO_INCREMENT");
}
else {
	$update->message("Primary keys in program_song ok.");
}

?>

<br/><br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>