<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.4.1			 *
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
 * Task 1: ...
 */

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>