<?php
/**
 * API Endpoint para procesar selecciones de flujo de bloqueo/recuperación desde fuentes externas.
 * Requiere una API Key en el header coincidente con la configurada en el sistema.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../WhatsAppController.php';
require_once __DIR__ . '/../../modelos/ConfigWhatsapp.php';

header("Content-Type: application/json");

// 1. Inicializar conexión y cargar configuración
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "Error de conexión a la base de datos"]);
    exit;
}

$configModel = new ConfigWhatsapp($db);
$config = $configModel->obtenerConfiguracionActiva();

if (!$config || !$configModel->tieneApiKey()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "Configuración de WhatsApp o API Key no encontrada en el sistema"]);
    exit;
}

// Obtener la API Key válida (desencriptada)
$apiKeyValida = $configModel->obtenerApiKeyParaUso();

// 2. Validar API Key en el header
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$apiKeyRecibida = $headers['apikey'] ?? null;

if ($apiKeyRecibida !== $apiKeyValida) {
    http_response_code(401);
    echo json_encode(["status" => "error", "msg" => "API Key no válida o ausente en el header"]);
    exit;
}

// 3. Obtener y validar datos de entrada
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data || !isset($data['respuesta']) || !isset($data['telefono'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "Datos incompletos. Se requiere 'respuesta' (si/no) y 'telefono'"]);
    exit;
}

$respuesta = strtolower(trim($data['respuesta']));
$telefonoFull = preg_replace('/[^0-9]/', '', $data['telefono']); // Limpiar caracteres no numéricos

if (!in_array($respuesta, ['si', 'no'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "Respuesta no válida. Debe ser 'si' o 'no'"]);
    exit;
}

// 4. Buscar el usuario asociado al número de teléfono
// Buscamos coincidencia exacta concatenando prefijo y número
$query = "SELECT p.usuario 
          FROM persona p 
          INNER JOIN telefono t ON p.IdPersona = t.IdPersona 
          INNER JOIN prefijo pref ON t.IdPrefijo = pref.IdPrefijo 
          WHERE CONCAT(REPLACE(pref.codigo_prefijo, '+', ''), t.numero_telefono) = :telefono 
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":telefono", $telefonoFull);
$stmt->execute();
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    http_response_code(404);
    echo json_encode(["status" => "error", "msg" => "No se encontró ningún usuario con el teléfono: $telefonoFull"]);
    exit;
}

$usuario = $userRow['usuario'];

/**
 * 5. Delegar el procesamiento al WhatsAppController.
 * El rowId esperado por procesarSeleccion tiene el formato: bloqueo_{si|no}_{usuario}
 */
$rowId = "bloqueo_{$respuesta}_{$usuario}";

try {
    $wa = new WhatsAppController($db);
    $wa->procesarSeleccion($rowId, $telefonoFull);
    
    echo json_encode([
        "status" => "ok", 
        "msg" => "Selección procesada para el usuario: $usuario",
        "data" => [
            "usuario" => $usuario,
            "respuesta" => $respuesta
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "Error procesando la selección: " . $e->getMessage()]);
}
