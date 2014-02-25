<?php

/**
 * Data Access Class for news data.
 * @author matti
 *
 */
class NachrichtenData extends AbstractData {

	private $newsFile;
	
	/**
	 * Build data provider.
	 * @param string $dir_prefix Optional parameter for include(s) prefix.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array();

		$this->references = array();

		$this->table = "";
				
		//$this->init($dir_prefix);
		
		$this->newsFile = "data/nachrichten.html";
	}
	
	public function fetchContent() {
		return file_get_contents($this->newsFile);
	}
	
	/**
	 * Delivers the content in a little formatted fashion.
	 */
	public function preparedContent() {
		$content = $this->fetchContent();
		return str_replace("\n", "<br/>\n", $content);
	}
	
	public function storeContent($content) {
		$this->check($content);
		file_put_contents($this->newsFile, $content);
	}
	
	public function check($content) {
		$content = strtolower($content);
		if(strpos($content, "<script") !== false
			|| strpos($content, "<iframe") !== false
			|| strpos($content, "<frame") !== false) {
			new Error("Der Inhalt der Nachricht ist nicht sicher. Bitte verwende keine Frames und Skripte.");
		}
	}
}

?>