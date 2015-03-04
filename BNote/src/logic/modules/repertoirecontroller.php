<?php

/**
 * Custom controller for Repertoire module, because Genre is a submodule.
 * @author Matti
 *
 */
class RepertoireController extends DefaultController {
	
	private $genreView;
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "genre") {
			$this->genre();
		}
		else {
			parent::start();
		}
	}
	
	private function initGenre() {
		if($this->genreView == null) {
			require_once $GLOBALS["DIR_DATA_MODULES"] . "genredata.php";
			require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "genreview.php";
			
			$ctrl = new DefaultController();
			$data = new GenreData();
			$this->genreView = new GenreView($ctrl);
			$ctrl->setData($data);
			$ctrl->setView($this->genreView);
		}
	}
	
	function getGenreView() {
		$this->initGenre();
		return $this->genreView;
	}
	
	private function genre() {
		$this->initGenre();
		if(isset($_GET["func"])) {
			$this->genreView->$_GET["func"]();
		}
		else {
			$this->genreView->start();
		}
	}
	
}

?>