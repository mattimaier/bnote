<?php
require_once 'print.php';
require_once 'pdftable.php';
require_once("lang.php");
/**
 * Class for a rehearsal participants list.
 * @author matti
 *
 */
class PartlistPDF {

	private $filename;

	/**
	 * PDF Document
	 * @var PrintPDF
	 */
	private $pdf;

	/**
	 * Data Provider
	 * @var ProbenData
	 */
	private $dao;

	/**
	 * Array of all contacts to print.
	 * @var array
	 */
	private $contacts;
	
	/**
	 * Rehearsal ID.
	 * @var int
	 */
	private $rid;

	/**
	 * Document title.
	 * @var string
	 */
	private $title;
	
	/**
	 * Create a printout of invitations for a rehearsal.
	 * @param String $filename Path and name of pdf.
	 * @param AbstractData $dao Data provider.
	 * @param Array $contacts Contacts to print.
	 * @param int $rid Rehearsal ID.
	 */
	function __construct($filename, $dao, $contacts, $rid) {
		$this->pdf = new PrintPDF();
		
		$this->filename = $filename;
		$this->dao = $dao;
		$this->contacts = $contacts;
		$this->rid = $rid;
		
		$this->title = Lang::txt("MembersPDF_PartlistPDF.title");
		
		// create the contents
		$this->contents();
		
		// write the contents
		$this->pdf->finish($filename);
	}
	
	/**
	 * Outlines the document.
	 */
	private function contents() {
		// header
		$this->pdf->SetFont($this->pdf->font_family, 'B', 18);
		$this->pdf->Ln(10);
		$this->pdf->Write($this->pdf->lineHeight, $this->title);
		$this->pdf->setFontStandard();
		$this->pdf->Ln($this->pdf->lineHeight * 2); //space
		
		// rehearsal stem data
		$reh = $this->dao->findByIdNoRef($this->rid);
		
		// -> when
		$this->pdf->Cell(30, $this->pdf->lineHeight, Lang::txt("MembersPDF_contents.from"));
		$this->pdf->SetX($this->pdf->leftMargin + 30);
		$this->pdf->Cell(50, $this->pdf->lineHeight, Data::convertDateFromDb($reh["begin"]) . Lang::txt("MembersPDF_contents.hour"));
		$this->pdf->Ln($this->pdf->lineHeight); // space
		
		// -> where
		$addy = $this->dao->adp()->getEntityForId("location", $reh["location"]);
		$this->pdf->Cell(30, $this->pdf->lineHeight, Lang::txt("MembersPDF_contents.location"));
		$this->pdf->SetX($this->pdf->leftMargin + 30);
		$this->pdf->Cell(50, $this->pdf->lineHeight, $addy["name"]);
		$this->pdf->Ln($this->pdf->lineHeight); // space
		
		// -> notes
		$notes = $reh["notes"];
		if($notes == "" || $notes == null) $notes = "-"; 
		
		$this->pdf->Cell(30, $this->pdf->lineHeight, Lang::txt("MembersPDF_contents.notes"));
		$this->pdf->SetX($this->pdf->leftMargin + 30);
		$this->pdf->MultiCell(100, $this->pdf->lineHeight, $notes);
		$this->pdf->Ln($this->pdf->lineHeight); // space
		
		// add signature column to contacts array
		$this->addSignatureCol();
		
		// Table of participants with name, instrument and field for signature
		$tab = new PDFTable($this->contacts);
		
		// -> remove some columns
		$tab->ignoreColumn(0);
		$tab->ignoreColumn(3);
		$tab->ignoreColumn(4);
		
		// -> name
		$tab->setColumnWidth(1, 50);
		$tab->changeColumnLabel(1, Lang::txt("MembersPDF_contents.name"));
		
		// -> instrument
		$tab->setColumnWidth(2, 50);
		$tab->changeColumnLabel(2, Lang::txt("MembersPDF_contents.Instrument"));
		
		// -> signature
		$tab->setColumnWidth(5, 70);
		
		// other settings
		$tab->setCellBorder('B');
		$tab->setVerticalSpacing(5);
		$tab->setPageBreakThreshold(250);
		
		$tab->write($this->pdf);
	}
	
	private function addSignatureCol() {
		// add header
		array_push($this->contacts[0], Lang::txt("MembersPDF_addSignatureCol.contact"));
		
		for($i = 1; $i < count($this->contacts); $i++) {
			array_push($this->contacts[$i], " ");
		}
	}
}

?>