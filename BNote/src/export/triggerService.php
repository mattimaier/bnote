<?php

/**
 * Client for the BNote Trigger service on bnote.info.
 * @author Matti
 *
 */
class TriggerServiceClient {
	
	private $service_url = "http://www.bnote.info/TriggerServer/createTrigger";
	private $service_token = "l12jqHgfhdfgHWE12lXMLPOLIfdWE57457459264j2bl35ij23";
	
	const DATE_FORMAT = "Y-m-d H:i:s";
	
	/**
	 * Creates a trigger that will POST a message to the callback_url with the given callback_data.
	 * @param String $trigger_on Y-m-d H:i:s formatted String.
	 * @param String $callback_url URL to call back -> notifyClients.php on the BNote Server
	 * @param Array $callback_data Associative array with oid, otype and token.
	 * @return True when ok, otherwise false (shows error on failure, but when notification date in past then just false is returned)
	 */
	public function createTrigger($trigger_on, $callback_url, $callback_data) {
		# check that the date is in the future
		$dt = DateTime::createFromFormat(TriggerServiceClient::DATE_FORMAT, $trigger_on);
		$now = new DateTime();
		$diff = $now->diff($dt);
		if($diff->invert == 1) {
			# date in the past
			return false;
		}
		
		# create post data array
		$post_data = array(
			"token" => $this->service_token,
			"trigger_on" => $trigger_on,
			"callback_url" => $callback_url,
			"callback_data" => $callback_data
		);
		try {
			$this->sendRequest($this->service_url, json_encode($post_data));
			return true;
		} catch(Exception $e) {
			new BNoteError(Lang::txt("TriggerServiceClient_createTrigger.error"));
			return false;
		}
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

?>