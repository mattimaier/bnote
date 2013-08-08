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

/**************************************************************************************************************************************/
//TODO: CONTINUE HERE...
/**************************************************************************************************************************************/
exit(0); // remove this to continue!

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