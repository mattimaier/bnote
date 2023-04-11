<?php

/**
 * Manages System Data
**/
require_once "regex.php";
require_once "database.php";

class Systemdata {

 public $dbcon;
 public $regex;

 private $cfg_system;
 private $cfg_company;
 private $cfg_dynamic;
 private $current_modid;
 
 private $user_module_permission;
 
 private $version;
 private $dir_prefix;
 
 private $theme;
 private $logoFilename;

	/**
	 * Creates a new system data object.
	 *
	 * @param String $dir_prefix
	 *        	Prefix for configuration files, e.g. "../../".
	 */
	function __construct($dir_prefix = "") {
		if (! isset($_GET["mod"])) {
			$this->current_modid = 0;
		} else {
			$this->current_modid = $_GET["mod"];
		}

		$this->cfg_system = new XmlData($dir_prefix . "config/config.xml", "Software");
		$this->cfg_company = new XmlData($dir_prefix . "config/company.xml", "Company");

		$this->dbcon = new Database();
		$this->regex = new Regex();
		$this->version = "";
		$this->dir_prefix = $dir_prefix;
		$this->user_module_permission = NULL;

		$this->initUserPermissions();

		// make sure dynamic configuration exists already
		$tabs = Database::flattenSelection($this->dbcon->getSelection("SHOW TABLES"), 0);
		if (in_array("configuration", $tabs)) {
			$this->cfg_dynamic = $this->getDynamicConfiguration();
		}
	}

	public function initUserPermissions() {
		if ($this->user_module_permission == NULL) {
			$this->user_module_permission = $this->getUserModulePermissions();
		}
	}

 /**
  * @param name Name of a module, e.g. "Proben".
  * @return Integer The current module's id.
  */
 public function getModuleId($name = null) {
 	if($name == null) {
 		return $this->current_modid;
 	}
 	else {
 		$mods = $this->getModuleArray();
 		foreach($mods as $id => $modRow) {
 			if($name == $modRow["name"]) return $id;
 		}
 		return 0;
 	}
 }

 /**
  * Retrieves the name of the (current) module.
  * @param Module ID.
  * @param bool $translated By default true - returns the translated name.
  * @return String The title of the module.
  */
 public function getModuleTitle($id = -1, $translated = true) {
 	$modId = ($id < 1) ? $this->current_modid : $id;
 	if(!is_numeric($modId)) {
 		$modId = $this->dbcon->colValue("SELECT id FROM module WHERE lower(name) = ?", "id", array(array("s", strtolower($modId))));
 	}
 	
 	// get module's name
 	$mods = $this->getModuleArray();
 	$name = $mods[$modId]["name"];
 	if($translated) {
 		return Lang::txt("navigation_$name");
 	}
 	return $name;
 }
	
	public function getModule($id) {
		$modId = ($id < 1) ? $this->current_modid : $id;
		if(!is_numeric($modId)) {
			$modId = $this->dbcon->colValue("SELECT id FROM module WHERE lower(name) = ?", "id", array(array("s", strtolower($modId))));
		}
		return $this->dbcon->fetchRow("SELECT * FROM module WHERE id = ?", array(array("i", $modId)));
	}

	/**
	 * Return the title of the company who owns the system
	 */
	public function getCompany() {
		return $this->cfg_company->getParameter("Name");
	}

	/**
	 * Returns the full name of the current user.
	 */
	public function getUsername() {
		$query = "SELECT CONCAT(name, ' ', surname) as fullname FROM contact c JOIN user u ON u.contact = c.id WHERE u.id = ?";
		return $this->dbcon->colValue($query, "fullname", array(array("i", $this->getUserId())));
	}

