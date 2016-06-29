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
		include $GLOBALS["DIR_PRESENTATION"] . "logo.php";
	}
	
	function showOptions() {
		// Mobile Website
		$mobile = new Link("../BNote-App", "Mobil");
		$mobile->write();
		$this->buttonSpace();
		
		// Login
		$mod = "login";
		if($_GET["mod"] != $mod) {
			$login = new Link("?mod=$mod", "Login");
			$login->write();
			$this->buttonSpace();
		}
		
		// PW
		$mod = "forgotPassword";
		if($_GET["mod"] != $mod) {
			$pwForgot = new Link("?mod=$mod", "Passwort vergessen");
			$pwForgot->write();
			$this->buttonSpace();
		}
		
		// Registration
		$mod = "registration";
		if($_GET["mod"] != $mod) {
			$reg = new Link("?mod=$mod", "Registrierung");
			$reg->write();
			$this->buttonSpace();
		}
		
		// Terms
		$mod = "terms";
		if($_GET["mod"] != $mod) {
			$terms = new Link("?mod=$mod", "Nutzungsbedingungen");
			$terms->write();
			$this->buttonSpace();
		}
		
		// Impressum
		$mod = "impressum";
		if($_GET["mod"] != $mod) {
			$imp = new Link("?mod=$mod", "Impressum");
			$imp->write();
		}
	}
	
	function login() {
		if(!isset($_GET["device"]) || $_GET["device"] != "desktop") {
			?>
			<script>
			$(document).ready(function() {
				// when the width of the screen is less than 560px (UI5 default) -> switch to app view
				var ww = window.screen.width;
				if(ww < 560) {
					location = "../BNote-App";
				}
			});
			</script>
			<?php
		}
		
		Writing::p("Bitte melde dich an um BNote zu nutzen. Wenn du noch kein
				Konto deiner Band hast, dann <a href=\"?mod=registration\">registriere</a> dich jetzt.");
		
		Writing::p("Wenn du dich wiederholt nicht anmelden kannst,
				dann ist dein Konto gegebenenfalls noch nicht freigeschalten. Bitte
				versuche es zu einem späteren Zeitpunkt noch einmal.");
		
		// login form
		$form = new Form("Anmeldung", $this->modePrefix() . "login");
		$form->addElement("Benutzername<br/>oder E-Mail-Adresse", new Field("login", "", FieldType::CHAR));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->write();
	}
	
	function forgotPassword() {
		Writing::h1("Passwort vergessen");
		Writing::p("Bitte gebe deine E-Mail-Adresse ein und das System wird dir ein neues Passwort per E-Mail zuschicken.");
		
		// forgotten password form
		$form = new Form("", $this->modePrefix() . "password");
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
		
<p class="login">Bitte fülle dieses Formular aus um dich als
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
		global $system_data;
		$cats = $system_data->getInstrumentCategories();
		for($i = 1; $i < count($instruments); $i++) {
			// filter instruments of categories
			if(!in_array($instruments[$i]["cat"], $cats)) continue;
			echo '<OPTION value="' . $instruments[$i]["id"] . '">';
			echo $instruments[$i]["category"] . ": " . $instruments[$i]["instrument"] . "</OPTION>\n";
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
			onChange="validateInput(this, 'password');" /><br /> <span
			style="font-size: 10px;">Bitte gebe mindestens 6 Zeichen und keine Leerzeichen ein.</span>
		</TD>
	</TR>
	<TR>
		<TD class="login">Passwort Wdh. *</TD>
		<TD class="loginInput"><input name="pw2" type="password" size="25"
			onChange="validateInput(this, 'password');" /></TD>
	</TR>
	<TR>
		<TD class="login">Ich stimme den <a href="?mod=terms" style="text-decoration: underline;" target="_blank">Nutzungsbedingungen</a> zu.*
		</TD>
		<TD class="loginInput"><input type="checkbox" name="terms" /></TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"
			style="font-size: 10pt; padding-bottom: 15px; width: 100%;">* Die mit Stern gekennzeichneten Felder sind auszufüllen.</TD>
	</TR>
	<TR>
		<TD class="login" colspan="2"><input name="register" type="submit"
			value="Registrieren"></TD>
	</TR>
</table>
</form>
<?php
	}
	
	function impressum() {
		include "data/impressum.html";
	}
	
	function terms() {
		include "data/terms.html";
	}
}

?>