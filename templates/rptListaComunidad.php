<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";

    public function setTitulo($titulo, $total){
        $this->titulo=$titulo;
        $this->total=$total;
    }

	function Header(){
		$this->Image('../public/assets/images/BITU transparente.png', 15, 5, 45, 30);

        $arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    	$subtitulo = "Al ".date('d')." de ".$arrMes[intval(date('m'))]." de ".date('Y')." ".date('H:i:s');

		$this->Ln(15);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 14);
		$this->Cell(0, 5, utf8_decode($this->titulo)."          ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
        $this->Cell(0, 5, utf8_decode($subtitulo)."            ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
        $this->Cell(0, 5, utf8_decode($this->total)."            ", 0, 1, 'R');		
		$this->Ln(10);
        $this->SetX(20);
        $this->Cell(173, 0, '', 'T', 0, 'L');
		$this->Ln(1);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(10);
		$this->Cell(50, 2.5, 'Credencial', 0, 0, 'C');
        $this->Cell(90, 2.5, 'Nombre', 0, 0, 'C');
        $this->Cell(43, 2.5, 'Colonia', 0, 0, 'C');
        $this->SetXY(20,47);
        $this->Cell(173, 0, '', 'T', 0, 'L');
	}

	function Footer(){
		$paginas = "Página " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
		$this->SetY(-15);
		$this->SetTextColor(77, 73, 72);
	    $this->Ln(4);
	    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
	    $this->Cell(0, 2.5, $paginas, 0, 0, 'C');
	}
}

$miReporte = new PDF('P', 'mm', 'letter'); 
$miReporte->setTitulo($vista, $total);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetDisplayMode('real');
$miReporte->SetAuthor('DDS.media'); 
$miReporte->SetCreator('www.ddsmedia.net'); 
$miReporte->SetTitle($vista); 
$miReporte->SetSubject($vista);

$miReporte->SetFillColor(240, 240, 240); 
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);

$miReporte->SetXY(20,50);
$miReporte->SetMargins(20, 50); 
$contador = 2;

$prove = '';
$pesoo = 0;
foreach($registros as $prod){
    $relleno = $contador % 2;
        $miReporte->Cell(60, 2.5, $prod->credencial, 0, 0, 'L', $relleno);
        $miReporte->Cell(70, 2.5, $prod->nombre, 0, 0, 'L', $relleno);
        $miReporte->Cell(43, 2.5, $prod->colonia, 0, 1, 'C', $relleno);
        $contador = $contador + 1;
}

$relleno = $contador % 2;
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(174, 2.5, '', 'B', 0, 'C', $relleno);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Output();
exit();
?>