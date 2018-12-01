<?php

/**
 * @autor Matti Maier
 * This is an installation wizzard to install BNote. 
 */
require_once("dirs.php");
require_once("lang.php");
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
					Daher kannst du diesen Schritt überspringen. Zum Ändern der Grunddaten passe bitte die Datei BNote/config/company.xml an.");
			$this->next("databaseConfig");
		}
		else {
			Writing::p("Bitte gebe die Kontaktdaten deiner Band ein.");
			
			$form = new Form("Band Konfiguration", "?step=databaseConfig&func=process&last=companyConfig");
			$form->addElement("Bandname", new Field("Name", "", FieldType::CHAR));
			$form->addElement("Stra&szlig;e", new Field("Street", "", FieldType::CHAR));
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
			new BNoteError("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
		}
	}
	
	private function write_appConfig() {
		// write config.xml if it does not exist already
		if(!file_exists("config/config.xml")) {
			$bnotePath = $_SERVER["SCRIPT_NAME"];
			$bnotePath = str_replace("install.php", "", $bnotePath);
			$system_url = $_SERVER["HTTP_HOST"] . $bnotePath;
				
			$fileContent = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
			<Software Name=\"BNote\">
		
			<!-- ID of the Start module -->
			<StartModule>1</StartModule>
		
			<!-- URL of the BNote system -->
			<URL>$system_url</URL>
		
			<!-- E-mail-address of the administrator -->
			<Admin>" . $_POST["Mail"] . "</Admin>
		
			<!-- True when this is a demo system with deactived mailing function, otherwise false (default). -->
			<DemoMode>false</DemoMode>
		
			<!-- The user IDs of all super users whos credentials will not be shown on the website.
			This is a comma separated list without spaces.
			-->
			<SuperUsers></SuperUsers>
		
			<!-- Default Permissions for a new user. Comma separated list of user IDs without spaces. -->
			<DefaultPrivileges>9,10,12,13,14</DefaultPrivileges>
		
			<!-- True when the gallery management is used
			and should be displayed and functional, otherwise false. -->
			<UseGallery>True</UseGallery>
		
			<!-- True when the infopage/news/additional pages management is used
			and should be displayed and functional, otherwise false. -->
			<UseInfoPages>True</UseInfoPages>
		
			<!-- Theme Name -->
			<Theme>default</Theme>
			
			<!-- Logo, BNote's Logo by default. Please put your logo in BNote/style/images -->
			<Logo>BNote_Logo_white_transparent_44px.png</Logo>
					
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
			new BNoteError("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
			}
		}
	}
	
	/**
	 * Step 3: Ask for database configuration if not present.
	 */
	function databaseConfig() {
		// before getting to the database configuration, make sure to write the app config, if necessary
		$this->write_appConfig();
		
		// continue with database configuration
		Writing::h1("Datenbank Konfiguration");
		
		if(file_exists("config/database.xml")) {
			new Message("Datenbank Konfiguration bereits vorhanden", "Es wurde erkannt, dass du bereits eine Datenbank Konfiguration angelegt hast.
					Daher kannst du diesen Schritt überspringen.");
			$this->next("adminUser");
			//TODO ask the user whether to install the database content or use the one present in the db
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
			new BNoteError("Die Konfiguration konnte nicht geschrieben werden. Bitte stelle sicher, dass BNote in das Verzeichnis config/ schreiben kann.");
		}
		else {
			// run database initialization
			$db = $this->getDbConnection();
			
			$queries = array();
			array_push($queries, 
					"CREATE TABLE IF NOT EXISTS `address` (
					  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `street` varchar(45) NOT NULL,
					  `city` varchar(45) NOT NULL,
					  `zip` varchar(45) DEFAULT NULL,
					  `state` varchar(50) DEFAULT NULL,
					  `country` varchar(45) NOT NULL,
					  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `category` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(60) DEFAULT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `comment` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`author` int(11) NOT NULL,
					`created_at` datetime NOT NULL,
					`otype` char(2) NOT NULL,
					`oid` int(10) unsigned NOT NULL,
					`message` text,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `composer` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(45) NOT NULL,
					`notes` text,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `concert` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`begin` datetime NOT NULL,
					`end` datetime DEFAULT NULL,
					`title` VARCHAR(150),
					`approve_until` datetime,
					`location` int(10) unsigned NOT NULL,
					`program` int(10) unsigned DEFAULT NULL,
					`notes` text,
					`contact` int(11),
					`outfit` int(11),
					`meetingtime` datetime,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `concert_user` (
					`concert` int(11) NOT NULL,
					`user` int(11) NOT NULL,
					`participate` tinyint(4) NOT NULL,
					`reason` varchar(200) DEFAULT NULL,
					PRIMARY KEY (`concert`,`user`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `configuration` (
					`param` varchar(100) NOT NULL,
					`value` text NOT NULL,
					`is_active` int(1) NOT NULL,
					PRIMARY KEY (`param`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `contact` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`surname` varchar(50) DEFAULT NULL,
					`name` varchar(50) DEFAULT NULL,
					`nickname` varchar(20) DEFAULT NULL,
					`phone` varchar(45) DEFAULT NULL,
					`fax` varchar(45) DEFAULT NULL,
					`mobile` varchar(30) DEFAULT NULL,
					`business` varchar(30) DEFAULT NULL,
					`email` varchar(100) DEFAULT NULL,
					`web` varchar(150) DEFAULT NULL,
					`notes` text,
					`address` int(10) unsigned NOT NULL,
					`status` varchar(10) DEFAULT NULL,
					`instrument` int(11) DEFAULT NULL,
					`birthday` DATE,
					`gdpr_ok` int(1) default 0,
					`gdpr_code` varchar(255) DEFAULT NULL,
					`is_conductor` int(1) default 0,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `contact_group` (
					`contact` int(11) NOT NULL DEFAULT '0',
					`group` varchar(50) NOT NULL DEFAULT '',
					PRIMARY KEY (`contact`,`group`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `gallery` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(50) NOT NULL,
					`previewimage` int(10) unsigned DEFAULT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `galleryimage` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`filename` varchar(200) NOT NULL,
					`name` varchar(100) NOT NULL,
					`description` text,
					`gallery` int(10) unsigned NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `genre` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(45) NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `group` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(50) NOT NULL,
					`is_active` int(1) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `infos` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`author` int(11) NOT NULL,
					`createdOn` datetime NOT NULL,
					`editedOn` datetime DEFAULT NULL,
					`title` varchar(200) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `author` (`author`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `instrument` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(60) NOT NULL,
					`category` int(10) unsigned NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `location` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(60) NOT NULL,
					`notes` text,
					`address` int(10) unsigned NOT NULL,
					`location_type` int(11) DEFAULT 1,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `module` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `privilege` (
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`user` int(11) unsigned NOT NULL,
					`module` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `program` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`notes` text,
					`isTemplate` tinyint(1) DEFAULT NULL,
					`name` varchar(80) NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `program_song` (
					`id` int(11) PRIMARY KEY AUTO_INCREMENT,
					`program` int(10) unsigned NOT NULL,
					`song` int(10) unsigned NOT NULL,
					`rank` int(11) DEFAULT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsal` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`begin` datetime NOT NULL,
					`end` datetime DEFAULT NULL,
					`approve_until` datetime,
					`notes` text,
					`location` int(10) unsigned NOT NULL,
					`serie` int(11),
					`conductor` int(11),
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsalphase` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(100) NOT NULL,
					`begin` date NOT NULL,
					`end` date NOT NULL,
					`notes` text,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsalphase_concert` (
					`rehearsalphase` int(11) NOT NULL,
					`concert` int(11) NOT NULL,
					PRIMARY KEY (`rehearsalphase`,`concert`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsalphase_contact` (
					`rehearsalphase` int(11) NOT NULL,
					`contact` int(11) NOT NULL,
					PRIMARY KEY (`rehearsalphase`,`contact`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsalphase_rehearsal` (
					`rehearsalphase` int(11) NOT NULL,
					`rehearsal` int(11) NOT NULL,
					PRIMARY KEY (`rehearsalphase`,`rehearsal`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsal_song` (
					`rehearsal` int(11) NOT NULL,
					`song` int(11) NOT NULL,
					`notes` varchar(200) DEFAULT NULL,
					PRIMARY KEY (`rehearsal`,`song`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsal_user` (
					`rehearsal` int(11) NOT NULL,
					`user` int(11) NOT NULL,
					`participate` tinyint(4) NOT NULL,
					`reason` varchar(200) DEFAULT NULL,
					PRIMARY KEY (`rehearsal`,`user`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `song` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`title` varchar(60) NOT NULL,
					`length` time DEFAULT NULL,
					`bpm` int(3),
					`music_key` varchar(40), 
					`notes` text,
					`genre` int(10) unsigned NOT NULL,
					`composer` int(10) unsigned NOT NULL,
					`status` int(10) unsigned NOT NULL,
					`setting` varchar(300),
					`is_active` int(1) default 1,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `song_solist` (
					`song` int(11) NOT NULL,
					`contact` int(11) NOT NULL,
					`notes` varchar(200) DEFAULT NULL,
					PRIMARY KEY (`song`,`contact`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
			);
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `status` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(45) NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `task` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(50) NOT NULL,
					`description` text,
					`created_at` datetime NOT NULL,
					`created_by` int(11) NOT NULL,
					`due_at` datetime DEFAULT NULL,
					`assigned_to` int(11) DEFAULT NULL,
					`is_complete` int(1) NOT NULL,
					`completed_at` datetime DEFAULT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `user` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`isActive` tinyint(1) NOT NULL DEFAULT '1',
					`login` varchar(45) NOT NULL,
					`password` varchar(60) NOT NULL,
					`lastlogin` datetime DEFAULT NULL,
					`contact` int(10) unsigned NOT NULL,
					`pin` int(6) DEFAULT NULL,
					`email_notification` int(1) DEFAULT 1,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `vote` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(100) NOT NULL,
					`author` int(11) NOT NULL,
					`is_multi` int(1) NOT NULL,
					`is_date` int(1) NOT NULL,
					`end` datetime NOT NULL,
					`is_finished` int(1) NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `vote_group` (
					`vote` int(11) NOT NULL,
					`user` int(11) NOT NULL,
					PRIMARY KEY (`vote`,`user`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `vote_option` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`vote` int(11) NOT NULL,
					`name` varchar(100) DEFAULT NULL,
					`odate` datetime DEFAULT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `vote_option_user` (
					`vote_option` int(11) NOT NULL,
					`user` int(11) NOT NULL,
					`choice` int(1) DEFAULT 1,
					PRIMARY KEY (`vote_option`,`user`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `rehearsal_contact` (
					`rehearsal` int(11) NOT NULL,
					`contact` int(11) NOT NULL,
					PRIMARY KEY (`rehearsal`,`contact`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `concert_contact` (
					`concert` int(11) NOT NULL,
					`contact` int(11) NOT NULL,
					PRIMARY KEY (`concert`,`contact`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `account` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(100) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `booking` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					account INT(11) NOT NULL,
					bdate DATE NOT NULL,
					subject VARCHAR(100) NOT NULL,
					amount_net DECIMAL(9,2) NOT NULL,
					amount_tax DECIMAL(9,2) NOT NULL DEFAULT 0,
					btype INT(1) NOT NULL,
					otype CHAR(1),
					oid INT(11),
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `recpay` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					account INT(11) NOT NULL,
					subject VARCHAR(100) NOT NULL,
					amount_net DECIMAL(9,2) NOT NULL,
					amount_tax DECIMAL(9,2) NOT NULL DEFAULT 0,
					btype INT(1) NOT NULL,
					otype CHAR(1),
					oid INT(11),
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `equipment` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					model VARCHAR(100) NOT NULL,
					make VARCHAR(100) NOT NULL,
					name VARCHAR(100),
					purchase_price DECIMAL(9,2),
					current_value DECIMAL(9,2),
					quantity INT(10) NOT NULL DEFAULT 1,
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(100) NOT NULL,
					start DATE NOT NULL,
					end DATE NOT NULL,
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `accommodation` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					tour INT(11) NOT NULL,
					location INT(11) NOT NULL,
					checkin DATE NOT NULL,
					checkout DATE NOT NULL,
					breakfast INT(1) NOT NULL DEFAULT 0,
					lunch INT(1) NOT NULL DEFAULT 0,
					dinner INT(1) NOT NULL DEFAULT 0,
					planned_cost DECIMAL(9,2),
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `travel` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					tour INT(11) NOT NULL,
					transportation VARCHAR(50),
					num VARCHAR(100),
					departure DATETIME NOT NULL,
					departure_location VARCHAR(255),
					arrival DATETIME NOT NULL,
					arrival_location VARCHAR(255),
					planned_cost DECIMAL(9,2),
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `doctype` (
					id int(11) PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(100) NOT NULL,
					is_active INT(1) NOT NULL DEFAULT 1
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour_rehearsal` (
					tour INT(11) NOT NULL,
					rehearsal INT(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour_concert` (
					tour INT(11) NOT NULL,
					concert INT(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour_contact` (
					tour INT(11) NOT NULL,
					contact INT(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour_equipment` (
					tour INT(11) NOT NULL,
					equipment INT(11) NOT NULL,
					quantity VARCHAR(50) NOT NULL DEFAULT '',
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `tour_task` (
					tour INT(11) NOT NULL,
					task INT(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `reservation` (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					begin DATETIME NOT NULL,
					end DATETIME NOT NULL,
					name VARCHAR(100) NOT NULL,
					location INT(11),
					contact INT(11),
					notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS song_files (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					song INT(11) NOT NULL,
					filepath VARCHAR(255) NOT NULL,
					notes TEXT,
					doctype INT(11)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE location_type (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(50) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE outfit (
					id INT(11) PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(50) NOT NULL,
					description TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE customfield (
				id int(11) PRIMARY KEY AUTO_INCREMENT,
				techname VARCHAR(50) NOT NULL,
				txtdefsingle VARCHAR(50) NOT NULL,
				txtdefplural VARCHAR(50) NOT NULL,
				fieldtype VARCHAR(20) NOT NULL,
				otype CHAR(1) NOT NULL,
				public_field INT(1) DEFAULT 0
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE customfield_value (
				id int(11) PRIMARY KEY AUTO_INCREMENT,
				customfield int(11) NOT NULL,
				otype CHAR(1) NOT NULL,
				oid int(11) NOT NULL,
				intval INT,
				dblval DECIMAL(12,2),
				strval VARCHAR(255),
				dateval DATE,
				datetimeval DATETIME
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE rehearsalserie (
				id int(11) PRIMARY KEY AUTO_INCREMENT,
				name VARCHAR(200) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS `appointment` (
				id int(11) PRIMARY KEY AUTO_INCREMENT,
				begin DATETIME NOT NULL,
				end DATETIME NOT NULL,
				name VARCHAR(100) NOT NULL,
				location INT(11),
				contact INT(11),
				notes TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS `appointment_group` (
				`appointment` int(11) NOT NULL,
				`group` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			foreach($queries as $i => $query) {
				$db->execute($query);
			}
		
			// fill database with initial content
			$queries = array();
			
			array_push($queries,
					"INSERT INTO `category` (`id`, `name`) VALUES
					(1, 'Streicher'),
					(2, 'Blechbläser'),
					(3, 'Holzbläser'),
					(4, 'Rhythmusgruppe'),
					(5, 'Gesang'),
					(6, 'Dirigent'),
					(7, 'Organisation'),
					(8, 'Sonstige');");

			// simple key
			$trigger_key = date("Ymd") . "X" . date("His") . rand(1000, 9999);
			
			array_push($queries,
					"INSERT INTO `configuration` (`param`, `value`, `is_active`) VALUES
					('rehearsal_start', '18:00', 1),
					('rehearsal_duration', '120', 1),
					('default_contact_group', '2', 1),
					('auto_activation', '1', 1),
					('instrument_category_filter', 'ALL', 1),
					('share_nonadmin_viewmode', '0', 1),
					('rehearsal_show_length', '1', 1),
					('allow_participation_maybe', '1', 1),
					('allow_zip_download', '1', 1),
					('rehearsal_show_max', '5', 1),
					('updates_show_max', '5', 1),
					('language', 'de', 1),
					('discussion_on', '1', 1),
					('google_api_key', '', 1),
					('trigger_key', '" . $trigger_key . "', 1),
					('trigger_cycle_days', '3', 1),
					('trigger_repeat_count', '3', 1),
					('enable_trigger_service', '1', 1),
					('default_conductor', '', 1),
					('default_country', 'DEU', 1);");
			
			array_push($queries,
					"INSERT INTO `genre` (`id`, `name`) VALUES
					(1, 'Swing'),
					(2, 'Latin'),
					(3, 'Jazz'),
					(4, 'Traditional Jazz'),
					(5, 'Pop'),
					(6, 'Rock'),
					(7, 'Blues'),
					(8, 'Blues Rock'),
					(9, 'Metal'),
					(10, 'Klassik'),
					(11, 'Bebop'),
					(12, 'Dixyland'),
					(13, 'Free Jazz'),
					(14, 'Smooth Jazz'),
					(15, 'Instrumental Jazz'),
					(16, 'Vocal Jazz'),
					(17, 'Funk');");

			array_push($queries,
					"INSERT INTO `group` (`id`, `name`, `is_active`) VALUES
					(1, 'Administratoren', 1),
					(2, 'Mitglieder', 1),
					(3, 'Externe', 1),
					(4, 'Bewerber', 1),
					(5, 'Sonstige', 1);");
			
			// create group directories
			mkdir("data/share");
			mkdir("data/share/groups");
			mkdir("data/share/groups/group_1"); // Administrators
			mkdir("data/share/groups/group_2"); // Members
			mkdir("data/share/groups/group_3"); // Externals
			mkdir("data/share/groups/group_4"); // Applicants
			mkdir("data/share/groups/group_5"); // Others

			array_push($queries,
					"INSERT INTO `instrument` (`id`, `name`, `category`) VALUES
					(1, 'Leiter', 6),
					(2, 'Sologesang', 5),
					(3, 'Organisator', 7),
					(4, 'Klavier / ePiano', 4),
					(5, 'Orgel', 4),
					(6, 'Elektro Orgel', 4),
					(7, 'Schlagzeug', 4),
					(8, 'Kontrabass', 4),
					(9, 'E-Bass', 4),
					(10, 'Tuba', 4),
					(11, 'Posaune', 2),
					(12, 'Trompete', 2),
					(13, 'Altsaxophon', 3),
					(14, 'Tenorsaxophon', 3),
					(15, 'Bariton Saxophon', 3),
					(16, 'Sopran Saxophon', 3),
					(17, 'Klarinette', 3),
					(18, 'Bassklarinette', 3),
					(19, 'Geige', 1),
					(20, 'Bratsche', 1),
					(21, 'Violoncello', 1),
					(22, 'Gambe', 8),
					(23, 'keine Angabe', 8),
					(24, 'Gitarre', 4),
					(25, 'Flügelhorn', 2),
					(26, 'Basskarinette', 3),
					(27, 'Sopran', 5),
					(28, 'Mezzo-Sopran', 5),
					(29, 'Alt', 5),
					(30, 'Tenor', 5),
					(31, 'Bass', 5),
					(32, 'Bariton', 5),
					(33, 'Countertenor', 5),
					(34, 'Background', 5),
					(35, 'Solistin Sopran', 5),
					(36, 'Solistin Alt', 5),
					(37, 'Solist Bass', 5),
					(38, 'Solist Tenor', 5),
					(39, 'Solist Bariton', 5),
					(40, 'Solist Countertenor', 5),
					(41, 'Querflöte', 3),
					(42, 'Blockflöte', 3),
					(43, 'Panflöte', 3),
					(44, 'Akkordeon', 8),
					(45, 'Althorn', 2),
					(46, 'Banjo', 8);");

			array_push($queries,
					"INSERT INTO `module` (`id`, `name`) VALUES
					(1, 'Start'),
					(2, 'User'),
					(3, 'Kontakte'),
					(4, 'Konzerte'),
					(5, 'Proben'),
					(6, 'Repertoire'),
					(7, 'Kommunikation'),
					(8, 'Locations'),
					(9, 'Kontaktdaten'),
					(10, 'Hilfe'),
					(11, 'Website'),
					(12, 'Share'),
					(13, 'Mitspieler'),
					(14, 'Abstimmung'),
					(15, 'Nachrichten'),
					(16, 'Aufgaben'),
					(17, 'Konfiguration'),
					(18, 'Probenphasen'),
					(19, 'Finance'),
					(20, 'Calendar'),
					(21, 'Equipment'),
					(22, 'Tour'),
					(23, 'Outfits'),
					(24, 'Stats');");

			array_push($queries,
					"INSERT INTO `status` (`id`, `name`) VALUES
					(1, 'Auftrittsreif'),
					(2, 'Kernrepertoire'),
					(3, 'Noten vorhanden'),
					(4, 'benötigt weitere Proben'),
					(5, 'nicht im Notenbestand'),
					(6, 'Idee');");

			array_push($queries, "INSERT INTO location_type (name) VALUES 
					('Probenräume'), ('Veranstaltungsorte'), ('Übernachtungsmöglichkeiten'), ('Studios'), ('Sonstige');");
			
			array_push($queries, "INSERT INTO `doctype` (name, is_action) VALUES 
					('Noten', 1), ('Text', 1), ('Aufnahme', 1), ('Sonstiges', 1);");
			
			foreach($queries as $i => $query) {
				$db->execute($query);
			}
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
			new BNoteError("Ungültiges Passwort. Bitte vergewissere dich, dass das Passwort mindestens 6 Zeichen hat und nicht leer ist.");
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
		$password = crypt($_POST["password"], 'BNot3pW3ncryp71oN');
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
		
		// create user directory
		mkdir("data/share/users");
		mkdir("data/share/users/" . $_POST["login"]);
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
			<style type="text/css">
			body {padding: 0px; margin: 0px;}
			</style>
			<LINK href="style/css/default/bnote.css" rel="StyleSheet" type="text/css">
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
			<div id="content_insets">
				<div id="content">
					<?php
					// routing
					if(isset($_GET["step"])) {
						$func = $_GET["step"];
						$this->$func();
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