<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $sub = "";
    private $sub2 = "";
    private $fecha = "";
    private $total = "";

    public function setTitulo($titulo, $sub, $sub2, $fecha, $total){
        $this->titulo=$titulo;
        $this->sub=$sub;
        $this->sub2=$sub2;
        $this->fecha=$fecha;
        $this->total=$total;
    }

	function Header(){
		$this->Image('../public/assets/images/BITU transparente.png', 15, 5, 45, 30);
		$this->Ln(12);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
		$this->Cell(0, 5, utf8_decode($this->titulo)."           ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->Cell(0, 5, utf8_decode($this->sub)."             ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->Cell(0, 5, utf8_decode($this->sub2)."             ", 0, 1, 'R');
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->Cell(0, 5, utf8_decode($this->total)."             ", 0, 1, 'R');
		$this->Ln(8);
        $this->SetX(20);
        $this->Cell(173, 0, '', 'T', 0, 'L');
		$this->Ln(1);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(10);
		$this->Cell(50, 3.5, 'Credencial', 0, 0, 'C');
        $this->Cell(70, 3.5, 'Nombre', 0, 0, 'C');

        date_default_timezone_set('America/Mexico_City');
        $selec = $this->fecha;
        $f1 = strtotime(substr($selec,8,10)."-".substr($selec,5,2)."-".substr($selec,0,4)." 00:00:00");
        $f2 = strtotime("27-09-2023 00:00:00");
        // $f2 = strtotime(date('d-m-Y'));
        // $f3 = strtotime("-1 week");
        // if($f1 <= $f2 && $f1 >=$f3){
        if($f1 <= $f2){
            $this->Cell(23, 3.5, "", 0, 0, 'C');
        }else{
            $this->Cell(23, 3.5, 'Faltas', 0, 0, 'C');
        }

        $this->Cell(20, 3.5, 'Hora', 0, 0, 'C');
        $this->Cell(20, 3.5, '', 0, 0, 'C');
        $this->SetXY(20,47);
        $this->Cell(173, 0, '', 'T', 0, 'L');
	}

	function Footer(){
		$paginas = "Página " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
		$this->SetY(-15);
		$this->SetTextColor(77, 73, 72);
	    $this->Ln(4);
	    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
	    $this->Cell(0, 3, $paginas, 0, 0, 'C');
	}
}

$miReporte = new PDF('P', 'mm', 'letter'); 
$miReporte->setTitulo($vista, $sub, $sub2, $fecha, $total);
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
        $miReporte->Cell(50, 3.5, $prod->credencial, 0, 0, 'L', $relleno);
        $miReporte->Cell(60, 3.5, $prod->nombre, 0, 0, 'L', $relleno);

        date_default_timezone_set('America/Mexico_City');
        $selec = $fecha;
        $f1 = strtotime(substr($selec,8,10)."-".substr($selec,5,2)."-".substr($selec,0,4)." 00:00:00");
        $f2 = strtotime("27-09-2023 00:00:00");
        // $f2 = strtotime(date('d-m-Y'));
        // $f3 = strtotime("-1 week");
        // if($f1 <= $f2 && $f1 >=$f3){
        if($f1 <= $f2){
            $miReporte->Cell(23, 3.5, "", 0, 0, 'C', $relleno);
        }else{
            $miReporte->Cell(23, 3.5, $prod->faltas, 0, 0, 'C', $relleno);
        }

        if($prod->hora==null) $miReporte->Cell(20, 3.5, "--", 0, 0, 'C', $relleno); else $miReporte->Cell(20, 3.5, $prod->hora, 0, 0, 'C', $relleno);
        if($prod->hora==null) $miReporte->Cell(20, 3.5, "No Recibió", 0, 1, 'C', $relleno); else $miReporte->Cell(20, 3.5, "Recibió", 0, 1, 'C', $relleno);
        $contador = $contador + 1;
}

$relleno = $contador % 2;
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(174, 3.5, '', 'B', 0, 'C', $relleno);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Output();
exit();
?>