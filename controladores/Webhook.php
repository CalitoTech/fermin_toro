<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../controladores/WhatsAppController.php';

header("Content-Type: application/json");

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    error_log("⚠️ Payload inválido en webhook.php");
    echo json_encode(["status" => "error", "msg" => "Payload inválido"]);
    exit;
}

if (isset($data['event']) && $data['event'] === 'messages.upsert') {
    $message = $data['data']['message'] ?? null;
    $from    = $data['data']['key']['remoteJid'] ?? null;

    if ($message && $from && isset($message['listResponseMessage']['singleSelectReply']['selectedRowId'])) {
        $rowId = $message['listResponseMessage']['singleSelectReply']['selectedRowId'];
        $fromLimpio = preg_replace('/[^0-9]/', '', explode('@', $from)[0]);

        // DB + Controlador
        $database = new Database();
        $conexion = $database->getConnection();
        $wa = new WhatsAppController($conexion);

        // Delegar a WhatsAppController
        $wa->procesarSeleccion($rowId, $fromLimpio);
    }
}

echo json_encode(["status" => "ok"]);