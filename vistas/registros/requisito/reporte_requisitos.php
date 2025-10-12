<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../libs/tcpdf/tcpdf.php';

$database = new Database();
$conexion = $database->getConnection();

// Nivel seleccionado
$nivelId = $_GET['nivel'] ?? null;
if (!$nivelId) die('Nivel no seleccionado.');

// Consulta de requisitos
$query = "SELECT requisito.IdRequisito, requisito.requisito, nivel.nivel, requisito.obligatorio
          FROM requisito
          INNER JOIN nivel ON requisito.IdNivel = nivel.IdNivel
          WHERE requisito.IdNivel = :nivelId
          ORDER BY requisito.IdRequisito ASC";
$stmt = $conexion->prepare($query);
$stmt->bindParam(':nivelId', $nivelId, PDO::PARAM_INT);
$stmt->execute();
$requisitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$requisitos) die('No hay requisitos para este nivel.');

// Crear PDF en orientación horizontal (Landscape)
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('UECFT Araure');
$pdf->SetTitle('Requisitos y Uniforme - ' . $requisitos[0]['nivel']);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// ===================== CABECERAS =====================
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(201, 0, 0);
$pdf->SetTextColor(255, 255, 255);

// ancho total disponible en horizontal (A4 = 297mm, menos márgenes = 267mm aprox.)
$colWidth = ($pdf->getPageWidth() - 30 - 6) / 2; // 15+15 márgenes y 6mm de separación

$pdf->Cell($colWidth, 8, 'REQUISITOS DE INSCRIPCIÓN (NUEVO INGRESO)', 0, 0, 'C', true);
$pdf->Cell(6, 8, '', 0, 0); // espacio entre columnas
$pdf->Cell($colWidth, 8, 'UNIFORME ESCOLAR', 0, 1, 'C', true);

$pdf->Ln(5); // espacio entre encabezado y texto

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 10);

// ===================== CONTENIDO DE REQUISITOS =====================
$requisitosHTML = '
<div style="line-height: 1.5; text-align: justify;">
<p><strong>Educación ' . htmlspecialchars($requisitos[0]['nivel']) . ':</strong></p>
<ul style="margin-left: 12px;">';
foreach ($requisitos as $r) {
    $oblig = ($r['obligatorio'] == 1) ? ' <strong>(OBLIGATORIO)</strong>' : '';
    $requisitosHTML .= '<li>' . htmlspecialchars($r['requisito']) . $oblig . '</li>';
}

// ---- aquí se agregan los 3 ítems extra exactamente como en la imagen ----
$requisitosHTML .= '
<li>Trabajador Dependiente: Traer Constancia de trabajo con logo de la empresa y vigencia no mayor a tres (3) meses firmada en original y con sello húmedo.</li>
<li>Trabajadores Independientes: Traer Certificación de Ingresos original, firmada y sellada por Contador Público colegiado, vigencia no mayor a tres (3) meses.</li>
<li>Empresarios: Copia del Registro Mercantil donde se verifique su posición como Propietario y/o Asociado de la empresa (Rif Jurídico, legible y actualizado).</li>
';

$requisitosHTML .= '</ul>
<p style="margin-top:8px;"><strong>Nota:</strong> Si el estudiante procede de una institución privada, debe consignar la solvencia administrativa firmada y sellada por el colegio.</p>
</div>';

// ===================== CONTENIDO DE UNIFORME =====================
switch ($nivelId) {
    case 1: // Inicial
        $uniformeHTML = '
        <div style="line-height: 1.5; text-align: justify;">
        <p><strong>Uniforme Escolar - Educación Inicial:</strong></p>
        <ul>
            <li>Franela blanca con logo institucional (uso diario).</li>
            <li>Mono azul marino sin rayas ni marcas.</li>
            <li>Zapatos deportivos blancos o negros.</li>
            <li>Medias blancas.</li>
            <li>Suéter azul marino (opcional) con logo institucional.</li>
        </ul>
        </div>';
        break;
    case 2: // Primaria
        $uniformeHTML = '
        <div style="line-height: 1.5; text-align: justify;">
        <p><strong>Uniforme Escolar - Educación Primaria:</strong></p>
        <ul>
            <li>Chemisse color beige, con logo del colegio bordado.</li>
            <li>Pantalón azul marino tipo gabardina (recto, sin roturas).</li>
            <li>Correa negra con hebilla sencilla.</li>
            <li>Zapatos color negro.</li>
            <li>Para Educación Física: mono azul sin rayas y franela blanca con logo del colegio.</li>
        </ul>
        </div>';
        break;
    case 3: // Media General
    default:
        $uniformeHTML = '
        <div style="line-height: 1.5; text-align: justify;">
        <p><strong>Uniforme Escolar (4to y 5to Año):</strong></p>
        <ul>
            <li>Chemisse color beige, por dentro, con el logo del colegio bordado.</li>
            <li>Pantalones color azul marino de gabardina corte clásico (recto, sin adornos ni roturas).</li>
            <li>Correa negra con hebilla sin adornos.</li>
            <li>Zapatos color negro.</li>
        </ul>
        <p><em>Para Educación Física:</em></p>
        <ul>
            <li>Mono azul sin rayas ni marcas.</li>
            <li>Medias blancas largas.</li>
            <li>Franela blanca con cuello V y logo del colegio bordado.</li>
            <li>Zapatos deportivos blancos o negros.</li>
        </ul>
        <p>En caso de usar suéter, debe ser tipo escolar, azul marino del mismo color del pantalón de gabardina.</p>
        <p>El uniforme o traje escolar debe estar limpio, sin roturas y lo mejor presentado posible.</p>
        <p style="font-size:9pt;"><em>En caso de presentarse cualquier situación que impida cumplir con el uniforme escolar, el o la representante está obligado a notificarlo por escrito a la Dirección del Colegio, tal como lo establecen los lineamientos emanados por el Ministerio del Poder Popular para la Educación.</em></p>
        </div>';
        break;
}

// ===================== TABLA FINAL =====================
$html = '
<style>
td { vertical-align: top; font-size: 10pt; line-height: 1.5; padding: 0 5px; }
ul { margin-left: 12px; padding-left: 5px; }
li { margin-bottom: 4px; text-align: justify; }
p { text-align: justify; line-height: 1.4; margin-bottom: 6px; }
</style>

<table border="0" cellpadding="4" cellspacing="0" width="100%">
<tr>
    <td width="48%">' . $requisitosHTML . '</td>
    <td width="4%"></td> <!-- espacio entre columnas -->
    <td width="48%">' . $uniformeHTML . '</td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Requisitos_Uniforme_' . $requisitos[0]['nivel'] . '.pdf', 'I');
exit();
?>