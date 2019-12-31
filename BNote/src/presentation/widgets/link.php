<?php
/**
 * A class to make links consistent
 **/

class Link implements iWriteable {
	
	private $href;
	private $label;
	private $target;
	private $icon;
	private $jsClick;
	private $submitButton = false;

	/**
	 * Creates a link
	 * @param $href String to where the field links
	 * @param $label Label of the link field
	 */
	function __construct($href, $label) {
		$this->href = $href;
		$this->label = $label;
		$this->jsClick = null;
	}

	/**
	 * Sets the links target.
	 * @param String $target HTML target value.
	 */
	function setTarget($target) {
		$this->target = $target;
	}

	function setJsClick($jsClick) {
		$this->jsClick = $jsClick;
	}
	
	/**
	 * When this mode is turned on, the link is rendered as a button with the type="submit"
	 * @param boolean $val Nothing or true to turn the mode on, false to turn it off (default).
	 */
	function isSubmitButton($val = true) {
		$this->submitButton = $val;
	}
	
	function write() {
		echo $this->generate();
	}

	/**
	 * Returns a string with the elements HTML code
	 */
	function toString() {
		return $this->generate();
	}
	
	private function generate() {
		if(isset($this->target) && $this->target != "") {
			$target = 'target="' . $this->target . '"';
		}
		else {
			$target = "";
		}
		
		if(isset($this->icon) && $this->icon != "") {
			$icon = "<img src=\"" . $GLOBALS["DIR_ICONS"] . $this->icon . ".png\""
				    . " height=\"15px\" class=\"linkIcon\" alt=\"" . $this->icon . "\" border=\"0\" />&nbsp;";
		}
		else {
			$icon = "";
		}
		
		$options = "";
		if($this->jsClick != null) {
			$options .= ' onclick="' . $this->jsClick . '"';
		}
		
		if($this->submitButton) {
			return '<input type="submit" class="linkbox"' . $options . ' value="' . $this->label . '">';
		}

		return '<a class="linkbox" ' . $target . 'href="' . $this->href . '"' . $options . '>'
		     . '<div class="linkbox">' . $icon . $this->label . '</div></a>';
	}

	/**
	 * To add an icon in front of the caption, execute this function with a
	 * name of the icon from the icons folder.
	 * @param String $icon_id Name of the icon file in the icon folder.
	 */
	function addIcon($icon_id) {
		$this->icon = $icon_id;
	}
	
}

?>