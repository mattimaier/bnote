<?php
require ("data.php");
require ("xmldata.php");

/**
 * Global Database Connection
 */
class Database extends Data {
	
	/**
	 * Connection parameters
	 * @var array
	 */
	private $connectionData;
	
	/**
	 * MySQLi Connection
	 * @var mysqli
	 */
	private $db;
	
	private $userTable;
	
	/**
	 * Builds a new database connection and offers basic methods.
	 */
	function __construct() {
		// build mysql connection with login-data from the xmlfile
		$this->readConfig();
		$this->db = mysqli_connect( 
				$this->connectionData["server"], 
				$this->connectionData["user"], 
				$this->connectionData["password"], 
				$this->connectionData["dbname"],
				$this->connectionData["port"] );
		if ($this->db->connect_errno) {
			new BNoteError ( "Unable to connect to database: " . $this->db->connect_error );
		}
	}
	
	// reads the database config from config/database.xml
	private function readConfig() {
		// Different locations for login and system
		$cfgfile = "config/database.xml";
		if (file_exists ( $cfgfile )) {
			$config = new XmlData ( $cfgfile, "Database" );
		}
		else {
			$config = new XmlData ( "../../" . $cfgfile, "Database" );
		}
		$this->connectionData = array (
				"server" => $config->getParameter ( "Server" ),
				"user" => $config->getParameter ( "User" ),
				"password" => $config->getParameter ( "Password" ),
				"dbname" => $config->getParameter ( "Name" ) ,
				"port" => intval($config->getParameter ( "Port" ))
		);
		$this->userTable = $config->getParameter ( "UserTable" );
	}
	
	/**
	 * Executes a MySQLi query and handles the result at first.
	 * @param string $query
	 * @return mixed
	 */
	protected function exe($query) {
		$res = $this->db->query( $query );
		if (!$res) {
			require_once ($GLOBALS ['DIR_WIDGETS'] . "error.php");
			new BNoteError ( "The database query has failed:<br />" . $this->db->error . ".<br>Debug:" . $query );
		} else {
			return $res;
		}
	}
	
	/**
	 * Returns the value of a single cell.
	 * 
	 * @param String $table
	 *        	Table of the cell.
	 * @param String $col
	 *        	Column of the cell.
	 * @param String $where
	 *        	Where clause without the "WHERE".
	 */
	public function getCell($table, $col, $where) {
		$query = "SELECT $col FROM $table WHERE $where";
		$res = $this->exe ( $query );
		$row = mysqli_fetch_assoc ( $res );
		return $row [$col];
	}
	
	/**
	 * Returns an array with the data from the query.
	 * 
	 * @param String $query
	 *        	SQL query.
	 */
	public function getSelection($query) {
		// Execute Query
		$res = $this->exe( $query );
		
		$dataTable = array ();
		// add header
		$header = array ();
		
		for ($i = 0; $i < $this->db->field_count; $i++) {
			$meta = mysqli_fetch_field_direct( $res, $i );
			if (! $meta) {
				new BNoteError ( "Invalid table header." );
			}
			array_push ( $header, ucfirst ( $meta->name ) );
		}
		array_push ( $dataTable, $header );
		
		// add Data
		while ( $row = mysqli_fetch_array ( $res, MYSQLI_BOTH ) ) {
			array_push ( $dataTable, $row );
		}
		
		return $dataTable;
	}
	
	/**
	 * Returns an array of the form $id => name with the possible foreign keys
	 * 
	 * @param string $table
	 *        	The referenced table
	 * @param string $idcolumn
	 *        	The referenced id column
	 * @param string $namecolumn
	 *        	Name columns for reference
	 */
	public function getForeign($table, $idcolumn, $namecolumns) {
		if (gettype ( $namecolumns ) == "string") {
			$namecols = $namecolumns;
		} else {
			$namecols = join ( ",", $namecolumns );
		}
		$query = "SELECT $idcolumn, $namecols FROM $table";
		
		// remove administrators from the corresponding tables
		global $system_data;
		if ($table == "contact") {
			$suContacts = $system_data->getSuperUserContactIDs ();
			if (count ( $suContacts ) > 0) {
				$query .= " WHERE ";
				foreach ( $suContacts as $i => $cid ) {
					if ($i > 0)
						$query .= " AND ";
					$query .= "id != $cid";
				}
			}
		} else if ($table == "user") {
			$suUsers = $system_data->getSuperUsers ();
			if (count ( $suUsers ) > 0) {
				$query .= " WHERE ";
				foreach ( $suUsers as $i => $uid ) {
					if ($i > 0)
						$query .= " AND ";
					$query .= "id != $uid";
				}
			}
		}
		$query .= " ORDER BY $namecols";
		
		$dbSelection = $this->getSelection ( $query );
		$ret = array ();
		for($i = 1; $i < count ( $dbSelection ); $i ++) {
			$row = $dbSelection [$i];
			if (gettype ( $namecolumns ) == "string") {
				$nameVal = $row [$namecolumns];
			} else {
				$naming = array ();
				foreach ( $namecolumns as $col ) {
					array_push ( $naming, $row [$col] );
				}
				$nameVal = join ( " ", $naming );
			}
			$ret [$row [$idcolumn]] = $nameVal;
		}
		return $ret;
	}
	
	/**
	 * Returns just one row as an array.
	 * 
	 * @param
	 *        	String query SQL query.
	 */
	public function getRow($query) {
		$res = $this->exe( $query );
		return mysqli_fetch_assoc( $res );
	}
	
	/**
	 * Executes the given String as an SQL statement.
	 * 
	 * @param String $query
	 *        	Database SQL query to be executed.
	 * @return The ID if the query has been an insert statement
	 *         with an autoincrement generator. See PHP manual for details.
	 */
	public function execute($query) {
		$res = $this->exe($query);
		return $this->db->insert_id;
	}
	
	/**
	 * Returns the name of the user table.
	 */
	public function getUserTable() {
		return $this->userTable;
	}
	
	/**
	 * Returns the name of the database.
	 */
	public function getDatabaseName() {
		return $this->connectionData ["dbname"];
	}
	
	/**
	 * Returns the name of the fields in the given table.
	 * 
	 * @param String $table
	 *        	Name of the table.
	 */
	public function getFieldsOfTable($table) {
		$res = $this->exe( "SHOW COLUMNS FROM $table" );
		
		$fields = array();
		if (mysqli_num_rows( $res ) > 0) {
			while ( $row = mysqli_fetch_assoc( $res ) ) {
				array_push ( $fields, $row["Field"] );
			}
		} else {
			new BNoteError( "Empty $table table. Please check your database " . $this->getDatabaseName () . "!" );
		}
		return $fields;
	}
	
	/**
	 * Computes the total number of rows of a table.
	 * @param string $table Table name
	 * @return int Number of rows
	 */
	public function getNumberRows($table) {
		return $this->getCell($table, "count(*)", "true");
	}
	
	/**
	 * Takes a selection and makes a flat array with the contents of the given column.
	 * 
	 * @param array $selection
	 *        	Database Selection.
	 * @param string $col
	 *        	Column Name.
	 * @return Flat array containg only the contents of the column.
	 */
	public static function flattenSelection($selection, $col) {
		$flat = array ();
		for($i = 1; $i < count ( $selection ); $i ++) {
			array_push ( $flat, $selection [$i] [$col] );
		}
		return $flat;
	}

}

?>