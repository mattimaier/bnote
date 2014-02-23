<?php

/**
 * Custom controller for Repertoire module, because Genre is a submodule.
 * @author Matti
 *
 */
class RepertoireController extends DefaultController {
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "genre") {
			$this->genre();
		}
		else {
			parent::start();
		}
	}
	
	private function genre() {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "genredata.php";
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "genreview.php";
		
		$ctrl = new DefaultController();
		$data = new GenreData();
		$view = new GenreView($ctrl);
		$ctrl->setData($data);
		$ctrl->setView($view);
		
		if(isset($_GET["func"])) {
			$view->$_GET["func"]();
		}
		else {
			$view->start();
		}
	}
	
}

?>