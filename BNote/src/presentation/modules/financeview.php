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
		$this->setEntityName(Lang::txt("finance_account"));
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
		
		if(isset($_POST["oid"])) {
			$oid = $_POST["oid"];
			$otype = $_POST["otype"];
		}
		else if(isset($_GET["oid"])) {
			$oid = $_GET["oid"];
			$otype = $_GET["otype"];
		}
		else {
			$otype = 0;
			$oid = null;
		}
		
		return array($from, $to, $otype, $oid);
	}
	
	function isSubModule($mode) {
		if($mode == "recpay") return true;
		return false;
	}
	
	function subModuleOptions() {
		$subOptionFunc = isset($_GET["sub"]) ? $_GET["sub"] . "Options" : "startOptions";
		if($this->isSubModule($_GET['mode'])) {
			if($_GET['mode'] == "recpay") {
				$ctrl = $this->getController()->getRecpayCtrl();
			}
			$ctrl->getView()->$subOptionFunc();
		}
		else {
			$this->defaultOptions();
		}
	}
	
	function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		
		$btn = new Link($this->modePrefix() . "recpay", Lang::txt("finance_recpay"));
		$btn->addIcon("recurring");
		$btn->write();
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
		$default_otype = $fromToArr[2];
		$default_oid = $fromToArr[3];
		
		?>
		<div class="finance_filter_box">
			<span class="finance_filter_title"><?php echo Lang::txt("finance_filter_items"); ?></span>
			<form action="<?php echo $this->modePrefix() . "view&id=" . $_GET["id"]; ?>" method="POST">
				<div class="finance_filter_row">
					<label for="from"><?php echo Lang::txt("finance_date_from"); ?></label>
					<input type="date" name="from" value="<?php echo $default_from; ?>" />
					<label for="to" style="width: 30px;"><?php echo Lang::txt("finance_date_to"); ?></label>
					<input type="date" name="to" value="<?php echo $default_to; ?>" />
				</div>
				<div class="finance_filter_row">
					<label for="otype"><?php echo Lang::txt("recpay_oid"); ?></label>
					<?php 
					$objdd = $this->getController()->getRecpayCtrl()->getView()->objectReferenceForm($default_otype, $default_oid);
					if($default_otype != NULL) {
						$objdd->setSelected($default_otype);
					}
					echo $objdd->write();
					if($default_otype != NULL) {
						?>
						<script>changeReference(document.getElementById("oref"));</script>
						<?php
					}
					?>
				</div>
			<input type="submit" style="margin-left: 0px;" value="<?php echo Lang::txt("finance_bookings_filter"); ?>" />
			</form>
		</div>
		<?php
		
		// Show bookings with total
		$bookings = $this->getData()->findBookings($default_from, $default_to, $accId, $default_otype, $default_oid);
		$bookings = Table::addDeleteColumn($bookings, $this->modePrefix() . "cancelBooking&id=" . $_GET["id"] . "&booking=", "cancel", "stornieren");
		$table = new Table($bookings);
		$table->removeColumn("account");
		$table->renameHeader("id", Lang::txt("finance_booking_id"));
		$table->renameHeader("bdate", Lang::txt("finance_booking_bdate"));
		$table->renameHeader("subject", Lang::txt("finance_booking_subject"));
		$table->renameHeader("amount_net", Lang::txt("finance_booking_amount_net"));
		$table->renameHeader("amount_tax", Lang::txt("finance_booking_amount_tax"));
		$table->renameHeader("amount_total", Lang::txt("finance_booking_amount_total"));
		$table->renameHeader("btype", Lang::txt("finance_booking_btype"));
		$table->renameHeader("otype", Lang::txt("recpay_otype"));
		$table->renameHeader("oid", Lang::txt("recpay_oid"));
		$table->renameHeader("notes", Lang::txt("finance_booking_notes"));
		$table->allowWordwrap(false);
		$table->setColumnFormat("amount", "DECIMAL");
		$table->write();
		
		// show metrics
		$this->verticalSpace();
		
		Writing::h3(Lang::txt("finance_metrics_header"));
		$metrics = $this->getData()->findBookingsMetrics($default_from, $default_to, $accId, $default_otype, $default_oid);
		$mtab = new Table($metrics);
		$mtab->renameHeader("btype", Lang::txt("finance_booking_btype"));
		$mtab->renameHeader("total_net", Lang::txt("finance_booking_amount_net"));
		$mtab->renameHeader("total_tax", Lang::txt("finance_booking_amount_tax"));
		$mtab->renameHeader("total", Lang::txt("finance_booking_amount_total"));
		$mtab->write();
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
		$objdd = $this->getController()->getRecpayCtrl()->getView()->objectReferenceForm();
		$form->addElement(Lang::txt("recpay_otype"), $objdd);
		$form->autoAddElementsNew(array(
			"bdate" => array(Lang::txt("finance_booking_bdate"), FieldType::DATE),
			"subject" => array(Lang::txt("finance_booking_subject"), FieldType::CHAR),
			"amount_net" => array(Lang::txt("finance_booking_amount_net"), FieldType::DECIMAL),
			"amount_tax" => array(Lang::txt("finance_booking_amount_tax"), FieldType::DECIMAL),
			"notes" => array(Lang::txt("finance_booking_notes"), FieldType::CHAR)
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
	
	function cancelBooking() {
		$account = $_GET["id"];
		$booking = $_GET["booking"];
		$this->getData()->cancelBooking($account, $booking);
		$this->view();
	}
}

?>