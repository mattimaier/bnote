<?php

/**
 * Controller for instrument methods in configuration module.
 * @author matti
 *
 */
class InstrumenteController extends DefaultController {

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

?>