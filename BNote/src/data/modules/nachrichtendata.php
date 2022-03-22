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
				
		$this->init($dir_prefix);
		$this->newsFile = $dir_prefix . "data/nachrichten.html";
	}
	
	public function fetchContent() {
		if(!file_exists($this->newsFile)) {
			file_put_contents($this->newsFile, "");
		}
		$content = file_get_contents($this->newsFile);
		return urldecode($content);
	}
	
	/**
	 * Delivers the content in a little formatted fashion.
	 */
	public function preparedContent() {
		$content = $this->fetchContent();
		return str_replace("\n", "<br/>\n", $content);
	}
	
	public function storeContent($content) {
		$fileContent = urlencode($content);
		file_put_contents($this->newsFile, $fileContent);
	}
	
}
