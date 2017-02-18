<?php

/**
 * Template class for all module controllers.
 * @author matti
 *
 */
class DefaultController {
	
	/**
	 * View of the module.
	 * @var AbstractView
	 */
	private $view;
	
	/**
	 * Data Access Object.
	 * @var AbstractData
	 */
	private $data;
	
	/**
	 * Entry point of module.
	 * Controls the flow of a module.
	 */
	public function start() {
		if(!isset($this->view)) {
			echo "No view.";
		}
		else {
			if(isset($_GET['mode'])) {
				$mode = $_GET['mode'];
				$this->view->$mode();
			}
			else {
				$this->view->start();
			}
		}
	}
	
	public function setView($view) {
		$this->view = $view;
	}
	
	public function getView() {
		return $this->view;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData() {
		return $this->data;
	}
}