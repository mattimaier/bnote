<?php

/**
 * Extending the CrudRefView with location specific functions.
 * @author matti
 *
 */
class CrudRefLocationView extends CrudRefView {
	
	/**
	 * Adds the address fields as defined in the AbstractLocationData.getAddressViewFields() method.
	 * @param Form $form Form object to add fields to.
	 * @param Array $obj Leave NULL if new.
	 */
	protected function addAddressFieldsToForm($form, $obj = NULL) {
		$addressFields = $this->getData()->getAddressViewFields();
		foreach($addressFields as $fieldName => $info) {
			// value
			$defaultVal = "";
			if($obj != NULL && isset($obj[$fieldName])) {
				$defaultVal = $obj[$fieldName];
			}
			
			// field display
			if($fieldName == "country") {
				$dd = $this->buildCountryDropdown($defaultVal, $obj);			
				$form->addElement($info[0], $dd, $info[2], $info[3]);
			}
			else {
				$form->addElement($info[0], new Field($fieldName, $defaultVal, $info[1]), $info[2], $info[3]);
			}
		}
	}
	
	/**
	 * Renames the address columns within the table.
	 * @param Table $table Table to rename columns.
	 * @param string $prefix Prefix for fields, e.g. "address" for "addressstreet".
	 */
	protected function renameTableAddressColumns($table, $prefix = "") {
		$table->renameHeader($prefix . "street", Lang::txt("CrudRefLocationView_renameTableAddressColumns.street"));
		$table->renameHeader($prefix . "city", Lang::txt("CrudRefLocationView_renameTableAddressColumns.city"));
		$table->renameHeader($prefix . "zip", Lang::txt("CrudRefLocationView_renameTableAddressColumns.zip"));
		$table->renameHeader($prefix . "state", Lang::txt("CrudRefLocationView_renameTableAddressColumns.state"));
		$table->renameHeader($prefix . "country", Lang::txt("CrudRefLocationView_renameTableAddressColumns.country"));
	}
	
	/**
	 * Rename address fields in data view object.
	 * @param Dataview $dataview Dataview to rename fields in.
	 * @param string $prefix Prefix for fields, e.g. "address" for "addressstreet".
	 */
	protected function renameDataViewFields($dataview, $prefix = "") {
		$dataview->renameElement($prefix . "street", Lang::txt("CrudRefLocationView_renameDataViewFields.street"));
		$dataview->renameElement($prefix . "city", Lang::txt("CrudRefLocationView_renameDataViewFields.city"));
		$dataview->renameElement($prefix . "zip", Lang::txt("CrudRefLocationView_renameDataViewFields.zip"));
		$dataview->renameElement($prefix . "state", Lang::txt("CrudRefLocationView_renameDataViewFields.state"));
		$dataview->renameElement($prefix . "country", Lang::txt("CrudRefLocationView_renameDataViewFields.country"));
	}
	
	/**
	 * Replace address fields in data view object with one "address" field and formatted address.
	 * @param Dataview $dataview Dataview to rename fields in.
	 * @param string $prefix Prefix for fields, e.g. "address" for "addressstreet".
	 */
	protected function replaceDataViewFieldWithAddress($dataview, $prefix = "") {
		$dataview->addElement(Lang::txt("CrudRefLocationView_replaceDataViewFieldWithAddress.address"), $this->formatAddress($dataview->getElements(), FALSE, $prefix, TRUE));
		$dataview->removeElement($prefix . "street");
		$dataview->removeElement($prefix . "city");
		$dataview->removeElement($prefix . "zip");
		$dataview->removeElement($prefix . "state");
		$dataview->removeElement($prefix . "country");
	}
	
	/**
	 * Checks the given keys whether it contains the needle.
	 * @param String $needle Contains-String to search for.
	 * @param Array $keyArray Keys.
	 * @return String name of the first occurance of a fuzzy key or null if not found.
	 */
	protected function fuzzyKeySearch($needle, $keyArray) {
		foreach($keyArray as $key) {
			if(substr_count($key, $needle) > 0) return $key;
		}
		return null;
	}
	
	private function handleAddressFieldValue($field, $row, $useFuzzy, $prefix) {
		$rowKeys = array_keys($row);
		$fieldValue = "";
		if(!isset($row[$prefix . $field])) {
			// look for the field in the keys
			$likeKey = $this->fuzzyKeySearch($field, $rowKeys);
			if($likeKey != null && $useFuzzy) $fieldValue = $row[$likeKey];
		}
		else {
			$fieldValue = $row[$prefix . $field];
		}
		return $fieldValue;
	}
	
	/**
	 * Searches the given array for the address fields.
	 * Then builds a string with the given address. In case a field cannot
	 * be found, it is omitted.
	 * @param Array $row Should contain at least one of the fields "city", "zip" or "street".
	 * @param bool $useFuzzy If the street/city/... name should just be searched within the fields and guessed. By default true.
	 * @param string $prefix Real prefix for the street/city/... fields.
	 * @param bool $multiline True if you want to show the address on multiple lines, False by default.
	 * @param string $multilineSeparator Separating character used to split lines, by default "<br>" but could also be "\n"
	 */
	protected function formatAddress($row, $useFuzzy=TRUE, $prefix="", $multiline=FALSE, $multilineSeparator="<br>") {
		$street = $this->handleAddressFieldValue("street", $row, $useFuzzy, $prefix);
		$city = $this->handleAddressFieldValue("city", $row, $useFuzzy, $prefix);
		$zip = $this->handleAddressFieldValue("zip", $row, $useFuzzy, $prefix);
		$state = $this->handleAddressFieldValue("state", $row, $useFuzzy, $prefix);
		$country = $this->handleAddressFieldValue("country", $row, $useFuzzy, $prefix);
	
		/*
		 * street & zip & city & state & country
		 * street & zip & city & state
		 * street & zip & city & country
		 * street & city
		 * street & city & state & country
		 * street & city & state
		 * street & city & country
		 * street & zip -> ignored, only street then
		 * city & zip
		 * city & zip & state & country
		 * city & zip & state
		 * state & country
		 * city & state & country
		 * city & state
		 * city & country
		 * only street
		 * only city
		 * only zip -> ignored, nothing then
		 * only state
		 * only country
		 */
		
		// compile local part
		$local = "";
		if($street != "" && $city != "" && $zip != "") {
			$local = "$street, $zip $city";
		}
		else if($street != "" && $city != "") {
			$local = "$street, $city";
		}
		else if($street != "") {
			$local = $street;
		}
		else if($city != "" && $zip != "") {
			$local = "$zip $city";
		}
		else if($city != "") {
			$local = $city;
		}
		
		// compile global part
		$global = "";
		if($state != "" && $country != "") {
			$global = "$state, " . $this->resolveCountryCode($country);
		}
		else if($state != "") {
			$global = $state;
		}
		else if($country != "") {
			$global = $this->resolveCountryCode($country);
		}
		
		// combile local and global
		$addy = "";
		if($local != "") {
			if($global != "") {
				$addy = "$local, $global";
			}
			else {
				$addy = $local;
			}
		}
		else if($global != "") {
			$addy = $global;
		}
		
		// multiline handling
		if($multiline) {
			return str_replace(",", $multilineSeparator, $addy);
		}
		return $addy;
	}
	
}

?>