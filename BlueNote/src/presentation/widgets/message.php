<?php
/**
 * This class handles messages
**/

class Message implements iWriteable {

 private $message;
 private $header;
	
 function __construct($h, $m) {
  $this->header = $h;
  $this->message = $m;
  echo '<p class="message">' . "\n";
  echo ' <font class="messageHeader">' . $this->header . '</font><br>' . "\n";
  echo $this->message . "\n";
  echo '</p>' . "\n";
 }
 
 function write() {
  // legacy|deprecated
 }

}

?>