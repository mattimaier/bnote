<?php 
/**
 * BNote - Band Management Software
 * Developed by Matti Maier Internet Solutions 2014
 */

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";
include $GLOBALS["DIR_LOGIC"] . "init.php";

# Login forward if necessary
if(isset($_GET["mod"]) && $_GET["mod"] === "login" && isset($_GET["mode"]) && $_GET["mode"] === "login") {
	include $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
	include $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
	include $GLOBALS["DIR_DATA"] . "fieldtype.php";
	include $GLOBALS["DIR_DATA"] . "abstractdata.php";
	include $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
	$ctrl = new LoginController();
	$loginData = new LoginData();
	$ctrl->setData($loginData);
	$ctrl->doLogin();
}

?>

<!DOCTYPE html>
<HTML lang="de">

<?php
# Display HTML HEAD
include $GLOBALS["DIR_PRESENTATION"] . "head.php";
?>

<BODY>

<?php

# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
?>

<!-- Content Area -->
<div id="content_container">
	<?php
	# Display Navigation
	include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";
	?>
	<div id="content_insets">
		<div id="content">
			<?php
			include $GLOBALS["DIR_LOGIC"] . "controller.php";
			new Controller();
			?>
		</div>
	</div>
</div>
				
<?php
# Display Footer
include $GLOBALS["DIR_PRESENTATION"] . "footer.php";
?>

</BODY>

</HTML>
