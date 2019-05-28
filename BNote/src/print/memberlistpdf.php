<?php
require_once $GLOBALS["DIR_PRINT"] . "print.php";
require_once("lang.php");
/**
 * Special landscape PDF with headers.
 * @author matti
 *
 */
class MemberlistPDF extends PrintPDF {

	/**
	 * Constructor
	 */
	function MemberlistPDF() {
		FPDF::__construct('L','mm','A4'); // Landscape, Millimeter, A4 
		$this->AliasNbPages(); // numerate all pages
		$this->SetAutoPageBreak(true); // automatically start new pages when page full
		$this->AddPage();
		$this->SetFont($this->font_family, '', $this->font_size);
		
		$this->topMargin = 12; // 1.2cm
		
		$this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
	}
	
	/**
	 * Displays a page header on every page,
	 * overwrites FPDF function
	 */
	function Header() {
		$this->SetY(5); //Position 0.2cm from top
	    $this->SetFont($this->font_family, '', $this->footerFontSize);
	    
	    // print header
	    $this->Cell(0, 10, Lang::txt("MemberlistPDF_Header.title"), 0, 0, 'L');
	}
}