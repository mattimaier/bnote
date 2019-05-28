<?php
require_once $GLOBALS["DIR_PRINT"] . "memberlistpdf.php";
require_once("lang.php");
/**
 * Class for a member pdf.
 * @author matti
 *
 */
class MembersPDF {
	
	private $filename;
	
	/**
	 * PDF Document
	 * @var PrintPDF
	 */
	private $pdf;
	
	/**
	 * Data Provider
	 * @var KontakteData
	 */
	private $dao;
	
	/**
	 * Array of all groups to print.
	 * @var array
	 */
	private $groups;
	
	/**
	 * Document title.
	 * @var string
	 */
	private $title;
	
	/**
	 * Create a printout of contacts.
	 * @param String $filename Path and name of pdf.
	 * @param AbstractData $dao Data provider.
	 * @param String $groups Groups to print. 
	 */
	function __construct($filename, $dao, $groups) {
		$this->pdf = new MemberlistPDF();
		
		$this->filename = $filename;
		$this->dao = $dao;
		$this->groups = $groups;
		
		$this->title = Lang::txt("MembersPDF_construct.title");
		
		// do it		
		$this->outline();
		$this->pdf->finish($filename);
	}
	
	/**
	 * Outlines the document.
	 */
	private function outline() {
		// header
		$this->pdf->SetFont($this->pdf->font_family, 'B', 18);
		$this->pdf->Ln(10);
		$this->pdf->Write($this->pdf->lineHeight, $this->title);
		$this->pdf->setFontStandard();
		$this->pdf->Ln($this->pdf->lineHeight * 2); //space
		
		// date
		global $system_data;
		$comp = $system_data->getCompanyInformation();
		$this->pdf->writeDate(date('d.m.Y'), utf8_encode($comp["City"]));
		$this->pdf->Ln($this->pdf->lineHeight*2); //space
		
		// one table per group
		foreach($this->groups as $i => $group) {
			$this->pdf->setFontBold();
			$this->pdf->Write($this->pdf->lineHeight, $this->dao->getGroupName($group));
			$this->pdf->Ln($this->pdf->lineHeight*1.5); //space
			$this->pdf->setFontStandard();
			$this->writeTable($this->getGroupContacts($group));
		}
	}
	
	private function getGroupContacts($group) {
		$contacts = $this->dao->getGroupContacts($group);
		return $this->prepareData($contacts);
	}
	
	private function prepareData($data) {
		// remove columns
		$toremove = array("id", "fax", "web", "notes",
				"address", "status", "instrument");
		$deletedkeys = array();
	
		foreach($data[0] as $k => $v) {
			if(in_array(strtolower($v), $toremove)) {
				unset($this->data[0][$k]);
				array_push($deletedkeys, $k);
			}
		}
	
		for($i = 1; $i < count($data); $i++) {
			foreach($data[$i] as $k => $v) {
				if(in_array($k, $toremove) || in_array($k, $deletedkeys)) {
					unset($data[$i][$k]);
				}
			}
		}
	
		return $data;
	}
	
	/**
	 * Writes a contact table.
	 * @param array $data Contacts.
	 */
	private function writeTable($data) {
		require_once $GLOBALS["DIR_PRINT"] . "pdftable.php";
		
		// create table with data and sum
		$table = new PDFTable($data);
		
		// set columns
		$table->setColumnWidth(1, 30);
		$table->setColumnWidth(2, 25);
		$table->setColumnWidth(3, 25);
		$table->setColumnWidth(5, 25);
		$table->setColumnWidth(6, 25);
		$table->setColumnWidth(7, 30);
		$table->setColumnWidth(13, 35);
		$table->setColumnWidth(14, 30);
		$table->setColumnWidth(15, 15);
		$table->setColumnWidth(16, 24);
		
		$table->changeColumnLabel(1, Lang::txt("MembersPDF_writeTable.surname")"Nachname");
		$table->changeColumnLabel(2, Lang::txt("MembersPDF_writeTable.title")"Vorname");
		$table->changeColumnLabel(3, Lang::txt("MembersPDF_writeTable.phone")"Privat");
		$table->changeColumnLabel(5, Lang::txt("MembersPDF_writeTable.mobile")"Mobil");
		$table->changeColumnLabel(6, Lang::txt("MembersPDF_writeTable.occupation")"Berufl.");
		$table->changeColumnLabel(7, Lang::txt("MembersPDF_writeTable.email")"E-Mail");
		$table->changeColumnLabel(13, Lang::txt("MembersPDF_writeTable.street")"Straße");
		$table->changeColumnLabel(14, Lang::txt("MembersPDF_writeTable.city")"Ort");
		$table->changeColumnLabel(15, Lang::txt("MembersPDF_writeTable.zip")"PLZ");
		$table->changeColumnLabel(16, Lang::txt("MembersPDF_writeTable.instrument")"Instrument");
		
		$table->setColumnType(3, FieldType::CHAR);
		$table->setColumnType(5, FieldType::CHAR);
		$table->setColumnType(6, FieldType::CHAR);
		$table->setColumnType(15, FieldType::CHAR);
		
		$table->setCellBorder("T");
		$table->setVerticalSpacing(1);
		
		// write the document
		$table->write($this->pdf);
	}
}

?>