	/**
	 * Find the current user's ID either from the session or a server-based authentication (e.g.
	 * BasicAuth).
	 *
	 * @return Integer|NULL User ID of NULL if not authenticated.
	 */
	public function getUserId() {
		if(isset($_SESSION["user"]) && $_SESSION["user"] > 0) {
			return $_SESSION["user"];
		}
		else if(isset($_SERVER["PHP_AUTH_USER"]) && strlen($_SERVER["PHP_AUTH_USER"])>2 && isset($_SERVER["PHP_AUTH_PW"])) {
			# check un/pw and get user for this username
			$un = $_SERVER["PHP_AUTH_USER"];
			require_once $this->dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
			$pw = crypt($_SERVER["PHP_AUTH_PW"], LoginController::ENCRYPTION_HASH);
			$params = array(
					array("s", $un),
					array("s", $un),
					array("s", $pw)
			);
			$query = "SELECT u.id FROM user u LEFT OUTER JOIN contact c ON u.contact = c.id 
						WHERE (u.login = ? OR c.email = ?) AND u.password = ?";
			return $this->dbcon->colValue($query, "id", $params);
		}
		return NULL;
	}
 
 /**
  * @param Integer $uid optional: User ID, by default current user.
  * @return Array An array with the module-ids the current user has permission for
  */
 public function getUserModulePermissions($uid = -1) {
 	$userId = ($uid == -1 && $this->isUserAuthenticated()) ? $this->getUserId() : $uid;
 	
 	$query = "SELECT module FROM privilege WHERE user = ?";
 	$privileges = $this->dbcon->getSelection($query, array(array("i", $userId)));
 	
 	if(!$privileges) {
 		new BNoteError(Lang::txt("Systemdata_getUserModulePermissions.error"));
 	} 
 	
 	$ret = array();
 	for($i = 1; $i < count($privileges); $i++) {
 		array_push($ret, $privileges[$i]["module"]);
 	}
 	return $ret;
 }
 
 public function userHasPermission($modulId, $uid = -1) {
 	if($modulId === "login" || (is_string($modulId) && intval(substr($modulId, 0, 2)) == $this->getModuleId("Hilfe"))) {
 		return true;
 	}
 	 	
 	$modId = (is_numeric($modulId) && $modulId < 1) ? $this->current_modid : $modulId;
 	if(!is_numeric($modulId)) {
 		$modId = $this->dbcon->colValue("SELECT id FROM module WHERE lower(name) = ?", "id", array(array("s", strtolower($modulId))));
 	}
 	$module = $this->getModuleArray()[$modId];
 	
 	// allow access to public pages always
 	if($module["category"] == "public") {
 		return true;
 	}
 	
 	// check access to restricted pages
 	if($uid == -1) {
 		$permissions = $this->user_module_permission;
 	}
 	else {
 		$permissions = $this->getUserModulePermissions($uid);
 	}
 	
 	if($permissions == null) {
 		return false;
 	}
 	
 	if($this->gdprOk($uid) == 0 && $modId != 1) {
 		return false;
 	}
 	return in_array($modId, $permissions);
 }

 /**
  * @param String $category Optional category, by default all
  * @return Array with all modules for the current situation: id => name
  */
 public function getModuleArray($category=NULL) {
 	if(!$this->isUserAuthenticated()) {
 		return $this->getInnerModuleArray("public");
 	}
 	
	return $this->getInnerModuleArray($category);
 }
 
 /**
  * @return Array with all modules of the inner system (not from the login-module).
  */
 public function getInnerModuleArray($category=NULL) {
 	$params = array();
 	$where = "";
 	if($category != NULL) {
 		$where = "WHERE category = ?";
 		array_push($params, array("s", $category));
 	}
 	$query = "SELECT * FROM module $where ORDER BY id";
 	$mods = $this->dbcon->getSelection($query, $params);
 	
 	$res = array();
 	for($i = 1; $i < count($mods); $i++) {
 		$res[$mods[$i]["id"]] = $mods[$i];
 	}
 	return $res;
 }
 
 /**
  * Returns an array with the company's full information
  */
 public function getCompanyInformation() {
 	return $this->cfg_company->getArray();
 }
 
 public function getApplicationName() {
 	return $this->cfg_system->getParameter("Name");
 }
 
 public function getStartModuleId() {
 	return "" . $this->cfg_system->getParameter("StartModule");
 }

