<?php

/**
 * Special controller for start module.
 * @author matti
 *
 */
class StartController extends DefaultController {

	public function start() {
		if(isset($_GET['mode'])) {
			if($_GET['mode'] == "saveParticipation") {
				$this->saveParticipation();
			}
			else {
				$this->getView()->$_GET['mode']();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	private function saveParticipation() {
		if(isset($_GET["action"]) && ($_GET["action"] == "maybe" || $_GET["action"] == "no")
				&& (!isset($_POST["explanation"]) || $_POST["explanation"] == "")) {
			// show reason view
			$this->getView()->askReason($_GET["obj"]);
		}
		else {
			// map parameters (this is necessary due to old implementation)
			if($_GET["obj"] == "rehearsal") {
				$_GET["rid"] = $_GET["id"];
				$_POST["rehearsal"] = $_GET["id"];
			}
			else if($_GET["obj"] == "concert") {
				$_GET["cid"] = $_GET["id"];
				$_POST["concert"] = $_GET["id"];
			}
			$_GET["status"] = $_GET["action"];
			
			$this->getData()->saveParticipation();
			$this->getView()->start();
		}
	}
}

?>