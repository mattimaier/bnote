<?php

class RecpayData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"subject" => array(Lang::txt("finance_booking_subject"), FieldType::CHAR),
			"account" => array(Lang::txt("recpay_account"), FieldType::REFERENCE),
			"amount_net" => array(Lang::txt("finance_booking_amount_net"), FieldType::DECIMAL),
			"amount_tax" => array(Lang::txt("finance_booking_amount_tax"), FieldType::DECIMAL),
			"btype" => array(Lang::txt("finance_booking_btype"), FieldType::ENUM),
			"otype" => array(Lang::txt("recpay_otype"), FieldType::ENUM),
			"oid" => array(Lang::txt("recpay_oid"), FieldType::CHAR),
			"notes" => array(Lang::txt("finance_booking_notes"), FieldType::CHAR)
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
			case "L": $otype = Lang::txt("location");
			$oid = $this->getLocationName($oid);
			break;
			case "H": $otype = Lang::txt("contact");
			$oid = $this->getContactName($oid);
			break;
			case "C": $otype = Lang::txt("concert");
			$oid = $this->getConcertName($oid);
			break;
			case "P": $otype = Lang::txt("rehearsalphase");
			$oid = $this->getPhaseName($oid);
			break;
			default:
				$otype = Lang::txt("recpay_no_otype");
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
				$row["btype"] = Lang::txt("finance_booking_type_0");
			}
			else if($row["btype"] == "1") {
				$row["btype"] = Lang::txt("finance_booking_type_1");
			}
			$r2v = $this->ref2val($row["otype"], $row["oid"]);
			$row["otype"] = $r2v[0];
			$row["oid"] = $r2v[1];
			array_push($res, $row);
		}
		return $res;
	}
	
	function getLocationName($id) {
		return $this->database->getCell("location", "name", "id = $id");
	}
	
	function getPhaseName($id) {
		return $this->database->getCell("rehearsalphase", "name", "id = $id");
	}
	
	function getConcertName($id) {
		return $this->database->getCell("concert", "begin", "id = $id");
	}
	
	function getContactName($id) {
		$c = $this->database->getRow("SELECT surname, name FROM contact WHERE id = $id");
		return $c["name"] . " " . $c["surname"];
	}
	
	function create($values) {
		// validate
		$this->regex->isPositiveAmount($values["account"]);
		$this->regex->isText($values["subject"]);
		$this->regex->isMoney($values["amount_net"]);
		$this->regex->isMoney($values["amount_tax"]);
		$this->regex->isText($values["notes"]);
		
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
	
	function book() {
		$selection = GroupSelector::getPostSelection($this->findAllNoRef(), "recpay");
		
		$finData = new FinanceData();
		
		foreach($selection as $id) {
			// create booking
			$recpay = $this->database->getRow("SELECT * FROM " . $this->table . " WHERE id = $id");
			$recpay["bdate"] = $_POST["bdate"];
			$finData->addBooking($recpay);
		}
	}
	
}

?>