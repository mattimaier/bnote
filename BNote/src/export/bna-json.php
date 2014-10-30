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
		
		header('Content-type: application/json; charset=utf-8');
	}
	
	function documentBegin() {
		echo "{ \"data\" : [ ";
	}
	
	function documentEnd() {
		echo "] }";
	}
	
	function beginOutputWith() {
		echo "{\n";
	}
	
	function endOutputWith() {
		echo "}";
	}
	
	function entitySeparator() {
		return ",";
	}
	
	function printEntities($selection, $line_node) {
		$this->beginOutputWith();
		echo '"' . $line_node . 's" : [';
		for($i = 1; $i < count($selection); $i++) {
			$e = $selection[$i];
			if($i > 1) echo $this->entitySeparator();
			echo "{";
			$j = 0;
			foreach($e as $index => $value) {
				if(is_numeric($index)) continue;
				if($j > 0) echo $this->entitySeparator();
				
				// conversions for globally unique identifiers
				if($this->global_on && $index == "id") {
					// singluar type
					echo '"type" : "' . $line_node . '"' . $this->entitySeparator() . ' ';
					$value = $this->instanceUrl . "/$line_node/$value"; 
				}
				
				echo "\"$index\" : \"" . $value . "\"";
				
				$j++;
			}
			echo "}";
		}
		echo ']';
		
		$this->endOutputWith();
	}
	
	function printEntityStructure($entities, $nodeName, $newOutput = true) {
		if($newOutput) $this->beginOutputWith();
		
		echo '"' . $nodeName . 's" : [';
		$count = 0;
		foreach($entities as $index => $entity) {
			if($count > 0) {
				echo $this->entitySeparator();
			}
			$fcnt = 0;
			foreach($entity as $field => $fieldVal) {
				if($fcnt > 0) {
					echo $this->entitySeparator();
				}
				if($field == "id" && $this->global_on) {
					echo '"type" : "' . $nodeName . '"' . $this->entitySeparator() . ' ';
					$fieldVal = $this->instanceUrl . "/$fieldVal/$value"; 
				}
				if(is_array($fieldVal)) {
					$this->printEntityStructure(array($fieldVal), $field, false);
				}
				else {
					echo '"' . $field . '": "' . $fieldVal . '"';
				}
				$fcnt++;
			}
			
			$count++;
		}
		echo ']';
		
		if($newOutput) $this->endOutputWith();
	}
	
	function writeEntity($entity, $type) {
		$this->beginOutputWith();
		
		$i = 0;
		foreach($entity as $attribute => $value) {
			if($i > 0) echo ",";
			echo "\"$attribute\":\"$value\"";
			$i++;
		}
		
		$this->endOutputWith();
	}
}

// run
new BNAjson();
