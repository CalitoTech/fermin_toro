<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ConfigWhatsapp.php';
require_once __DIR__ . '/../modelos/MensajeWhatsapp.php';

class WhatsAppController {
    private $conexion;
    private $evolutionApiUrl;
    private $evolutionApiKey;
    private $nombreInstancia;
    private $loginUrl;
    private $configModel;
    private $mensajeModel;

    public function __construct($conexion) {
        $this->conexion = $conexion;

        // Cargar configuración desde la base de datos
        $this->configModel = new ConfigWhatsapp($conexion);
        $this->mensajeModel = new MensajeWhatsapp($conexion);

        $config = $this->configModel->obtenerConfiguracionActiva();

        if ($config) {
            $this->evolutionApiUrl = $config['api_url'];
            $this->evolutionApiKey = $this->configModel->obtenerApiKeyParaUso();
            $this->nombreInstancia = $config['nombre_instancia'];
            $this->loginUrl = $config['login_url'];
        } else {
            // Valores por defecto si no hay configuración
            $this->evolutionApiUrl = 'http://localhost:8080';
            $this->evolutionApiKey = 'A8DB43E66C28-4108-AA2D-9A3E84E98648';
            $this->nombreInstancia = 'Test';
            $this->loginUrl = null;
        }
    }

    public function responderWhatsApp($telefono, $mensaje) {
        return $this->enviarMensajeWhatsApp($telefono, $mensaje, "Webhook");
    }

