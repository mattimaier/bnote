<?php
/**
 * Class to display errors
**/

class Error {

 function __construct($message) {
  echo "<p><b>ERROR</b><br>$message</p>\n";
  exit(-1);
 }

}

?>