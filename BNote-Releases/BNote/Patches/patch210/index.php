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
  <link href="<?php echo $GLOBALS["DIR_CSS"]; ?>login.css" rel="StyleSheet" type="text/css" /> 
 </head>

<body>
	<div id="topline">
		<?php echo $swname; ?>
	</div>
	<div id="lowline">by Matti Maier Internet Solutions</div>
	
	<form method="POST" action="<?php echo $GLOBALS["DIR_LOGIC"]; ?>login.php">
	
		<div id="login">
			<img src="<?php echo $GLOBALS["DIR_ICONS"]; ?>key.png" border="0" alt="" height="24px" /> LOGIN
			<p class="login">Bitte gebe deinen Benutzernamen und dein Passwort ein.</p>

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
		</div>
		
		<div id="login_options">
			<a href="register.php">Registrierung</a>&nbsp;&nbsp;<a href="pwforgotten.php">Passwort vergessen</a>
			
			<p class="login">Wenn du dich wiederholt nicht anmelden kannst, dann<br />
				ist dein Konto gegebenenfalls noch nicht freigeschalten. Bitte<br />
				versuche es zu einem sp&auml;teren Zeitpunkt noch einmal.</p>
		</div>
		
		<div id="login_info">
			Warum BlueNote nutzen?&nbsp;&nbsp;
			<img src="<?php echo $GLOBALS["DIR_ICONS"]; ?>arrow_right.png" border="0" alt="" height="15px" />
			<script>
			function show_why_bluenote() {
				document.getElementById('login_info').style.height = '420px';
				document.getElementById('login_info').style.width = '800px';
				document.getElementById('why_bluenote').style.display = 'block';
			}
			</script>
			<a onClick="show_why_bluenote();" class="why_bluenote_link">Antwort</a>
			<div id="why_bluenote">
			<p>
			Du denkst: noch so eine Software? Muss das denn sein???<br/>
			BlueNote ist nicht nur irgendeine Software, sie hilft dir und deiner Band euch besser zu organisieren.
			Aus Erfahrung dauern organisatorische Dinge lange und das geht von der Probenzeit (und natürlich dem Spass) ab.
			Das ist schade! BlueNote hilft euch die Organisation zu systematisieren und damit Zeit zu sparen.
			Konkret sind hier ein paar Gründe aufgelistet warum ihr BlueNote nutzt solltet:
			</p>
			<ul>
				<li class="why_bluenote"><b>BlueNote nimmt euch arbeit ab</b><br/>Bsp.: Probenbenachrichtigungen an alle Mitglieder können automatisch versandt werden.</li>
				<li class="why_bluenote"><b>Ihr behaltet den Überblick</b><br/>Konzerte, Proben, Kontaktdaten, wer kommt wann, wohin eigentlich? - hiermit hilft BlueNote!</li>
				<li class="why_bluenote"><b>Organisatorische Fehler werden reduziert</b><br/>Bsp: Ein Bandmitglieder wechselt seine Handynummer.
				Neue Nummern zu verteilen ist mühsam, dauert und man erwischt nie alle. Ändert man seine persönlichen Daten in BlueNote haben alle immer
				die aktuelle Nummer parat.</li>
				<li class="why_bluenote"><b>Informationen werden schnell und zuverlässig verteilt</b><br/>Ein kleiner Dateimanager ermöglicht es, Noten zu verteilen, Plakate, Setlisten,
				usw. einzustellen und allen Bandmitgliedern zugänglich zu machen.</li>
				<li class="why_bluenote"><b>Ihr behaltet das Reperatoire im Griff</b><br/>Wie lange dauert ein Titel? Welche Titel haben wir eigentlich? Wie lange dauert unser Programm?
				Ein Programm zusammenzustellen ist nicht einfach - doch wenn man die notwendigen Informationen zur Hand hat, kann man sich auf die eigentliche
				Gestaltung konzentrieren und sich nicht mit Zahlen und Druckern rumschlagen.</li>		
			</ul>
			<p>
			Und? Überzeugt? Wenn ja, dann <a href="register.php">registriert</a> euch!<br/>
			Wenn nicht, dann sagt <a href="mailto:<?php echo $admin; ?>">mir</a> Bescheid, warum nicht. Entwicklung ist immer notwendig.
			</p>
			</div>
		</div>
		<br/>
	</form>

</body>
</html>