<?php

/*************************
 * UPGRADES THE DATABASE *
 * @author Matti Maier   *
 * Update 2.3.1			 *
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
 * TASK 1.1: Add more instruments to the selection
 * Enhancement #10, #21
 */
$query = "INSERT INTO instrument (name, category) VALUES ";
$query .= "('Gitarre', 4), ";
$query .= "('Fl&uuml;gelhorn', 2), ";  // enhancement #10
$query .= "('Basskarinette', 3), ";

// enhancement #21
$query .= "('Sopran', 5), ";
$query .= "('Mezzo-Sopran', 5), ";
$query .= "('Alt', 5), ";
$query .= "('Tenor', 5), ";
$query .= "('Bass', 5), ";
$query .= "('Bariton', 5), ";
$query .= "('Countertenor', 5), ";
$query .= "('Background', 5), ";
$query .= "('Solistin Sopran', 5), ";
$query .= "('Solistin Alt', 5), ";
$query .= "('Solist Bass', 5), ";
$query .= "('Solist Tenor', 5), ";
$query .= "('Solist Bariton', 5), ";
$query .= "('Solist Countertenor', 5)";

$db->execute($query);

/*
 * TASK 1.2: Update current listings on instruments and categories.
 */
$db->execute("UPDATE instrument SET name = 'Musikalischer Leiter' WHERE id = 1");
$db->execute("UPDATE instrument SET name = 'Sologesang' WHERE id = 2");
$db->execute("UPDATE instrument SET name = 'keine Angabe' WHERE id = 23");

/*
 * TASK 1.3: Update current categories
 */
$db->execute("UPDATE category SET name = 'Blechbl&auml;ser' WHERE id = 2");
$db->execute("UPDATE category SET name = 'Holzbl&auml;ser' WHERE id = 3");

echo "<i>Diverse Instrumente hinzugef&uuml;gt, Instrumentnamen und Kategorien aktualisiert.</i><br/>\n";

?>
<br/>
<b><i>COMPLETE.</i></b>
</p>

</body>
</html>