<?php
// carnet_grupo_interes.php
// Genera el carnet del grupo de interés en formato PDF

session_start();

require_once __DIR__ .'/../../config/conexion.php';
require_once __DIR__ .'/../../modelos/Persona.php';
require_once __DIR__ .'/../../libs/tcpdf/tcpdf.php';

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Obtener parámetros
$idEstudiante = intval($_GET['id'] ?? 0);

if ($idEstudiante <= 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'ID de estudiante inválido']);
    exit;
}

$database = new Database();
$conexion = $database->getConnection();

// Obtener datos del estudiante
$personaModel = new Persona($conexion);
$estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);

if (!$estudiante) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Estudiante no encontrado']);
    exit;
}

// Validar que tenga foto de perfil
// Debug: registrar información
error_log("Carnet - ID Estudiante: " . $idEstudiante);
error_log("Carnet - Foto perfil DB size: " . (strlen($estudiante['foto_perfil'] ?? '')));

// Verificar si el campo foto_perfil está vacío o es NULL
if (empty($estudiante['foto_perfil']) || is_null($estudiante['foto_perfil'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'error' => 'El estudiante no tiene una foto de perfil registrada. Por favor, sube una foto de perfil antes de imprimir el carnet.',
        'debug' => [
            'id_estudiante' => $idEstudiante,
            'foto_perfil_exists' => !empty($estudiante['foto_perfil'])
        ]
    ]);
    exit;
}

// Obtener año escolar activo
$queryAnoActivo = "SELECT IdFecha_Escolar, fecha_escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1";
$stmtAnoActivo = $conexion->prepare($queryAnoActivo);
$stmtAnoActivo->execute();
$anoEscolarActivo = $stmtAnoActivo->fetch(PDO::FETCH_ASSOC);
$idAnoActivo = $anoEscolarActivo ? $anoEscolarActivo['IdFecha_Escolar'] : null;
$nombreAnoActivo = $anoEscolarActivo ? $anoEscolarActivo['fecha_escolar'] : 'Sin año activo';

if (!$idAnoActivo) {
    die('No hay año escolar activo');
}

// Obtener sección actual
$seccionActual = $personaModel->obtenerSeccionActualEstudiante($idEstudiante, $idAnoActivo);

// Obtener grupo de interés
$queryGrupo = "SELECT 
                    tgi.nombre_grupo,
                    tgi.descripcion,
                    gi.IdGrupo_Interes
               FROM inscripcion_grupo_interes igi
               INNER JOIN grupo_interes gi ON igi.IdGrupo_Interes = gi.IdGrupo_Interes
               INNER JOIN tipo_grupo_interes tgi ON gi.IdTipo_Grupo = tgi.IdTipo_Grupo
               INNER JOIN inscripcion i ON igi.IdInscripcion = i.IdInscripcion
               WHERE igi.IdEstudiante = :idEstudiante 
               AND i.IdFecha_Escolar = :idFecha
               LIMIT 1";
$stmtGrupo = $conexion->prepare($queryGrupo);
$stmtGrupo->bindParam(':idEstudiante', $idEstudiante);
$stmtGrupo->bindParam(':idFecha', $idAnoActivo);
$stmtGrupo->execute();
$grupoInteres = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

if (!$grupoInteres) {
    die('El estudiante no está inscrito en ningún grupo de interés');
}

// Crear PDF
class PDFCarnet extends TCPDF {
    public function Header() {
        // Header institucional
        $logo = __DIR__ . '/../../assets/images/fermin.png';
        
        if (file_exists($logo)) {
            $this->Image($logo, 12, 5, 35, '', 'PNG');
        }
        
        // Texto institucional
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(0, 0, 0); // Negro
        
        $texto = "REPÚBLICA BOLIVARIANA DE VENEZUELA\nMINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN\nUNIDAD EDUCATIVA COLEGIO FERMÍN TORO\nINSCRITO EN EL MPPE BAJO EL NO. PD04281802";
        
        $ancho = 120;
        $x = $this->GetX() + 40;
        $y = $this->GetY() + 5;
        
        $this->MultiCell($ancho, 4, $texto, 0, 'C', false, 1, $x, $y);
        
        // Línea separadora
        $this->SetDrawColor(200, 0, 0);
        $this->SetLineWidth(0.5);
        $this->Line(15, 30, 200, 30); // Más separada
        
        $this->Ln(17); // Más espacio
    }
    
    public function Footer() {
        $this->SetY(-20);
        
        // Línea superior del footer
        $this->SetDrawColor(200, 0, 0);
        $this->SetLineWidth(0.3);
        $this->Line(15, $this->GetY(), 200, $this->GetY());
        
        $this->Ln(2);
        
        // Información del footer
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 100, 100);
        
        $fecha = date('d/m/Y H:i');
        $this->Cell(0, 4, 'Fecha de impresión: ' . $fecha, 0, 1, 'C');
        
        $this->SetFont('helvetica', 'I', 6);
        $this->Cell(0, 3, 'Este carnet es personal e intransferible - Válido únicamente para el año escolar indicado', 0, 1, 'C');
    }
}

