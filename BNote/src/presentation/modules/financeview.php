<?php
/**
 * View for finance module.
 * @author matti
 *
 */
class FinanceView extends CrudView {
	
	/**
	 * Create the repertoire view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Account");
	}
	
	private function getFilterSettings() {
		if(isset($_GET["from"])) {
			$from = $_GET["from"];
			$to = $_GET["to"];
		}
		else if(isset($_POST["from"])) {
			$from = $_POST["from"];
			$to = $_POST["to"];
		}
		else {
			$from = date("Y") . "-01-01";
			$to = date("Y-m-d");
		}
		return array($from, $to);
	}
	
	function view() {
		$accId = $_GET["id"];
		$accDetails = $this->getData()->findByIdNoRef($accId);
		Writing::h2($accDetails["name"]);
		Writing::p(Lang::txt("finance_account_id") . ": " . $accId);
		
		// Show filter
		$fromToArr = $this->getFilterSettings();
		$default_from = $fromToArr[0];
		$default_to = $fromToArr[1];
		?>
		<div class="finance_filter_line">
			<span class="finance_filter_title"><?php echo Lang::txt("finance_filter_items"); ?></span>
			<form action="<?php echo $this->modePrefix() . "view&id=" . $_GET["id"]; ?>" method="POST">
			<label for="from"><?php echo Lang::txt("finance_date_from"); ?></label><input type="date" name="from" value="<?php echo $default_from; ?>" />
			<label for="to"><?php echo Lang::txt("finance_date_to"); ?></label><input type="date" name="to" value="<?php echo $default_to; ?>" />
			<input type="submit" value="<?php echo Lang::txt("finance_bookings_filter"); ?>" />
			</form>
		</div>
		<?php
		
		// Show bookings with total
		$bookings = $this->getData()->findBookings($default_from, $default_to, $accId);
		$table = new Table($bookings);
		$table->removeColumn("account");
		$table->renameHeader("id", Lang::txt("finance_booking_id"));
		$table->renameHeader("bdate", Lang::txt("finance_booking_bdate"));
		$table->renameHeader("subject", Lang::txt("finance_booking_subject"));
		$table->renameHeader("amount", Lang::txt("finance_booking_amount"));
		$table->renameHeader("btype", Lang::txt("finance_booking_btype"));
		$table->renameHeader("notes", Lang::txt("finance_booking_notes"));
		$table->removeColumn("otype");
		$table->removeColumn("oid");
		$table->allowWordwrap(false);
		$table->setColumnFormat("amount", "DECIMAL");
		$table->write();
		
		// show metrics
		Writing::h3(Lang::txt("finance_metrics_header"));
		$dv = new Dataview();
		$metrics = $this->getData()->findBookingsMetrics($default_from, $default_to, $accId);
		$dv->addElement(Lang::txt("finance_metrics_income"), $metrics["income"]);
		$dv->addElement(Lang::txt("finance_metrics_expenses"), $metrics["expenses"]);
		$dv->addElement(Lang::txt("finance_metrics_total"), $metrics["total"]);
		$dv->addElement(Lang::txt("finance_metrics_margin"), $metrics["margin"]);
		$dv->write();
	}
	
	function additionalViewButtons() {
		$fromToArr = $this->getFilterSettings();
		$from = $fromToArr[0];
		$to = $fromToArr[1];
		$addBooking = new Link($this->modePrefix() . "addBooking&id=" . $_GET["id"] . "&from=$from&to=$to", Lang::txt("finance_add_booking"));
		$addBooking->addIcon("plus");
		$addBooking->write();
	}
	
	function addBooking() {
		$fromToArr = $this->getFilterSettings();
		$from = $fromToArr[0];
		$to = $fromToArr[1];
		$form = new Form(Lang::txt("finance_add_booking"), $this->modePrefix() . "addBookingProcess&id=" . $_GET["id"] . "&from=$from&to=$to");
		$dd = new Dropdown("btype");
		$btypes = FinanceData::getBookingTypes();
		foreach($btypes as $val => $capt) {
			$dd->addOption($capt, $val);
		}
		$dd->setSelected(1);
		$form->addElement(Lang::txt("finance_booking_btype"), $dd);
		$form->autoAddElementsNew(array(
			"bdate" => array(Lang::txt("finance_booking_bdate"), FieldType::DATE),
			"subject" => array(Lang::txt("finance_booking_subject"), FieldType::CHAR),
			"amount" => array(Lang::txt("finance_booking_amount"), FieldType::DECIMAL),
			"notes" => array(Lang::txt("finance_booking_notes"), FieldType::TEXT)
		));
		
		$form->write();
	}
	
	function addBookingOptions() {
		$this->backToViewButton($_GET["id"] . "&from=" . $_GET["from"] . "&to=" . $_GET["to"]);	
	}
	
	function addBookingProcess() {
		$_POST["account"] = $_GET["id"];
		$this->getData()->addBooking($_POST);
		new Message(Lang::txt("finance_booking_saved_title"), Lang::txt("finance_booking_saved"));
	}
	
	function addBookingProcessOptions() {
		$this->addBookingOptions();
	}
}

?>