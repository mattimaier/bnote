<?php
/**
 * Center for control and coordination
 **/
class Controller {

	private $moduleCtrl;
	private $moduleView;
	private $moduleData;
	
	function __construct() {

		global $system_data;

		# Check for Permission
		if(!$system_data->userHasPermission($system_data->getModuleId())) {
			$req_view = urlencode($_SERVER["QUERY_STRING"]);
			header("location: main.php?mod=login&fwd=$req_view");
		}

		# Check all $_GET attributes for attack
		foreach($_GET as $key => $value) {
			if(!preg_match("/^[[:alnum:]" . Regex::$SPECIALCHARACTERS . "\.\,\_\-\%\ \/\'\(\)]{1,255}$/", $value)
					&& !(isset($_GET["mod"]) && $_GET["mod"] == "login" && $key == "fwd")) {
				new BNoteError("Es wurde ein vermeintlicher Angriff festgestellt.<br>
						Sollte diese Meldung weiterhin auftreten, wenden Sie sich bitten an Ihren Systemadministrator.");
			}
		}
		# Start Module
		$modName = strtolower($system_data->getModuleTitle(-1, false));
		$loginModules = array("home", "login", "logout", "forgotpassword", "registration", "whybnote", "terms", "impressum", "gdpr", "extgdpr");
		if(!is_numeric($system_data->getModuleId()) || in_array(strtolower($modName), $loginModules)) {
			$modName = "login";
		}
		
		// include preliminaries
		require_once $GLOBALS['DIR_DATA'] . "fieldtype.php";

		// include abstract classes
		require_once $GLOBALS['DIR_DATA'] . "abstractdata.php";
		require_once $GLOBALS["DIR_DATA"] . "abstractlocationdata.php";
		require_once $GLOBALS['DIR_PRESENTATION'] . "abstractview.php";
		require_once $GLOBALS['DIR_PRESENTATION'] . "crudview.php";
		require_once $GLOBALS['DIR_PRESENTATION'] . "crudrefview.php";
		require_once $GLOBALS['DIR_PRESENTATION'] . "crudreflocationview.php";

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
		$this->moduleCtrl = new $controllerClass();
		$this->moduleData = new $dataClass();
		$this->moduleView = new $viewClass($this->moduleCtrl);
		$this->moduleCtrl->setData($this->moduleData);
		$this->moduleCtrl->setView($this->moduleView);
	}

	public function getController() {
		return $this->moduleCtrl;
	}
	
	public function getData() {
		return $this->moduleData;
	}
	
	public function getView() {
		return $this->moduleView;
	}
}

?>
