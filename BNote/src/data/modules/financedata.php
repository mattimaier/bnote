<?php

/**
 * Data Access Class for finance data.
 * @author matti
 *
 */
class FinanceData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("finance_account_id"), FieldType::INTEGER),
				"name" => array(Lang::txt("finance_account_name"), FieldType::CHAR)
		);

		$this->references = array();
		$this->table = "account";
		$this->init($dir_prefix);
	}
	
	public static function getBookingTypes() {
		return array(
			0 => Lang::txt("finance_booking_type_0"),
			1 => Lang::txt("finance_booking_type_1")
		);
	}

	function findBookings($from, $to, $accountId) {		
		$query = "SELECT * FROM booking 
				WHERE bdate >= \"$from\" AND bdate <=\"$to\" AND account = $accountId
				ORDER BY bdate ASC";
		
		// get the booking types in reverse
		$btypes = $this->getBookingTypes();
		
		// edit data
		$result = $this->database->getSelection($query);
		$bookings = array();
		foreach($result as $i => $row) {
			if($i > 0) {
				$t = $row["btype"];
				$row["btype"] = $btypes[$t];
			}
			array_push($bookings, $row);
		}
		
		return $bookings;
	}
	
	function findBookingsMetrics($from, $to, $accountId) {
		$where = "bdate >= \"$from\" AND bdate <=\"$to\" AND account = $accountId";
	
		$expenses = $this->database->getCell("booking", "sum(amount)", $where . " AND btype = 1");
		$income = $this->database->getCell("booking", "sum(amount)", $where . " AND btype = 0");
		if($income == null || $income == "") {
			$income = 0;
		}
		$total = $income - $expenses; 
		if($income != 0) {
			$margin = $total / $income;
		}
		else {
			$margin = "-";
		}
		
		return array(
			"expenses" => $expenses,
			"income" => $income,
			"total" => $total,
			"margin" => $margin * 100 . "%"
		);
	}
	
	function addBooking($values) {
		// validate
		$this->regex->isPositiveDecimalOrInteger($values["account"]);
		$this->regex->isDatabaseDateQuiet($values["bdate"]);
		$this->regex->isSubject($values["subject"]);
		$this->regex->isPositiveDecimalOrInteger($values["btype"]);
		$this->regex->isText($values["notes"]);
		
		// insert to db
		$query = "INSERT INTO booking (
			account, bdate, subject, amount, btype, notes
		) VALUES (
			" . $values["account"] . ",
			\"" . Data::convertDateToDb($values["bdate"]) . "\",
			\"" . $values["subject"] . "\",
			" . Lang::decimalToDb($values["amount"]) . ",
			" . $values["btype"] . ",		
			\"" . $values["notes"] . "\"
		)";
		
		$this->database->execute($query);
	}
}

?>