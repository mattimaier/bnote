<?php

/*
 * - Read from the post
 * - Validate input
 * - Validate access (token in DB)
 * - create database entries for all executions (no unlimited executions)
 * - insert unique hostnames in separate table
 */

class TriggerPublicInterface {
	
	private $dao;
	private $input_data;
	
	public static function error($code=500, $message) {
		http_response_code($code);
		echo $message;
		exit($code);
	}
	
	/**
	 * Singleton for database access in lazy way.
	 * @return TriggerDB
	 */
	private function getData() {
		if($this->dao == null) {
			require_once("triggerdb.php");
			$this->dao = new TriggerDB();
		}
		return $this->dao;
	}
	
	public function readInput() {
		// read from $_POST array
		$input_raw = file_get_contents('php://input');
		$data = json_decode($input_raw);
		
		if($this->isValidInput($data)) {
			$this->input_data = $data;
		}
		else {
			TriggerPublicInterface::error(400, "Invalid data.");
		}
	}
	
	private function isValidInput($data) {
		if(!isset($data->oid) || !isset($data->otype) || !isset($data->token)) {
			return false;
		}
		return (is_numeric($data->oid) && preg_match("/^[A-Z]{1}$/", $data->otype) && preg_match("/^[a-zA-Z0-9]{10,100}$/", $data->token));
	}
	
	public function validateToken() {
		$token = $this->getData()->getSetting("token");
		if($this->input_data->token != $token) {
			TriggerPublicInterface::error(403, "Invalid token.");
		}
	}
	
	public function createTrigger() {
		//TODO implement
	}
	
	public function run() {
		$this->readInput();
		$this->validateToken();
		$this->createTrigger();
	}
}

// main
$processor = new TriggerPublicInterface();
$processor->run();

?>