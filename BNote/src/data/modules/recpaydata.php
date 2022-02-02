<?php

class RecpayData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
			"id" => array(Lang::txt("RecpayData_construct.id"), FieldType::INTEGER),
			"subject" => array(Lang::txt("RecpayData_construct.subject"), FieldType::CHAR),
			"account" => array(Lang::txt("RecpayData_construct.account"), FieldType::REFERENCE),
			"amount_net" => array(Lang::txt("RecpayData_construct.amount_net"), FieldType::CURRENCY, true),
			"amount_tax" => array(Lang::txt("RecpayData_construct.amount_tax"), FieldType::CURRENCY, true),
			"btype" => array(Lang::txt("RecpayData_construct.btype"), FieldType::ENUM),
			"otype" => array(Lang::txt("RecpayData_construct.otype"), FieldType::ENUM),
			"oid" => array(Lang::txt("RecpayData_construct.oid"), FieldType::CHAR),
			"notes" => array(Lang::txt("RecpayData_construct.notes"), FieldType::CHAR, true)
		);
	
		$this->references = array(
			"account" => "account"
		);
	
		$this->table = "`recpay`";
		$this->init();
	}
	
	function getPhases() {
		$query = "SELECT * FROM rehearsalphase ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function ref2val($otype, $oid) {
		switch($otype) {
			case "L": $otype = Lang::txt("RecpayData_ref2val.location");
				$oid = $this->getLocationName($oid);
				break;
			case "H": $otype = Lang::txt("RecpayData_ref2val.contact");
				$oid = $this->getContactName($oid);
				break;
			case "C": $otype = Lang::txt("RecpayData_ref2val.concert");
				$oid = $this->getConcertName($oid);
				break;
			case "P": $otype = Lang::txt("RecpayData_ref2val.rehearsalphase");
				$oid = $this->getPhaseName($oid);
				break;
			case "T": $otype = Lang::txt("RecpayData_ref2val.tour");
				$oid = $this->getTourName($oid);
				break;
			case "E": $otype = Lang::txt("RecpayData_ref2val.equipment");
				$oid = $this->getEquipmentName($oid);
				break;
			default:
				$otype = Lang::txt("RecpayData_ref2val.no_otype");
				$oid = null;
				break;
		}
		return array($otype, $oid);
	}
	
	function getRecurringPayments($joinedAttributes) {
		$sel = $this->findAllJoined($joinedAttributes);
		$res = array(
			$sel[0]  // header
		);
		foreach($sel as $i => $row) {
			if($i == 0) continue;
			if($row["btype"] == "0") {
				$row["btype"] = Lang::txt("RecpayData_getRecurringPayments.type_0");
			}
			else if($row["btype"] == "1") {
				$row["btype"] = Lang::txt("RecpayData_getRecurringPayments.type_1");
			}
			$r2v = $this->ref2val($row["otype"], $row["oid"]);
			$row["otype"] = $r2v[0];
			$row["oid"] = $r2v[1];
			array_push($res, $row);
		}
		return $res;
	}
	
	function getLocationName($id) {
		return $this->database->colValue("SELECT name FROM location WHERE id = ?", "name", array(array("i", $id)));
	}
	
	function getPhaseName($id) {
		return $this->database->colValue("SELECT name FROM rehearsalphase WHERE id = ?", "name", array(array("i", $id)));
	}
	
	function getConcertName($id) {
		return $this->database->colValue("SELECT begin FROM concert WHERE id = ?", "begin", array(array("i", $id)));
	}
	
	function getContactName($id) {
		$c = $this->database->fetchRow("SELECT surname, name FROM contact WHERE id = ?", array(array("i", $id)));
		return $c["name"] . " " . $c["surname"];
	}
	
	function getTourName($id) {
		return $this->database->colValue("SELECT name FROM tour WHERE id = ?", "name", array(array("i", $id)));
	}
	
	function getEquipmentName($id) {
		return $this->database->colValue("SELECT name FROM equipment WHERE id = ?", "name", array(array("i", $id)));
	}
	
	function create($values) {
		// validate
		$this->validateRecpay($values);
		
		// translate type
		if($values["otype"] == "0") {
			$values["otype"] = null;
			$values["oid"] = null;
		}
		
		if($values["btype"] == "0") {
			$values["btype"] = 0;
		}
		else {
			$values["btype"] = 1;
		}
		
		parent::create($values);
	}
	
	protected function validateRecpay($values) {
		$this->regex->isPositiveAmount($values["account"]);
		$this->regex->isText($values["subject"]);
		$this->regex->isMoney($values["amount_net"]);
		$this->regex->isMoney($values["amount_tax"]);
		$this->regex->isText($values["notes"]);
	}
	
	function update($id, $values) {
		// validate
		$this->validateRecpay($values);
		
		// translate type
		if($values["otype"] == "0") {
			$values["otype"] = null;
			$values["oid"] = null;
		}
		
		if($values["btype"] == "0") {
			$values["btype"] = 0;
		}
		else {
			$values["btype"] = 1;
		}
		
		parent::update($id, $values);
	}
	
	function book() {
		$selection = GroupSelector::getPostSelection($this->findAllNoRef(), "recpay");
		
		$finData = new FinanceData();
		
		foreach($selection as $id) {
			// create booking
			$recpay = $this->database->fetchRow("SELECT * FROM `recpay` WHERE id = ?", array(array("i", $id)));
			$recpay["bdate"] = $_POST["bdate"];
			// convert to local decimal as if it was sent from the form
			$recpay["amount_net"] = $recpay["amount_net"];
			$recpay["amount_tax"] = $recpay["amount_tax"];
			$finData->addBooking($recpay);
		}
	}
	
}

?>