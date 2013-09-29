<?php

/**
 * @autor Matti Maier
 * This is an installation wizzard to install BNote. 
 */
require_once("dirs.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "message.php");
require_once($GLOBALS["DIR_WIDGETS"] . "writing.php");
require_once($GLOBALS["DIR_WIDGETS"] . "link.php");
require_once($GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($GLOBALS["DIR_WIDGETS"] . "field.php");
require_once($GLOBALS["DIR_WIDGETS"] . "dropdown.php");
require_once($GLOBALS["DIR_WIDGETS"] . "form.php");

class Installation {
	
	function __construct() {
		if(isset($_GET["func"]) && isset($_GET["last"]) && $_GET["func"] == "process") {
			$process = "process_" . $_GET["last"];
			$this->$process();
		}
		$this->screen();
	}
	
	/**
	 * Step 1: Welcome the user and thank him/her for using BNote. 
	 */
	function welcome() {
		Writing::h1("Willkommen");
		
		Writing::p("Danke dass du dich für BNote entschieden hast. Du tust dir und deiner Band damit einen großen Gefallen.
					Jetzt brauchst du nur noch die Installation abschließen und dann kann es losgehen!");
		
		Writing::p("Den ersten Schritt auf dem Weg zu BNote hast du bereits geschafft.
					Du hast BNote entpackt, es auf deinen Webserver geladen und die Installation gestartet.   
					Um die Installation erfolgreich abzuschließen gehst du wie folgt vor:");
		?>
		<ul style="list-style-type: disc;">
			<li style="margin-left: 20px; margin-bottom: 5px;">Erstelle einen neuen MySQL Datenbankbenutzer mit Passwort und weiße ihm eine neue/leere Datenbank zu.</li>
			<li style="margin-left: 20px; margin-bottom: 5px;">Stelle sicher, dass dieses Script in den Unterordner config/ von BNote schreiben kann.<br/>
				In der Regel musst du die Berechtigungen des Ordners für den Webserver-User zugänglich machen oder
				deinem Hosting Provider sagen, dass Scripte schreiben dürfen.</li>
			<li style="margin-left: 20px">Halte den Namen und die Anschrift deiner Band bereit.</li>
		</ul>
		
		<?php
		echo '<br/><br/>';
		$this->next("companyConfig");
	}
	
	/**
	 * Step 2: Ask for company configuration if not present.
	 */
	function companyConfig() {
		Writing::h1("Deine Band");
		
		if(file_exists("config/company.xml")) {
			new Message("Band Konfiguration bereits vorhanden", "Es wurde erkannt, dass du bereits eine Band Konfiguration angelegt hast.
					Daher kannst du diesen Schritt überspringen.");
			$this->next("databaseConfig");
		}
		else {
			Writing::p("Bitte gebe die Kontaktdaten deiner Band ein.");
			
			$form = new Form("Band Konfiguration", "?step=databaseConfig&func=process&last=companyConfig");
			$form->addElement("Bandname", new Field("Name", "", FieldType::CHAR));
			$form->addElement("Straße", new Field("Street", "", FieldType::CHAR));
			$form->addElement("PLZ", new Field("Zip", "", FieldType::INTEGER));
			$form->addElement("Stadt", new Field("City", "", FieldType::CHAR));
			$form->addElement("Land", new Field("Country", "", FieldType::CHAR));
			$form->addElement("Telefon", new Field("Phone", "", FieldType::CHAR));
			$form->addElement("Bandleader E-Mail", new Field("Mail", "", FieldType::CHAR));
			$form->addElement("Website", new Field("Web", "", FieldType::CHAR));
			$form->changeSubmitButton("Weiter");
			$form->write();
		}
	}
	
	function process_companyConfig() {
		if(!isset($_POST["Mail"]) || $_POST["Mail"] == "") {
			$_POST["Mail"] = "support@bnote.info";
		}
		
		$fileContent = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<Company>
 <Name>" . $_POST["Name"] . "</Name>
 <Street>" . $_POST["Street"] . "</Street>
 <Zip>" . $_POST["Zip"] . "</Zip>
 <City>" . $_POST["City"] . "</City>
 <Country>" . $_POST["Country"] . "</Country>
 <Phone>" . $_POST["Phone"] . "</Phone>
 <Mail>" . $_POST["Mail"] . "</Mail>
 <Web>" . $_POST["Web"] . "</Web>
</Company>";
		
		$res = file_put_contents("config/company.xml", $fileContent);
		if(!$res) {
			new Error("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
		}
		
		// write config.xml, too.
		if(!file_exists("config/config.xml")) {
			$bnotePath = $_SERVER["SCRIPT_NAME"];
			$bnotePath = str_replace("install.php", "", $bnotePath);
			$system_url = $_SERVER["HTTP_ORIGIN"] . $bnotePath;
			
			$fileContent = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<Software Name=\"BNote\">

 <!-- ID of the Start module -->
 <StartModule>1</StartModule>
 
 <!-- URL of the BNote system -->
 <URL>$system_url</URL>
 
 <!-- E-mail-address of the administrator -->
 <Admin>" . $_POST["Mail"] . "</Admin>
 
 <!-- Path to the manual file, i.e. PDF file -->
 <Manual>data/manual.pdf</Manual>
 
 <!-- True when this is a demo system with deactived mailing function, otherwise false (default). -->
 <DemoMode>false</DemoMode>
 
 <!-- In case users have to be activated by the administrator set this to true,
      otherwise users will be activated by clicking on a link in an email (false).
 -->
 <ManualUserActivation>false</ManualUserActivation>
 
 <!-- The user IDs of all super users whos credentials will not be shown on the website.
      This is a comma separated list without spaces.
  -->
 <SuperUsers>1</SuperUsers>
 
 <!-- Default Permissions for a new user. Comma separated list of user IDs without spaces. -->
 <DefaultPrivileges>9,10,12,13,14</DefaultPrivileges>
 
 <!-- This property specifies who / which group can upload data to the share. This includes the right to edit folders.
 	  Possible values are: ADMIN, MEMBER, EXTERNAL, APPLICANT, OTHER
 	  Constraints: ADMIN can always edit;
 	  Advise: A value of EXTERNAL, APPLICANT or OTHER is not adviseable! 
 	  Only one value is accepted. -->
 <ShareEditGroup>ADMIN</ShareEditGroup>
 
 <!-- True when the gallery management is used
      and should be displayed and functional, otherwise false. -->
 <UseGallery>True</UseGallery>
 
 <!-- True when the infopage/news/additional pages management is used
      and should be displayed and functional, otherwise false. -->
 <UseInfoPages>True</UseInfoPages>
 
 <!-- Only show the instruments of these category ids. Separate ids by comma without spaces.
 	  If set to \"ALL\" then show all available instruments. -->
 <InstrumentCategoryFilter>ALL</InstrumentCategoryFilter>
 
 <!-- The webpages available in the website module.
 	  A page tag contains an attribute \"file\" specifying the filename without the html-extension
 	  in the data/webpages folder and the body containing the displayed name of the page. -->
 <WebPages>
 	<Page file=\"startseite\">Startseite</Page>
	<Page file=\"news\">Nachrichten</Page>
 	<Page file=\"infos\">Informationen</Page>
	<Page file=\"band\">Die Band</Page>
 	<Page file=\"geschichte\">Geschichte</Page>
	<Page file=\"mitspieler\">Besetzung</Page>
	<Page file=\"konzerte\">Konzerte</Page>
 	<Page file=\"mediathek\">mediathek</Page>
 	<Page file=\"galerie\">Galerie</Page>
 	<Page file=\"videos\">Videos</Page>
 	<Page file=\"samples\">Aufnahmen</Page>
	<Page file=\"kontakt\">Kontakt</Page>
	<Page file=\"impressum\">Impressum</Page>
 </WebPages>
</Software>";
			
			$res = file_put_contents("config/config.xml", $fileContent);
			if(!$res) {
				new Error("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
			}
		}
	}
	
	/**
	 * Step 3: Ask for database configuration if not present.
	 */
	function databaseConfig() {
		Writing::h1("Datenbank Konfiguration");
		
		if(file_exists("config/database.xml")) {
			new Message("Datenbank Konfiguration bereits vorhanden", "Es wurde erkannt, dass du bereits eine Datenbank Konfiguration angelegt hast.
					Daher kannst du diesen Schritt überspringen.");
			$this->next("adminUser");
		}
		else {
			Writing::p("Bitte gebe die Zugangsdaten zur BNote Datenbank ein.");
				
			$form = new Form("Datenbank Konfiguration", "?step=adminUser&func=process&last=databaseConfig");
			$form->addElement("Server", new Field("Server", "localhost", FieldType::CHAR));
			$form->addElement("Port", new Field("Port", "3306", FieldType::INTEGER));
			$form->addElement("Datenbankname", new Field("Name", "", FieldType::CHAR));
			$form->addElement("Benutzername", new Field("User", "", FieldType::CHAR));
			$form->addElement("Passwort", new Field("Password", "", FieldType::PASSWORD));
			$form->changeSubmitButton("Weiter");
			$form->write();
		}		
	}
	
	function process_databaseConfig() {
		$fileContent = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<Database ConnectionName=\"Default MySQL-Connection\">
 <Server>" . $_POST["Server"] . "</Server>
 <Port>" . $_POST["Port"] . "</Port>
 <Name>" . $_POST["Name"] . "</Name>
 <User>" . $_POST["User"] . "</User>
 <Password>" . $_POST["Password"] . "</Password>
 
 <UserTable>user</UserTable>
</Database>";
		
		$res = file_put_contents("config/database.xml", $fileContent);
		if(!$res) {
			new Error("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
		}
	}
	
	/**
	 * Step 4: Create a new admin user.
	 */
	function adminUser() {
		Writing::h1("Benutzer anlegen");
		
		$form = new Form("Neuer Benutzer", "?step=finalize&func=process&last=adminUser");
		
		$form->addElement("Benutzername", new Field("login", "", FieldType::CHAR));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		
		$form->addElement("Vorname", new Field("name", "", FieldType::CHAR));
		$form->addElement("Nachname", new Field("surname", "", FieldType::CHAR));
		$form->addElement("Telefon", new Field("phone", "", FieldType::CHAR));
		$form->addElement("Handy", new Field("mobile", "", FieldType::CHAR));
		$form->addElement("E-Mail-Adresse", new Field("email", "", FieldType::CHAR));
		$form->addElement("Straße", new Field("street", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::INTEGER));
		$form->addElement("Ort", new Field("city", "", FieldType::CHAR));
		
		$db = $this->getDbConnection();
		$instruments = $db->getSelection("SELECT i.id, i.name, c.name as category
										  FROM instrument i, category c
										  WHERE i.category = c.id
										  ORDER BY category, name");
		$dd = new Dropdown("instrument");
		for($i = 1; $i < count($instruments); $i++) {
			$label = $instruments[$i]["category"] . ": " . $instruments[$i]["name"];
			$dd->addOption($label, $instruments[$i]["id"]);
		}
		$form->addElement("Instrument", $dd);
		
		$form->write();
	}
	
	function process_adminUser() {
		// validate password
		if(!isset($_POST["password"]) || !isset($_POST["login"]) || $_POST["password"] == "" || strlen($_POST["password"]) < 6) {
			new Error("Ungültiges Passwort. Bitte vergewissere dich, dass das Passwort mindestens 6 Zeichen hat und nicht leer ist.");
		}
		
		// get database connection
		$db = $this->getDbConnection();
		
		// create contact address
		$query = "INSERT INTO address (street, city, zip) VALUES (";
		$query .= '"' . $_POST["street"] . '", "' . $_POST["city"] . '", "' . $_POST["zip"] . '")';
		$aid = $db->execute($query);
		
		// create contact
		$query = "INSERT INTO contact (surname, name, phone, mobile, email, address, instrument) VALUES (";
		$query .= '"' . $_POST["surname"] . '", "' . $_POST["name"] . '", ';
		$query .= '"' . $_POST["phone"] . '", "' . $_POST["mobile"] . '", ';
		$query .= '"' . $_POST["email"] . '", ' . $aid . ', ' . $_POST["instrument"] . ')';
		$cid = $db->execute($query);
		
		// create user
		$password = crypt($_POST["password"], CRYPT_BLOWFISH);
		$query = "INSERT INTO user (login, password, contact, isActive) VALUES (";
		$query .= '"' . $_POST["login"] . '", "' . $password . '", ' . $cid . ', 1)';
		$uid = $db->execute($query);
		
		// create default privileges plus user module privileges
		$modules = $db->getSelection("SELECT * FROM module");
		$query = "INSERT INTO privilege (user, module) VALUES ";
		for($i = 1; $i < count($modules); $i++) {
			if($i > 1) $query .= ", ";
			$mod = $modules[$i]["id"];
			$query .= "($uid, $mod)";
		}
		$db->execute($query);
	}
	
	private function getDbConnection() {
		require_once($GLOBALS["DIR_DATA"] . "database.php");
		return new Database();
	}
	
	/**
	 * Step 5: Show what to do next and where to login.
	 */
	function finalize() {
		Writing::h1("Was es noch zu tun gibt...");
		
		Writing::p("Du hast es geschafft: Die Installation ist nun abgeschlossen!
					So startest du richtig mit BNote:");
		
		$bnotePath = $_SERVER["SCRIPT_NAME"];
		$bnotePath = str_replace("install.php", "", $bnotePath);
		$system_url = $_SERVER["HTTP_ORIGIN"] . $bnotePath . "main.php?mod=login";
		?>
		<ul style="list-style-type: disc;">
			<li style="margin-left: 20px; margin-bottom: 5px;">Melde dich unter <a href="<?php echo $system_url; ?>"><?php echo $system_url; ?></a> an.</li>
			<li style="margin-left: 20px; margin-bottom: 5px;">Du bist jetzt Administrator und hast Zugriff auf das gesamte System. Gehe vorsichtig damit um!</li>
			<li style="margin-left: 20px; margin-bottom: 5px;">Gehe ins Kontaktdaten-Modul und vervollständige deine Kontaktdaten.</li>
			<li style="margin-left: 20px; color: red; font-weight: bold">Lösche das install.php Script aus dem BNote-Ordner
				um niemandem unbefugten Zutritt zu verschaffen!
			</li>
		</ul>
		<br/><br/>
		<?php
		$login = new Link("main.php?mod=login", "Zur Anmeldung");
		$login->addIcon("arrow_right");
		$login->write();
	}
	
	/************** HELPERS ****************/
	
	private function screen() {
		?>
		<!DOCTYPE html>
		<HTML lang="de">
		<HEAD>
			<title>BNote | Installation</title>
 			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			
			<LINK href="style/css/!reset.css" rel="StyleSheet" type="text/css">
			<LINK href="style/css/gui.css" rel="StyleSheet" type="text/css">
			<LINK href="style/css/ids.css" rel="StyleSheet" type="text/css">
		</HEAD>
		<BODY>	
				
		<!-- Banner -->
		<div id="banner">
			<div id="bannerContent">
				<div id="logoBanner">
					<a href="/">
			 			<img src="style/images/BNote_Logo_white_on_blue_44px.png" />	
			 		</a>	
				 </div>
				
				<div id="CompanyName">BNote Installation</div>
		 	   
					</div> 
		</div>
		<!-- Content Area -->
		<div id="content_container">
			<!-- Navigation -->
		<div id="navigation">
		
			<a class="navi" href="?step=welcome"><div class="navi_item_selected">Installation</div></a>
			<a class="navi" href="main.php?mod=login"><div class="navi_item">Login</div></a>
			<a class="navi" href="main.php?mod=whyBNote"><div class="navi_item">Warum BNote?</div></a>
			<a class="navi" href="main.php?mod=terms"><div class="navi_item">Nutzungs-bedingungen</div></a>
			<a class="navi" href="main.php?mod=impressum"><div class="navi_item">Impressum</div></a>
		</div>
			<div id="content_insets">
				<div id="content">
					<?php
					// routing
					if(isset($_GET["step"])) {
						$this->$_GET["step"]();
					}
					else {
						$this->welcome();
					}
					?>
				</div>
			</div>
		</div>
				
		</BODY>
		
		</HTML>
		<?php
	}
	
	private function next($step) {
		$lnk = new Link("?step=$step", "Weiter");
		$lnk->addIcon("arrow_right");
		$lnk->write();
	}
}

// run the installation when calling this script
new Installation();

?>