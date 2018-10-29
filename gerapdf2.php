<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$path = '/home/ubuntu/webbots/automata/tcpdf/include';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once './tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

$pdf->SetCreator('Automata & TCPDF');
$pdf->SetAuthor('Vinicius De Martin Viude');


$pdf->setHeaderData('', 0, 'Escola SENAI "Morvan Figueiredo" - Programação de Cursos Livres');
$pdf->AddPage();

$pdf->write(0.5,'Vinicius De Martin');

$yatual = $pdf->GetY();
while ($yatual < ($pdf->getPageHeight()-200)) {
    $pdf->write(0.5,number_format($yatual));
    $pdf->Ln();
    $yatual = $pdf->GetY();
}

$pdf->Output('/home/ubuntu/webbots/automata/fg.pdf','F');

