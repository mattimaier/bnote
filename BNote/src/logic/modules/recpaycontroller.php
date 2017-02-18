<?php

class RecpayController extends DefaultController {

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