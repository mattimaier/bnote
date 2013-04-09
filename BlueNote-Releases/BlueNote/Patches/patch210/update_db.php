<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Patch 2.1			 *
 *************************/

// path to src/ folder
$PATH_TO_SRC = "src/";

?>

<html>
<head>
	<title>Database Update</title>
</head>
<body>

<?php 

// include necessary libs
require_once $PATH_TO_SRC . "data/database.php";
require_once $PATH_TO_SRC . "presentation/widgets/error.php";

// build DB connection
$db = new Database();

?>


<p><b>This script updates the bluenote system's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

/*
 * TASK 1: Add columns "mobile" and "business" to table "contact" 
 */
// get a list of fields
$fields = $db->getFieldsOfTable("contact");

// add fields only when they do not exist
if(!in_array("mobile", $fields)) {
	$query = "ALTER TABLE contact ADD COLUMN mobile varchar(30) AFTER fax";
	$db->execute($query);
	echo "<i>column contact.mobile added</i><br>";
}
else {
	echo "<i>column contact.mobile already exists.</i><br>";
}

if(!in_array("business", $fields)) {
	$query = "ALTER TABLE contact ADD COLUMN business varchar(30) AFTER mobile";
	$db->execute($query);
	echo "<i>column contact.business added</i><br>";
}
else {
	echo "<i>column contact.business already exists.</i><br>";
}

/*
 * TASK 2: Add row to "module" table with new contact module for users
 */
$mod_exists = $db->getCell("module", "count(*)", "name = 'Mitspieler'");
$mod_id = -1;
if($mod_exists > 0) {
	echo "<i>Module 'Mitspieler' already exists in database.</i><br>";
}
else {
	$query = "INSERT INTO module (name) VALUES ('Mitspieler')";
	$mod_id = $db->execute($query);
	echo "<i>Module 'Mitspieler' added.</i><br>";
}

/*
 * TASK 3: Add privileges for all users for the new contact module
 */
if($mod_id > 0) {
	$query = "SELECT id FROM " . $db->getUserTable();
	$users = $db->getSelection($query);
	
	for($i = 1; $i < count($users); $i++) {
		$query = "INSERT INTO privilege (user, module) VALUES (" . $users[$i]["id"] . ", $mod_id)";
		$db->execute($query);
	}
	echo "<i>Privileges to the new module 'Mitspieler' added to <u>all</u> users.</i><br>";
}

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>