 /**
  * Holds the permissions a user has when created.
  * @return Array The IDs of the modules.
  */
 public function getDefaultUserCreatePermissions() {
 	// get default privileges from configuration
 	$defaultMods = explode(",", $this->cfg_system->getParameter("DefaultPrivileges"));
 	array_push($defaultMods, $this->getStartModuleId());
 	
 	// make sure at least one permission is specified -> start module
 	if(count($defaultMods) < 1) {
 		return array($this->getStartModuleId());
 	}
 	
 	return $defaultMods;
 }
 
 /**
  * Returns the URL where the system is reachable.
  */
 public function getSystemURL() {
 	return $this->cfg_system->getParameter("URL");
 }
 
 /**
  * Returns true when DemoMode is on, otherwise false.
  */
 public function inDemoMode() {
 	$demoMode = $this->cfg_system->getParameter("DemoMode");
 	if(strtolower($demoMode) == "true") return true;
 	else return false;
 }
 
 /**
  * Returns true when users should be able to activate their accounts
  * by clicking on a link in an email after registration.
  */
 public function autoUserActivation() {
 	$autoActiv = $this->getDynamicConfigParameter('auto_activation');
	return (strval($autoActiv) == "1");
 }
 
 /**
  * @return Array All super users from the configuration.
  */
 public function getSuperUsers() {
 	$su = $this->cfg_system->getParameter("SuperUsers");
 	$sus = explode(",", $su);
 	if($sus[0] == "") return array();
 	return $sus;
 }
 
 /**
  * Checks whether the given user is a super user or not.
  * @param Integer $uid User ID. In case no id is given, the current user is checked. 
  * @return True when the user is a super user, otherwise false.
  */
 public function isUserSuperUser($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	return in_array($uid, $this->getSuperUsers());
 }
 
 /**
  * Checks if the contact's user is a super user.
  * @param int $cid Contact ID, default current.
  * @return Boolean If super user.
  */
 public function isContactSuperUser($cid = -1) {
 	if($cid == -1) return $this->isUserSuperUser();
 	$uid = $this->dbcon->colValue("SELECT id FROM user WHERE contact = ?", "id", array(array("i", $cid)));
 	return $this->isUserSuperUser($uid);
 }
 
 /**
  * Checks whether the given or current user is a member of the given group.<br/>
  * Default Groups are: 1=Administrators, 2=Members
  * @param Integer $groupId Group ID.
  * @param Integer $uid optional: User ID, by default current user.
  * @return True when the user is a member of the given group, otherwise false.
  */
 public function isUserMemberGroup($groupId, $uid = -1) {
 	if(!$this->isUserAuthenticated()) return false;
 	if($uid == -1) $uid = $this->getUserId();
 	if($this->isUserSuperUser($uid) && $groupId == 1) return true;
 	$query = "SELECT count(*) as n FROM contact_group cg
 		JOIN user u ON u.contact = cg.contact
 		WHERE u.id = ? AND cg.`group` = ?";
 	$n = $this->dbcon->colValue($query, "n", array(array("i", $uid), array("i", $groupId)));
 	return $n > 0;
 }
 
 /**
  * @return Array with the IDs of the super user's contacts
  */
 public function getSuperUserContactIDs() {
 	$superUsers = $this->getSuperUsers();
 	if(count($superUsers) == 0) return array();
 	$params = array();
 	$whereList = array();
 	foreach($superUsers as $uid) {
 		array_push($params, array("i", $uid));
 		array_push($whereList, "id = ?");
 	}
 	$query = "SELECT contact FROM user WHERE " . join(" OR ", $whereList);
 	$su = $this->dbcon->getSelection($query, $params);
 	return $this->dbcon->flattenSelection($su, "contact");
 }
 
 /**
  * Checks if the user is part of the admin group.<br/>
  * <i>Utility method only</i>
  * @param int $uid Optional user ID, default current user.
  */
 public function isUserAdmin($uid = -1) {
 	return $this->isUserMemberGroup(1, $uid);
 }
 
