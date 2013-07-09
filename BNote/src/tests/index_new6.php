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
  body { padding: 0px; margin: 0px; font-family: sans-serif; background-image: url(style/images/otis_redding.png);	background-repeat: repeat-x repeat-y; }
  #bluenote { font-size: 26px; font-weight: bold; color:#285DB8; margin-left: 10%; margin-top: 5%;}
  #main { width: 700px; margin-left: 10%; }
  #navi { height: 400px; float: left; margin-top: 3px; }
  #content { height: 300px; border: 5px solid gray; margin-left: 150px; background-color: white; }
  .navi_item { background-color: #F0F0F0; text-align: right; padding-top: 18px; padding-bottom: 18px; padding-left: 5px; padding-right: 4px; } 
  
  </style>
  </head>
  <body>
  
  <div id="bluenote">BlueNote</div>
  
  <div id="main">
  	<div id="navi">  		
  		<div class="navi_item" style="width: 100px;">Passwort vergessen</div>
  		<div class="navi_item" style="">Warum Bluenote?</div>
  		<div class="navi_item" style="">Registrieren</div>
  		<div class="navi_item" style="">Impressum</div>
  		<div class="navi_item" style="">Login</div>
  	</div>
  	
  	<div id="content">
			<table class="login">
				<TR>
					<TD class="login">Benutzername</TD>
					<TD class="loginInput"><input name="login" type="text" size="25" />
					</TD>
				</TR>
				<TR>
					<TD class="login">Passwort</TD>
					<TD class="loginInput"><input name="password" type="password"
						size="25" /></TD>
				</TR>
				<TR>
					<TD class="login" colspan="2"><input type="submit" value="Anmelden">
					</TD>
				</TR>
			</table>
		</div>
  </div>
  
  </body>
  </html>