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
 	
 	// get automatic user activation status from configuration
 	$userActivation = strtolower($sysdata->getSystemConfigParameter("ManualUserActivation"));
 	$autoActiv = 0;
 	if($userActivation == "false") {
 		$autoActiv = 1;
 	}
 	
 	// get category filter from configuration
 	$catFilter = $sysdata->getSystemConfigParameter("InstrumentCategoryFilter");
 	
 	// insert initial configuration parameters
 	$query = "INSERT INTO configuration (param, value, is_activ) VALUES ";
 	$query .= "(\"rehearsal_start\", \"18:00\", 1), ";
 	$query .= "(\"rehearsal_duration\", \"90\", 1), ";
 	$query .= "(\"default_contact_group\", \"2\", 1), "; // members
 	$query .= "(\"auto_activation\", \"$autoActiv\", 1), "; // converted from xml configuration
 	$query .= "(\"instrument_category_filter\", \"$catFilter\", 1)";  // converted from xml configuration
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
	echo "<i>Table task already exists.</i><br/>";
}


/*
 * TASK 4.1: Create table "group" and add default groups.
 */
if(!in_array("group", $tables)) {
	$query = "CREATE TABLE `group` (";
	$query .= " `id` int(11) PRIMARY KEY AUTO_INCREMENT, ";
	$query .= " `name` varchar(50) NOT NULL, ";
	$query .= " `is_active` int(1) NOT NULL DEFAULT 1 ";
	$query .= ")";
	$db->execute($query);
	
	echo "<i>Table group created.</i><br/>";
	
	// add default groups
	$query = "INSERT INTO `group` (id,name) VALUES ";
	$query .= "(1, 'Administratoren'), ";
	$query .= "(2, 'Mitspieler'), ";
	$query .= "(3, 'Externe Mitspieler'), ";
	$query .= "(4, 'Bewerber'), ";
	$query .= "(5, 'Sonstige')";
	$db->execute($query);
	
	echo "<i>Five initial groups added.</i></br>";
}
else {
	echo "<i>Table group already exists.</i><br/>";
}

/*
 * TASK 4.2: Add table contact_group.
 */
if(!in_array("contact_group", $tables)) {
	$query = "CREATE TABLE `contact_group` (";
	$query .= " `contact` int(11),";
	$query .= " `group` varchar(50),";
	$query .= " PRIMARY KEY (`contact`, `group`) ";
	$query .= ")";
	$db->execute($query);
	
	echo "<i>Table contact_group created.</i><br/>";
}
else {
	echo "<i>Table contact_group already exists.</i><br/>";
}

/*
 * TASK 4.3: Set contact.status to NULL and convert contact.status to a contact_group entry. 
 */
$contacts = $db->getSelection("SELECT id, status FROM contact");
echo "<i>Updating contacts...</i><br>\n";
for($i = 1; $i < count($contacts); $i++) {
	$cid = $contacts[$i]["id"];
	$status = $contacts[$i]["status"];
	if($status == "") continue;
	else {
		if($status == "ADMIN") $grp = 1;
		else if($status == "EXTERNAL") $grp = 3;
		else if($status == "APPLICANT") $grp = 4;
		else if($status == "OTHER") $grp = 5;
		else $grp = 2;
		
		$query = "UPDATE contact SET status = NULL WHERE id = $cid";
		$db->execute($query);
		
		$query = "INSERT INTO contact_group (`contact`, `group`) VALUES ";
		$query .= "($cid, $grp)";
		$db->execute($query);
		
		echo "&nbsp;&nbsp;&nbsp;<i>contact $cid updated to group $grp.</i><br>\n";		
	}
}

/*
 * TASK 5: Insert important instruments.
 */
$instruments = array(
	"Querfl&ouml;te" => "3",
	"Blockfl&ouml;te" => "3",
	"Panfl&ouml;te" => "3",
	"Akkordeon" => "8",
	"Althorn" => "2"
);
$added = 0;
foreach($instruments as $name => $category) {
	// check existance
	$ct = $db->getCell("instrument", "count(*)", "name = '$name'");
	if($ct < 1) {
		$query = "INSERT INTO instrument (name, category) VALUES ('$name', $category)";
		$db->execute($query);
		$added++;
	}
}
echo "<i>Added $added instruments.</i><br/>\n";


/*
 * TASK 6: Add rehearsal phase database structure.
 */
if(!in_array("rehearsalphase", $tables)) {
	$query = "CREATE TABLE rehearsalphase ( ";
	$query .= "id int(11) PRIMARY KEY AUTO_INCREMENT, ";
	$query .= "name varchar(100) NOT NULL, ";
	$query .= "begin date NOT NULL, ";
	$query .= "end date NOT NULL, ";
	$query .= "notes text )";
	$db->execute($query);
	echo "<i>Table rehearsalphase created.</i><br/>";
}
else {
	echo "<i>Table rehearsalphase already exists.</i><br/>";
}

if(!in_array("rehearsalphase_rehearsal", $tables)) {
	$query = "CREATE TABLE rehearsalphase_rehearsal ( ";
	$query .= "rehearsalphase int(11) NOT NULL, ";
	$query .= "rehearsal int(11) NOT NULL, ";
	$query .= "PRIMARY KEY (rehearsalphase, rehearsal) )";
	$db->execute($query);	
	echo "<i>Table rehearsalphase_rehearsal created.</i><br/>";
}
else {
	echo "<i>Table rehearsalphase_rehearsal already exists.</i><br/>";
}

if(!in_array("rehearsalphase_concert", $tables)) {
	$query = "CREATE TABLE rehearsalphase_concert ( ";
	$query .= "rehearsalphase int(11) NOT NULL, ";
	$query .= "concert int(11) NOT NULL, ";
	$query .= "PRIMARY KEY (rehearsalphase, concert) )";
	$db->execute($query);	
	echo "<i>Table rehearsalphase_concert created.</i><br/>";
}
else {
	echo "<i>Table rehearsalphase_concert already exists.</i><br/>";
}

if(!in_array("rehearsalphase_contact", $tables)) {
	$query = "CREATE TABLE rehearsalphase_contact ( ";
	$query .= "rehearsalphase int(11) NOT NULL, ";
	$query .= "contact int(11) NOT NULL, ";
	$query .= "PRIMARY KEY (rehearsalphase, contact) )";
	$db->execute($query);	
	echo "<i>Table rehearsalphase_contact created.</i><br/>";
}
else {
	echo "<i>Table rehearsalphase_contact already exists.</i><br/>";
}

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>