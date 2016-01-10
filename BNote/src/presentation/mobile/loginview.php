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
		// ignore
	}
	
	function login() {
		// login form
		$form = new MobileForm("Anmeldung", $this->modePrefix() . "login");
		$form->addElement("Benutzername oder E-Mail-Adresse", new Field("login", "", FieldType::CHAR));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->changeSubmitButton("Login");
		$form->hideTitle();
		$form->write();
		
		// notes
		Writing::p("Bitte melde dich an um BNote zu nutzen. Wenn du noch kein
				Konto deiner Band hast, dann <a href=\"?mod=registration\">registriere</a> dich jetzt.");
		
		Writing::p("Wenn du dich wiederholt nicht anmelden kannst,
				dann ist dein Konto gegebenenfalls noch nicht freigeschalten. Bitte
				versuche es zu einem sp&auml;teren Zeitpunkt noch einmal.");
		
		$desktop = new Link("?mod=login&device=desktop", "Desktop Version");
		$desktop->setJsClick("location.reload(true);");
		$desktop->write();
	}
	
}

?>