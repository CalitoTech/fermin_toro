<?php
/**
 * Script asíncrono para enviar mensajes de WhatsApp
 * Se ejecuta en segundo plano para no bloquear la UI
 */

// Evitar timeout
set_time_limit(300); // 5 minutos máximo
ignore_user_abort(true); // Continúa aunque el usuario cierre la conexión

// Obtener parámetros de línea de comandos
$inscripcionesIds = isset($argv[1]) ? $argv[1] : null;

if (!$inscripcionesIds) {
    error_log("❌ Script de WhatsApp: No se recibieron IDs de inscripciones");
    exit(1);
}

// Convertir string de IDs a array
$idsArray = explode(',', $inscripcionesIds);

error_log("📤 Iniciando envío asíncrono de mensajes WhatsApp para " . count($idsArray) . " inscripciones");

try {
    // Incluir dependencias
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../controladores/WhatsappController.php';

    // Instanciar controlador
    $whatsappController = new WhatsAppController($conexion);

    $enviados = 0;
    $errores = 0;

    // Enviar mensaje para cada inscripción
    foreach ($idsArray as $idInscripcion) {
        $idInscripcion = trim($idInscripcion);

        if (empty($idInscripcion) || !is_numeric($idInscripcion)) {
            continue;
        }

        try {
            // Enviar mensaje de estado rechazado (IdStatus = 12)
            $resultado = $whatsappController->enviarMensajesCambioEstado($idInscripcion, 12);

            if ($resultado) {
                $enviados++;
                error_log("✅ Mensaje enviado para inscripción #$idInscripcion");
            } else {
                $errores++;
                error_log("⚠️ No se pudo enviar mensaje para inscripción #$idInscripcion");
            }

            // Pequeña pausa entre mensajes para evitar saturar la API
            usleep(500000); // 0.5 segundos

        } catch (Exception $e) {
            $errores++;
            error_log("❌ Error enviando mensaje para inscripción #$idInscripcion: " . $e->getMessage());
        }
    }

    error_log("📊 Resumen envío WhatsApp: $enviados enviados, $errores errores");

} catch (Exception $e) {
    error_log("❌ Error crítico en script WhatsApp: " . $e->getMessage());
    exit(1);
}

exit(0);
