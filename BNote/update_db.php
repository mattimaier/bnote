<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.4.0			 *
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


<p><b>This script updates BNote's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

/*
 * TASK 1: Add new modules to the modules table.
 */
$newMods = array("Nachrichten", "Aufgaben", "Konfiguration", "Probenphasen");
		
// get modules 
$mods = $sysdata->getInnerModuleArray();

if(!in_array("Nachrichten", $mods)) {
	// add new module
	$query = 'INSERT INTO module (name) VALUES ';
	foreach($newMods as $i => $newMod) {
		if($i > 0) $query .= ",";
		$query .= '("' . $newMod . '")';
	}
	$db->execute($query);
	
	// fetch module IDs
	$query = "SELECT id,name FROM module";
	$allMods = $db->getSelection($query);
	$newModIds = array();
	foreach($allMods as $i => $row) {
		if($i == 0) continue;
		if(in_array($row["name"], $newMods)) {
			$newModIds[$row["id"]] = $row["name"];
			echo "<i>New module</i> " . $row["name"] . " (" . $row["id"] . ") <i>added.</i><br/>";
		}
	}
	
	// add privileges for super user
	$users = $sysdata->getSuperUsers();

	$query = "INSERT INTO privilege (user, module) VALUES ";
	for($i = 0; $i < count($users); $i++) {
		if($i > 1) $query .= ",";
		$j = 0;
		foreach($newModIds as $id => $name) {
			if($j > 0) $query .= ",";
			$query .= "(" . $users[$i] . ", " . $id . ")";
			$j++;
		}
	}
	if(count($users) > 0) {
		$db->execute($query);
		echo "<i>Privileges for modules added for all super users.</i><br/>";
	}
	else {
		echo "<i>Please add privileges yourself, since no super users are configured.</i><br/>";
	}
	
}
else {
	echo "<i>New Modules already exists.</i><br/>";
}

/*
 * TASK 2: Create table for "Konfiguration" module.
 */
$tabs = $db->getSelection("SHOW TABLES");
$tables = array();
for($i = 1; $i < count($tabs); $i++) {
	array_push($tables, $tabs[$i][0]);
}

// check whether table "configuration" exists, if not create it
if(!in_array("configuration", $tables)) {
	// add table
	$query = "CREATE TABLE configuration (";
	$query .= " param varchar(100) PRIMARY KEY, ";
	$query .= " value text NOT NULL, ";
	$query .= " is_active int(1) NOT NULL ";
	$query .= ")";
 	$db->execute($query);
 	
 	// insert initial configuration parameters
 	$query = "INSERT INTO configuration (param, value, is_activ) VALUES ";
 	$query .= "(rehearsal_start, \"18:00\", 1), ";
 	$query .= "(rehearsal_duration, \"90\", 1)";
 	$db->execute($query);
 	
	echo "<i>Table configuration with initial parameters created.</i><br/>";
}
else {
	echo "<i>Table configuration already exists.</i><br/>";
}

/*
 * TASK 3: Create table for "Aufgaben" module.
 */
// check whether table "configuration" exists, if not create it
if(!in_array("task", $tables)) {
	// add table
	$query = "CREATE TABLE task (";
	$query .= " id int(11) PRIMARY KEY AUTO_INCREMENT, ";
	$query .= " title varchar(50) NOT NULL, ";
	$query .= " description text, ";
	$query .= " created_at datetime NOT NULL,";
	$query .= " created_by int(11) NOT NULL,";
	$query .= " due_at datetime,";
	$query .= " assigned_to int(11),";
	$query .= " is_complete int(1) NOT NULL, ";
	$query .= " completed_at datetime ";
	$query .= ")";
	$db->execute($query);

	echo "<i>Table task created.</i><br/>";
}
else {
	echo "<i>Table configuration already exists.</i><br/>";
}

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>