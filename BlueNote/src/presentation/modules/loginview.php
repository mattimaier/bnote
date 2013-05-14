<?php

/**
 * Login views.
 * @author matti
 *
 */
class LoginView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		$this->home();
	}
	
	function login() {					
		Writing::h1("Login");
		
		Writing::p("Bitte melde dich an um BNote zu nutzen. Wenn du noch kein
				Konto deiner Band hast, dann <a href=\"?mod=registration\">registriere</a> dich jetzt.");
		
		Writing::p("Wenn du dich wiederholt nicht anmelden kannst,
				dann ist dein Konto gegebenenfalls noch nicht freigeschalten. Bitte
				versuche es zu einem sp&auml;teren Zeitpunkt noch einmal.");
		
		// login form
		$form = new Form("Anmeldung", $this->modePrefix() . "login");
		$form->addElement("Benutzername", new Field("login", "", FieldType::CHAR));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->write();
	}
	
	function forgotPassword() {
		Writing::h1("Passwort vergessen");
		Writing::p("Bitte gebe deine E-Mail-Adresse ein und das System wird dir ein neues Passwort per E-Mail zuschicken.");
		
		// forgotten password form
		$form = new Form("Neues Passwort anfordern", $this->modePrefix() . "password");
		$form->addElement("E-Mail-Adresse", new Field("email", "", FieldType::EMAIL));
		$form->write();
	}
	
	function registration() {
		Writing::h1("Registrierung");
		
		?>
<form method="POST" action="<? echo $this->modePrefix(); ?>register">
		
<script>
	  <?php echo $this->getData()->getJSValidationFunctions(); ?>
</script> 
		
<p class="login">Bitte f&uuml;lle dieses Formular aus um dich als
	Mitglied zu registrieren. Die angegebenen Daten werden vertraulich
	behandelt und nicht an Dritte weitergegeben.</p>

<table class="login">
	<TR>
		<TD class="login">Vorname *</TD>
		<TD class="loginInput"><input name="name" type="text" size="25"
			onChange="validateInput(this, 'name');" /></TD>
	</TR>
	<TR>
		<TD class="login">Name *</TD>
		<TD class="loginInput"><input name="surname" type="text" size="25"
			onChange="validateInput(this, 'name');" /></TD>
	</TR>
	<TR>
		<TD class="login">Telefon</TD>
		<TD class="loginInput"><input name="phone" type="text" size="25"
			onChange="validateInputOptional(this, 'phone');" /></TD>
	</TR>
	<TR>
		<TD class="login">E-Mail *</TD>
		<TD class="loginInput"><input name="email" type="text" size="25"
			onChange="validateInput(this, 'email');" /></TD>
	</TR>
	<TR>
		<TD class="login">Stra&szlig;e *</TD>
		<TD class="loginInput"><input name="street" type="text" size="25"
			onChange="validateInput(this, 'street');" /></TD>
	</TR>
	<TR>
		<TD class="login">PLZ *</TD>
		<TD class="loginInput"><input name="zip" type="text" size="25"
			onChange="validateInput(this, 'zip');" /></TD>
	</TR>
	<TR>
		<TD class="login">Stadt *</TD>
		<TD class="loginInput"><input name="city" type="text" size="25"
			onChange="validateInput(this, 'city');" /></TD>
	</TR>
	<TR>
		<TD class="login">Instrument</TD>
		<TD class="loginInput"><SELECT name="instrument">
				<?php
		$instruments = $this->getData()->getInstruments();
		for($i = 1; $i < count($instruments); $i++) {
			echo '<OPTION value="' . $instruments[$i]["id"] . '">';
			echo $instruments[$i]["name"] . "</OPTION>\n";
		}
		?>
		</SELECT>
		</TD>
	</TR>
	<TR>
		<TD class="login">Login *</TD>
		<TD class="loginInput"><input name="login" type="text" size="25"
			onChange="validateInput(this, 'login');" /><br /> <span
			style="font-size: 10px;">Erlaubte Zeichen: Buchstaben, Zahlen, Punkt,
				Bindestrich, Unterstrich</span>
		</TD>
	</TR>
	<TR>
		<TD class="login">Passwort *</TD>
		<TD class="loginInput"><input name="pw1" type="password" size="25"
			onChange="validateInput(this, 'password');" /></TD>
	</TR>
	<TR>
		<TD class="login">Passwort Wdh. *</TD>
		<TD class="loginInput"><input name="pw2" type="password" size="25"
			onChange="validateInput(this, 'password');" /></TD>
	</TR>
	<TR>
		<TD class="login">Ich stimme den<br />
		 <a href="?mod=terms" style="text-decoration: underline;" target="_blank">Nutzungsbedingungen</a> zu.*
		</TD>
		<TD class="loginInput"><input type="checkbox" name="terms" /></TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"
			style="font-size: 10pt; padding-bottom: 15px; width: 100%;">* Die mit
			Stern gekennzeichneten Felder sind auszuf&uuml;llen.</TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"><input name="register" type="submit"
			value="Registrieren"></TD>
	</TR>
</table>
</form>
<?php
	}
	
	function whyBNote() {
		Writing::h1("Warum BNote nutzen?");
		
		Writing::p("Du denkst: noch so eine Software? Muss das denn sein???<br />
			BNote ist nicht nur irgendeine Software, sie hilft dir und deiner
			Band euch besser zu organisieren. Aus Erfahrung dauern
			organisatorische Dinge lange und das geht von der Probenzeit (und
			natürlich dem Spass) ab. Das ist schade! BNote hilft euch die
			Organisation zu systematisieren und damit Zeit zu sparen. Konkret
			sind hier ein paar Gründe aufgelistet warum ihr BNote nutzt
			solltet:");
		
		Writing::p("
			<span class=\"login_whyBNote_topic\">BNote nimmt euch arbeit ab</span><br/>
			Zum Beispiel: Probenbenachrichtigungen an alle Mitglieder können automatisch versandt werden.<br/><br/>
					
			<span class=\"login_whyBNote_topic\">Ihr behaltet den Überblick</span><br/>
			Konzerte, Proben, Kontaktdaten, wer kommt wann, wohin eigentlich? - hiermit hilft BNote!<br/><br/>
			
			<span class=\"login_whyBNote_topic\">Organisatorische Fehler werden reduziert</span><br/>
			Zum Beispiel wechselt ein Bandmitglieder seine Handynummer.	Neue Nummern zu verteilen ist mühsam, dauert und man erwischt nie
			alle. Ändert man seine persönlichen Daten in BNote haben alle immer die aktuelle Nummer parat.<br/><br/>
			
			<span class=\"login_whyBNote_topic\">Informationen werden schnell und zuverlässig verteilt</span><br/>
			Ein kleiner Dateimanager ermöglicht es,	Noten zu verteilen, Plakate, Setlisten, usw. einzustellen und allen
			Bandmitgliedern zugänglich zu machen.<br/><br/>
	
			<span class=\"login_whyBNote_topic\">Ihr behaltet das Reperatoire im Griff</span><br/>
			Wie lange dauert ein Titel? Welche Titel haben wir eigentlich? Wie lange dauert unser Programm? Ein Programm
			zusammenzustellen ist nicht einfach - doch wenn man die notwendigen Informationen zur Hand hat, kann man sich
			auf die eigentliche Gestaltung konzentrieren und sich nicht mit Zahlen und Druckern	rumschlagen.");
		
		
		Writing::h3("Überzeugt?");
		Writing::p('Wenn ja, dann <a href="?mod=registration">registriert</a>
			euch!<br /> Wenn nicht, dann sagt <a href="mailto:server@inizio.org">mir</a> Bescheid, warum nicht.
			Entwicklung ist immer notwendig.');
	}
	
	function impressum() {
		include "data/impressum.html";
	}
	
	function terms() {
		include "data/terms.html";
	}
	
	function home()
	{
		include $GLOBALS["DIR_PRESENTATION"] . "logo.php";
	}
}

?>