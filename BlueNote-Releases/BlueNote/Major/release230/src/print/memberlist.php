<?php
require_once $GLOBALS["DIR_PRINT"] . "memberlistpdf.php";

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
	 * Either one of KontaktData or
	 * 100 for admin, member
	 * 101 for admin, member, external
	 * @var String
	 */
	private $group;
	
	private $data;
	private $title;
	
	/**
	 * Create a printout of contacts.
	 * @param String $filename Path and name of pdf.
	 * @param AbstractData $dao Data provider.
	 * @param String $group Group to print. 
	 */
	function __construct($filename, $dao, $group) {
		$this->pdf = new MemberlistPDF();
		
		$this->filename = $filename;
		$this->dao = $dao;
		$this->group = $group;
		
		$this->prepareData();
		
		// do it		
		$this->outline();
		$this->pdf->finish($filename);
	}
	
	private function prepareData() {
		switch($this->group) {
			case KontakteData::$STATUS_ADMIN:
				$this->title = "Administratoren";
				$this->data = $this->dao->getAdmins();
				break;
			case KontakteData::$STATUS_MEMBER:
				$this->title = "Band Mitspieler";
				$this->data = $this->dao->getMembers();
				break;
			case KontakteData::$STATUS_EXTERNAL:
				$this->title = "Externe Mitspieler";
				$this->data = $this->dao->getExternals();
				break;
			case KontakteData::$STATUS_APPLICANT:
				$this->title = "Bewerber";
				$this->data = $this->dao->getApplicants();
				break;
			case KontakteData::$STATUS_OTHER:
				$this->title = "Sonstige Kontakte";
				$this->data = $this->dao->getOthers();
				break;
			case 100:
				$this->title = "Administratoren und Mitspieler";
				$this->data = $this->dao->getMembers();
				break;
			case 101:
				$this->title = "Administratoren, Mitspieler und Externe";
				$d1 = $this->dao->getMembers();
				$d2 = $this->dao->getExternals();
				for($i = 1; $i < count($d2); $i++) {
					array_push($d1, $d2[$i]);
				}
				$this->data = $d1;
				break;
		}
		
		// remove columns
		$toremove = array("id", "fax", "web", "notes",
							"address", "status", "instrument");
		$deletedkeys = array();
		
		foreach($this->data[0] as $k => $v) {
			if(in_array(strtolower($v), $toremove)) {
				unset($this->data[0][$k]);
				array_push($deletedkeys, $k);
			}
		}
		
		for($i = 1; $i < count($this->data); $i++) {
			foreach($this->data[$i] as $k => $v) {
				if(in_array($k, $toremove) || in_array($k, $deletedkeys)) {
					unset($this->data[$i][$k]);
				}
			}
		}
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
		
		// table
		$this->writeTable();
	}
	
	/**
	 * Writes the main table.
	 */
	private function writeTable() {
		require_once $GLOBALS["DIR_PRINT"] . "pdftable.php";
		
		// create table with data and sum
		$table = new PDFTable($this->data);
		
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
		
		$table->changeColumnLabel(1, "Nachname");
		$table->changeColumnLabel(2, "Vorname");
		$table->changeColumnLabel(3, "Privat");
		$table->changeColumnLabel(5, "Mobil");
		$table->changeColumnLabel(6, "Berufl.");
		$table->changeColumnLabel(7, "E-Mail");
		$table->changeColumnLabel(13, "Straße");
		$table->changeColumnLabel(14, "Ort");
		$table->changeColumnLabel(15, "PLZ");
		$table->changeColumnLabel(16, "Instrument");
		
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
