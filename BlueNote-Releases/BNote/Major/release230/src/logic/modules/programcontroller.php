<?php

/**
 * Controller for program methods in concert module.
 * @author matti
 *
 */
class ProgramController extends DefaultController {
	
	function start() {
		if(isset($_GET['sub'])) {
			$this->getView()->$_GET['sub']();
		}
		else {
			$this->getView()->start();
		}
	}
}