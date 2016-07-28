<?php

/********************************
 * CREATES/UPDATES THE DATABASE *
 * @author Matti Maier          *
 ********************************/

?>

<html>
<head>
	<title>Database Update / Check</title>
</head>
<body>
<h3>Log</h3>

<?php 

// include necessary scripts
require_once("triggerdb.php");


class UpdateDb {
	
	/**
	 * Database Wrapper
	 * @var TriggerDB
	 */
	private $db;
	
	private $tabs; // existing tables
	
	function __construct() {
		$this->db = new TriggerDB();
		$this->loadTabs();
	}
	
	private function loadTabs() {
		try {
			$this->tabs = $this->db->getTables();
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	private function handleException($e) {
		echo "<strong>Exception! " . $e->getMessage() . "</strong><br/><pre>" . $e . "</pre>";
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
<p>
<?php 

// Create tables
$update->addTable("jobs", "CREATE TABLE IF NOT EXISTS jobs (
	id INT(11) PRIMARY KEY AUTO_INCREMENT,
	created DATETIME NOT NULL DEFAULT NOW(),
	trigger_on DATETIME NOT NULL,
	callback_data TEXT NOT NULL,
	callback_url VARCHAR(255) NOT NULL
)");

$update->addTable("instances", "CREATE TABLE IF NOT EXISTS instances (
	id INT(11) PRIMARY KEY AUTO_INCREMENT,
	hostname VARCHAR(255) NOT NULL,
	first_seen DATETIME NOT NULL,
	count INT NOT NULL DEFAULT 1
)");

$update->addTable("settings", "CREATE TABLE IF NOT EXISTS settings (
	setting VARCHAR(30) PRIMARY KEY,
	value VARCHAR(255) NOT NULL
)");

?>

<br/><br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>