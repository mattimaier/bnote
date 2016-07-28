<?php

class NotificationLogger {
	
	const LOGFILE = "notify.log";
	
	public static function error($message) {
		NotificationLogger::writeMessage("ERROR", $message);
	}
	
	public static function warn($message) {
		NotificationLogger::writeMessage("WARN", $message);
	}
	
	public static function info($message) {
		NotificationLogger::writeMessage("INFO", $message);
	}
	
	private static function writeMessage($level, $message) {
		$msg = $level . "\t" . $message . "\n"; 
		file_put_contents(NotificationLogger::LOGFILE, $msg, FILE_APPEND);
	}
}

?>