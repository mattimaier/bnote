<?php

/**
 * Shows an html editor. Usually this is tinyMCE if set as such in the
 * javascript files.
 * @author matti
 *
 */
class HtmlEditor implements iWriteable {
	
	private $html;
	private $name;
	
	/**
	 * Creates a new html editor. Make sure the javascript has
	 * a tinyMCE init for the exact element "tinymcefull".
	 * @param String $name Name/ID for the object.
	 * @param String $default HTML content.
	 */
	function __construct($name, $default) {
		$this->name = $name;
		$this->html = $default;
	}
	
	function write() {
		$editor = '<textarea id="tinymcefull" name="' . $this->name . '"';
		$editor .= ' cols="100" rows="20">';
 		$editor .= $this->html . '</textarea>' . "\n";
 		echo $editor;
	}
	
	public function getName() { return $this->name; }
}

?>