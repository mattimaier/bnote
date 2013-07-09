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

}

  #login_bottom { font-size: 12px; color: #A0A0A0; position: absolute; bottom: 5px; left: 10px; }
  a.login_bottom { color: #A0A0A0; }

  #top_bar {
  background-image: linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
background-image: -o-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
background-image: -moz-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
background-image: -webkit-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
background-image: -ms-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);

background-image: -webkit-gradient(
	linear,
	left bottom,
	left top,
	color-stop(0.1, rgb(40,93,184)),
	color-stop(0.86, rgb(103,155,245))
);

	color: white; margin-top: 2%; font-size: 30px; font-weight: bold; padding-top: 5px; padding-bottom: 5px; padding-left: 10%;
	border: 2px solid #DCDCDC;
	border-left-width: 0px;
	border-right-width: 0px;
  }
  
  #login_box {
  	margin-left: 10%;
  	margin-top: 5%;
  	background-color: #F0F0F0;
  	border-radius: 10px;
  	border: 1px solid #DCDCDC;
  	padding: 10px;
  	font-size: 12px;
  	width: 300px;
  }
  
</style> 
 </head>
 <body>

 	<img src="style/images/mic_demo_shutterstock.jpg" height="600px" style="position: absolute; bottom: 0px; left: 60%;" alt="" />

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