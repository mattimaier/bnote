<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.2.0			 *
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
require_once $PATH_TO_SRC . "data/systemdata.php";
require_once $PATH_TO_SRC . "presentation/widgets/error.php";

// build DB connection
$sysdata = new Systemdata();
$db = $sysdata->dbcon;
$regex = $sysdata->regex;
?>


<p><b>This script updates the bluenote system's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

/*
 * TASK 1: Add column PIN to user table.
 */
// get a list of fields
$fields = $db->getFieldsOfTable($db->getUserTable());

// add fields only when they do not exist
if(!in_array("pin", $fields)) {
	$query = "ALTER TABLE " . $db->getUserTable() . " ADD COLUMN pin int(6) AFTER contact";
	$db->execute($query);
	echo "<i>column " . $db->getUserTable() . ".pin added</i><br>";
}
else {
	echo "<i>column " . $db->getUserTable() . ".pin already exists.</i><br>";
}

/*
 * TASK 2: Add new module "Abstimmung" to the modules table.
 */
// get modules
$mods = $sysdata->getModuleArray();
if(!in_array("Abstimmung", $mods)) {
	// add new module
	$query = "INSERT INTO module (name) VALUES (\"Abstimmung\")";
	$newModId = $db->execute($query);
	echo "<i>Module " . $newModId . " Abstimmung added.</i><br/>";
	
	// add privileges for all users
	$query = "SELECT id FROM " . $db->getUserTable();
	$users = $db->getSelection($query);
	
	$query = "INSERT INTO privilege (user, module) VALUES ";
	for($i = 1; $i < count($users); $i++) {
		if($i > 1) $query .= ",";
		$query .= "(" . $users[$i]["id"] . ", " . $newModId . ")";
	}
	$db->execute($query);
	echo "<i>Privileges for module " . $newModId . " Abstimmung added for <b>all</b> users.</i><br/>";
}
else {
	echo "<i>Module Abstimmung already exists.</i><br/>";
}

/*
 * TASK 3: Create tables for "Abstimmung" module.
 */
$tabs = $db->getSelection("SHOW TABLES");
$tables = array();
for($i = 1; $i < count($tabs); $i++) {
	array_push($tables, $tabs[$i][0]);
}

// check whether table "vote" exists, if not create it
if(!in_array("vote", $tables)) {
	// add table
	$query = "CREATE TABLE vote (";
	$query .= " id int(11) PRIMARY KEY AUTO_INCREMENT, ";
	$query .= " name varchar(100) NOT NULL, ";
	$query .= " author int(11) NOT NULL, ";
	$query .= " end datetime NOT NULL, ";
	$query .= " is_multi int(1) NOT NULL, ";
	$query .= " is_date int(1) NOT NULL, ";
	$query .= " is_finished int(1) NOT NULL ";
	$query .= ")";
 	$db->execute($query);
	echo "<i>Table vote created.</i><br/>";
}
else {
	echo "<i>Table vote already exists.</i><br/>";
}

// check whether table "vote_group" exists, if not create it
if(!in_array("vote_group", $tables)) {
	// add table
	$query = "CREATE TABLE vote_group (";
	$query .= " vote int(11) NOT NULL, ";
	$query .= " user int(11) NOT NULL,";
	$query .= "PRIMARY KEY (vote, user) ";
	$query .= ")";
	$db->execute($query);
	echo "<i>Table vote_group created.</i><br/>";
}
else {
	echo "<i>Table vote_group already exists.</i><br/>";
}

// check whether table "vote_option" exists, if not create it
if(!in_array("vote_option", $tables)) {
	// add table
	$query = "CREATE TABLE vote_option (";
	$query .= " id int(11) PRIMARY KEY AUTO_INCREMENT, ";
	$query .= " vote int(11) NOT NULL, ";
	$query .= " name varchar(100), ";
	$query .= " odate datetime ";
	$query .= ")";
	$db->execute($query);
	echo "<i>Table vote_option created.</i><br/>";
}
else {
	echo "<i>Table vote_option already exists.</i><br/>";
}

// check whether table "vote_option_user" exists, if not create it
if(!in_array("vote_option_user", $tables)) {
	// add table
	$query = "CREATE TABLE vote_option_user (";
	$query .= " vote_option int(11) NOT NULL, ";
	$query .= " user int(11) NOT NULL, ";
	$query .= "PRIMARY KEY (vote_option, user) ";
	$query .= ")";
	$db->execute($query);
	echo "<i>Table vote_option_user created.</i><br/>";
}
else {
	echo "<i>Table vote_option_user already exists.</i><br/>";
}

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>