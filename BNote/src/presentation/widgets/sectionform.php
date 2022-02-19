<?php 

/**
 * A form with sections
 * @author matti
 *
 */
class SectionForm extends Form {
	
	protected $sections = array();
	
	/**
	 * Groups fields by section.
	 * @param String $sectionId Name of the section (must be unique)
	 * @param Array $fields Field names (must match the elements)
	 */
	public function setSection($sectionId, $fields) {
		$this->sections[$sectionId] = $fields;
	}
	
	public function write() {
		$this->createForeign();
		?>
		<form method="<?php echo $this->method; ?>" action="<?php echo $this->action;?>" <?php echo $this->multipart ?>>
		<div id="sectionform">
			<h4 class="h4"><?php echo $this->formname; ?></h4>
		<?php
		foreach($this->sections as $sectionId => $fields) {
			?>
			<h5 class="h5 mt-3"><?php echo $sectionId; ?></h5>
			<div class="row g-2 sectionform_section_content">
				<?php
				foreach ( $this->elements as $label => $element ) {
					if(!in_array($element->getName(), $fields)) continue;
					if(isset($this->fieldColSize[$label])) {
						$colClass = "col-md-" . $this->fieldColSize[$label];
					}
					else {
						$colClass = "col-md-6";
					}
					echo "<div class=\"$colClass mb-1\">";
					$required = "";
					if (isset ( $this->requiredFields [$label] ) && $this->requiredFields [$label])
						$required = "*";
						if (isset ( $this->rename [$label] ))
							$label = $this->rename [$label];
							echo "  <label class=\"col-form-label bnote-form-label\">$label$required</label>";
							echo $element->write();
							echo "</div>";
				}
				if (count ( $this->requiredFields ) > 0) {
					echo '<div class="row"><div class="col-auto"><span class="form-text">' . Lang::txt("SectionForm_write.message") . "</span></div></div>";
				}
				?>
			</div>
			<?php
		}
		?>
		</div>
		
		<?php 
		// add hidden values
		foreach ( $this->hidden as $name => $value ) {
			echo '<input type="hidden" value="' . $value . '" name="' . $name . '">';
		}
		
		// Submit Button
		if (! $this->removeSubmitButton) {
			echo '<input type="submit" class="btn btn-primary" value="' . $this->submitValue . '">';
		}
		?>
		</form>
		<?php
	}
	
}

?>