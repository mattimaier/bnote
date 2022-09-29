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
		Writing::h1(Lang::txt("Installation_welcome.title"));
		
		Writing::p(Lang::txt("Installation_welcome.message_1"));
		
		Writing::p(Lang::txt("Installation_welcome.message_2"));
		?>
		<ul style="list-style-type: disc;">
			<li style="margin-left: 20px; margin-bottom: 5px;"><?php echo Lang::txt("Installation_welcome.message_3"); ?></li>
			<li style="margin-left: 20px; margin-bottom: 5px;"><?php echo Lang::txt("Installation_welcome.message_4"); ?></li>
			<li style="margin-left: 20px"><?php echo Lang::txt("Installation_welcome.message_5"); ?></li>
		</ul>
		
		<?php
		$this->next("companyConfig");
	}
	
	/**
	 * Step 2: Ask for company configuration if not present.
	 */
	function companyConfig() {
		Writing::h1(Lang::txt("Installation_companyConfig.title"));
		
		if(file_exists("config/company.xml")) {
			new Message("Information", Lang::txt("Installation_companyConfig.message_1"));
			echo "<br>\n";
			$this->next("databaseConfig");
		}
		else {
			Writing::p(Lang::txt("Installation_companyConfig.message_2"));
			
			$form = new Form(Lang::txt("Installation_companyConfig.Form"), "?step=databaseConfig&func=process&last=companyConfig");
			$form->addElement(Lang::txt("Installation_companyConfig.Name"), new Field("Name", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Street"), new Field("Street", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Zip"), new Field("Zip", "", FieldType::INTEGER));
			$form->addElement(Lang::txt("Installation_companyConfig.City"), new Field("City", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Country"), new Field("Country", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Phone"), new Field("Phone", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Mail"), new Field("Mail", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_companyConfig.Web"), new Field("Web", "", FieldType::CHAR));
			$form->changeSubmitButton(Lang::txt("Installation_companyConfig.submit"));
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
			new BNoteError(Lang::txt("Installation_companyConfig.Error"));
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
			<UseGallery>False</UseGallery>
		
			<!-- True when the infopage/news/additional pages management is used
			and should be displayed and functional, otherwise false. -->
			<UseInfoPages>True</UseInfoPages>
		
			<!-- Theme Name -->
			<Theme>default</Theme>
			
			<!-- Logo, BNote's Logo by default. Please put your logo in BNote/style/images -->
			<Logo>BNote_Logo_white_transparent.svg</Logo>
					
			<!-- The webpages available in the website module.
			A page tag contains an attribute \"file\" specifying the filename without the html-extension
			in the data/webpages folder and the body containing the displayed name of the page. -->
			<WebPages>
			<Page file=\"startseite\">Startseite</Page>
			</WebPages>
			</Software>";
				
			$res = file_put_contents("config/config.xml", $fileContent);
			if(!$res) {
			new BNoteError(Lang::txt("Installation_write_appConfig.Error"));
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
		Writing::h1(Lang::txt("Installation_databaseConfig.title"));
		
		if(file_exists("config/database.xml")) {
			new Message(Lang::txt("Installation_databaseConfig.message_1"));
			$this->next("adminUser");
			//TODO ask the user whether to install the database content or use the one present in the db
		}
		else {
			Writing::p(Lang::txt("Installation_databaseConfig.message_2"));
				
			$form = new Form(Lang::txt("Installation_databaseConfig.Form"), "?step=adminUser&func=process&last=databaseConfig");
			$form->addElement(Lang::txt("Installation_databaseConfig.Server"), new Field("Server", "localhost", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_databaseConfig.Port"), new Field("Port", "3306", FieldType::INTEGER));
			$form->addElement(Lang::txt("Installation_databaseConfig.Name"), new Field("Name", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_databaseConfig.User"), new Field("User", "", FieldType::CHAR));
			$form->addElement(Lang::txt("Installation_databaseConfig.Password"), new Field("Password", "", FieldType::PASSWORD));
			$form->changeSubmitButton(Lang::txt("Installation_databaseConfig.Submit"));
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
			new BNoteError(Lang::txt("Installation_process_databaseConfig.error"));
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
					`organizer` VARCHAR(200),
					`accommodation` INT(11),
					`payment` DECIMAL(12,2),
					`conditions` TEXT,
					`status` varchar(20) default 'planned',
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS `concert_equipment` (
					`concert` int(11) NOT NULL,
					`equipment` int(11) NOT NULL,
					`amount` int(10) NOT NULL DEFAULT 1,
					`notes` VARCHAR(255)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

			array_push($queries,
					"CREATE TABLE IF NOT EXISTS `concert_user` (
					`concert` int(11) NOT NULL,
					`user` int(11) NOT NULL,
					`participate` tinyint(4) NOT NULL,
					`reason` varchar(200) DEFAULT NULL,
					`replyon` datetime,
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
					`company` VARCHAR(100),
					`share_address` int(1) default 1,
					`share_phones` int(1) default 1,
					`share_birthday` int(1) default 1,
					`share_email` int(1) default 1,
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
					`rank` int(4),
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
					`icon` varchar(50),
					`category` varchar(50),
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
					`status` varchar(20) default 'planned',
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
					`replyon` datetime,
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
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS `rehearsal_group` (
				`rehearsal` int(11) NOT NULL,
				`group` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			array_push($queries, "CREATE TABLE IF NOT EXISTS `concert_group` (
				`concert` int(11) NOT NULL,
				`group` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
			
			foreach($queries as $i => $query) {
				$db->execute($query, array());
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
					('user_registration', '1', 1),
					('instrument_category_filter', 'ALL', 1),
					('share_nonadmin_viewmode', '0', 1),
					('rehearsal_show_length', '1', 1),
					('allow_participation_maybe', '1', 1),
					('allow_zip_download', '1', 1),
					('rehearsal_show_max', '5', 1),
					('updates_show_max', '5', 1),
					('concert_show_max', '5', 1),
					('appointments_show_max', '5', 1),
					('language', 'de', 1),
					('discussion_on', '1', 1),
					('google_api_key', '', 1),
					('trigger_key', '" . $trigger_key . "', 1),
					('trigger_cycle_days', '3', 1),
					('trigger_repeat_count', '3', 1),
					('enable_trigger_service', '1', 1),
					('default_conductor', '', 1),
					('default_country', 'DEU', 1),
					('currency', 'EUR', 1),
					('export_rehearsal_notes', '0', 1),
					('export_rehearsalsong_notes', '0', 1);");
			
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
					"INSERT INTO `module` (`id`, `name`, `icon`, `category`) VALUES
					(1, 'Start', 'play-circle', 'main'),
					(2, 'User', 'people', 'admin'),
					(3, 'Kontakte', 'person-video2', 'main'),
					(4, 'Konzerte', 'mic', 'main'),
					(5, 'Proben', 'collection-play', 'main'),
					(6, 'Repertoire', 'music-note-list', 'main'),
					(7, 'Kommunikation', 'envelope', 'main'),
					(8, 'Locations', 'geo-alt', 'main'),
					(9, 'Kontaktdaten', 'person-bounding-box', 'user'),
					(10, 'Hilfe', 'info-circle-fill', 'help'),
					(12, 'Share', 'folder2-open', 'main'),
					(13, 'Mitspieler', 'person-badge', 'main'),
					(14, 'Abstimmung', 'check2-square', 'main'),
					(15, 'Nachrichten', 'newspaper', 'admin'),
					(16, 'Aufgaben', 'list-task', 'main'),
					(17, 'Konfiguration', 'sliders', 'admin'),
					(18, 'Probenphasen', 'calendar-range', 'main'),
					(19, 'Finance', 'piggy-bank', 'main'),
					(20, 'Calendar', 'calendar2-week', 'main'),
					(21, 'Equipment', 'boombox', 'main'),
					(22, 'Tour', 'truck', 'main'),
					(23, 'Outfits', 'handbag', 'main'),
					(24, 'Stats', 'bar-chart', 'admin'),
					(25, 'Home', 'house', 'public'),
					(26, 'Login', 'door-open', 'public'),
					(27, 'Logout', 'box-arrow-right', 'public'),
					(28, 'ForgotPassword', 'asterisk', 'public'),
					(29, 'Registration', 'journal-plus', 'public'),
					(30, 'WhyBNote', 'question-circle', 'public'),
					(31, 'Terms', 'file-text', 'public'),
					(32, 'Impressum', 'building', 'public'),
					(33, 'Gdpr', 'bookmark-check', 'public'),
					(34, 'ExtGdpr', 'bookmark-check', 'public'),
					(35, 'Admin', 'gear-fill', 'admin');");

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
			
			array_push($queries, "INSERT INTO `doctype` (name, is_active) VALUES 
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
		Writing::h1(Lang::txt("Installation_adminUser.title"));
		
		$form = new Form(Lang::txt("Installation_adminUser.form"), "?step=finalize&func=process&last=adminUser");
		$form->addElement(Lang::txt("Installation_adminUser.login"), new Field("login", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.password"), new Field("password", "", FieldType::PASSWORD));
		$form->addElement(Lang::txt("Installation_adminUser.name"), new Field("name", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.surname"), new Field("surname", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.company"), new Field("company", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.phone"), new Field("phone", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.mobile"), new Field("mobile", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.email"), new Field("email", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.street"), new Field("street", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.zip"), new Field("zip", "", FieldType::INTEGER));
		$form->addElement(Lang::txt("Installation_adminUser.city"), new Field("city", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.state"), new Field("state", "", FieldType::CHAR));
		$form->addElement(Lang::txt("Installation_adminUser.country"), new Field("country", "DEU", FieldType::CHAR));
		
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
			new BNoteError(Lang::txt("Installation_process_adminUser.error"));
		}
		
		// get database connection
		$db = $this->getDbConnection();
		
		// create contact address
		$query = "INSERT INTO address (street, city, zip, state, country) VALUES (?,?,?,?,?)";
		$aid = $db->prepStatement($query, array(
				array("s", $_POST["street"]), 
				array("s", $_POST["city"]), 
				array("s", $_POST["zip"]),
				array("s", $_POST["state"]),
				array("s", $_POST["country"])));
		
		// create contact
		$query = "INSERT INTO contact (surname, name, company, phone, mobile, email, address, instrument, gdpr_ok, is_conductor)
				VALUES (?,?,?,?,?,?,?,?,?,?)";
		$cid = $db->prepStatement($query, array(
				array("s", $_POST["surname"]),
				array("s", $_POST["name"]), 
				array("s", $_POST["company"]),
				array("s", $_POST["phone"]),
				array("s", $_POST["mobile"]),
				array("s", $_POST["email"]),
				array("i", $aid), 
				array("i", $_POST["instrument"]), 
				array("i", 1), 
				array("i", 1)));
		
		// add the contact to the admin group (gid=1)
		$query = "INSERT INTO contact_group (contact, `group`) VALUES (?, ?)"; 
		$ugroup = $db->execute($query, array(array("i", $cid), array("i", 1)));
				
		// create user
		$password = crypt($_POST["password"], 'BNot3pW3ncryp71oN');
		$query = "INSERT INTO user (login, password, contact, isActive) VALUES (?, ?, ?, 1)";
		$uid = $db->execute($query, array(array("s", $_POST["login"]), array("s", $password), array("i", $cid)));
		
		// create default privileges plus user module privileges
		$modules = $db->getSelection("SELECT * FROM module");
		$tuples = array();
		$params = array();
		for($i = 1; $i < count($modules); $i++) {
			array_push($tuples, "(?, ?)");
			array_push($params, array("i", $uid));
			array_push($params, array("i", $modules[$i]["id"]));
		}
		$query = "INSERT INTO privilege (user, module) VALUES " . join(",", $tuples);
		$db->execute($query, $params);
		
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
		Writing::h1(Lang::txt("Installation_finalize.title"));
		
		Writing::p(Lang::txt("Installation_finalize.message"));
		
		$bnotePath = $_SERVER["SCRIPT_NAME"];
		$bnotePath = str_replace("install.php", "", $bnotePath);
		$system_url = $_SERVER["HTTP_ORIGIN"] . $bnotePath . "main.php?mod=login";
		?>
		<ul style="list-style-type: disc;">
			<li style="margin-left: 20px; margin-bottom: 5px;"><?php echo Lang::txt("Installation_finalize.message_1"); ?><a href="<?php echo $system_url; ?>"><?php echo $system_url; ?></a><?php echo Lang::txt("Installation_finalize.message_2"); ?></li>
			<li style="margin-left: 20px; margin-bottom: 5px;"><?php echo Lang::txt("Installation_finalize.message_3"); ?></li>
			<li style="margin-left: 20px; margin-bottom: 5px;"><?php echo Lang::txt("Installation_finalize.message_4"); ?></li>
			<li style="margin-left: 20px; color: red; font-weight: bold"><?php echo Lang::txt("Installation_finalize.message_5"); ?></li>
		</ul>
		<br/><br/>
		<?php
		$login = new Link("main.php?mod=login", Lang::txt("Installation_finalize.login"));
		$login->addIcon("arrow_right");
		$login->write();
	}
	
	/************** UI ****************/
	
	private function screen() {
		?>
		<!DOCTYPE html>
		<HTML lang="de">
		<HEAD>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			 
			<link rel="shortcut icon" href="favicon.png" type="image/png" />
			<link rel="icon" href="favicon.png" type="image/png" />
			  
			<link href='https://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
			 
			<link href="lib/bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet" />
			<link type="text/css" href="lib/jquery/jquery.jqplot.min.css" rel="stylesheet" /> 
			<link type="text/css" href="lib/dropzone.css" rel="stylesheet" />
			<link rel="stylesheet" href="lib/bootstrap-icons-1.8.1/font/bootstrap-icons.css" />
			<link rel="stylesheet" type="text/css" href="lib/DataTables/datatables.min.css" />
			 
			<link type="text/css" href="style/css/default/bnote.css" rel="stylesheet" />
		</HEAD>
		<BODY>	
				
		<!-- Banner -->
		<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow d-print-none">		
			<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="?mod=1">
				<img src="style/images/BNote_Logo_white_transparent.svg" alt="BNote" height="32px" id="bnote-logo" />
				<span class="d-none d-md-inline-block">BNote</span>
			</a>
		</header>
		
		<!-- Content Area -->
		<div class="container-fluid mb-3">
			<div class="row mt-3">
				<main class="col-md-12 px-md-4">
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
				</main>
			</div>
		</div>
			
			<script src="lib/bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
		</BODY>
		
		</HTML>
		<?php
	}
	
	private function next($step) {
		$lnk = new Link("?step=$step", Lang::txt("Installation_next.next"));
		$lnk->addIcon("arrow-right");
		$lnk->write();
	}
}

// run the installation when calling this script
new Installation();

?>