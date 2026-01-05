<?php
require_once 'config.php';
require_role(['admin','editor']);

require_once __DIR__ . '/lib/fpdf/fpdf.php';

$date = $_GET['date'] ?? date('Y-m-d');
if (!is_valid_date_ymd($date)) $date = date('Y-m-d');

$stmt = $pdo->prepare("
  SELECT title, duration_minutes, rating, show_time, projection_format
  FROM movies
  WHERE show_date = ?
  ORDER BY show_time, title
");
$stmt->execute([$date]);
$movies = $stmt->fetchAll();

$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,iconv('UTF-8','ISO-8859-1//TRANSLIT',"Program Cinema Transilvania - $date"),0,1,'L');

$pdf->Ln(2);
$pdf->SetFont('Arial','',10);

if (!$movies) {
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1//TRANSLIT',"Nu exista filme programate pentru aceasta data."),0,1);
} else {
    // Header tabel
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(60,8,'Titlu',1);
    $pdf->Cell(18,8,'Ora',1);
    $pdf->Cell(18,8,'Durata',1);
    $pdf->Cell(20,8,'Format',1);
    $pdf->Cell(20,8,'Rating',1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',10);
    foreach ($movies as $m) {
        $title = (string)$m['title'];
        $time  = substr((string)$m['show_time'], 0, 5);
        $dur   = (int)$m['duration_minutes'] . ' min';
        $fmt   = (string)$m['projection_format'];
        $rat   = (string)$m['rating'];

        // iconv pentru diacritice (FPDF default nu e UTF-8)
        $pdf->Cell(60,8,iconv('UTF-8','ISO-8859-1//TRANSLIT',$title),1);
        $pdf->Cell(18,8,$time,1);
        $pdf->Cell(18,8,iconv('UTF-8','ISO-8859-1//TRANSLIT',$dur),1);
        $pdf->Cell(20,8,iconv('UTF-8','ISO-8859-1//TRANSLIT',$fmt),1);
        $pdf->Cell(20,8,iconv('UTF-8','ISO-8859-1//TRANSLIT',$rat),1);
        $pdf->Ln();
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="program_' . $date . '.pdf"');
$pdf->Output('D');
exit;

