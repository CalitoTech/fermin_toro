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

    // ========== CONFIGURACIÓN DE PRUEBAS ==========
    // Cambiar a TRUE para usar número real de la BD, FALSE para usar número de pruebas
    private const USAR_TELEFONO_REAL = false;
    private const TELEFONO_PRUEBAS = '584263519830';
    // ==============================================

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
        // 1. Buscar datos de la persona y su teléfono
        $query = "SELECT p.usuario, p.nombre, p.apellido,
                        t.numero_telefono, pref.codigo_prefijo
                FROM persona p
                LEFT JOIN telefono t ON t.IdPersona = p.IdPersona
                LEFT JOIN prefijo pref ON t.IdPrefijo = pref.IdPrefijo
                WHERE p.IdPersona = :id
                LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(":id", $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$persona || empty($persona['numero_telefono'])) {
            error_log("❌ No se encontró teléfono para la persona $idPersona");
            return false;
        }

        $telefono = $this->obtenerTelefonoParaEnvio($persona['codigo_prefijo'], $persona['numero_telefono']);
        $usuario  = $persona['usuario'];
        $nombre   = "{$persona['nombre']}";
        
        // --- 🎯 Cambio de Endpoint a sendPoll ---
        $endpoint = $this->evolutionApiUrl . '/message/sendPoll/' . $this->nombreInstancia;

        // --- 🧠 Definir el contenido según el tipo de aviso ---
        if ($tipoAviso === 'bloqueo') {
            $pollName = "🔒 *Bloqueo de cuenta*\n\nHola *$nombre*, tu cuenta fue bloqueada por 3 intentos fallidos.\n\n¿Fuiste tú?";
            $optionSi = "✅ Sí, fui yo";
            $optionNo = "❌ No, no fui yo";
        } else if ($tipoAviso === 'recuperacion') {
            $pollName = "🔐 *Recuperación de acceso*\n\nHola *$nombre*, recibimos una solicitud de acceso.\n\n¿La realizaste tú?";
            $optionSi = "✅ Sí, continuar";
            $optionNo = "❌ No, cancelar";
        } else {
            error_log("⚠️ Tipo de aviso desconocido: $tipoAviso");
            return false;
        }

        // --- 📦 Construir payload para Encuesta ---
        // Nota: Evolution API v2 usa una estructura simple para Polls
        $payload = [
            "number" => $telefono,
            "name" => $pollName,
            "values" => [
                $optionSi,
                $optionNo
            ],
            "selectableCount" => 1
        ];

        $this->logPayload('SEND POLL WHATSAPP', $endpoint, $payload);

        // --- 🚀 Enviar mensaje ---
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

        $responseData = json_decode($response, true);
        $exito = ($httpCode >= 200 && $httpCode < 300);

        if ($exito) {
            error_log("✅ Encuesta de '$tipoAviso' enviada correctamente a $telefono");
        } else {
            error_log("❌ Falló el envío de encuesta a $telefono - HTTP: $httpCode - Respuesta: $response");
        }

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
                $datosInscripcion['IdNivel'],
                null,
                $datosInscripcion['fecha_reunion']
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
                    $destinatario,
                    $datosInscripcion['fecha_reunion']
                );

                // Solo enviar si hay un mensaje activo configurado
                if ($mensaje === null) {
                    error_log("⏭️ No hay mensaje activo para estado $nuevoEstado - No se envía WhatsApp");
                    continue;
                }

                // Obtener teléfono formateado (prefijo + número, sin +)
                $telefonoFormateado = $this->obtenerTelefonoParaEnvio(
                    $destinatario['codigo_prefijo'],
                    $destinatario['numero_telefono']
                );

                $resultado = $this->enviarMensajeWhatsApp(
                    $telefonoFormateado,
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
            st.status AS nombre_estado,
            i.fecha_reunion
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
     * Obtiene los destinatarios únicos (solo celulares) con prefijo
     */
    private function obtenerDestinatarios($datosInscripcion) {
         $query = "SELECT DISTINCT
            p.IdPersona,
            p.nombre,
            p.apellido,
            t.numero_telefono,
            pref.codigo_prefijo,
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
        LEFT JOIN prefijo pref ON t.IdPrefijo = pref.IdPrefijo
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
        WHERE r.IdEstudiante = (
            SELECT IdEstudiante FROM inscripcion WHERE IdInscripcion = :idInscripcion
        )
        AND tt.tipo_telefono = 'Celular'
        AND pr.nombre_perfil = 'Representante'
        ORDER BY par.IdParentesco";

        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':idInscripcion', $datosInscripcion['IdInscripcion'], PDO::PARAM_INT);
        $stmt->execute();

        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $representante = null,
        $fechaReunion = null
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
                'cedula_representante' => $cedulaRep,
                'fecha_reunion' => $fechaReunion
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
        $endpoint = $this->evolutionApiUrl . '/message/sendText/' . $this->nombreInstancia;

        $payload = [
            'number' => $telefono,
            'text' => $mensaje,
            'options' => [
                'delay' => 1200,
                'presence' => 'composing',
                'linkPreview' => true
            ]
        ];

        $this->logPayload(
            'SEND TEXT WHATSAPP',
            $endpoint,
            $payload
        );

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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log de debug
        error_log("🔧 Debug Evolution API:");
        error_log("   URL: $endpoint");
        error_log("   Teléfono: $telefono");
        error_log("   HTTP Code: $httpCode");
        error_log("   Response: " . $response);
        if ($error) error_log("   Error: " . $error);

        if ($httpCode !== 200) {
            error_log("❌ Error enviando WhatsApp a $nombreDestinatario - Código: $httpCode");
            return false;
        }

        error_log("✅ Mensaje enviado a $telefono para $nombreDestinatario");
        return true;
    }

    /**
     * Obtiene el teléfono formateado para envío (prefijo + número, sin +)
     * Respeta la configuración de pruebas (USAR_TELEFONO_REAL)
     *
     * @param string|null $codigoPrefijo El prefijo del país (ej: "+58")
     * @param string $numeroTelefono El número de teléfono
     * @return string Teléfono listo para enviar (ej: "584263519830")
     */
    private function obtenerTelefonoParaEnvio($codigoPrefijo, $numeroTelefono) {
        // Si está en modo pruebas, usar el teléfono hardcodeado
        if (!self::USAR_TELEFONO_REAL) {
            error_log("📱 Modo pruebas: usando teléfono " . self::TELEFONO_PRUEBAS . " en lugar de $codigoPrefijo$numeroTelefono");
            return self::TELEFONO_PRUEBAS;
        }

        // Modo producción: construir teléfono real desde BD
        // Quitar el + del prefijo si existe
        $prefijoLimpio = str_replace('+', '', $codigoPrefijo ?? '');

        // Concatenar prefijo + número
        return $prefijoLimpio . $numeroTelefono;
    }

    /**
     * Loggea payloads JSON de forma legible y segura
     */
    private function logPayload($titulo, $endpoint, array $payload) {
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        error_log("🧪 $titulo");
        error_log("➡️ Endpoint: $endpoint");
        error_log("📦 Payload enviado:");
        error_log($json);
    }

}