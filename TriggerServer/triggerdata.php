<?php
require_once 'triggerdb.php';


class TriggerData extends TriggerDB {

	public function instanceExists($hostname) {
		$id = $this->getCell("instances", "id", "hostname = '$hostname'");
		return ($id > 0);
	}
	
	public function increaseCountOfInstance($hostname) {
		$stmt = $this->db->prepare("UPDATE instances SET count = count + 1 WHERE hostname = ?");
		$stmt->bind_param("s", $hostname);
		if(!$stmt->execute()) {
			throw new Exception("Cannot increase count of instance: " . $this->db->error);
		}
		return true;
	}

	public function addInstance($hostname, $first_seen) {
		$stmt = $this->db->prepare("INSERT INTO instances (hostname, first_seen) VALUES (?, ?)");
		$stmt->bind_param("ss", $hostname, $first_seen);
		if(!$stmt->execute()) {
			throw new Exception("Cannot insert new host: " . $this->db->error);
		}
		return $this->db->insert_id;
	}

	public function addJob($created, $trigger_on, $callback_data, $callback_url) {
		$stmt = $this->db->prepare("INSERT INTO jobs (created, trigger_on, callback_data, callback_url)
				VALUES (?, ?, ?, ?)");
		if(!$stmt->bind_param("ssss", $created, $trigger_on, $callback_data, $callback_url)) {
			throw new Exception("Invalid parameter: " . $this->db->error);
		}
		if(!$stmt->execute()) {
			throw new Exception("Cannot insert new job: " . $this->db->error);
		}
		return $this->db->insert_id;
	}

	public function getCurrentHoursJobs() {
		// build date/time strings
		$end = new DateTime("now");
		$end->setTime(date("H"), 0, 0);
		$start = clone $end;
		$end->add(new DateInterval("PT1H"));  # plus time 1 hour -> inplace operation
		
		// build query
		$query = "SELECT id, callback_data, callback_url FROM jobs WHERE trigger_on >= ? AND trigger_on < ?";
		$stmt = $this->db->prepare($query);
		if(!$stmt) {
			throw new Exception("Invalid statement: " . $this->db->error);
		}
		$start_s = $start->format(TriggerDB::DATETIME_FORMAT_DB);
		$end_s = $end->format(TriggerDB::DATETIME_FORMAT_DB);
		if(!$stmt->bind_param("ss", $start_s, $end_s)) {
			throw new Exception("Invalid parameter: " . $this->db->error);
		}
		if(!$stmt->execute()) {
			throw new Exception("Cannot get jobs: " . $this->db->error);
		}
		// process result
		$stmt->bind_result($jobid, $callback_data, $callback_url);
		$jobs = array();
		while($stmt->fetch()) {
			array_push($jobs, array($jobid, $callback_data, $callback_url));
		}
		$stmt->close();
		return $jobs;
	}

	public function getSetting($key) {
		return $this->getCell("settings", "value", "setting ='$key'");
	}
}

?>