<?php
/*
 * INTERFACE for BlueNote App
 * This interface can be called from anywhere in the web.
 * Parameters are given with the URL, results are returned as JSON.
 * 
 * Usage
 * -----
 * bna-json.php?pin=<pin>&func=<function name>[&parameter=value]*
 * 
 * @author Matti Maier
 */

require_once("bna-interface.php");

/*********************************************************
 * IMPLEMENTATION										 *
*********************************************************/

class BNAjson extends AbstractBNA {
	
	function beginOutputWith() {
		echo "{\n";
	}
	
	function endOutputWith() {
		echo "}";
	}
	
	function getAll() {
		$this->beginOutputWith();
		$this->getRehearsals(); echo ",\n";
		$this->getConcerts(); echo ",\n";
		$this->getContacts(); echo ",\n";
		$this->getLocations(); echo ",\n";
		$this->getAddresses(); echo "\n";
		$this->endOutputWith();
	}
	
	function printEntities($selection, $line_node) {
		echo '"' . $line_node . 's" : [';
		for($i = 1; $i < count($selection); $i++) {
			$e = $selection[$i];
			if($i > 1) echo ",";
			//echo "\"$i\" : {";
			echo "{";
			$j = 0;
			foreach($e as $index => $value) {
				if(is_numeric($index)) continue;
				if($j > 0) echo ",";
				echo "\"$index\" : \"" . $value . "\"";
				$j++;
			}
			echo "}";
		}
		echo ']';
	}
}

// run
new BNAjson();
