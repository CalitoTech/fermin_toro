<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../libs/tcpdf/tcpdf.php';

$database = new Database();
$conexion = $database->getConnection();

// Nivel seleccionado
$nivelId = $_GET['nivel'] ?? null;
if (!$nivelId) die('Nivel no seleccionado.');

// Consulta de requisitos (incluyendo generales y específicos del nivel)
$query = "SELECT
            r.IdRequisito,
            r.requisito,
            r.obligatorio,
            r.descripcion_adicional,
            n.nivel,
            tr.tipo_requisito,
            tr.IdTipo_Requisito,
            tt.tipo_trabajador
          FROM requisito r
          INNER JOIN tipo_requisito tr ON r.IdTipo_Requisito = tr.IdTipo_Requisito
          LEFT JOIN nivel n ON r.IdNivel = n.IdNivel
          LEFT JOIN tipo_trabajador tt ON r.IdTipoTrabajador = tt.IdTipoTrabajador
          WHERE (r.IdNivel = :nivelId OR r.IdNivel IS NULL)
          AND r.solo_plantel_privado = FALSE
          ORDER BY
            CASE
              WHEN r.IdTipoTrabajador IS NOT NULL THEN 1
              ELSE 0
            END,
            tr.IdTipo_Requisito,
            r.obligatorio DESC,
            r.requisito ASC";
$stmt = $conexion->prepare($query);
$stmt->bindParam(':nivelId', $nivelId, PDO::PARAM_INT);
$stmt->execute();
$requisitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$requisitos) die('No hay requisitos para este nivel.');

// Obtener nombre del nivel
$queryNivel = "SELECT nivel FROM nivel WHERE IdNivel = :nivelId";
$stmtNivel = $conexion->prepare($queryNivel);
$stmtNivel->bindParam(':nivelId', $nivelId, PDO::PARAM_INT);
$stmtNivel->execute();
$nivelNombre = $stmtNivel->fetchColumn();

// Crear PDF en orientación horizontal (Landscape)
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('UECFT Araure');
$pdf->SetTitle('Requisitos y Uniforme - ' . $nivelNombre);
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
// Separar requisitos normales (todos excepto uniforme en una sola lista)
$requisitosNormales = [];
foreach ($requisitos as $r) {
    // Excluir requisitos de tipo Uniforme (IdTipo_Requisito = 2)
    if ($r['IdTipo_Requisito'] != 2) {
        $requisitosNormales[] = $r;
    }
}

$requisitosHTML = '
<div style="line-height: 1.5; text-align: justify;">
<p><strong>Educación ' . htmlspecialchars($nivelNombre) . ':</strong></p>';

// Generar lista única (sin separar por tipo)
$requisitosHTML .= '<ul style="margin-left: 12px;">';
foreach ($requisitosNormales as $r) {
    $oblig = ($r['obligatorio'] == 1) ? ' <strong>(OBLIGATORIO)</strong>' : '';
    $texto = htmlspecialchars($r['requisito']);

    // Si es requisito por tipo de trabajador, agregar la especificación
    if ($r['tipo_trabajador']) {
        $texto = 'Trabajador ' . htmlspecialchars($r['tipo_trabajador']) . ': ' . $texto;
    }

    // Si tiene descripción adicional, agregarla
    if ($r['descripcion_adicional']) {
        $texto .= ' <em>(' . htmlspecialchars($r['descripcion_adicional']) . ')</em>';
    }

    $requisitosHTML .= '<li>' . $texto . $oblig . '</li>';
}
$requisitosHTML .= '</ul>';

$requisitosHTML .= '
<p style="margin-top:8px;"><em>Nota:</em> Si el estudiante procede de una institución privada, debe consignar la solvencia administrativa firmada y sellada por el colegio.</p>
</div>';

// ===================== CONTENIDO DE UNIFORME =====================
// Obtener requisitos de uniforme desde la base de datos
$requisitosUniforme = array_filter($requisitos, function($r) {
    return $r['IdTipo_Requisito'] == 2; // Tipo Uniforme
});

// Separar uniformes normales de los de educación física
$uniformeNormal = [];
$uniformeEducacionFisica = [];

foreach ($requisitosUniforme as $u) {
    // Si la descripción contiene "educación física" o "deportivo", clasificar como uniforme de educación física
    $esEducacionFisica = false;
    if ($u['descripcion_adicional']) {
        $descripcionLower = mb_strtolower($u['descripcion_adicional']);
        if (strpos($descripcionLower, 'educación física') !== false ||
            strpos($descripcionLower, 'educacion fisica') !== false ||
            strpos($descripcionLower, 'deportivo') !== false) {
            $esEducacionFisica = true;
        }
    }

    if ($esEducacionFisica) {
        $uniformeEducacionFisica[] = $u;
    } else {
        $uniformeNormal[] = $u;
    }
}

$uniformeHTML = '
<div style="line-height: 1.5; text-align: justify;">
<p><strong>Uniforme Escolar:</strong></p>';

// Uniforme normal
if (!empty($uniformeNormal)) {
    $uniformeHTML .= '<ul>';
    foreach ($uniformeNormal as $u) {
        $texto = htmlspecialchars($u['requisito']);
        $uniformeHTML .= '<li>' . $texto . '</li>';
    }
    $uniformeHTML .= '</ul>';
}

// Uniforme de educación física
if (!empty($uniformeEducacionFisica)) {
    $uniformeHTML .= '<p style="margin-top:8px;">Es de uso obligatorio para Educación Física el siguiente uniforme:</p>';
    $uniformeHTML .= '<ul>';
    foreach ($uniformeEducacionFisica as $u) {
        $texto = htmlspecialchars($u['requisito']);
        $uniformeHTML .= '<li>' . $texto . '</li>';
    }
    $uniformeHTML .= '</ul>';
}

$uniformeHTML .= '
<p style="margin-top:8px;">El uniforme o traje escolar debe estar limpio, sin roturas y lo mejor presentado posible.</p>
<p style="font-size:9pt;"><em>En caso de presentarse cualquier situación que impida cumplir con el uniforme escolar, el o la representante está obligado a notificarlo por escrito a la Dirección del Colegio, tal como lo establecen los lineamientos emanados por el Ministerio del Poder Popular para la Educación.</em></p>
</div>';

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
$pdf->Output('Requisitos_Uniforme_' . $nivelNombre . '.pdf', 'I');
exit();
?>