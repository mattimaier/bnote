<?php
require_once 'notificationLogger.php';
/*
 * THIS SCRIPT IS EXECUTED HOURLY
 * 
 * - Read all jobs that need to be notified in this hour
 * - Send the callbacks
 * 
 * -> write success/failure to stdout
 * -> update DB for the hostname usage 
 */

class NotificationService {

	private $dao;
	
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
	
	public function run() {
		$jobs = $this->getData()->getCurrentHoursJobs();
		$count = 0;
		$failed = 0;
		foreach($jobs as $i => $job) {
			$callback_data = urldecode($job[1]);
			$callback_url = $job[2];
			try {
				$this->sendRequest($callback_url, $callback_data);
				$count++;
			}
			catch(Exception $e) {
				NotificationLogger::error($e->getMessage());
				$failed++;
			}
		}
		echo date(TriggerDB::DATETIME_FORMAT_DB) . "\t$count Notifications successfully sent. $failed failed.";
	}
	
	protected function sendRequest($url, $post_data) {
		$options = array(
				'http' => array(
						'header'  => "Content-type: application/json\r\n",
						'method'  => 'POST',
						'content' => $post_data
				)
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) {
			throw new Exception("Sending request to $url failed: $result");
		}
	}
	
}

$service = new NotificationService();
$service->run();


?>