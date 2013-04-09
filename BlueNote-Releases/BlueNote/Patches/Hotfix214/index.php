<?php

/**
 * Login
 */

// include "dirs.php";
// include $DIR_DATA . "database.php";
// $sysconfig = new XmlData("config/config.xml", "Software");
// $swname = $sysconfig->getParameter("Name");
// $admin = $sysconfig->getParameter("Admin");
include "src/data/systemdata.php";
$sd = new Systemdata();
date_default_timezone_set("Europe/Berlin");
?>

<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo $sd->getApplicationName(); ?> | Login</title>
  <link href="<?php echo $GLOBALS["DIR_CSS"]; ?>login.css" rel="StyleSheet" type="text/css" /> 
 </head>
 <body>

 <?php 
 function loginBackButton() {
 	echo '<a href="?show=login">Zurück</a>';
 }
 
 ?>
 
 	<div id="top_bar">BlueNote</div>
 		<?php
 		if(isset($_GET["show"]) && $_GET["show"] == "impressum") {
 			?>
 			<div id="impressum">
 				<?php  
 				loginBackButton();
 				include "data/impressum.html";
 				?>
 			</div>
 			<?php
		}
		else if(isset($_GET["show"]) && $_GET["show"] == "whyBlueNote") {
			include "data/whyBlueNote.php";
		}
		else if(isset($_GET["show"]) && $_GET["show"] == "register") {
			?>
			<div id="register">
				<?php
				loginBackButton();
				include "register.php";
				?>
			</div>
			<?php
		}
		else if(isset($_GET["show"]) && $_GET["show"] == "pwforgotten") {
			include "pwforgotten.php";			
		}
 		else {
 			include "data/login.html";
 		}
 		?>
		
	<div id="login_bottom">by Matti Maier Internet Solutions | <a class="login_bottom" href="?show=impressum">Impressum</a></div>
 
</body>
</html>