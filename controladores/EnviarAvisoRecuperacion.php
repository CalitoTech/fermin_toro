<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../controladores/WhatsAppController.php';

header('Content-Type: application/json');

// ✅ Recibir datos del formulario
$cedula = $_POST['cedula'] ?? '';
$nacionalidad = $_POST['nacionalidad'] ?? '';

if (empty($cedula) || empty($nacionalidad)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Faltan datos: nacionalidad o cédula.']);
    exit;
}

// ✅ Configuración de bloqueo
$bloqueo_segundos = 60;
$cedulaKey = $nacionalidad . $cedula;

// Inicializar el array de bloqueos si no existe
if (!isset($_SESSION['bloqueos'])) {
    $_SESSION['bloqueos'] = [];
}

// ✅ Verificar si la cédula ya tiene un bloqueo vigente
if (isset($_SESSION['bloqueos'][$cedulaKey])) {
    $tiempo_restante = $_SESSION['bloqueos'][$cedulaKey] - time();

    if ($tiempo_restante > 0) {
        echo json_encode([
            'ok' => false,
            'bloqueado' => true,
            'tiempo_restante' => $tiempo_restante,
            'mensaje' => "Debes esperar {$tiempo_restante} segundos antes de volver a solicitar el código."
        ]);
        exit;
    }
}

// ✅ Si no está bloqueado, registrar nuevo bloqueo
$_SESSION['bloqueos'][$cedulaKey] = time() + $bloqueo_segundos;

try {
    // ✅ Conexión a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // ✅ Crear objeto Persona
    $persona = new Persona($conn);

    // ✅ Buscar persona por nacionalidad y cédula
    $datosPersona = $persona->obtenerPorCedula($nacionalidad, $cedula);

    if (!$datosPersona) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No existe ningún usuario con esa nacionalidad y cédula.'
        ]);
        exit;
    }

    // ✅ Obtener ID y continuar con el envío del aviso
    $idPersona = $datosPersona['IdPersona'];

    $whatsapp = new WhatsAppController($conn);

    // 📲 Enviar mensaje por WhatsApp
    $resultado = $whatsapp->enviarAvisoBloqueo($idPersona, 'recuperacion');

    if (isset($resultado['success']) && $resultado['success']) {
        echo json_encode([
            'ok' => true,
            'mensaje' => 'El código fue enviado correctamente a tu WhatsApp.'
        ]);
    } else {
        $detalle = $resultado['response']['message'] ?? 'Ocurrió un problema al enviar el mensaje.';
        echo json_encode([
            'ok' => false,
            'mensaje' => $detalle
        ]);
    }

} catch (Exception $e) {
    error_log("❌ Error en EnviarAvisoRecuperacion: " . $e->getMessage());
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del sistema.']);
}