 /**
  * @return String The name of the group who can edit the share module.
  * @deprecated as of 2.4.0, use grouping instead
  */
 public function getShareEditGroup() {
 	return "" . $this->cfg_system->getParameter("ShareEditGroup");
 }
 
 /**
  * @return True when the gallery management is used and should be displayed and functional, otherwise false.
  */
 public function isGalleryFeatureEnabled() {
 	$gal = $this->cfg_system->getParameter("UseGallery");
 	if($gal != null && strtolower($gal) == "true") return true;
 	else return false;
 }
 
 /**
  * @return True when the infopage/news/additional pages management is used and should be displayed and functional, otherwise false.
  */
 public function isInfopageFeatureEnabled() {
 	$gal = $this->cfg_system->getParameter("UseInfoPages");
 	if($gal != null && strtolower($gal) == "true") return true;
 	else return false;
 }
 
 /**
  * @return True when the user is not logged in, otherwise false.
  */
 public function isUserAuthenticated() {
 	return $this->getUserId() != NULL;
 }
 
 /**
  * Returns the configured instrument categories.
  * <i>Needed for registration...</i>
  * @return Array simply containing all category ids.
  */
 public function getInstrumentCategories() {
 	$catFilter = $this->dbcon->colValue("SELECT value FROM configuration WHERE param = ?", "value", array(array("s", "instrument_category_filter")));
 	$cats = explode(",", $catFilter);
 	$categories = $this->dbcon->getSelection("SELECT * FROM category");
 	$result = array();
 	for($i = 1; $i < count($categories); $i++) {
 		if($catFilter == "ALL" || in_array($categories[$i]["id"], $cats)) {
 			array_push($result, $categories[$i]["id"]);	
 		}
 	}
 	return $result;
 }
 
 /**
  * Returns the configured pages as an array.
  * @return Array format: <Page Name> => <Filename wo/ extension>
  */
 public function getConfiguredPages() { 	
 	$xml = $this->cfg_system->getXmlNode();
 	$pages = $xml->xpath("/Software/WebPages/Page");
 	$result = array();
 	foreach($pages as $page) {
 		$attribs = $page->attributes();
 		$result["".$page] = "".$attribs["file"];
 	}
 	return $result;
 }
 
 /**
  * Fetches all dynamic configuration parameters from the configuration table.
  * @return Array format: <parameter identifier> => <value>
  */
 private function getDynamicConfiguration() {
 	$res = $this->dbcon->getSelection("SELECT * FROM configuration");
 	$config = array();
 	for($i = 1; $i < count($res); $i++) {
 		$config[$res[$i]["param"]] = $res[$i]["value"];
 	}
 	return $config;
 }
 
 /**
  * Determines the value of the dynamically configured parameter.
  * @param String $parameter Identifier of the parameter.
  * @return String Value of the parameter, "untyped".
  */
 public function getDynamicConfigParameter($parameter) {
 	if($this->cfg_dynamic == NULL) {
 		$this->cfg_dynamic = $this->getDynamicConfiguration();
 	}
 	if($this->cfg_dynamic != NULL && isset($this->cfg_dynamic[$parameter])) {
 		return $this->cfg_dynamic[$parameter];
 	}
 	return NULL;
 }
 
 /**
  * Retrieves static system configuraiton parameters.
  * @param string $parameter Parameter name.
  * @return string Value of the parameter.
  */
 public function getSystemConfigParameter($parameter) {
 	return $this->cfg_system->getParameter($parameter);
 }
 
 /**
  * Get the contact ID from the user ID
  * @param int $uid User ID.
  * @return NULL|Integer Contact ID
  */
 public function getContactFromUser($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	return $this->dbcon->colValue("SELECT contact FROM user WHERE id = ?", "contact", array(array("i", $uid)));
 }
 
 /**
  * Retrieves the current user's contact in case there is one.
  * @param Integer $uid optional: User ID, by default the current user.
  * @return NULL|Array In case the user has a contact, this is returned, otherwise null.
  */
 public function getUsersContact($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	return $this->dbcon->fetchRow("SELECT c.* FROM contact c JOIN user u ON u.contact = c.id WHERE u.id = ?", array(array("i", $uid)));
 }
 
