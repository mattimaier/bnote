<?php

class RecpayController extends DefaultController {

	function start() {
		if(isset($_GET['sub'])) {
			$this->getView()->$_GET['sub']();
		}
		else {
			$this->getView()->start();
		}
	}
	
}

?>