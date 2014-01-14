<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.x.x			 *
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
 * Task 1: Insert Configuration
 */
$confParams = $db->getSelection("SELECT param FROM configuration");
// $containsShowLength = false;
// $containsMaybe = false;
// foreach($confParams as $i => $row) {
// 	if($row["param"] == "rehearsal_show_length") $containsShowLength = true;
// 	else if($row["param"] == "allow_participation_maybe") $containsMaybe = true; 
// }
// if(!$containsShowLength) {
// 	$query = "INSERT INTO configuration (param, value, is_active) VALUES ";
// 	$query .= "('rehearsal_show_length', '1', 1)";
// 	$db->execute($query);
// 	echo "<i>Added configuration parameter rehearsal_show_length.</i><br/>";
// }
// else {
// 	echo "<i>Configuration parameter rehearsal_show_length exists.</i><br/>";
// }
// if(!$containsMaybe) {
// 	$query = "INSERT INTO configuration (param, value, is_active) VALUES ";
// 	$query .= "('allow_participation_maybe', '1', 1)";
// 	$db->execute($query);
// 	echo "<i>Added configuration parameter allow_participation_maybe.</i><br/>";
// }
// else {
// 	echo "<i>Configuration parameter allow_participation_maybe exists.</i><br/>";
// }

?>
<br/><br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>