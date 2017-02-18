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
			$this->getView()->$sub();
		}
		else {
			$this->getView()->start();
		}
	}
}