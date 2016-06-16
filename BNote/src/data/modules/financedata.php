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

	function findBookings($from, $to, $accountId, $otype=NULL, $oid=NULL) {		
		$otype_oid = "";
		
		if($otype != null and $oid != null) {
			$otype_oid = "AND otype = \"$otype\" AND oid = $oid ";
		}
		
		$query = "SELECT id, bdate, subject, amount_net, amount_tax, amount_net + amount_tax as amount_total, 
						 btype, otype, oid, notes 
				FROM booking 
				WHERE bdate >= \"$from\" AND bdate <=\"$to\" AND account = $accountId $otype_oid
				ORDER BY bdate ASC";
		
		// get the booking types in reverse
		$btypes = $this->getBookingTypes();
		
		// edit data
		$recpayData = new RecpayData();
		$result = $this->database->getSelection($query);
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
	
	function findBookingsMetrics($from, $to, $accountId, $otype=NULL, $oid=NULL) {
		$otype_oid = "";
		
		if($otype != null and $oid != null) {
			$otype_oid = "AND otype = \"$otype\" AND oid = $oid ";
		}
		
		$where = "bdate >= \"$from\" AND bdate <=\"$to\" AND account = $accountId $otype_oid";
	
		$query = "SELECT btype, sum(amount_net) as total_net, sum(amount_tax) as total_tax, sum(amount_net)+sum(amount_tax) as total 
				FROM `booking` WHERE $where GROUP BY btype";
		
		$tab = $this->database->getSelection($query);
		
		$result = array(
			$tab[0]  // header
		);
		$row_total = array(
			"btype" => Lang::txt("finance_metrics_sum"),
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
				$row["btype"] = Lang::txt("finance_metrics_income");
			}
			elseif ($row["btype"] == "1") {
				// expense
				$row["btype"] = Lang::txt("finance_metrics_expenses");
				
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
		$row_total["total_net"] = Data::convertFromDb($row_total["total_net"]);
		$row_total["total_tax"] = Data::convertFromDb($row_total["total_tax"]);
		$row_total["total"] = Data::convertFromDb($row_total["total"]);
		
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
		
		// insert to db
		$query = "INSERT INTO booking (
			account, bdate, subject, amount_net, amount_tax, btype, otype, oid, notes
		) VALUES (
			" . $values["account"] . ",
			\"" . Data::convertDateToDb($values["bdate"]) . "\",
			\"" . $values["subject"] . "\",
			" . Lang::decimalToDb($values["amount_net"]) . ",
			" . Lang::decimalToDb($values["amount_tax"]) . ",
			" . $values["btype"] . ",
			\"" . $values["otype"] . "\",
			" . $values["oid"] . ",				
			\"" . $values["notes"] . "\"
		)";
		
		$this->database->execute($query);
	}
	
	function cancelBooking($account, $booking_id) {
		// validation
		$this->regex->isPositiveAmount($account);
		$this->regex->isPositiveAmount($booking_id);
		
		// get the booking
		$booking = $this->database->getRow("SELECT * FROM booking WHERE account = $account AND id = $booking_id");
		
		// update the booking - just set the amounts to 0 and add the previous ones to the notes
		$notes = $booking["notes"];
		if($notes == null) $notes = "";
		if($notes != "") $notes .= "; ";
		$notes .= "STORNIERT: netto " . Lang::formatDecimal($booking["amount_net"]) . " steuer " . Lang::formatDecimal($booking["amount_tax"]);
		
		$query = "UPDATE booking SET amount_net = 0, amount_tax = 0, notes = \"$notes\" WHERE id = $booking_id";
		$this->database->execute($query);
	}
}

?>