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
	 */
	function __construct() {
		$this->fields = array();

		$this->references = array();

		$this->table = "";
				
		$this->init();
		
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
		file_put_contents($this->newsFile, $content);
	}
}

?>