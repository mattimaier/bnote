<?php

/**
 * Creates a csv files out of a database selection result.
 * @author matti
 *
 */
class CsvCreator {
	
	private $selection;
	private $separator;
	private $remove = array();
	private $types = array();
	
	/**
	 * Creates a new csv file out of the given database selection result.
	 * @param Array $selection Return value of a Database->getSelection(...) call.
	 */
	public function __construct($selection) {
		$this->selection = $selection;
		$this->separator = ",";
	}
	
	/**
	 * Sets the separator between values. By default a comma.
	 * @param String $separator Separator, e.g. ",".
	 */
	public function setSeparator($separator) {
		$this->separator = $separator;
	}
	
	/**
	 * Remove a column from the output.
	 * @param String $col Name of the column in the data set.
	 */
	public function removeColumn($col) {
		array_push($this->remove, $col);
	}
	
	/**
	 * Sets the type of a column in order to be formatted accordingly.
	 * @param String $col Name of the column in the data set.
	 * @param FieldType $type Type of the column.
	 */
	public function setFieldType($col, $type) {
		$this->types[$col] = $type;
	}
	
	/**
	 * Writes the csv file to the standard output.
	 */
	public function write() {
		// write header
		$header = "";
		$removeColNums = array();
		for($i = 0; $i < count($this->selection[0]); $i++) {
			if(in_array($this->selection[0][$i], $this->remove)) {
				array_push($removeColNums, $i);
				continue;
			}
			$header .= $this->selection[0][$i] . $this->separator;
		}
		$header = substr($header, 0, strlen($header)-1); // cut last separator
		echo $header . "\n";
		
		// write data
		for($i = 1; $i < count($this->selection); $i++) {
			$row = $this->selection[$i];
			$rowout = "";
			for($j = 0; $j < (count($row)/2); $j++) {
				if(in_array($j, $removeColNums)) continue;
				$col = $this->selection[0][$j];
				$entry = $row[$j];
				if(isset($this->types[$col])) {
					// format according to type
					$type = $this->types[$col];
					if($type == FieldType::DATE || $type == FieldType::DATETIME || $type == FieldType::TIME) {
						$entry = Data::convertDateFromDb($entry);
					}
					else if($type == FieldType::DECIMAL || $type == FieldType::CURRENCY) {
						$entry = Data::convertFromDb($entry);
					}
				}
				$rowout .= "\"" . $entry . "\"" . $this->separator;
			}
			$rowout = substr($rowout, 0, strlen($rowout)-1);
			echo $rowout . "\n";
		}
	}
	
}