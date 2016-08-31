<?php
require_once $GLOBALS["DIR_PRINT"] . "print.php";

/**
 * Class for project timesheets.
 * @author matti
 * @deprecated as of v3.1.2 -> use print style in browser instead!
 */
class ProgramPDF {
	
	private $filename;
	
	/**
	 * PDF Document
	 * @var PrintPDF
	 */
	private $pdf;
	
	/**
	 * Data Provider
	 * @var ProgramData
	 */
	private $dao;
	
	private $pid; // program id
	
	/**
	 * Create a printout of a project.
	 * @param String $filename Full path to the file to write.
	 * @param AbstractData $dao Data Access Object.
	 * @param int $pid Project ID.
	 */
	function __construct($filename, $dao, $pid) {
		$this->pdf = new PrintPDF();
		
		$this->filename = $filename;
		$this->dao = $dao;
		$this->pid = $pid;
		
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
		$this->pdf->Write($this->pdf->lineHeight, $this->dao->getProgramName($this->pid));
		$this->pdf->setFontStandard();
		$this->pdf->Ln($this->pdf->lineHeight * 2); //space
				
		// table
		$this->writeTable();
	}
	
	/**
	 * Writes the main table.
	 */
	private function writeTable() {
		require_once $GLOBALS["DIR_PRINT"] . "pdftable.php";
		
		// fetch data
		$data = $this->dao->getSongsForProgramPrint($this->pid);
		
		// create table with data and sum
		$table = new PDFTable($data);
		$sum = $this->dao->totalProgramLength();
		
		// set columns
		$table->setColumnWidth(0, 60);
		// $table->setColumnWidth(1, 30);
		$table->setColumnWidth(2, 81);
		$table->setColumnWidth(3, 19);
		
		$table->changeColumnLabel(0, "Titel");
		// $table->changeColumnLabel(1, "Arrangeur");
		$table->changeColumnLabel(2, "Anmerkungen");
		$table->changeColumnLabel(3, "Länge");
		
		// add sum
		$table->addSumLine("Gesamtlänge", $sum, 2);
		
		// write the document
		$table->write($this->pdf);
	}
}

?>
