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
background-image: url(style/images/debut_dark.png); background-repeat: repeat-x repeat-y;
}

  #login_bottom { font-size: 12px; color: #A0A0A0; position: absolute; bottom: 5px; left: 5%; margin-left: 230px; }
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
	border-bottom-width: 0px;
  }
  
  #login_bar {
    position: absolute;
  	left: 5%;
  	top: 0px;
  	
  	border: 0px solid #DCDCDC;
  	font-size: 12px;
  	height: 100%;
  	width: 200px;
  	padding-left: 10px;
  	padding-right: 10px;
  	
  	background-image: -webkit-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
  	opacity: 0.4;
  }
  
  #page {
  	 position: absolute;
  	left: 5%;
  	top: 0px;
  	margin-left:220px;
  	
  	border: 0px solid #DCDCDC;
  	font-size: 12px;
  	height: 100%;
  	width: 500px;
  	padding-left: 10px;
  	padding-right: 10px;
 	
	background-image: -webkit-linear-gradient(bottom, rgb(40,93,184) 10%, rgb(103,155,245) 86%);
	opacity: 0.2;
  }
  
</style> 
 </head>
 <body> 	
		<div id="login_bar">
			<!-- LEFT SIDE -->
			<form action="login.php" method="POST">
					
					Benutzername<br/>
					<input name="login" type="text" size="25" /><br/>
				
					Passwort<br/>
					<input name="password" type="password" size="25" /><br/>
				
					<input type="submit" value="Anmelden">
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
		
		 <div id="page">
		 	<h1>BlueNote</h1>
		 	<div id="why_bluenote">
				<p>
					Du denkst: noch so eine Software? Muss das denn sein???<br />
					BlueNote ist nicht nur irgendeine Software, sie hilft dir und
					deiner Band euch besser zu organisieren. Aus Erfahrung dauern
					organisatorische Dinge lange und das geht von der Probenzeit (und
					natürlich dem Spass) ab. Das ist schade! BlueNote hilft euch die
					Organisation zu systematisieren und damit Zeit zu sparen. Konkret
					sind hier ein paar Gründe aufgelistet warum ihr BlueNote nutzt
					solltet:
				</p>
				<ul>
					<li class="why_bluenote"><b>BlueNote nimmt euch arbeit ab</b><br />Bsp.:
						Probenbenachrichtigungen an alle Mitglieder können automatisch
						versandt werden.</li>
					<li class="why_bluenote"><b>Ihr behaltet den Überblick</b><br />Konzerte,
						Proben, Kontaktdaten, wer kommt wann, wohin eigentlich? - hiermit
						hilft BlueNote!</li>
					<li class="why_bluenote"><b>Organisatorische Fehler werden
							reduziert</b><br />Bsp: Ein Bandmitglieder wechselt seine
						Handynummer. Neue Nummern zu verteilen ist mühsam, dauert und man
						erwischt nie alle. Ändert man seine persönlichen Daten in BlueNote
						haben alle immer die aktuelle Nummer parat.</li>
					<li class="why_bluenote"><b>Informationen werden schnell und
							zuverlässig verteilt</b><br />Ein kleiner Dateimanager ermöglicht
						es, Noten zu verteilen, Plakate, Setlisten, usw. einzustellen und
						allen Bandmitgliedern zugänglich zu machen.</li>
					<li class="why_bluenote"><b>Ihr behaltet das Reperatoire im Griff</b><br />Wie
						lange dauert ein Titel? Welche Titel haben wir eigentlich? Wie
						lange dauert unser Programm? Ein Programm zusammenzustellen ist
						nicht einfach - doch wenn man die notwendigen Informationen zur
						Hand hat, kann man sich auf die eigentliche Gestaltung
						konzentrieren und sich nicht mit Zahlen und Druckern rumschlagen.</li>
				</ul>
				<p>
					Und? Überzeugt? Wenn ja, dann <a href="register.php">registriert</a>
					euch!<br /> Wenn nicht, dann sagt <a
						href="mailto:<?php echo $admin; ?>">mir</a> Bescheid, warum nicht.
					Entwicklung ist immer notwendig.
				</p>
			</div>
		 </div>
		 
		
	<div id="login_bottom">by Matti Maier Internet Solutions | <a class="login_bottom" href="?show=impressum">Impressum</a></div>
 
</body>
</html>