    public function procesarSeleccion($rowId, $numeroWhatsApp) {
        if (preg_match('/bloqueo_(si|no)_(.+)/', $rowId, $matches)) {
            $opcion  = $matches[1]; // si | no
            $usuario = $matches[2];

            $persona = new Persona($this->conexion);

            // 🔍 Buscar persona por usuario
            $stmt = $this->conexion->prepare("SELECT IdPersona FROM persona WHERE usuario = :usuario LIMIT 1");
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->responderWhatsApp($numeroWhatsApp, "⚠️ No pude encontrar tu usuario en la base de datos.");
                return;
            }

            $persona->IdPersona = $row['IdPersona'];

            if ($opcion === 'si') {
                // ✅ Generar y guardar código temporal
                $codigo = $persona->generarCodigoTemporal();

                if ($codigo) {
                    $respuesta = "🔐 Código de verificación: *$codigo*\n\n"
                            . "⚠️ Este código expira en 1 minuto. Úsalo de inmediato.";
                } else {
                    $respuesta = "⚠️ No se pudo generar tu código temporal. Intenta más tarde.";
                }

                $this->responderWhatsApp($numeroWhatsApp, $respuesta);
            }

            if ($opcion === 'no') {
                // 🚫 Bloquear la cuenta
                if ($persona->bloquearCuenta()) {
                    $respuesta = "🚫 Tu cuenta ha sido bloqueada por seguridad.\n\n"
                            . "👉 Para recuperar acceso, comunícate con el área administrativa.";
                } else {
                    $respuesta = "⚠️ Ocurrió un error al bloquear tu cuenta.";
                }

                $this->responderWhatsApp($numeroWhatsApp, $respuesta);
            }
        }
    }

    public function enviarAvisoBloqueo($idPersona, $tipoAviso = 'bloqueo') {
        // 1. Buscar el número y usuario
        $query = "SELECT usuario, nombre, apellido, 
                        (SELECT numero_telefono FROM telefono t 
                        WHERE t.IdPersona = p.IdPersona 
                        LIMIT 1) AS telefono
                FROM persona p 
                WHERE p.IdPersona = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(":id", $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$persona || empty($persona['telefono'])) {
            error_log("❌ No se encontró teléfono para la persona $idPersona");
            return false;
        }

        $telefono = $this->formatearTelefono($persona['telefono']);
        $usuario  = $persona['usuario'];
        $nombre   = "{$persona['nombre']} {$persona['apellido']}";
        $endpoint = $this->evolutionApiUrl . '/message/sendList/' . $this->nombreInstancia;

        // --- 🧠 Definir el contenido según el tipo de aviso ---
        if ($tipoAviso === 'bloqueo') {
            $title = "🔒 Bloqueo de cuenta";
            $description = "Hola *$nombre*, tu cuenta fue bloqueada debido a que realizó 3 intentos fallidos de inicio de sesión.\n¿Fuiste tú?";
            $footer = "Confirma para continuar";
        } else if ($tipoAviso === 'recuperacion') {
            $title = "🔐 Recuperación de acceso";
            $description = "Hola *$nombre*, recibimos una solicitud para recuperar el acceso a tu cuenta.\n¿Fuiste tú quien la realizó?";
            $footer = "Confirma para proceder con la recuperación";
        } else {
            error_log("⚠️ Tipo de aviso desconocido: $tipoAviso");
            return false;
        }

        // --- 📦 Construir payload WhatsApp ---
        $payload = [
            "number"     => $telefono,
            "title"      => $title,
            "description"=> $description,
            "buttonText" => "Responder",
            "footerText" => $footer,
            "sections"   => [
                [
                    "title" => "Confirma tu respuesta",
                    "rows"  => [
                        [
                            "rowId"      => "bloqueo_si_{$usuario}",
                            "title"      => "✅ Sí, fui yo",
                            "description"=> ($tipoAviso === 'bloqueo')
                                ? "Recuperar cuenta"
                                : "Continuar con la recuperación"
                        ],
                        [
                            "rowId"      => "bloqueo_no_{$usuario}",
                            "title"      => "❌ No, no fui yo",
                            "description"=> ($tipoAviso === 'bloqueo')
                                ? "Marcar actividad sospechosa"
                                : "Cancelar la recuperación"
                        ]
                    ]
                ]
            ]
        ];

        // --- 🚀 Enviar mensaje WhatsApp ---
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $this->evolutionApiKey
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("📤 WhatsApp ($tipoAviso) enviado a {$persona['telefono']} -> HTTP $httpCode -> Respuesta: $response");

        // 🧩 Analizar la respuesta JSON de Evolution API (si existe)
        $responseData = json_decode($response, true);

        // ✅ Determinar si el envío fue exitoso según el código o el cuerpo
        $exito = false;

        if ($httpCode >= 200 && $httpCode < 300) {
            $exito = true;
        } elseif (isset($responseData['status']) && in_array(strtolower($responseData['status']), ['success', 'ok', 'sent'])) {
            $exito = true;
        } elseif (isset($responseData['message']) && stripos($responseData['message'], 'success') !== false) {
            $exito = true;
        }

        // 💬 Log más informativo
        if ($exito) {
            error_log("✅ Mensaje de tipo '$tipoAviso' enviado correctamente a $telefono");
        } else {
            error_log("❌ Falló el envío del mensaje ($tipoAviso) a $telefono - HTTP: $httpCode - Respuesta: " . print_r($responseData, true));
        }

        // 📦 Retornar datos útiles al controlador
        return [
            'success'  => $exito,
            'httpCode' => $httpCode,
            'response' => $responseData
        ];
    }


    /**
     * Envía mensajes de WhatsApp para cambio de estado de inscripción
     */
    public function enviarMensajesCambioEstado($idInscripcion, $nuevoEstado, $estadoAnterior = null) {
        try {
            // 1. Obtener información de la inscripción
            $datosInscripcion = $this->obtenerDatosInscripcion($idInscripcion);
            
            if (!$datosInscripcion) {
                error_log("No se encontraron datos para la inscripción ID: $idInscripcion");
                return false;
            }

            // 2. Obtener destinatarios únicos (solo celulares)
            $destinatarios = $this->obtenerDestinatarios($datosInscripcion);
            
            if (empty($destinatarios)) {
                error_log("No se encontraron destinatarios para la inscripción ID: $idInscripcion");
                return false;
            }

            // 3. Generar mensaje según el estado
            $mensaje = $this->generarMensajeEstado(
                $nuevoEstado, 
                $datosInscripcion['estudiante_nombre'],
                $datosInscripcion['codigo_inscripcion'],
                $datosInscripcion['curso'],
                $datosInscripcion['seccion'],
                $datosInscripcion['IdNivel'] 
            );

            // 4. Enviar mensajes a todos los destinatarios
            $resultados = [];
            foreach ($destinatarios as $destinatario) {
                // Generar mensaje personalizado para cada representante
                $mensaje = $this->generarMensajeEstado(
                    $nuevoEstado,
                    $datosInscripcion['estudiante_nombre'],
                    $datosInscripcion['codigo_inscripcion'],
                    $datosInscripcion['curso'],
                    $datosInscripcion['seccion'],
                    $datosInscripcion['IdNivel'],
                    $destinatario
                );

                // Solo enviar si hay un mensaje activo configurado
                if ($mensaje === null) {
                    error_log("⏭️ No hay mensaje activo para estado $nuevoEstado - No se envía WhatsApp");
                    continue;
                }

                $resultado = $this->enviarMensajeWhatsApp(
                    $destinatario['telefono'],
                    $mensaje,
                    $destinatario['nombre']
                );
                $resultados[] = $resultado;
            }

            if (empty($resultados)) {
                error_log("ℹ️ No se enviaron mensajes para inscripción ID: $idInscripcion (sin mensaje activo)");
                return [];
            }

            error_log("✅ Enviados " . count($resultados) . " mensajes para inscripción ID: $idInscripcion");
            return $resultados;

        } catch (Exception $e) {
            error_log("Error en WhatsAppController: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos completos de la inscripción
     */
    private function obtenerDatosInscripcion($idInscripcion) {
        $query = "SELECT 
            i.IdInscripcion,
            i.codigo_inscripcion,
            i.IdStatus,
            e.nombre AS estudiante_nombre,
            e.apellido AS estudiante_apellido,
            c.curso,
            s.seccion,
            n.IdNivel,
            st.status AS nombre_estado
        FROM inscripcion i
        INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
        INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
        INNER JOIN curso c ON cs.IdCurso = c.IdCurso
        INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
        INNER JOIN nivel n ON c.IdNivel = n.IdNivel   -- 👈 asegúrate de que curso tenga relación con nivel
        INNER JOIN status st ON i.IdStatus = st.IdStatus
        WHERE i.IdInscripcion = :idInscripcion";

        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los destinatarios únicos (solo celulares)
     */
    private function obtenerDestinatarios($datosInscripcion) {
         $query = "SELECT DISTINCT
            p.IdPersona,
            p.nombre,
            p.apellido,
            t.numero_telefono AS telefono,
            par.parentesco,
            pr.nombre_perfil,
            p.password,
            p.usuario, 
            p.cedula
        FROM representante r
        INNER JOIN persona p ON r.IdPersona = p.IdPersona
        INNER JOIN parentesco par ON r.IdParentesco = par.IdParentesco
        INNER JOIN telefono t ON p.IdPersona = t.IdPersona
        INNER JOIN tipo_telefono tt ON t.IdTipo_Telefono = tt.IdTipo_Telefono
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
        WHERE r.IdEstudiante = (
            SELECT IdEstudiante FROM inscripcion WHERE IdInscripcion = :idInscripcion
        )
        AND tt.tipo_telefono = 'Celular'  -- Solo teléfonos celulares
        AND pr.nombre_perfil = 'Representante'  -- Solo representantes, no contactos de emergencia
        ORDER BY par.IdParentesco";

        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':idInscripcion', $datosInscripcion['IdInscripcion'], PDO::PARAM_INT);
        $stmt->execute();

        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Log esencial: cuántos destinatarios se encontraron
        if (!empty($destinatarios)) {
            error_log("📞 Destinatarios encontrados: " . count($destinatarios));
            foreach ($destinatarios as $dest) {
                error_log("   - {$dest['nombre']} {$dest['apellido']} ({$dest['parentesco']})");
            }
        }

        return $destinatarios;
    }

    /**
     * Genera el mensaje según el estado usando la configuración de la BD
     */
    private function generarMensajeEstado(
        $nuevoEstado,
        $estudianteNombre,
        $codigoInscripcion,
        $curso,
        $seccion,
        $idNivel = null,
        $representante = null
    ) {
        $nombreRep = $representante['nombre'] ?? 'Representante';
        $cedulaRep = $representante['cedula'] ?? 'No asignada';

        // Intentar obtener mensaje personalizado de la BD
        $mensajeConfig = $this->mensajeModel->obtenerPorStatus($nuevoEstado);

        if ($mensajeConfig) {
            // Preparar datos para el procesamiento del mensaje
            $datos = [
                'nombre_representante' => $nombreRep,
                'nombre_estudiante' => $estudianteNombre,
                'codigo_inscripcion' => $codigoInscripcion,
                'curso' => $curso,
                'seccion' => $seccion,
                'cedula_representante' => $cedulaRep
            ];

            // Obtener requisitos si el mensaje los incluye (sin uniformes)
            $requisitos = [];
            if ($mensajeConfig['incluir_requisitos'] && $idNivel) {
                require_once __DIR__ . '/../modelos/Requisito.php';
                $requisitoModel = new Requisito($this->conexion);
                $requisitos = $requisitoModel->obtenerPorNivelSinUniforme($idNivel);
            }

            // Procesar el mensaje con las variables
            return $this->mensajeModel->procesarMensaje($datos, $requisitos, $this->loginUrl);
        }

        // No hay mensaje activo configurado para este estado - no enviar nada
        return null;
    }


    /**
     * Envía mensaje a través de Evolution API
     */
    private function enviarMensajeWhatsApp($telefono, $mensaje, $nombreDestinatario) {
    // ✅ Hardcodeado para pruebas
    $telefonoLimpio = '584263519830';
    
    $endpoint = $this->evolutionApiUrl . '/message/sendText/' . $this->nombreInstancia;

    $payload = [
        'number' => $telefonoLimpio,
        'text' => $mensaje,
        'options' => [
            'delay' => 1200,
            'presence' => 'composing',
            'linkPreview' => true
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $this->evolutionApiKey
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // ← Agregar para debugging
        CURLOPT_SSL_VERIFYHOST => false  // ← Agregar para debugging
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch); // ← Capturar error de conexión
    curl_close($ch);

    // ✅ Debugging completo
    error_log("🔧 Debug Evolution API:");
    error_log("   URL: $endpoint");
    error_log("   HTTP Code: $httpCode");
    error_log("   Response: " . $response);
    error_log("   Error: " . $error);
    error_log("   Payload: " . json_encode($payload));

    if ($httpCode !== 200) {
        error_log("❌ Error enviando WhatsApp a $nombreDestinatario - Código: $httpCode");
        return false;
    }

    error_log("✅ Mensaje enviado a $telefonoLimpio para $nombreDestinatario");
    return true;
}

    /**
     * Formatea el número de teléfono para WhatsApp usando el prefijo de la base de datos
     */
    private function formatearTelefono($telefono) {
        // Eliminar todo excepto números y el signo +
        $telefonoLimpio = preg_replace('/[^0-9+]/', '', $telefono);

        // Si empieza con +, es formato internacional
        if (strpos($telefonoLimpio, '+') === 0) {
            // Eliminar el + y mantener solo números
            return substr($telefonoLimpio, 1);
        }

        // Intentar obtener el número con prefijo de la base de datos
        try {
            $query = "SELECT t.numero_telefono, p.codigo_prefijo
                     FROM telefono t
                     LEFT JOIN prefijo p ON t.IdPrefijo = p.IdPrefijo
                     WHERE t.numero_telefono = :telefono
                     LIMIT 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':telefono', $telefonoLimpio);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['codigo_prefijo'])) {
                // Usar el prefijo de la base de datos
                $codigoPrefijo = str_replace('+', '', $result['codigo_prefijo']);
                return $codigoPrefijo . $telefonoLimpio;
            }
        } catch (Exception $e) {
            error_log("Error al obtener prefijo: " . $e->getMessage());
        }

        // Fallback: lógica antigua para números venezolanos sin prefijo en BD
        $longitud = strlen($telefonoLimpio);

        // Formato 10 dígitos (0412-3456789)
        if ($longitud === 10 && substr($telefonoLimpio, 0, 1) === '0') {
            return '58' . substr($telefonoLimpio, 1);
        }
        // Formato 11 dígitos que empieza con 0 (04263519830)
        elseif ($longitud === 11 && substr($telefonoLimpio, 0, 1) === '0') {
            return '58' . substr($telefonoLimpio, 1);
        }
        // Formato 9 dígitos (4123456789) - sin el 0 inicial
        elseif ($longitud === 9) {
            return '58' . $telefonoLimpio;
        }
        // Ya está en formato internacional (584123456789)
        elseif ($longitud >= 11 && substr($telefonoLimpio, 0, 2) === '58') {
            return $telefonoLimpio;
        }

        error_log("Formato de teléfono no reconocido: $telefono (limpio: $telefonoLimpio)");
        return false;
    }
}