<?php
require_once($DIR_WIDGETS . "form.php");

class MobileForm extends Form {
	
	protected $showTitle = true;
	
	function hideTitle($hide=true) {
		$this->showTitle = !$hide;
	}
	
	function write() {
		$this->createForeign();
		
		echo '<form method="' . $this->method . '" action="' . $this->action . '"';
		echo $this->multipart . '>' . "\n";
		
		echo '<fieldset>';
		if($this->showTitle) {
			echo "<legend class=\"FormBox\">" . $this->formname . "</legend>\n";
		}
		
		foreach($this->elements as $label => $element) {
			echo "<div class=\"form_row\">\n";
			if(isset($this->rename[$label])) $label = $this->rename[$label];
			echo " <label class=\"form_label\">" . $label . "</label>\n";
			echo " " . $element->write() . "\n";
			echo "</div>\n";
		}
		
		// add hidden values
		foreach($this->hidden as $name => $value) {
			echo '<input type="hidden" value="' . $value . '" name="' . $name . '">' . "\n";
		}
		
		// Submit Button
		if(!$this->removeSubmitButton) {
			echo '<input type="submit" value="' . $this->submitValue . '">' . "\n";
		}
		echo '</fieldset>' . "\n";
		echo '</form>' . "\n";
	}
	
}

?>