 public function gdprOk($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	$gdprOk = $this->dbcon->colValue("SELECT gdpr_ok FROM contact c JOIN user u ON u.contact = c.id WHERE u.id = ?", "gdpr_ok", array(array("i", $uid)));
 	return $gdprOk;
 }
 
 public function gdprAccept($accept) {
 	$info = $this->getUsersContact();
 	$query = "UPDATE contact SET gdpr_ok = ? WHERE id = ?";
 	$this->dbcon->prepStatement($query, array(array("i", $accept), array("i", $info["id"])));
 }
 
 /**
  * Holds the generation of the user's home directory.
  * @param Integer $uid optional: User ID, by default current user.
  * @return String Relative path to user's directory.
  */
 public function getUsersHomeDir($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	$login = $this->dbcon->colValue("SELECT login FROM user WHERE id = ?", "login", array(array("i", $uid)));
 	return $GLOBALS["DATA_PATHS"]["userhome"] . $login;
 }
 
 /**
  * Holds the generation of the group's home directory.
  * @param Integer $groupId Group ID.
  * @return String Relative path to group's directory.
  */
 public function getGroupHomeDir($groupId) {
 	$dirname = "group_" . $groupId; // name can contain spaces and other weird characters
 	return $GLOBALS["DATA_PATHS"]["grouphome"] . $dirname;
 }
 
 /**
  * Returns the path to the filehandler script.
  */
 public function getFileHandler() {
 	return $GLOBALS["DIR_DATA"] . "filehandler.php";
 }
 
 /**
  * @return True when auto activation of accounts is on, otherwise false.
  */
 public function isAutologinActive() {
 	$autoLogin = $this->getDynamicConfigParameter("auto_activation");
 	return (intval($autoLogin) === 1);
 }
 
 /**
  * @param $uid optional: User ID, by default current user.
  * @return True when the user allows email notifications, otherwise false.
  */
 public function userEmailNotificationOn($uid = -1) {
 	if($uid == -1) $uid = $this->getUserId();
 	$val = $this->dbcon->colValue("SELECT email_notification FROM user WHERE id = ? AND isActive = 1", "email_notification", 
 			array(array("i", $uid)));
 	return ($val == 1);
 }
 
 /**
  * @param Integer $cid Contact ID.
  * @return True when the user allows email notifications, otherwise false.
  */
 public function contactEmailNotificationOn($cid) {
 	if($cid == "") return false;
 	$contactsUserId = $this->dbcon->colValue("SELECT id FROM user WHERE contact = ?", "id", array(array("i", $cid)));
 	if($contactsUserId == null) return false;
 	return $this->userEmailNotificationOn($contactsUserId); 
 }
 
 /**
  * @return String BNote version
  */
 public function getVersion() {
 	if($this->version == "") {
 		$contents = file_get_contents($this->dir_prefix . "bnote_version");
 		$lines = explode("\n", $contents);
 		foreach($lines as $line) {
 			if(substr(trim($line), 0, 1) == "#") continue;
 			$this->version = $line;
 			break;
 		}
 	}
 	return $this->version;
 }
 
 /**
  * @return String Language Code to use from the settings.
  */
 public function getLang() {
 	return $this->getDynamicConfigParameter("language");
 }
 
 /**
  * @return string Name of the theme to use.
  */
 public function getTheme() {
 	if($this->theme == null) {
 		$this->theme = $this->cfg_system->getParameter("Theme");
 		if($this->theme == null) {
 			$this->theme = "default";
 		}
 	}
 	return $this->theme;
 }
 
 /**
  * @return string Filename of the logo in style/images
  */
 public function getLogoFilename() {
 	if($this->logoFilename == null) {
 		$this->logoFilename = $this->cfg_system->getParameter("Logo");
 		if($this->logoFilename == null) {
 			return "BNote_Logo_white_transparent.svg";
 		}
 	}
 	return $this->logoFilename;
 }
}

?>