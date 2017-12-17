<?php

/**
 * Controller for custom field methods in configuration module.
* @author matti
*
*/
class CustomFieldsController extends DefaultController {

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