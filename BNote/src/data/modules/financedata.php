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
			"id" => array(Lang::txt("FinanceData_construct.id"), FieldType::INTEGER),
			"name" => array(Lang::txt("FinanceData_construct.name"), FieldType::CHAR)
		);

		$this->references = array();
		$this->table = "account";
		$this->init($dir_prefix);
	}
	
	public static function getBookingTypes() {
		return array(
			0 => Lang::txt("FinanceData_getBookingTypes.type_0"),
			1 => Lang::txt("FinanceData_getBookingTypes.type_1")
		);
	}

	function findBookings($from, $to, $accountId, $otype=NULL, $oid=NULL) {		
		$params = array(array("s", $from), array("s", $to), array("i", $accountId));
		$otype_oid = "";
		if($otype != null and $oid != null) {
			$otype_oid = "AND otype = ? AND oid = ? ";
			array_push($params, array("s", $otype));
			array_push($params, array("i", $oid));
		}
		$query = "SELECT id, bdate, subject, amount_net, amount_tax, amount_net + amount_tax as amount_total, 
						 btype, otype, oid, notes 
				FROM booking 
				WHERE bdate >= ? AND bdate <= ? AND account = ? $otype_oid
				ORDER BY bdate DESC";
		$result = $this->database->getSelection($query, $params);
		
		// get the booking types in reverse
		$btypes = $this->getBookingTypes();
		
		// edit data
		$recpayData = new RecpayData();
		$bookings = array();
		foreach($result as $i => $row) {
			if($i > 0) {
				$t = $row["btype"];
				$row["btype"] = $btypes[$t];
				
				$r2v = $recpayData->ref2val($row["otype"], $row["oid"]);
				$row["otype"] = $r2v[0];
				$row["oid"] = $r2v[1];
			}
			array_push($bookings, $row);
		}
		
		return $bookings;
	}
	
	function findBookingsMetrics($from, $to, $accountId, $otype=NULL, $oid=NULL, $formatted=TRUE) {
		$params = array(array("s", $from), array("s", $to), array("i", $accountId));
		$otype_oid = "";
		if($otype != null and $oid != null) {
			$otype_oid = "AND otype = ? AND oid = ? ";
			array_push($params, array("s", $otype));
			array_push($params, array("i", $oid));
		}
		$query = "SELECT btype, sum(amount_net) as total_net, sum(amount_tax) as total_tax, sum(amount_net)+sum(amount_tax) as total 
				FROM `booking` 
				WHERE bdate >= ? AND bdate <= ? AND account = ? $otype_oid
				GROUP BY btype";
		$tab = $this->database->getSelection($query, $params);
		
		$result = array(
			$tab[0]  // header
		);
		$row_total = array(
			"btype" => Lang::txt("FinanceData_findBookingsMetrics.sum"),
			"total_net" => 0.0,
			"total_tax" => 0.0,
			"total" => 0.0
		);
		
		for($i = 1; $i < count($tab); $i++) {
			$row = $tab[$i];
			
			$net = $row["total_net"];
			$tax = $row["total_tax"];
			$tot = $row["total"];
			
			if($net == null || $net == "") $net = 0.0;
			if($tax == null || $tax == "") $tax = 0.0;
			if($tot == null || $tot == "") $tot = 0.0;
			
			if($row["btype"] == "0") {
				// income
				$row["btype"] = Lang::txt("FinanceData_findBookingsMetrics.income");
			}
			elseif ($row["btype"] == "1") {
				// expense
				$row["btype"] = Lang::txt("FinanceData_findBookingsMetrics.expenses");
				
				$net *= -1;
				$tax *= -1;
				$tot *= -1;
			}
			
			$row_total["total_net"] += $net;
			$row_total["total_tax"] += $tax;
			$row_total["total"] += $tot;
			
			array_push($result, $row);
		}
		
		// format total
		if($formatted) {
			$row_total["total_net"] = Data::convertFromDb($row_total["total_net"]);
			$row_total["total_tax"] = Data::convertFromDb($row_total["total_tax"]);
			$row_total["total"] = Data::convertFromDb($row_total["total"]);
		}
		
		array_push($result, $row_total);
		
		return $result;
	}
	
	function addBooking($values) {
		// validate
		$this->regex->isPositiveDecimalOrInteger($values["account"]);
		$this->regex->isDatabaseDateQuiet($values["bdate"]);
		$this->regex->isSubject($values["subject"]);
		$this->regex->isPositiveDecimalOrInteger($values["btype"]);
		$this->regex->isText($values["notes"]);
		$this->regex->isMoneyEnglish($values["amount_net"], "amount_net");
		
		if(!isset($values["oid"])) {
			$values["oid"] = "NULL";
		}
		if($values["amount_tax"] == "") {
			$values["amount_tax"] = 0;
		}
		else {
			$this->regex->isMoneyEnglish($values["amount_tax"], "amount_tax");
		}
		
		// insert to db
		$query = "INSERT INTO booking (account, bdate, subject, amount_net, amount_tax, btype, otype, oid, notes)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params = array(
			array("i", $values["account"]),
			array("s", $values["bdate"]),
			array("s", $values["subject"]),
			array("d", $values["amount_net"]),
			array("d", $values["amount_tax"]),
			array("i", $values["btype"]),
			array("s", $values["otype"]),
			array("i", $values["oid"]),				
			array("s", $values["notes"])
		);
		$this->database->execute($query, $params);
	}
	
	function cancelBooking($account, $booking_id) {
		// validation
		$this->regex->isPositiveAmount($account);
		$this->regex->isPositiveAmount($booking_id);
		
		// get the booking
		$bookingQuery = "SELECT * FROM booking WHERE account = ? AND id = ?";
		$booking = $this->database->fetchRow($bookingQuery, array(array("i", $account), array("i", $booking_id)));
		
		// update the booking - just set the amounts to 0 and add the previous ones to the notes
		$notes = $booking["notes"];
		if($notes == null) $notes = "";
		if($notes != "") $notes .= "; ";
		$notes .= Lang::txt("FinanceData_cancelBooking.netto") . Lang::formatDecimal($booking["amount_net"]) . Lang::txt("FinanceData_cancelBooking.steuer") . Lang::formatDecimal($booking["amount_tax"]);
		
		$query = "UPDATE booking SET amount_net = 0, amount_tax = 0, notes = \"$notes\" WHERE id = $booking_id";
		$this->database->execute($query);
	}
	
	function transfer($booking) {
		// validate
		$this->regex->isPositiveAmount($booking["account_from"]);
		$this->regex->isPositiveAmount($booking["account_to"]);
		if($booking["account_from"] == $booking["account_to"]) {
			new Error(Lang::txt("FinanceData_transfer_same_account"));
		}
		$this->regex->isDate($booking["bdate"]);
		$this->regex->isSubject($booking["subject"]);
		$this->regex->isMoney($booking["amount_net"]);
		$this->regex->isMoney($booking["amount_tax"]);
		if($booking["amount_tax"] == "") {
			$booking["amount_tax"] = 0;
		}
		
		// read account names
		$name_from = $this->getAccountName($booking['account_from']);
		$name_to = $this->getAccountName($booking['account_to']);
		
		// prepare bookings
		$booking['notes'] = Lang::txt("FinanceData_transfer_note", array($name_from)) . " " . $name_to;
		$booking['otype'] = 0;
		$booking_from = $booking;
		$booking_from["btype"] = 1;  // withdraw
		$booking_from['account'] = $booking['account_from'];
		$booking_to = $booking;
		$booking_to["btype"] = 0;  // payment
		$booking_to['account'] = $booking['account_to'];
		
		// do the booking: not transaction save!!!
		$this->addBooking($booking_from);
		$this->addBooking($booking_to);
	}
	
	public function getAccountName($aid) {
		return $this->database->colValue("SELECT name FROM account WHERE id = ?", "name", array(array("i", $aid)));
	}
}

?>