// Configuración del PDF - Tamaño carta para imprimir
$pdf = new PDFCarnet('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->SetCreator('UECFT Araure');
$pdf->SetAuthor('UECFT Araure');
$pdf->SetTitle('Carnet Grupo de Interés - ' . $estudiante['nombre'] . ' ' . $estudiante['apellido']);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 35, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// Foto ya obtenida de DB
// $fotoPath = __DIR__ .'/../../' . $estudiante['foto_perfil'];

// Logo institucional (si existe)
$logo = __DIR__ . '/../../assets/images/fermin.png';

// ==================== DISEÑO DEL CARNET ====================

// Fondo blanco
$pdf->SetFillColor(255, 255, 255);

// Contenedor principal del carnet - Formato VERTICAL (98mm x 121mm)
$x = 55;  // Centrado en la página
$y = 45;
$width = 98;
$height = 121;

// Borde punteado exterior (para recorte)
$pdf->SetLineStyle(array('width' => 0.5, 'dash' => '2,2', 'color' => array(150, 150, 150)));
$pdf->Rect($x, $y, $width, $height, 'D');

// Borde sólido interior
$pdf->SetLineStyle(array('width' => 0.8, 'color' => array(200, 0, 0)));
$pdf->Rect($x + 3, $y + 3, $width - 6, $height - 6, 'D');

// Header del carnet con logo
if (file_exists($logo)) {
    $pdf->Image($logo, $x + 8, $y + 8, 18, 0, 'PNG');
}

// Texto institucional en el header
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(200, 0, 0);
$pdf->SetXY($x + 28, $y + 8);
$pdf->Cell($width - 35, 3.5, 'UNIDAD EDUCATIVA COLEGIO', 0, 1, 'L');
$pdf->SetXY($x + 28, $y + 12);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($width - 35, 4, '"FERMÍN TORO"', 0, 1, 'L');
$pdf->SetXY($x + 28, $y + 16.5);
$pdf->SetFont('helvetica', '', 6);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell($width - 35, 3, 'Araure - Estado Portuguesa', 0, 1, 'L');

// Línea separadora
$pdf->SetDrawColor(200, 0, 0);
$pdf->Line($x + 8, $y + 24, $x + $width - 8, $y + 24);

// Título del carnet
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(200, 0, 0);
$pdf->SetXY($x + 8, $y + 26);
$pdf->Cell($width - 16, 5, 'CARNET DE GRUPO DE INTERÉS', 0, 1, 'C');

// Contenedor de la foto (centrada)
$fotoSize = 35;
$fotoX = $x + ($width - $fotoSize) / 2;
$fotoY = $y + 35;

// Borde de la foto
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect($fotoX - 1, $fotoY - 1, $fotoSize + 2, $fotoSize + 2, 'F');
$pdf->SetDrawColor(200, 0, 0);
$pdf->SetLineWidth(0.5);
$pdf->Rect($fotoX - 1, $fotoY - 1, $fotoSize + 2, $fotoSize + 2, 'D');

// Foto del estudiante
if (!empty($estudiante['foto_perfil'])) {
    // Usamos @ para indicar a TCPDF que es data cruda (blob), no un path
    $pdf->Image('@' . $estudiante['foto_perfil'], $fotoX, $fotoY, $fotoSize, $fotoSize, '', '', '', true, 300, '', false, false, 0, false, false, false);
}

// Información del estudiante (debajo de la foto)
$infoY = $fotoY + $fotoSize + 8;

// Nombre
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetXY($x + 8, $infoY);
$pdf->Cell($width - 16, 3, 'NOMBRE COMPLETO:', 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($x + 8, $infoY + 3.5);
$nombreCompleto = strtoupper($estudiante['nombre'] . ' ' . $estudiante['apellido']);
if (strlen($nombreCompleto) > 30) {
    $pdf->SetFont('helvetica', 'B', 7);
}
$pdf->MultiCell($width - 16, 3.5, $nombreCompleto, 0, 'C');

// Cédula
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetXY($x + 8, $infoY + 11);
$pdf->Cell(($width - 16) / 2, 3, 'CÉDULA:', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$cedula = '';
if (!empty($estudiante['cedula'])) {
    $cedula = $estudiante['nacionalidad'] . '-' . number_format($estudiante['cedula'], 0, '', '.');
} else {
    $cedula = 'No registrada';
}
$pdf->Cell(($width - 16) / 2, 3, $cedula, 0, 1, 'R');

// Sección
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetXY($x + 8, $infoY + 15);
$pdf->Cell(($width - 16) / 2, 3, 'SECCIÓN:', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$seccion = $seccionActual ? $seccionActual['curso'] . ' "' . $seccionActual['seccion'] . '"' : 'No asignada';
$pdf->Cell(($width - 16) / 2, 3, $seccion, 0, 1, 'R');

// Año Escolar
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetXY($x + 8, $infoY + 19);
$pdf->Cell(($width - 16) / 2, 3, 'AÑO ESCOLAR:', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(($width - 16) / 2, 3, $nombreAnoActivo, 0, 1, 'R');

// Línea separadora antes del grupo
$pdf->SetDrawColor(220, 220, 220);
$pdf->Line($x + 8, $infoY + 25, $x + $width - 8, $infoY + 25);

// Badge del grupo de interés (destacado) - CORREGIDO para que no se salga
$badgeY = $infoY + 28;
$badgeHeight = 12;

// Verificar que el badge no se salga del borde
$maxY = $y + $height - 6; // Límite del borde interior
if (($badgeY + $badgeHeight) > $maxY) {
    $badgeHeight = $maxY - $badgeY - 2; // Ajustar altura
}

$pdf->SetFillColor(200, 0, 0);
$pdf->RoundedRect($x + 8, $badgeY, $width - 16, $badgeHeight, 2, '1111', 'F');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY($x + 8, $badgeY + 2);
$nombreGrupo = strtoupper($grupoInteres['nombre_grupo']);
if (strlen($nombreGrupo) > 35) {
    $pdf->SetFont('helvetica', 'B', 8);
}
$pdf->MultiCell($width - 16, 4, $nombreGrupo, 0, 'C', false, 1);

// Salida del PDF
$pdf->Output('Carnet_Grupo_Interes_' . $estudiante['cedula'] . '.pdf', 'I');
exit();
