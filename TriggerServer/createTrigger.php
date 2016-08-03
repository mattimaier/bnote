<?php
require_once 'notificationLogger.php';

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
		NotificationLogger::error($code . " " . $message);
		exit($code);
	}
	
	public static function success() {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			"success" => TRUE,
			"message" => "OK"
		));
	}
	
	/**
	 * Singleton for database access in lazy way.
	 * @return TriggerData
	 */
	private function getData() {
		if($this->dao == null) {
			require_once("triggerdata.php");
			$this->dao = new TriggerData();
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
		if(!isset($data->trigger_on) || !isset($data->callback_url) || !isset($data->token)) {
			return false;
		}
		return (preg_match("/^[a-zA-Z0-9]{10,100}$/", $data->token) 
				&& preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}$/", $data->trigger_on)
				&& strpos($data->callback_url, "http") !== FALSE);
	}
	
	public function validateToken() {
		$token = $this->getData()->getSetting("token");
		if($this->input_data->token != $token) {
			TriggerPublicInterface::error(403, "Invalid token.");
		}
	}
	
	protected function now() {
		return date("Y-m-d H:i:s");
	}
	
	public function createTrigger() {
		$created = $this->now();
		$trigger_on = $this->input_data->trigger_on;
		if(isset($this->input_data->callback_data)) {
			$callback_data = json_encode($this->input_data->callback_data);
			$callback_data = urlencode($callback_data);  # for safety reasons
		}
		else {
			$callback_data = "";
		}
		$callback_url = $this->input_data->callback_url;
		
		// check if this host is known
		// -> yes: increase count
		// -> no:  insert it
		$parse_result = parse_url($callback_url);
		if($parse_result === FALSE || $parse_result['host'] == "localhost" || $parse_result['host'] == "127.0.0.1") {
			TriggerPublicInterface::error(421, "URL not valid.");
		}
		try {
			// Update instance
			$this->updateInstance($parse_result['host']);
			
			// Create job
			$this->getData()->addJob($created, $trigger_on, $callback_data, $callback_url);
			
		} catch(Exception $e) {
			$this->error(500, $e->getMessage());
		}
		TriggerPublicInterface::success();
	}
	
	protected function updateInstance($hostname) {
		if($this->dao->instanceExists($hostname)) {
			$this->dao->increaseCountOfInstance($hostname);
		}
		else {
			$first_seen = $this->now();
			$this->dao->addInstance($hostname, $first_seen);
		}
	}
	
	public function run() {
		$this->readInput();
		$this->validateToken();
		$this->createTrigger();
		$this->getData()->disconnect();
	}
}

// main
$processor = new TriggerPublicInterface();
$processor->run();

?>