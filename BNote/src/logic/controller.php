<?php
/**
 * Center for control and coordination within the IS
**/
class Controller {

 function __construct() {

  global $system_data;

  # Check for Permission
  if(!$system_data->userHasPermission($system_data->getModuleId())) {
  	echo "<b>Keine Berechtigung</b><br>Sie haben keine Berechtigung dieses Modul zu benutzen.";
  	exit();
  }

  # Check all $_GET attributes for attack
  foreach($_GET as $key => $value) {
  	if(!preg_match("/^[[:alnum:]\.\_\-\%\ \/\'\(\)]{1,255}$/", $value)) {
  		new Error("Es wurde ein vermeintlicher Angriff festgestellt.<br>
  					Sollte diese Meldung weiterhin auftreten, wenden Sie sich bitten an Ihren Systemadministrator.");
  	}
  }
  # Start Module
  if(!is_numeric($system_data->getModuleId())) {
  	$modName = "login";
  }
  else {
  	$modName = strtolower($system_data->getModuleTitle());
  }
  
  // include preliminaries
  require $GLOBALS['DIR_DATA'] . "fieldtype.php";
  
  // include abstract classes
  require($GLOBALS['DIR_DATA'] . "abstractdata.php");
  require($GLOBALS['DIR_PRESENTATION'] . "abstractview.php");
  require($GLOBALS['DIR_PRESENTATION'] . "crudview.php");
  require($GLOBALS['DIR_PRESENTATION'] . "crudrefview.php");
  
  // check whether there is an individual controller, if not go by default
  require($GLOBALS['DIR_LOGIC'] . "defaultcontroller.php");
  $modCtrlPath = $GLOBALS["DIR_LOGIC_MODULES"] . $modName . "controller.php";
  $individualController = false;
  if(file_exists($modCtrlPath)) {
  	require($modCtrlPath);
  	$individualController = true;
  }
  
  // include module classes
  require($GLOBALS['DIR_DATA_MODULES'] . $modName . "data.php");
  require($GLOBALS['DIR_PRESENTATION_MODULES'] . $modName . "view.php");
  
  // build class names
  $className = ucfirst($modName);
  
  if($individualController) {
  	$controllerClass = $className . "Controller";
  }
  else {
  	$controllerClass = "DefaultController";
  }
  
  $dataClass = $className . "Data";
  $viewClass = $className . "View";
  
  // build module
  $ctrl = new $controllerClass();
  $data = new $dataClass();
  $view = new $viewClass($ctrl);
  $ctrl->setData($data);
  $ctrl->setView($view);
  
  // start module
  $ctrl->start();
 }

 }

?>
