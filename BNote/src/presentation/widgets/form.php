<?php

/**
 * Prints a form in a formbox
 **/
class Form implements iWriteable {
	
	protected $formname;
	protected $method;
	protected $action;
	protected $multipart;
	protected $elements = array();
	protected $foreign = array();
	protected $hidden = array();
	protected $rename = array();
	protected $submitValue;
	protected $removeSubmitButton = false;
	protected $requiredFields = array();
	
	/**
	 * Constructor
	 * 
	 * @param String $name
	 *        	The form's header description
	 * @param String $action
	 *        	link to call when form is submitted
	 */
	function Form($name, $action) {
		$this->formname = $name;
		$this->method = 'POST';
		$this->action = $action;
		$this->multipart = ""; // none by default
		$this->submitValue = "OK";
	}
	
	/**
	 * Sets the form's header.
	 * 
	 * @param String $title
	 *        	Title of the form.
	 */
	function setTitle($title) {
		$this->formname = $title;
	}
	
	/**
	 * Sets either POST or GET as method.
	 * 
	 * @param String $method
	 *        	Method attribute of form-tag.
	 */
	function setMethod($method) {
		$this->method = $method;
	}
	
	/**
	 * adds an Element to the form
	 * 
	 * @param String $name
	 *        	Label of the element
	 * @param iWriteable $element
	 *        	Reference to an iWriteable implementing object
	 */
	public function addElement($name, $element) {
		$this->elements[$name] = $element;
	}
	public function getElement($name) {
		if(isset($this->elements[$name])) {
			return $this->elements[$name];
		}
		return null;
	}
	
	/**
	 * Automatically adds elements from an array
	 * 
	 * @param $array Array
	 *        	with format field => fieldtype
	 * @param $table table
	 *        	associated with the array
	 * @param $id id
	 *        	to fill the form with the data of the row with this id
	 * @param $forceFields Array
	 * 			list of fields which are forced to be added
	 */
	public function autoAddElements($array, $table, $id, $forceFields=array()) {
		global $system_data;
		$entity = $system_data->dbcon->getRow ( "SELECT * FROM $table WHERE id = $id" );
		foreach ( $array as $field => $info ) {
			// ignore custom fields
			if(!isset($entity[$field]) && !in_array($field, $forceFields)) {
				continue;
			}
			
			// process regular fields
			$value = isset($entity[$field]) ? $entity[$field] : "";
			if (($info[1] == FieldType::DATE || $info[1] == FieldType::DATETIME) && !empty($value)) {
				$value = Data::convertDateFromDb ( $value );
			} else if ($info[1] == FieldType::DECIMAL) {
				$value = Data::convertFromDb ( $value );
			} else if ($info[1] == FieldType::PASSWORD) {
				$value = "";
			}
			
			// create element
			$this->addElement($field, new Field($field, $value, $info[1]));
			
			// configure element
			$this->renameElement ( $field, $info [0] );
			if (count ( $info ) > 2 && $info [2] == true) {
				$this->setFieldRequired ( $field );
			}
		}
	}
	
	/**
	 * automatically adds elements from an array, but without values
	 * 
	 * @param $array Array
	 *        	with format fieldid => [fieldname, fieldtype]
	 */
	public function autoAddElementsNew($array) {
		foreach($array as $field => $info) {
			$this->addElement($field, new Field($field, "", $info [1]));
			$this->renameElement($field, $info[0]);
			if (count($info) > 2 && $info[2] == true) {
				$this->setFieldRequired($field);
			}
		}
	}
	
	/**
	 * Sets a certain column as a foreign key and creates a dropbox for it
	 * 
	 * @param string $field
	 *        	The column which is the foreign key
	 * @param string $table
	 *        	The table the foreign key references to
	 * @param string $idcolumn
	 *        	The column in the foreign table which is referenced
	 * @param string $namecolumns
	 *        	An array with the naming columns
	 * @param string $selectedid
	 *        	The id which is currently set, set -1 if none
	 */
	public function setForeign($field, $table, $idcolumn, $namecolumns, $selectedid) {
		// check whether key even exists
		if (! array_key_exists ( $field, $this->elements )) {
			new BNoteError (Lang::txt("Form_setForeign.error"));
		}
			
		// create new dropdown list
		$dropdown = new Dropdown ( $field );
		
		global $system_data;
		$choices = $system_data->dbcon->getForeign ( $table, $idcolumn, $namecolumns );
		foreach ( $choices as $id => $name ) {
			$dropdown->addOption ( $name, $id );
		}
		if ($selectedid >= 0)
			$dropdown->setSelected ( $selectedid );
		
		$this->foreign [$field] = $dropdown;
	}
	
	/**
	 * Add an option to a foreign key dropboxbox
	 * 
	 * @param Identifier $field
	 *        	Name of the field to add the option for
	 * @param String $optionname
	 *        	Name of the option
	 * @param String $optionvalue
	 *        	Value of the option
	 */
	public function addForeignOption($field, $optionname, $optionvalue) {
		$dp = $this->foreign [$field];
		$dp->addOption ( $optionname, $optionvalue );
	}
	
