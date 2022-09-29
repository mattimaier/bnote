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
		$this->setEntityName(lang::txt("FinanceView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("FinanceView_construct.addEntityName"));
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
	
	function getTitle() {
		if(isset($_GET["mode"]) && $this->isSubModule($_GET['mode'])) {
			if($_GET['mode'] == "recpay") {
				$ctrl = $this->getController()->getRecpayCtrl();
			}
			return $ctrl->getView()->getTitle();
		}
		return parent::getTitle();
	}
	
	function startOptions() {
		parent::startOptions();
		
		$btn = new Link($this->modePrefix() . "recpay", Lang::txt("FinanceView_startOptions.recpay"));
		$btn->addIcon("calendar4-range");
		$btn->write();
		
		$transfer = new Link($this->modePrefix() . "transfer", Lang::txt("FinanceView_startOptions.transfer"));
		$transfer->addIcon("signpost");
		$transfer->write();
		
		$multi_reporting = new Link($this->modePrefix() . "multireport", Lang::txt("FinanceView_startOptions.multireporting"));
		$multi_reporting->addIcon("clipboard-data");
		$multi_reporting->write();
	}
	
	function viewTitle() {
		$accId = $_GET["id"];
		$accDetails = $this->getData()->findByIdNoRef($accId);
		return $accDetails["name"] . " (" . $accId . ")";
	}
	
	function view() {
		?>
		<style>
		/* Optimize Print Styling for this page */
		@media print {
			.finance_filter_box {
				display: none;
			}
		}
		</style>
		<?php
		$accId = $_GET["id"];	
		
		// Show filter
		$fromToArr = $this->getFilterSettings();
		$default_from = $fromToArr[0];
		$default_to = $fromToArr[1];
		$default_otype = $fromToArr[2];
		$default_oid = $fromToArr[3];
		
		?>
		<div class="finance_filter_box">
			<h6 class="finance_filter_title h6"><?php echo Lang::txt("FinanceView_finance_filter_box.filter_items"); ?></h6>
			<form action="<?php echo $this->modePrefix() . "view&id=" . $_GET["id"]; ?>" method="POST">
			<div class="row">
				<div class="col-md-3">
					<label for="from"><?php echo Lang::txt("FinanceView_finance_filter_box.date_from"); ?></label>
					<input type="date" class="form-control" name="from" value="<?php echo $default_from; ?>" />
				</div>
				<div class="col-md-3">
					<label for="to" style="width: 30px;"><?php echo Lang::txt("FinanceView_finance_filter_box.date_to"); ?></label>
					<input type="date" class="form-control" name="to" value="<?php echo $default_to; ?>" />
				</div>
				<div class="col-md-3">
					<label for="otype"><?php echo Lang::txt("FinanceView_finance_filter_row.otype"); ?></label>
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
				<div class="col-md-3">
					<input type="submit" class="btn btn-primary mt-4" value="<?php echo Lang::txt("FinanceView_finance_filter_row.bookings_filter"); ?>" />
				</div>
			</div>
			</form>
		</div>
		<?php
		
		// show metrics
		Writing::h4(Lang::txt("FinanceView_Table_metrics.header"), "mt-3");
		$metrics = $this->getData()->findBookingsMetrics($default_from, $default_to, $accId, $default_otype, $default_oid);
		$mtab = new Table($metrics);
		$mtab->showFilter(false);
		$mtab->renameHeader("btype", Lang::txt("FinanceView_Table_metrics.btype"));
		$mtab->renameHeader("total_net", Lang::txt("FinanceView_Table_metrics.amount_net"));
		$mtab->renameHeader("total_tax", Lang::txt("FinanceView_Table_metrics.amount_tax"));
		$mtab->renameHeader("total", Lang::txt("FinanceView_Table_metrics.amount_total"));
		$mtab->setColumnFormat("total_net", "CURRENCY");
		$mtab->setColumnFormat("total_tax", "CURRENCY");
		$mtab->setColumnFormat("total", "CURRENCY");
		$mtab->write();
		
		// Show bookings with total
		Writing::h4(Lang::txt("FinanceView_Table_booking.header"), "mt-3");
		$bookings = $this->getData()->findBookings($default_from, $default_to, $accId, $default_otype, $default_oid);
		$bookings = Table::addDeleteColumn(
				$bookings,
				$this->modePrefix() . "cancelBooking&id=" . $_GET["id"] . "&booking=",
				"cancel",
				Lang::txt("FinanceView_Table_booking.cancel"),
				"journal-minus"
		);
		$table = new Table($bookings);
		$table->removeColumn("account");
		$table->renameHeader("id", Lang::txt("FinanceView_Table_booking.id"));
		$table->renameHeader("bdate", Lang::txt("FinanceView_Table_booking.bdate"));
		$table->renameHeader("subject", Lang::txt("FinanceView_Table_booking.subject"));
		$table->renameHeader("amount_net", Lang::txt("FinanceView_Table_booking.amount_net"));
		$table->renameHeader("amount_tax", Lang::txt("FinanceView_Table_booking.amount_tax"));
		$table->renameHeader("amount_total", Lang::txt("FinanceView_Table_booking.amount_total"));
		$table->renameHeader("btype", Lang::txt("FinanceView_Table_booking.booking_btype"));
		$table->renameHeader("otype", Lang::txt("FinanceView_Table_booking.otype"));
		$table->renameHeader("oid", Lang::txt("FinanceView_Table_booking.oid"));
		$table->renameHeader("notes", Lang::txt("FinanceView_Table_booking.notes"));
		$table->allowWordwrap(false);
		$table->setColumnFormat("amount", "CURRENCY");
		$table->setColumnFormat("id", "TEXT");
		$table->setOptionColumnNames(array("cancel"));
		$table->write();
		
		
	}
	
	function additionalViewButtons() {
		$fromToArr = $this->getFilterSettings();
		$from = $fromToArr[0];
		$to = $fromToArr[1];
		$addBooking = new Link($this->modePrefix() . "addBooking&id=" . $_GET["id"] . "&from=$from&to=$to", Lang::txt("FinanceView_additionalViewButtons.addbooking"));
		$addBooking->addIcon("plus");
		$addBooking->write();
		
		$prt = new Link("javascript:window.print()", Lang::txt("FinanceView_additionalViewButtons.print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function addBookingTitle() { return Lang::txt("FinanceView_addBooking.Form"); }
	
	function addBooking() {
		$fromToArr = $this->getFilterSettings();
		$from = $fromToArr[0];
		$to = $fromToArr[1];
		$form = new Form("", $this->modePrefix() . "addBookingProcess&id=" . $_GET["id"] . "&from=$from&to=$to");
		
		$dd = new Dropdown("btype");
		$btypes = FinanceData::getBookingTypes();
		foreach($btypes as $val => $capt) {
			$dd->addOption($capt, $val);
		}
		$dd->setSelected(1);
		$form->addElement(Lang::txt("FinanceView_addBooking.btype"), $dd);
		$form->setFieldRequired(Lang::txt("FinanceView_addBooking.btype"));
		
		$objdd = $this->getController()->getRecpayCtrl()->getView()->objectReferenceForm();
		$form->addElement(Lang::txt("FinanceView_addBooking.otype"), $objdd);
		$form->autoAddElementsNew(array(
			"bdate" => array(Lang::txt("FinanceView_addBooking.bdate"), FieldType::DATE, true, 3),
			"subject" => array(Lang::txt("FinanceView_addBooking.subject"), FieldType::CHAR, true, 3),
			"amount_net" => array(Lang::txt("FinanceView_addBooking.amount_net"), FieldType::CURRENCY, true, 3),
			"amount_tax" => array(Lang::txt("FinanceView_addBooking.amount_tax"), FieldType::CURRENCY, true, 3),
			"notes" => array(Lang::txt("FinanceView_addBooking.notes"), FieldType::CHAR, false, 12)
		));
		$form->setFieldValue("amount_tax", "0,00");
		
		$form->write();
	}
	
	function addBookingOptions() {
		$this->backToViewButton($_GET["id"] . "&from=" . $_GET["from"] . "&to=" . $_GET["to"]);	
	}
	
	function addBookingProcess() {
		$_POST["account"] = $_GET["id"];
		$this->getData()->addBooking($_POST);
		new Message(Lang::txt("FinanceView_addBookingProcess.title"), Lang::txt("FinanceView_addBookingProcess.saved"));
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
	
	function cancelBookingOptions() {
		$this->viewOptions();
	}
	
	function transfer() {
		$form = new Form(Lang::txt("FinanceView_transfer.Form"), $this->modePrefix() . "processTransfer");
		
		$accounts = $this->getData()->findAllNoRef();
		$accountBoxFrom = new Dropdown("account_from");
		$accountBoxTo = new Dropdown("account_to");
		for($i = 1; $i < count($accounts); $i++) {
			$accountBoxFrom->addOption($accounts[$i]["name"], $accounts[$i]["id"]);
			$accountBoxTo->addOption($accounts[$i]["name"], $accounts[$i]["id"]);
		}
		
		$form->addElement(Lang::txt("FinanceView_transfer.from"), $accountBoxFrom);
		$form->addElement(Lang::txt("FinanceView_transfer.to"), $accountBoxTo);
		
		$form->addElement(Lang::txt("FinanceView_transfer.bdate"), new Field("bdate", "", FieldType::DATE));
		$form->addElement(Lang::txt("FinanceView_transfer.subject"), new Field("subject", "", FieldType::CHAR));
		$form->addElement(Lang::txt("FinanceView_transfer.amount_net"), new Field("amount_net", "0,00", FieldType::CURRENCY));
		$form->addElement(Lang::txt("FinanceView_transfer.amount_tax"), new Field("amount_tax", "0,00", FieldType::CURRENCY));
		
		$form->write();
	}
	
	function processTransfer() {
		$this->getData()->transfer($_POST);
		new Message(Lang::txt("FinanceView_processTransfer.title"), Lang::txt("FinanceView_processTransfer.message"));
	}
	
	function multireport() {
		/* Filters
		 * -------
		 * - Time
		 * - Accounts
		 * - Objects
		 */
		// Show filter
		$fromToArr = $this->getFilterSettings();
		$default_from = $fromToArr[0];
		$default_to = $fromToArr[1];
		$default_otype = $fromToArr[2];
		$default_oid = $fromToArr[3];
		
		?>
		<div class="row">
			<h4 class="h4"><?php echo Lang::txt("FinanceView_multireport.items"); ?></h4>
			<form action="<?php echo $this->modePrefix() . "multireportResult"; ?>" method="POST">
				<div class="col-md-3">
					<label for="from"><?php echo Lang::txt("FinanceView_multireport.from"); ?></label>
					<input class="form-control" type="date" name="from" value="<?php echo $default_from; ?>" />
				</div>
				<div class="col-md-3">
					<label for="to" style="width: 30px;"><?php echo Lang::txt("FinanceView_multireport.to"); ?></label>
					<input class="form-control" type="date" name="to" value="<?php echo $default_to; ?>" />
				</div>
				<div class="col-md-3">
					<label for="otype"><?php echo Lang::txt("FinanceView_multireport.oid"); ?></label>
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
				<div class="col-md-3 mt-2">
					<label for="accounts"><?php echo Lang::txt("FinanceView_multireport.accounts"); ?></label>
					<?php 
					$accounts = $this->getData()->findAllNoRef();
					$objdd = new GroupSelector($accounts, array(), "accounts");
					$objdd->additionalCssClasses("finance_multireport_account_selection");
					echo $objdd->write();
					?>
				</div>
				<div class="col-md-3 mt-3">
					<input class="btn btn-primary" type="submit" style="margin-left: 0px;" value="<?php echo Lang::txt("FinanceView_multireport.submit"); ?>" />
				</div>
			</form>
		</div>
		<?php
	}
	
	function multireportResult() {
		// show results according to filters
		Writing::h1(Lang::txt("FinanceView_multireportResult.title"));
		?>
		<style>
		@media print {
			@page {size: landscape}
			
			.dataTables_filter {
			    display: none;
			}
			
			td.DataTable_Header {
				color: #444444;
				border-bottom-width: 2px;
			}
		}
		</style>
		<div id="finance_multireport_report">
		<?php
		
		$accounts = $this->getData()->findAllNoRef();
		$accountIds = GroupSelector::getPostSelection($accounts, "accounts");
		
		// show metrics per account in one table
		$sumMetrics = array(array(
			"account", "in_total_net", "in_total_tax", "in_total", "out_total_net", "out_total_tax", "out_total", "sum_net", "sum_tax", "sum_gross"
		));
		$sumMetricsTotals = array();
		foreach($accountIds as $i => $accId) {			
			$default_otype = null;
			if($_POST['otype'] > 0) {
				$default_otype = $_POST['otype'];
			}
			$default_oid = null;
			if(isset($_POST['oid'])) {
				$default_oid = $_POST['oid'];
			}
			
			$accName = $this->getData()->findByIdNoRef($accId)['name'];
			$metrics = $this->getData()->findBookingsMetrics($_POST["from"], $_POST['to'], $accId, $default_otype, $default_oid, FALSE);
			$accRow = array(
				"account" => $accName,
				"in_total_net" => $metrics[1]['total_net'],
				"in_total_tax" => $metrics[1]['total_tax'],
				"in_total" => $metrics[1]['total'],
				"out_total_net" => $metrics[2]['total_net'],
				"out_total_tax" => $metrics[2]['total_tax'],
				"out_total" => $metrics[2]['total'],
				"sum_net" => $metrics[1]['total_net'] - $metrics[2]['total_net'],
				"sum_tax" => $metrics[1]['total_tax'] - $metrics[2]['total_tax'],
				"sum_gross" => $metrics[1]['total'] - $metrics[2]['total']
			);
			array_push($sumMetrics, $accRow);
			foreach($accRow as $k => $v) {
				if(!isset($sumMetricsTotals[$k])) {
					$sumMetricsTotals[$k] = $v;
				}
				else if($k != "account") {
					$sumMetricsTotals[$k] += $v;
				}
			}
		}
		$sumMetricsTotals['account'] = Lang::txt("FinanceView_multireportResult.sum");
		array_push($sumMetrics, $sumMetricsTotals);
		
		// show total
		$mtab = new Table($sumMetrics);
		$mtab->renameHeader("account", Lang::txt("FinanceView_multireportResult.account"));
		$mtab->renameHeader("in_total_net", Lang::txt("FinanceView_multireportResult.in_total_net"));
		$mtab->setColumnFormat("in_total_net", "CURRENCY");
		$mtab->renameHeader("in_total_tax", Lang::txt("FinanceView_multireportResult.in_total_tax"));
		$mtab->setColumnFormat("in_total_tax", "CURRENCY");
		$mtab->renameHeader("in_total", Lang::txt("FinanceView_multireportResult.in_total"));
		$mtab->setColumnFormat("in_total", "CURRENCY");
		$mtab->renameHeader("out_total_net", Lang::txt("FinanceView_multireportResult.out_total_net"));
		$mtab->setColumnFormat("out_total_net", "CURRENCY");
		$mtab->renameHeader("out_total_tax", Lang::txt("FinanceView_multireportResult.out_total_tax"));
		$mtab->setColumnFormat("out_total_tax", "CURRENCY");
		$mtab->renameHeader("out_total", Lang::txt("FinanceView_multireportResult.out_total"));
		$mtab->setColumnFormat("out_total", "CURRENCY");
		$mtab->renameHeader("sum_net", Lang::txt("FinanceView_multireportResult.sum_net"));
		$mtab->setColumnFormat("sum_net", "CURRENCY");
		$mtab->renameHeader("sum_tax", Lang::txt("FinanceView_multireportResult.sum_tax"));
		$mtab->setColumnFormat("sum_tax", "CURRENCY");
		$mtab->renameHeader("sum_gross", Lang::txt("FinanceView_multireportResult.sum_gross"));
		$mtab->allowRowSorting(false);
		$mtab->write();
		?></div><?php
	}
	
	function multireportResultOptions() {
		$multi_reporting = new Link($this->modePrefix() . "multireport", Lang::txt("FinanceView_multireportResultOptions.back"));
		$multi_reporting->addIcon("arrow_left");
		$multi_reporting->write();
		
		$print = new Link("javascript:print()", Lang::txt("FinanceView_multireportResultOptions.print"));
		$print->addIcon("printer");
		$print->write();
	}
}

?>