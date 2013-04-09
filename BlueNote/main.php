<?php 

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";
include $GLOBALS["DIR_LOGIC"] . "init.php";

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php

/**
 * Developed by Matti Maier Internet Solutions 2011
**/

?>

<HTML lang="de">

<?php
# Display HTML HEAD
include $GLOBALS["DIR_PRESENTATION"] . "head.php";
?>

<BODY>

<?php

# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";

# Display Navigation
include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";

?>

<!-- Content Area -->
<div id="content">

<?php
 include $GLOBALS["DIR_LOGIC"] . "controller.php";

 # Build Controller
 $controller = new Controller();
?>

</div>

</BODY>

</HTML>
