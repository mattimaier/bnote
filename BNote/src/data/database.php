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
		
		if($this->db->connect_errno) {
			new BNoteError ( "Unable to connect to database: " . $this->db->connect_error );
		}
		
		if(array_key_exists("encoding", $this->connectionData)) {
			mysqli_set_charset($this->db, $this->connectionData["encoding"]);
		}
	}
	
	// reads the database config from config/database.xml
	private function readConfig() {
		// Different locations for login and system
		$cfgfile = "config/database.xml";
		if(file_exists($cfgfile)) {
			$config = new XmlData($cfgfile, "Database");
		}
		else {
			$config = new XmlData("../../" . $cfgfile, "Database");
		}
		$this->connectionData = array (
				"server" => $config->getParameter("Server"),
				"user" => $config->getParameter("User"),
				"password" => $config->getParameter("Password"),
				"dbname" => $config->getParameter("Name"),
				"port" => intval($config->getParameter("Port"))
		);
		$encoding = $config->getParameter("Encoding");
		if($encoding != null) {
			$this->connectionData["encoding"] = $encoding;
		}
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
			$this->mysql_error_display($query);
		} else {
			return $res;
		}
	}
	
	private function mysql_error_display($query) {
		require_once ($GLOBALS['DIR_WIDGETS'] . "error.php");
		new BNoteError("The database query has failed:<br />" . $this->db->error . ".<br>Debug:" . $query);
	}
	
	/**
	 * Manipulates a row in the database with the prepared statement.
	 * @param String $query Prepared statement to execute. Use "?" as a placeholder.
	 * @param Array $params Parameter array in the form i => array(type, value).
	 * @return integer Optional insert ID in case it was an insert.
	 */
	public function prepStatement($query, $params) {
		$typeDefs = "";
		$paramValues = array();
		
		foreach($params as $i => $val_def) {
			$typeDefs .= $val_def[0];
			array_push($paramValues, $val_def[1]);
		}
		$res = $this->mysqli_prepared_query($query, $typeDefs, $paramValues);
		if($res === FALSE) {
			$this->mysql_error_display($query);
		}
		return $this->db->insert_id;
	}
	
	/**
	 * Get selection from database with a prepared statement.
	 * @param String $query Prepared query.
	 * @param Array $params Parameter array in the form i => array(type, value).
	 * @return Array Associated result from mysqli.
	 */
	public function preparedQuery($query, $params) {
		return $this->preparedQueryRaw($query, $params)->fetch_all(MYSQLI_ASSOC);
	}
	
	protected function preparedQueryRaw($query, $params) {
		$stmt = $this->db->prepare($query);
		$bindTypes = "";
		$bindValues = array();
		foreach($params as $i => $param) {
			$bindTypes .= $param[0];
			array_push($bindValues, $param[1]);
		}
		if(count($bindValues) > 0) {
			$stmt->bind_param($bindTypes, ...$bindValues);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		return $result;
	}
	
	/**
	 * Helper function from http://php.net/manual/de/mysqli.prepare.php
	 * @param string $sql Prepared SQL statement.
	 * @param string $typeDef Optional string-concatenated type definition.
	 * @param string $params Parameter value array.
	 * @return Array result. 
	 */
	function mysqli_prepared_query($sql, $typeDef = FALSE, $params = FALSE){
		if($stmt = $this->db->prepare($sql)){
			if(count($params) == count($params,1)){
				$params = array($params);
				$multiQuery = FALSE;
			} else {
				$multiQuery = TRUE;
			}
	
			if($typeDef){
				$bindParams = array();
				$bindParamsReferences = array();
				$bindParams = array_pad($bindParams,(count($params,1)-count($params))/count($params),"");
				foreach($bindParams as $key => $value){
					$bindParamsReferences[$key] = &$bindParams[$key];
				}
				array_unshift($bindParamsReferences,$typeDef);
				$bindParamsMethod = new ReflectionMethod('mysqli_stmt', 'bind_param');
				$bindParamsMethod->invokeArgs($stmt,$bindParamsReferences);
			}
	
			$result = array();
			foreach($params as $queryKey => $query){
				foreach($bindParams as $paramKey => $value){
					$bindParams[$paramKey] = $query[$paramKey];
				}
				$queryResult = array();
				if(mysqli_stmt_execute($stmt)){
					$resultMetaData = mysqli_stmt_result_metadata($stmt);
					if($resultMetaData){
						$stmtRow = array();
						$rowReferences = array();
						while ($field = mysqli_fetch_field($resultMetaData)) {
							$rowReferences[] = &$stmtRow[$field->name];
						}
						mysqli_free_result($resultMetaData);
						$bindResultMethod = new ReflectionMethod('mysqli_stmt', 'bind_result');
						$bindResultMethod->invokeArgs($stmt, $rowReferences);
						while(mysqli_stmt_fetch($stmt)){
							$row = array();
							foreach($stmtRow as $key => $value){
								$row[$key] = $value;
							}
							$queryResult[] = $row;
						}
						mysqli_stmt_free_result($stmt);
					} else {
						$queryResult[] = mysqli_stmt_affected_rows($stmt);
					}
				} else {
					$queryResult[] = FALSE;
				}
				$result[$queryKey] = $queryResult;
			}
			mysqli_stmt_close($stmt);
		} else {
			$result = FALSE;
		}
	
		if($multiQuery){
			return $result;
		} else {
			return $result[0];
		}
	}
	
	/**
	 * Get a single value from a table.
	 * @param String $query Prepared statement. 
	 * @param $col Column to fetch
	 * @param Array $params Parameter array in the form i => array(type, value).
	 * @return NULL|value
	 */
	public function colValue($query, $col, $params) {
		$res = $this->preparedQuery($query, $params);
		return $res && count($res) > 0 ? $res[0][$col] : NULL;
	}
	
	/**
	 * Returns an array with the data from the query.
	 * 
	 * @param String $preparedStatement Prepared statement (SQL) with "?" placeholders
	 * @param Array $params Parameter array in the form i => array(type, value).
	 * @return NULL|Array Data table
	 */
	public function getSelection($preparedStatement, $params=array()) {
		// Execute Query
		$res = $this->preparedQueryRaw($preparedStatement, $params);
		$dataTable = array();
		
		// add header
		$header = array();
		
		for($i = 0; $i<$this->db->field_count; $i ++) {
			$meta = mysqli_fetch_field_direct($res, $i);
			if (! $meta) {
				new BNoteError("Invalid table header.");
			}
			array_push($header, ucfirst($meta->name));
		}
		array_push($dataTable, $header);
		
		// add Data
		foreach($res as $i => $row) {
			array_push($dataTable, $row);
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
	 * @param string|Array $namecolumns
	 *        	Either string (1 name column) or array (multiple name columns)
	 */
	public function getForeign($table, $idcolumn, $namecolumns) {
		// Validating input to contain only db-permitted (safe) content
		global $system_data;
		$system_data->regex->isDbItem($table, "table name");
		$system_data->regex->isDbItem($idcolumn, "id column");
		if (gettype($namecolumns)=="string") {
			$namecols = $namecolumns;
			$system_data->regex->isDbItem($namecols);
		} else {
			foreach($namecolumns as $i => $nc) {
				$system_data->regex->isDbItem($nc);
			}
			$namecols = join(",", $namecolumns);
		}
		$query = "SELECT $idcolumn, $namecols FROM $table";
		
		// remove administrators from the corresponding tables
		$params = array();
		if ($table=="contact") {
			$suContacts = $system_data->getSuperUserContactIDs();
			if (count($suContacts)>0) {
				$suCids = array();
				foreach ( $suContacts as $i => $cid ) {
					array_push($suCids, "id <> ?");
					array_push($params, array("i", $cid));
				}
				$query .= " WHERE " . join(" AND ", $suCids);
			}
		} else if ($table=="user") {
			$suUsers = $system_data->getSuperUsers();
			if (count($suUsers)>0) {
				$suUids = array();
				foreach ( $suUsers as $i => $uid ) {
					array_push("id <> ?");
					array_push($params, array("i", $uid));
				}
				$query .= " WHERE " . join(" AND ", $suUids);
			}
		}
		$query .= " ORDER BY $namecols";
		
		// call db
		$dbSelection = $this->getSelection($query, $params);
		
		// process results
		$ret = array();
		for($i = 1; $i<count($dbSelection); $i ++) {
			$row = $dbSelection[$i];
			if (gettype($namecolumns)=="string") {
				$nameVal = $row[$namecolumns];
			} else {
				$naming = array();
				foreach ( $namecolumns as $col ) {
					array_push($naming, $row[$col]);
				}
				$nameVal = join(" ", $naming);
			}
			$ret[$row[$idcolumn]] = $nameVal;
		}
		return $ret;
	}
	
	/**
	 * Returns one row as an array.
	 * @param String $preparedStmt Prepared statement to select the row.
	 * @param Array $params Parameter array in the form i => array(type, value).
	 */
	public function fetchRow($preparedStmt, $params) {
		$res = $this->preparedQuery($preparedStmt, $params);
		return $res && count($res) > 0 ? $res[0] : NULL;
	}
	
	/**
	 * Executes the given String as an SQL statement.
	 * 
	 * @param String $query
	 *        	Database SQL query to be executed.
	 * @return The ID if the query has been an insert statement
	 *         with an autoincrement generator. See PHP manual for details.
	 * @deprecated
	 */
	public function execute($query) {
		//FIXME: typically unsafe statement use (141 uses)
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
		return $this->connectionData["dbname"];
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
			new BNoteError("Empty $table table. Please check your database " . $this->getDatabaseName () . "!");
		}
		return $fields;
	}
	
	/**
	 * Computes the total number of rows of a table.
	 * @param string $table Table name
	 * @return int Number of rows
	 */
	public function getNumberRows($table) {
		return $this->colValue("SELECT count(*) as cnt FROM $table", "cnt", array());
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
		if(is_numeric($col)) {
			// find the name of the col in the header
			$colName = $selection[0][$col];
		}
		else {
			$colName = $col;
		}
		$flat = array();
		for($r=1; $r<count($selection); $r++) {
			array_push($flat, $selection[$r][$colName] );
		}
		return $flat;
	}

}

?>