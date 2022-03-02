<?php

/*******************************
 * BNote Application Interface *
 *******************************/

// connect to application
$dir_prefix = "../../";
global $dir_prefix;

require_once $dir_prefix . "dirs.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "abstractlocationdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "mitspielerdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "locationsdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "nachrichtendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "repertoiredata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "equipmentdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "calendardata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "aufgabendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "instrumentedata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "programdata.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC"] . "mailing.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "startcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
require_once $dir_prefix . "lang.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");

require_once $dir_prefix . $GLOBALS["DIR_EXPORT"] . "BNoteApiInterface.php";
require_once $dir_prefix . $GLOBALS["DIR_EXPORT"] . "BNoteApiImpl.php";


class BNoteApi {
	
	private $bnote;
	
	function __construct() {
		$this->bnote = new BNoteApiImpl();
	}
	
	public function onRequest() {
		// set response content type header
		header("Content-Type: application/json");
		
		// check authentication
		if(!isset($_SERVER["PHP_AUTH_USER"])) {
			session_start();
		}
		if(!$this->bnote->getSysdata()->isUserAuthenticated()) {
			http_response_code(403);
			return json_encode(array("success" => false, "message" => "Unauthorized"));
		}
		
		// route based on requested function
		if(!isset($_GET["func"])) {
			http_response_code(400);
			return json_encode(array("success" => false, "message" => "Please specify a 'func' query parameter"));
		}
		$function = $_GET["func"];
		
		// check parameters
		$reflectionClazz = new ReflectionClass(BNoteApiImpl::class);
		if(!$reflectionClazz->hasMethod($function)) {
			http_response_code(400);
			return json_encode(array("success" => false, "message" => "Unknown method $function"));
		}
		$method = $reflectionClazz->getMethod($function);
		$params = $method->getParameters();
		if(count($params) > 0) {
			$missingParams = array();
			$vals = array();
			foreach($params as $param) {
				// make sure it exists as POST or GET attribute
				$pname = $param->getName();
				if(!isset($_GET[$pname]) && !isset($_POST[$pname])) {
					// if uid is not set, then use the current user
					if($pname == "uid") {
						array_push($vals, $this->bnote->getSysdata()->getUserId());
					}
					else {
						array_push($missingParams, $pname);
					}
					continue;
				}
				if(isset($_GET[$pname])) {
					array_push($vals, $_GET[$pname]);
				}
				else {
					array_push($vals, $_POST[$pname]);
				}
			}
			if(count($missingParams) > 0) {
				http_response_code(412);
				return json_encode(array("success" => false, "message" => "Missing parameters: " . join(", ", $missingParams)));
			}
			$body = $this->bnote->$function(...$vals);
		}
		else {
			$body = $this->bnote->$function();
		}
		return json_encode($body);
	}
}

// main
$api = new BNoteApi();
echo $api->onRequest();
