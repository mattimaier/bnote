<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.5.x to 3.0.0 *
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
}

$update = new UpdateDb();

?>


<p><b>This script updates BNote's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

// Task 1: Insert Language Configuration
$update->addDynConfigParam("language", "de", 1);

// Migration of all passwords might be necessary -> ask for new default password!
echo "<span style=\"color: red; font-weight: bold;\">Please be aware that you might have to reset all passwords, except you upgrade from v2.5.5!</span>";
echo "<br/>";

// Task 2a: Insert new table account
$account_def = "CREATE TABLE account (
			id INT(11) PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL
)";

$update->addTable("account", $account_def);

// Task 2b: Insert new table booking
$booking_def = "CREATE TABLE booking (
		id INT(11) PRIMARY KEY AUTO_INCREMENT,
		account INT(11) NOT NULL,
		bdate DATE NOT NULL,
		subject VARCHAR(100) NOT NULL,
		amount_net DECIMAL(9,2) NOT NULL,
		amount_tax DECIMAL(9,2) NOT NULL DEFAULT 0,
		btype INT(1) NOT NULL,
		otype CHAR(1),
		oid INT(11),
		notes TEXT
)";
$update->addTable("booking", $booking_def);

// Task 2c: Insert new table recpay
$recpay_def = "CREATE TABLE recpay (
		id INT(11) PRIMARY KEY AUTO_INCREMENT,
		account INT(11) NOT NULL,
		subject VARCHAR(100) NOT NULL,
		amount_net DECIMAL(9,2) NOT NULL,
		amount_tax DECIMAL(9,2) NOT NULL DEFAULT 0,
		btype INT(1) NOT NULL,
		otype CHAR(1),
		oid INT(11),
		notes TEXT
)";
$update->addTable("recpay", $recpay_def);

// Task 2d: Add module finance
$update->addModule("Finance");

// Task 3a: Calendar
$calendar_mod_id = $update->addModule("Calendar");

// Task 3b: Insert privileges for calendar module for everybody
$update->addPrivilegeForAllUsers($calendar_mod_id);

// Task 4: add birthday to users
$update->addColumnToTable("contact", "birthday", "DATE");

// Task 5: Equipment module
$update->addTable("equipment", "CREATE TABLE equipment (
	id INT(11) PRIMARY KEY AUTO_INCREMENT,
	model VARCHAR(100) NOT NULL,
	make VARCHAR(100) NOT NULL,
	name VARCHAR(100),
	purchase_price DECIMAL(9,2),
	current_value DECIMAL(9,2),
	quantity INT(10) NOT NULL DEFAULT 1,
	notes TEXT
)");
$update->addModule("Equipment");

// Task 6a: add module Tour
$update->addModule("Tour");

// Task 6b: tour main table
$update->addTable("tour", "CREATE TABLE tour (
	id INT(11) PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(100) NOT NULL,
	start DATE NOT NULL,
	end DATE NOT NULL,
	notes
)");


?>
<br/><br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>