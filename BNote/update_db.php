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
		$colName = $tabs[0][0];
		$tables = array();
		for($i = 1; $i < count($tabs); $i++) {
			array_push($tables, $tabs[$i][$colName]);
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
		foreach($confParams as $row) {
			if(isset($row["param"]) && $row["param"] == $param) {
				$containsParam = true;
				break;
			}
		}
		if(!$containsParam) {
			$query = "INSERT INTO configuration (param, value, is_active) VALUES (?, ?, ?)";
			$this->db->execute($query, array(array("s", $param), array("s", $default), array("i", $active)));
			$this->message("Added configuration parameter $param.");
		}
		else {
			$this->message("<i>Configuration parameter $param exists.");
		}
	}
	
	function addModule($modname, $icon, $category) {
		$moduleNames = Database::flattenSelection($this->mods, "name");
		if(!in_array($modname, $moduleNames)) {
			// add new module
			$query = 'INSERT INTO module (name, icon, category) VALUES (?, ?, ?)';
			$modId = $this->db->prepStatement($query, array(array("s", $modname), array("s", $icon), array("s", $category)));
		
			$this->message("New module $modname (ID $modId) added.");
		
			// add privileges for super user
			$users = $this->sysdata->getSuperUsers();
		
			$query = "INSERT INTO privilege (user, module) VALUES ";
			$params = array();
			$tuples = array();
			for($i = 0; $i < count($users); $i++) {
				array_push($tuples, "(?, ?)");
				array_push($params, array("i", $users[$i]));
				array_push($params, array("i", $modId));
			}
			if(count($users) > 0) {
				$this->db->execute($query . join(", ", $tuples), $params);
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
	
	function getModuleIds($modnames) {
		$q = array();
		$params = array();
		foreach($modnames as $name) {
			array_push($q, "?");
			array_push($params, array("s", $name));
		}
		$qstr = "SELECT id, name FROM module WHERE name = " . join(" OR name = ", $q);
		$mods = $this->db->getSelection($qstr, $params);
		$res = array();
		for($i = 1; $i < count($mods); $i++) {
			$res[$mods[$i]["name"]] = $mods[$i]["id"];
		}
		return $res;
	}
	
	function updateModule($id, $name, $icon, $category) {
		$q = "UPDATE module SET name = ?, icon = ?, category = ? WHERE id = ?";
		$this->db->execute($q, array(array("s", $name), array("s", $icon), array("s", $category), array("i", $id)));
		$this->message("Module $id ($name) updated.");
	}
	
	function removeModule($id) {
		$this->db->execute("DELETE FROM module WHERE id = ?", array(array("i", $id)));
		$this->message("Module $id removed from database.");
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
		$this->db->execute("DELETE FROM privilege WHERE module = ?", array(array("i", $module_id)));
		
		// insert privilege for all
		$users_db = $this->db->getSelection("SELECT id FROM user");
		$params = array();
		$tuples = array();
		for($i = 1; $i < count($users_db); $i++) {
			$uid = $users_db[$i]["id"];
			array_push($tuples, "(?, ?)");
			array_push($params, array("i", $uid));
			array_push($params, array("i", $module_id));
		}
		$query = "INSERT INTO privilege (user, module) VALUES " . join(", ", $tuples);
		$this->db->execute($query, $params);
		$this->message($this->sysdata->getModuleTitle($module_id) . " privileges for all users added.");
	}
	
	function addPrivilegeForAdmins($module_id) {
		if($module_id <= 0) {
			$this->message("Cannot insert privileges. Invalid module ID.");
			return;
		}
		
		// remove all privileges for this module first
		$adminQuery = "SELECT u.id FROM user u JOIN contact_group cg ON cg.contact = u.contact WHERE cg.group = 1";  // 1 = Admins
		$delQuery = "DELETE FROM privilege WHERE module = ? AND user IN ($adminQuery)";
		$this->db->execute($delQuery, array(array("i", $module_id)));
		
		// insert privilege for all
		$adminUserIds = $this->db->getSelection($adminQuery);
		$params = array();
		$tuples = array();
		for($i = 1; $i < count($adminUserIds); $i++) {
			$uid = $adminUserIds[$i]["id"];
			array_push($tuples, "(?, ?)");
			array_push($params, array("i", $uid));
			array_push($params, array("i", $module_id));
		}
		$query = "INSERT INTO privilege (user, module) VALUES " . join(", ", $tuples);
		if(count($tuples) > 0) {
			$this->db->execute($query, $params);
			$this->message("Privileges for module $module_id added to all admins (group 1).");
		}
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
	
	function getNumberRows($table) {
		return $this->db->getNumberRows($table);
	}
	
	function executeQuery($query, $params=array()) {
		return $this->db->execute($query, $params);
	}
	
	function getSelection($query, $params=array()) {
		return $this->db->getSelection($query, $params);
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

// --- 4.0.1 UPDATES ---
// Task: Add admin privileges for admin users
$modinfo = $update->getModuleIds(array("Admin"));
$update->addPrivilegeForAdmins($modinfo["Admin"]);

// --- 4.0.2 UPDATES ---
// Task: Add more instruments
$instruments = array(array("Banjo", 8), array('Erster Tenor', 5), array('Zweiter Tenor', 5), array('Erster Bass', 5), array('Zweiter Bass', 5));
foreach($instruments as $inst) {
	$dbInst = $update->getSelection("SELECT id, name FROM instrument WHERE name = ?", array(array("s", $inst[0])));
	if(count($dbInst) < 2) {
		$addInstrumentsSql = "INSERT INTO instrument (`name`, `category`) VALUES (?, ?)";
		$update->executeQuery($addInstrumentsSql, array(array("s", $inst[0]), array("i", $inst[1])));
		$update->message("Created instrument '" . $inst[0] . "'.");
	}
}
// Task: Add website module again
$modId = $update->addModule('Website', 'globe', 'main');
$update->addPrivilegeForAdmins($modId);

// Task: Config parameter to active Log for failed login attempts
$update->addDynConfigParam("enable_failed_login_log", "0");

?>

<div style="font-weight: bold; font-style: italic;">COMPLETE.</div>

</body>
</html>