<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Hotfix 211			 *
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
require_once $PATH_TO_SRC . "data/database.php";
require_once $PATH_TO_SRC . "data/regex.php";
require_once $PATH_TO_SRC . "presentation/widgets/error.php";

// build DB connection
$db = new Database();
$regex = new Regex();
?>


<p><b>This script updates the bluenote system's database structure. Please make sure it is only executed once!</b></p>

<h3>Log</h3>
<p>
<?php 

/*
 * TASK 1: Check for accounts with not valid user name / login
 */
$count_ok = 0;
$count_invalid = 0;
$users = $db->getSelection("SELECT id, login FROM " . $db->getUserTable());
for($i = 1; $i < count($users); $i++) {
	if(!$regex->isLoginQuiet($users[$i]["login"])) {
		echo "<i>User with ID " . $users[$i]["id"];
		echo " and login '" . $users[$i]["login"];
		echo "' has an <b>invalid login-name</b>!</i><br/>";
		$count_invalid++;
	}
	else {
		$count_ok++;
	}
}
echo "<i><u>TASK 1:</u> $count_invalid of " . ($count_ok + $count_invalid) . " user login names need to be updated manually.</i><br/>";

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>