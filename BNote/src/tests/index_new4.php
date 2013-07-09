<?php

/**
 * Login
 */

include "dirs.php";
include $DIR_DATA . "database.php";
$sysconfig = new XmlData("config/config.xml", "Software");
$swname = $sysconfig->getParameter("Name");
$admin = $sysconfig->getParameter("Admin");
date_default_timezone_set("Europe/Berlin");
?>

<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo $swname; ?> | Login</title>
  <style>
body {
	padding: 0px;
	margin: 0px;
	font-family: sans-serif;

	background-image: url(style/images/shutterstock_demo_microphone.jpg);
	background-position: center;
	background-repeat: no-repeat;
	background-color: black;
	
	color: white;
}

  #login_bottom { font-size: 12px; color: #A0A0A0; position: absolute; bottom: 5px; left: 10px; }
  a.login_bottom { color: #A0A0A0; }

  #top_bar {
	color: #285db8; margin-top: 2%; font-size: 30px; font-weight: bold; padding-top: 5px; padding-bottom: 5px; padding-left: 10%;
  }
  
  a { color: white; }
  
  #login_box {
  	margin-left: 10%;
  	margin-top: 5%;
  }
  
</style> 
 </head>
 <body>

 	<div id="top_bar">BlueNote</div>
 	
		<div id="login_box">
			<!-- LEFT SIDE -->
			<form action="login.php" method="POST">
							<table class="login">
				<TR>
					<TD class="login">Benutzername</TD>
					<TD class="loginInput"><input name="login" type="text" size="25" /></TD>
				</TR>
				<TR>
					<TD class="login">Passwort</TD>
					<TD class="loginInput"><input name="password" type="password" size="25" /></TD>
				</TR>
				<TR>
					<TD class="login" colspan="2"><input type="submit" value="Anmelden"></TD>
				</TR>
			</table>
			</form>

			<div id="login_options">
				<a href="register.php">Registrierung</a>&nbsp;&nbsp;<a
					href="pwforgotten.php">Passwort vergessen</a>

				<p class="login">
					Wenn du dich wiederholt nicht anmelden kannst, dann<br /> ist dein
					Konto gegebenenfalls noch nicht freigeschalten. Bitte<br />
					versuche es zu einem sp&auml;teren Zeitpunkt noch einmal.
				</p>
			</div>

		</div>
		
	<div id="login_bottom">by Matti Maier Internet Solutions | <a class="login_bottom" href="?show=impressum">Impressum</a></div>
 
</body>
</html>