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
				$dd = $this->buildCountryDropdown($defaultVal);			
				$form->addElement($info[0], $dd);
			}
			else {
				$form->addElement($info[0], new Field($fieldName, $defaultVal, $info[1]));
			}
		}
	}
	
	protected function buildCountryDropdown($defaultVal, $obj = NULL) {
		$countries = $this->getData()->getCountries();
		$dd = new Dropdown("country");
		foreach($countries as $country) {
			$caption = $country["code"] . " - " . $country[$this->getData()->getSysdata()->getLang()];
			$dd->addOption($caption, $country["code"]);
		}
		if($obj == NULL) {
			$defaultVal = $this->getData()->getSysdata()->getDynamicConfigParameter("default_country");
		}
		$dd->setSelected($defaultVal);
		return $dd;
	}
}

?>