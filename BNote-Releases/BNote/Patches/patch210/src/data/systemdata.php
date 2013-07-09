<?php

/**
 * Manages System Data
**/
include "dirs.php";

require("regex.php");
require("database.php");

class Systemdata {

 public $dbcon;
 public $regex;

 private $cfg_system;
 private $cfg_company;
 private $current_modid;
 
 private $user_module_permission;
 private $modulearray;

 function __construct() {
  if(!isset($_GET["mod"])) $this->current_modid = 0;
  else $this->current_modid = $_GET["mod"];

  $this->cfg_system = new XmlData("config/config.xml", "Software");
  $this->cfg_company = new XmlData("config/company.xml", "Company");

  $this->dbcon = new Database();
  $this->regex = new Regex();

  if($_SESSION["user"] > 0) {
   $this->user_module_permission = $this->getUserModulePermissions();
  }
 }

 /* GETTER */
 /**
  * Return the current module's id
  */
 public function getModuleId() {
  return $this->current_modid;
 }

 /**
  * Return the current module's title
  */
 public function getModuleTitle() {
  return $this->dbcon->getCell("module", "name", "id = " . $this->current_modid);
 }

 public function getModuleDescriptor() {
  return $this->dbcon->getCell("module", "descriptor", "modulId = " . $this->current_modid);
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
 	$query = "SELECT surname, name FROM contact c, user u WHERE u.id = " . $_SESSION["user"];
 	$query .= " AND c.id = u.contact";
 	$un = $this->dbcon->getRow($query);
 	return $un["name"] . " " . $un["surname"];
 }
 
 /**
  * Returns an array with the module-ids the current user has permission for
  */
 public function getUserModulePermissions() {
 	$ret = array();
 	
 	$query = "SELECT module FROM privilege WHERE user = " . $_SESSION["user"];
 	$res = mysql_query($query);
 	if(!$res) new Error("The database query to retrieve the privileges failed.");
 	if(mysql_num_rows($res) == 0) new Error("You don't have sufficient privileges to access this system. Please contact your system administrator.");
 	
 	while($row = mysql_fetch_array($res)) {
 		array_push($ret, $row["module"]);
 	}
 	return $ret;
 }
 
 public function userHasPermission($modulId) {
 	return in_array($modulId, $this->user_module_permission);
 }

 /**
  * Returns an array with all modules: id => name
  */
 public function getModuleArray() {
 	if(isset($this->modulearray) && count($this->modulearray) > 0) {
 		return $this->modulearray;
 	}

  $mods = array();

  $query = "SELECT id, name FROM module ORDER BY id";
  $res = mysql_query($query);
  if(!$res) new Error("Die Datenbankabfrage schlug fehl.");
  if(mysql_num_rows($res) == 0) new Error("Datenbankfehler. Es muss mindestens ein Modul eingetragen sein.");

  while($row = mysql_fetch_array($res)) {
   $mods[$row["id"]] = $row["name"];
  }

  $this->modulearray = $mods;
  return $mods;
 }
 
 /**
  * Returns the name of the module with the given id
  */
 public function nameOfModule($id) {
 	return $this->modulearray[$id];
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
 	return $this->cfg_system->getParameter("StartModule");
 }

 /**
  * Holds the permissions a user has when created.
  * @return The IDs of the modules.
  */
 public function getDefaultUserCreatePermissions() {
 	/*
 	 * AT LEAST ONE PERMISSION HAS TO BE SPECIFIED!!!
 	 */
 	return array(
 		$this->getStartModuleId(), 9, 10, 12, 13
 	);
 }
 
 /**
  * Returns the URL where the system is reachable.
  */
 public function getSystemURL() {
 	return $this->cfg_system->getParameter("URL");
 }
 
 /**
  * Returns the full manual path.
  */
 public function getManualPath() {
 	return $this->cfg_system->getParameter("Manual");
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
 	$mua = $this->cfg_system->getParameter("ManualUserActivation");
 	if(strtolower($mua) == "true") return false;
 	else return true;
 }
}

?>