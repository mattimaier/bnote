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
		
		}
		else {
			$this->message("Module $modname already exists.");
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
}

$update = new UpdateDb();

?>


<p><b>This script updates BNote's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

// Task 1: Insert Language Configuration
$update->addDynConfigParam("language", "de", 1);

//FIXME: Migration of all passwords might be necessary -> ask for new default password!

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

?>
<br/><br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>