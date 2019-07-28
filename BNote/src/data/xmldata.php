<?php

// Implementation of XML Data Interface
class XmlData extends Data {

	protected $filename;
	protected $baseNode;
	protected $xml;
	protected $array;

	function __construct($filename, $baseNode) {
		$this->filename = $filename;
		$this->baseNode = $baseNode;

		if(file_exists($filename)) {
			$this->xml = simplexml_load_file($filename);
			if(!$this->xml) exit("$filename" . Lang::txt("XmlData_construct.filename"));
		}
		else {
			exit("$filename not found!");
		}

		$this->generateArray();
	}

	private function generateArray() {
		$this->array = array();

		foreach($this->xml->children() as $name => $value) {
			$this->array[$name] = $value;
	 }

	 foreach($this->xml->attributes() as $name => $value) {
	 	$this->array[$name] = $value;
	 }
	}

	public function getParameter($parameter) {
		if(!array_key_exists($parameter, $this->array)) return null;
		else return $this->array[$parameter];
	}

	public function getArray() {
		return $this->array;
	}

	public function getXmlNode() {
		return $this->xml;
	}

}

?>