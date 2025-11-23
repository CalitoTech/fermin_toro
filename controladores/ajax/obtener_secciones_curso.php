<?php
/**
 * Endpoint AJAX para obtener las secciones disponibles de un curso
 */
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

require_once __DIR__ . '/../../config/conexion.php';

$database = new Database();
$conexion = $database->getConnection();

$idCurso = isset($_GET['idCurso']) ? (int)$_GET['idCurso'] : 0;

if ($idCurso <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de curso inválido']);
    exit();
}

try {
    // Obtener secciones del curso (excluyendo la sección "Inscripcion" que es IdSeccion = 1)
    $sql = "SELECT cs.IdCurso_Seccion, s.IdSeccion, s.seccion
            FROM curso_seccion cs
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            WHERE cs.IdCurso = :idCurso
            AND s.IdSeccion != 1
            ORDER BY s.seccion ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':idCurso', $idCurso, PDO::PARAM_INT);
    $stmt->execute();
    $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'secciones' => $secciones
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_secciones_curso.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las secciones'
    ]);
}
