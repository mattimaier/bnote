<?php
/*
 * INTERFACE for BNote App
 * This interface can be called from anywhere in the web.
 * Parameters are given with the URL, results are returned as XML.
 * 
 * Usage
 * -----
 * bna-xml.php?pin=<pin>&func=<function name>[&parameter=value]*
 * 
 * @author Matti Maier
 */

require_once("bna-interface.php");

/*********************************************************
 * IMPLEMENTATION										 *
 *********************************************************/
require_once($dir_prefix . $GLOBALS['DIR_LIB'] . "xmlarray.php");

class BNAxml extends AbstractBNA {
	
	function init() {
		header('Content-type: application/xml');
	}
	
	private function xmlHeader() {
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	}
	
	function documentBegin() {
		$this->xmlHeader();
		echo "<entities>\n";
	}
	
	function documentEnd() {
		echo "</entities>";
	}
	
	function beginOutputWith() {
		// empty
	}
	
	function endOutputWith() {
		// empty
	}

	function printEntities($selection, $line_node) {
		$root_node = $line_node . "s";
		echo "<$root_node>\n";
		
		$header = $selection[0];
		
		for($i = 1; $i < count($selection); $i++) {
			echo " <$line_node>\n";
			foreach($header as $j => $col) {
				$v = $selection[$i][strtolower($col)];
				$c = strtolower($col);
				echo "  <$c>".$v."</$c>\n";
			}
			echo " </$line_node>\n";
		}
		
		echo "</$root_node>";
	}
	
	function writeEntity($entity, $type) {
		$this->xmlHeader();
		echo "<" . $type . ">\n";
		
		foreach($entity as $attribute => $value) {
			echo "<" . $attribute . ">" . $value . "</" . $attribute . ">\n";
		}
		
		echo "</" . $type . ">";
	}
}

// run
new BNAxml();

?>