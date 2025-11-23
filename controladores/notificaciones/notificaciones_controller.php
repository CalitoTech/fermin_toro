<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../modelos/Notificacion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idPersona'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';
$idPersona = $_SESSION['idPersona'];

$database = new Database();
$db = $database->getConnection();
$notificacionModel = new Notificacion($db);

try {
    switch ($action) {
        case 'obtener_no_leidas':
            $notificaciones = $notificacionModel->obtenerNoLeidas($idPersona);
            $total = $notificacionModel->contarNoLeidas($idPersona);
            echo json_encode([
                'success' => true, 
                'data' => $notificaciones,
                'total' => $total
            ]);
            break;

        case 'marcar_leida':
            $idNotificacion = $_POST['id'] ?? 0;
            if ($idNotificacion) {
                $result = $notificacionModel->marcarComoLeida($idNotificacion, $idPersona);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
            }
            break;

        case 'marcar_todas_leidas':
            $result = $notificacionModel->marcarTodasComoLeidas($idPersona);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