	/**
	 * Change what's selected on a foreign field
	 * 
	 * @param Identifier $field
	 *        	Name of the field where to change the option
	 * @param integer $id
	 *        	Selected option
	 */
	public function setForeignOptionSelected($field, $id) {
		$dp = $this->foreign [$field];
		$dp->setSelected ( $id );
	}
	public function getForeignElement($field) {
		return $this->foreign [$field];
	}
	
	/**
	 * Prepare dropdownlists to be written
	 */
	protected function createForeign() {
		foreach ( $this->foreign as $field => $dropdown ) {
			// write dropdown list to elements array
			$this->elements [$field] = $dropdown;
		}
	}
	
	/**
	 * Removes the element from the form
	 * 
	 * @param Identifier $name
	 *        	The name of the element to remove
	 */
	public function removeElement($name) {
		// determine position of element to remove
		$i = 0;
		foreach ( $this->elements as $key => $value ) {
			if ($key == $name)
				break;
			$i ++;
		}
		
		// remove element
		array_splice ( $this->elements, $i, 1 );
	}
	
	/**
	 * Adds a hidden field to the form
	 * 
	 * @param Identifier $name
	 *        	The identifier in the $_POST array
	 * @param String $value
	 *        	Value of the identifier
	 */
	public function addHidden($name, $value) {
		$this->hidden [$name] = $value;
	}
	
	/**
	 * Changes the caption for the submit button
	 * 
	 * @param String $name
	 *        	Caption of the submit button
	 */
	public function changeSubmitButton($name) {
		$this->submitValue = $name;
	}
	
	/**
	 * Changes the label for the Element
	 * 
	 * @param String $name
	 *        	Name of the Element
	 * @param String $label
	 *        	New label for the Element
	 */
	public function renameElement($name, $label) {
		$this->rename [$name] = $label;
	}
	
	/**
	 * Returns the given elements currently saved value
	 * 
	 * @param Identifier $name
	 *        	Identifying name of the element
	 */
	public function getValueForElement($name) {
		$el = $this->elements [$name];
		return $el->getValue ();
	}
	
	/**
	 * Sets the value for a element
	 * 
	 * @param String $name
	 *        	Name of element.
	 * @param String $value
	 *        	Value as string.
	 */
	public function setFieldValue($name, $value) {
		if (isset ( $this->elements [$name] )) {
			$el = $this->elements [$name];
			$el->setValue ( $value );
		}
	}
	
	/**
	 * Sets whether the form contains multipart fields, e.g.
	 * file fields.
	 * 
	 * @param boolean $bool
	 *        	True if it contains multipart data (default), otherwise false.
	 */
	public function setMultipart($bool = true) {
		if ($bool)
			$this->multipart = ' enctype="multipart/form-data"';
		else
			$this->multipart = "";
	}
	
	/**
	 * Sets whether the submit button should be shown or not.
	 * 
	 * @param boolean $bool
	 *        	True or nothing when the button should be removed, otherwise false.
	 */
	public function removeSubmitButton($bool = true) {
		$this->removeSubmitButton = $bool;
	}
	public function setFieldRequired($field, $required = true) {
		$this->requiredFields [$field] = $required;
	}
	
	/**
	 * print html output
	 */
	public function write() {
		$this->createForeign ();
		
		echo '<form method="' . $this->method . '" action="' . $this->action . '"';
		echo $this->multipart . '>' . "\n";
		
		echo '<fieldset>';
		echo "<legend class=\"FormBox\">" . $this->formname . "</legend>\n";
		
		echo '<table>' . "\n";
		
		foreach ( $this->elements as $label => $element ) {
			echo " <tr>\n";
			$required = "";
			if (isset ( $this->requiredFields [$label] ) && $this->requiredFields [$label])
				$required = "*";
			if (isset ( $this->rename [$label] ))
				$label = $this->rename [$label];
			echo "  <td>$label$required</td>\n";
			echo "  <td>" . $element->write () . "</td>\n";
			echo " </tr>\n";
		}
		if (count ( $this->requiredFields ) > 0) {
			echo "<tr><td colspan=\"2\" style=\"font-size: 8pt;\">" . Lang::txt("Form_write.message") . "</td></tr>";
		}
		echo '</table>' . "\n";
		
		// add hidden values
		foreach ( $this->hidden as $name => $value ) {
			echo '<input type="hidden" value="' . $value . '" name="' . $name . '">' . "\n";
		}
		
		// Submit Button
		if (! $this->removeSubmitButton) {
			echo '<input type="submit" value="' . $this->submitValue . '">' . "\n";
		}
		echo '</fieldset>' . "\n";
		echo '</form>' . "\n";
	}
}

?>