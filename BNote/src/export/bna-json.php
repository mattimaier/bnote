<?php
/*
 * INTERFACE for BNote App
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
	
	/**
	 * Globally unique identifiers.
	 * @var boolean
	 */
	private $global_on = false;
	
	/**
	 * The URL where this BNote instance runs.
	 * @var string
	 */
	private $instanceUrl = "";
	
	function init() {
		if(isset($_GET["global"]) && $_GET["global"] != "") {
			$this->global_on = true;
		}
		
		$this->instanceUrl = $this->sysdata->getSystemURL();
		if(Data::startsWith($this->instanceUrl, "http://")) {
			$this->instanceUrl = substr($this->instanceUrl, 7); // cut prefix
		}
	}
	
	function beginOutputWith() {
		header('Content-type: application/json; charset=utf-8');
		echo "{\n";
	}
	
	function endOutputWith() {
		echo "}";
	}
	
	function entitySeparator() {
		return ",";
	}
	
	function printEntities($selection, $line_node) {
		echo '"' . $line_node . 's" : [';
		for($i = 1; $i < count($selection); $i++) {
			$e = $selection[$i];
			if($i > 1) echo ",";
			echo "{";
			$j = 0;
			foreach($e as $index => $value) {
				if(is_numeric($index)) continue;
				if($j > 0) echo ",";
				
				// conversions for globally unique identifiers
				if($this->global_on && $index == "id") {
					// singluar type
					echo '"type" : "' . $line_node . '", ';
					$value = $this->instanceUrl . "/$line_node/$value"; 
				}
				
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
