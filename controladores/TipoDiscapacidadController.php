<?php
require_once __DIR__ . '/../modelos/TipoDiscapacidad.php';
require_once __DIR__ . '/../config/conexion.php';

$database = new Database();
$conexionPDO = $database->getConnection();
$modeloTipoDiscapacidad = new TipoDiscapacidad($conexionPDO);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    try {
        switch ($_GET['action']) {
            case 'obtenerTodos':
                $tipos = $modeloTipoDiscapacidad->obtenerTodos();
                header('Content-Type: application/json');
                echo json_encode($tipos);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud no válida']);
}
?>