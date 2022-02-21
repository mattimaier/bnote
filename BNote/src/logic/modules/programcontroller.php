<?php

/**
 * Controller for program methods in concert module.
 * @author matti
 *
 */
class ProgramController extends DefaultController {
	
	function start() {
		if(isset($_GET['sub'])) {
			$sub = $_GET['sub'];
			if($sub == "saveList") {
				// read JSON from body
				$inputJSON = file_get_contents('php://input');
				$input = json_decode($inputJSON, TRUE);
				$this->getData()->updateRanks($input);
				echo "OK";  // no view required, since this is a pure async ajax call
			}
			else {
				$this->getView()->$sub();
			}
		}
		else {
			$this->getView()->start();
		}
	}
}