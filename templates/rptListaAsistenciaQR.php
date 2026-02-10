<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $sub = "";
    private $sub2 = "";

    public function setTitulo($titulo, $sub, $sub2){
        $this->titulo=$titulo;
        $this->sub=$sub;
        $this->sub2=$sub2;
    }

	function Header(){
		$this->Image('../public/assets/images/BITU transparente.png', 15, 5, 45, 30);    
		$this->Ln(15);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 15);
		$this->Cell(0, 5, utf8_decode($this->titulo)."         ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 11);
        $this->Cell(0, 5, $this->sub."            ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->Cell(0, 5, utf8_decode($this->sub2)."              ", 0, 1, 'R');
		$this->Ln(10);
        $this->SetX(20);
        $this->Cell(173, 0, '', 'T', 0, 'L');
		$this->Ln(1);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(10);
		$this->Cell(40, 3.5, 'Credencial', 0, 0, 'C');
        $this->Cell(80, 3.5, 'Nombre', 0, 0, 'C');
        $this->Cell(14, 3.5, 'Lista', 0, 0, 'R');
        $this->Cell(18, 3.5, 'Hora', 0, 0, 'R');
        $this->Cell(20, 3.5, 'Faltas', 0, 0, 'C');
        $this->Cell(9, 3.5, '', 0, 0, 'C');
        $this->SetXY(20,47);
        $this->Cell(173, 0, '', 'T', 0, 'L');
	}

	function Footer(){
		$paginas = "Pagina " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
		$this->SetY(-15);
		$this->SetTextColor(77, 73, 72);
	    $this->Ln(4);
	    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
	    $this->Cell(0, 3, $paginas, 0, 0, 'C');
	}
}

$miReporte = new PDF('P', 'mm', 'letter'); 
$miReporte->setTitulo($vista, $sub, $sub2);
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
        $miReporte->Cell(35, 3.5, $prod->credencial, 0, 0, 'L', $relleno);
        $miReporte->Cell(75, 3.5, $prod->nombre, 0, 0, 'L', $relleno);
        $miReporte->Cell(20, 3.5, $prod->lista, 0, 0, 'C', $relleno);
        if($prod->hora==null) $miReporte->Cell(17, 3.5, "--", 0, 0, 'C', $relleno); else $miReporte->Cell(17, 3.5, $prod->hora, 0, 0, 'C', $relleno);
        $miReporte->Cell(14, 3.5, $prod->faltas, 0, 0, 'C', $relleno);
        if($prod->hora==null) $miReporte->Cell(13, 3.5, "No Asistio", 0, 1, 'C', $relleno); else $miReporte->Cell(13, 3.5, "Asistio", 0, 1, 'C', $relleno);
        $contador = $contador + 1;
}

$relleno = $contador % 2;
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(174, 3.5, '', 'B', 0, 'C', $relleno);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Output();
exit();
?>