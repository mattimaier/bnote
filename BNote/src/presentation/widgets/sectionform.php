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
		<h2><?php echo $this->formname; ?></h2>
		<div id="sectionform">
		<?php
		foreach($this->sections as $sectionId => $fields) {
			?>
			<h3><?php echo $sectionId; ?></h3>
			<div class="sectionform_section_content">
				<table>
				<?php
				foreach ( $this->elements as $label => $element ) {
					if(!in_array($element->getName(), $fields)) continue;
					
					echo " <tr>\n";
					$required = "";
					if (isset ( $this->requiredFields [$label] ) && $this->requiredFields [$label])
						$required = "*";
						if (isset ( $this->rename [$label] ))
							$label = $this->rename [$label];
							echo "  <td>$label$required</td>\n";
							echo "  <td>" . $element->write() . "</td>\n";
							echo " </tr>\n";
				}
				if (count ( $this->requiredFields ) > 0) {
					echo "<tr><td colspan=\"2\" style=\"font-size: 8pt;\">* markierte Felder sind anzugeben</td></tr>";
				}
				?>
				</table>
			</div>
			<?php
		}
		?>
		</div>
		<div style="height: 20px;">&nbsp;</div>
		<?php 
		// add hidden values
		foreach ( $this->hidden as $name => $value ) {
			echo '<input type="hidden" value="' . $value . '" name="' . $name . '">' . "\n";
		}
		
		// Submit Button
		if (! $this->removeSubmitButton) {
			echo '<input type="submit" value="' . $this->submitValue . '">' . "\n";
		}
		?>
		</form>
		<?php
	}
	
}

?>