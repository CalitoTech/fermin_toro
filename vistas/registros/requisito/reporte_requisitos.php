<?php

require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../libs/tcpdf/tcpdf.php'; // Ajusta ruta

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

// Crear PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('UECFT Araure');
$pdf->SetTitle('Reporte de Requisitos - Nivel ' . $requisitos[0]['nivel']);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->AddPage();

// Logo
$pdf->Image('../../../assets/images/fermin.png', 10, 10, 30, '', 'PNG');
$pdf->SetY(15);

// Título con estilo
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 10, 'REPORTE DE REQUISITOS', 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 12);
$pdf->Cell(0, 6, 'Nivel: ' . $requisitos[0]['nivel'], 0, 1, 'C');

// Usuario y fecha
$usuario = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Sistema';
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 5, 'Generado por: ' . $usuario, 0, 1, 'L');
$pdf->Cell(0, 5, 'Fecha: ' . date('d-m-Y H:i'), 0, 1, 'L');
$pdf->Ln(5);

// Tabla con CSS bonito
$css = "
<style>
    table {border-collapse: collapse; width: 100%; font-family: Helvetica; font-size: 11pt;}
    th {
        background-color: #c90000;
        color: #ffffff;
        padding: 8px;
        text-align: center;
        border-radius: 5px 5px 0 0;
        font-weight: bold;
    }
    td {
        padding: 8px;
        border-bottom: 1px solid #dddddd;
    }
    tr:nth-child(even) {background-color: #f2f2f2;}
    tr:hover {background-color: #ffe6e6;}
    .obligatorio {text-align: center; font-weight: bold; color: #c90000;}
</style>
";

$html = $css;
$html .= '<table>';
$html .= '<thead>
            <tr>
                <th width="10%">ID</th>
                <th width="70%">Requisito</th>
                <th width="20%">Obligatorio</th>
            </tr>
          </thead><tbody>';

foreach ($requisitos as $r) {
    $obligatorio = $r['obligatorio'] == 1 ? 'Sí' : 'No';
    $html .= '<tr>
                <td align="center">' . $r['IdRequisito'] . '</td>
                <td>' . htmlspecialchars($r['requisito']) . '</td>
                <td class="obligatorio">' . $obligatorio . '</td>
              </tr>';
}
$html .= '</tbody></table>';

// Renderizar HTML en PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Reporte_Requisitos_Nivel_' . $requisitos[0]['nivel'] . '.pdf', 'D');
exit();
