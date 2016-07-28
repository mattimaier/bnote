<?php

class TriggerDB {
	
	const CONFIG_FILE = "db.xml";
	const DATETIME_FORMAT_DB = "Y-m-d H:i:s";
	
	protected $config = null;
	
	/**
	 * MySQLi Connection
	 * @var mysqli
	 */
	protected $db = null;
	
	function __construct() {
		$this->readConfig();
		$this->connect();
	}
	
	private function readConfig() {
		if($this->config == null) {
			$this->config = simplexml_load_file(TriggerDB::CONFIG_FILE);
		}
	}
	
	private function connect() {
		if($this->db == null) {
			$this->db = new mysqli($this->config->host, 
					$this->config->username, $this->config->password, 
					$this->config->dbname,
					intval($this->config->port));
			if($this->db->connect_errno) {
				throw new Exception("Unable to connect: " . $this->db->connect_error);
			}
		}
	}
	
	public function getTables() {
		$res = $this->db->query("SHOW TABLES");
		if(!$res) {
			throw new Exception("Unable to read tables from database: " . $this->db->error);
		}
		return $res->fetch_assoc();
	}
	
	public function getFieldsOfTable($table) {
		$stmt = $this->db->prepare("SHOW COLUMNS FROM ?");
		$stmt->bind_param("s", $table);
		if(!$stmt->execute()) {
			throw new Exception("Failed to execute: " . $this->db->error);
		}
		$stmt->bind_result($columnName);
		$cols = array();
		while($stmt->fetch()) {
			array_push($cols, $columnName);
		}
		$stmt->close();
		return $cols;
	}
	
	public function execute($query) {
		$res = $this->db->real_query($query);
		if(!$res) {
			throw new Exception("Failed to execute: " . $this->db->error);
		}
		return true;
	}
	
	public function getCell($table, $col, $where) {
		/*
		 * Comment: althought the use of prepared statement seems appropriate, 
		 * they suck balls. The mysqli interface is very poor.
		 */
		$query = "SELECT $col FROM $table WHERE $where";
		$res = $this->db->query($query);
		if(!$res) {
			throw new Exception("Failed to retrieve result: " . $this->db->error);
		}
		$row = $res->fetch_row();
		return $row[0];
	}
	
	public function disconnect() {
		$this->db->close();
	}

}

?>