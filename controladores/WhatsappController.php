<?php
require_once __DIR__ . '/../config/conexion.php';

class WhatsAppController {
    private $conexion;
    private $evolutionApiUrl;
    private $evolutionApiKey;
    private $nombreInstancia;
     private $loginUrl;

    public function __construct($conexion) {
        $this->conexion = $conexion;
        
        // Configuración de Evolution API
        $this->evolutionApiUrl = 'http://host.docker.internal:8080';
        $this->evolutionApiKey = 'A8DB43E66C28-4108-AA2D-9A3E84E98648';
        $this->nombreInstancia = 'Test';

        // 👇 URL opcional (puedes dejarla null en local)
        $this->loginUrl = null;
        // Ejemplo: 
        // $this->loginUrl = 'http://localhost/mis_apps/fermin_toro/vistas/login/login.php';
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
                // 👇 Generar mensaje personalizado para cada representante
                $mensaje = $this->generarMensajeEstado(
                    $nuevoEstado, 
                    $datosInscripcion['estudiante_nombre'],
                    $datosInscripcion['codigo_inscripcion'],
                    $datosInscripcion['curso'],
                    $datosInscripcion['seccion'],
                    $datosInscripcion['IdNivel'],
                    $destinatario // 👈 aquí pasamos el representante
                );

                $resultado = $this->enviarMensajeWhatsApp(
                    $destinatario['telefono'],
                    $mensaje,
                    $destinatario['nombre']
                );
                $resultados[] = $resultado;
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
     * Genera el mensaje según el estado
     */
    private function generarMensajeEstado(
        $nuevoEstado,
        $estudianteNombre,
        $codigoInscripcion,
        $curso,
        $seccion,
        $idNivel = null,
        $representante = null // 👈 lo recibimos
    ) {
        $nombreRep = $representante['nombre'] ?? 'Representante';
        $cedulaRep = $representante['cedula'] ?? 'No asignada';

        // ✅ Estado 8: requisitos dinámicos
        if ($nuevoEstado == 8 && $idNivel) {
            require_once __DIR__ . '/../modelos/Requisito.php';
            $requisitoModel = new Requisito($this->conexion);
            $requisitos = $requisitoModel->obtenerPorNivel($idNivel);

            $listaRequisitos = "";
            if (!empty($requisitos)) {
                foreach ($requisitos as $req) {
                    $listaRequisitos .= "\n• " . $req['requisito'];
                    if ($req['obligatorio']) {
                        $listaRequisitos .= " (Obligatorio)";
                    }
                }
            } else {
                $listaRequisitos = "\n• Requisitos generales de inscripción";
            }

            return "✅ *Aprobado para Reunión*\n\nEstimado(a) *$nombreRep*,\n\n"
                . "La solicitud de *$estudianteNombre* ha sido pre-aprobada.\n\n"
                . "*📅 Próximo paso:* Asistir a la reunión de formalización entre el *1 y 31 de octubre* en horario de oficina.\n\n"
                . "*📋 Debe traer:*$listaRequisitos\n\n"
                . "Código de seguimiento: $codigoInscripcion";
        }

        // ✅ Mensajes personalizados
        $mensajes = [
            7 => "⏳ *Solicitud en Proceso*\n\nEstimado(a) *$nombreRep*,\n\n"
                 . "La solicitud de inscripción de *$estudianteNombre* (Código: $codigoInscripcion)" 
                 . "ha sido recibida y está en revisión inicial.\n\n"
                 . "Nuestro equipo administrativo verificará la documentación y le notificará"
                 . "los próximos pasos en un plazo de 48 horas hábiles.",

            9 => "💳 *Pendiente de Pago*\n\nEstimado(a) *$nombreRep*,\n\n*${estudianteNombre}*"
                . "ha sido *aceptado oficialmente* en nuestra institución.\n\n"
                . "*📅 Próximo paso:* Diríjase a la caja para realizar el pago de:\n"
                . "• Matrícula de inscripción\n• Primera mensualidad\n\n"
                . "*⏰ Horario de caja:*\nLunes a Viernes: 7:00 AM - 2:00 PM\n\n"
                . "Una vez realizado el pago, la inscripción se completará automáticamente.\n"
                . "Código de Seguimiento: $codigoInscripcion",

            10 => "🎉 *¡Inscripción Completada!*\n\nEstimado(a) *$nombreRep*,\n\n*¡Felicidades!* \n\n*$estudianteNombre* ha sido oficialmente inscrito(a) en:\n"
                . "• 🏫 Curso: $curso\n"
                . "• 📚 Sección: $seccion\n\n"
                . "*📅 Inicio de clases:*\nPrimera semana de noviembre\n\n"
                . "*🌐 Información importante:*\n"
                . "Ahora puede consultar el horario y demás información en nuestro sitio web.\n\n"
                . "👤 Usuario: $cedulaRep\n"
                . "🔑 Contraseña: $cedulaRep\n\n"
                . "⚠️ *Importante:* Por seguridad, cambie su contraseña después de iniciar sesión por primera vez.\n\n"
                . (!empty($this->loginUrl) ? "🔗 Acceda aquí: {$this->loginUrl}\n\n" : "") // 👈 Solo si existe URL
                . "¡Bienvenido(a) a nuestra familia fermintoriana!",

            11 => "❌ *Solicitud Rechazada*\n\nEstimado(a) *$nombreRep*,\n\n"
                . "Luego de revisar la documentación de *$estudianteNombre*,"
                . "lamentamos informarle que la solicitud de inscripción no pudo ser procesada.\n\n"
                . "*📞 Contacte a administración* para:\n• Conocer los motivos específicos\n"
                . "• Recibir orientación sobre opciones disponibles\n"
                . "• Solicitar reconsideración si aplica\n\n"
                . "Horario de atención: Lunes a Viernes 7:00 AM - 3:00 PM\n\n"
                . "Código de Seguimiento: $codigoInscripcion"
        ];

        return $mensajes[$nuevoEstado] ??
            "📢 *Actualización de Estado*\n\nEstimado(a) *$nombreRep*,\n\nEl estado de la inscripción de *$estudianteNombre* ha cambiado.\n\nNuevo estado: #$nuevoEstado\nCódigo de seguimiento: $codigoInscripcion\n\nPara más información, contacte a la administración.";
    }


    /**
     * Envía mensaje a través de Evolution API
     */
    private function enviarMensajeWhatsApp($telefono, $mensaje, $nombreDestinatario) {
        // ✅ Hardcodeado para pruebas
        // $telefonoLimpio = '584263519830';

        // ✅ Nuevo (usa la función formateadora)
        $telefonoFormateado = $this->formatearTelefono($telefono);
        if (!$telefonoFormateado) {
            error_log("❌ Teléfono inválido: $telefono para $nombreDestinatario");
            return false;
        }
        
        $endpoint = $this->evolutionApiUrl . '/message/sendText/' . $this->nombreInstancia;

        $payload = [
            'number' => $telefonoFormateado,
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
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("❌ Error enviando WhatsApp a $nombreDestinatario");
            return false;
        }

        return true;
    }

    /**
     * Formatea el número de teléfono para WhatsApp
     */
    private function formatearTelefono($telefono) {
        // Eliminar todo excepto números y el signo +
        $telefonoLimpio = preg_replace('/[^0-9+]/', '', $telefono);
        
        // Si empieza con +, es formato internacional
        if (strpos($telefonoLimpio, '+') === 0) {
            // Eliminar el + y mantener solo números
            $telefonoLimpio = substr($telefonoLimpio, 1);
            return $telefonoLimpio; // Ya está en formato internacional correcto
        }
        
        // Si no tiene +, asumimos que es número venezolano
        $longitud = strlen($telefonoLimpio);
        
        // Formato 10 dígitos (0412-3456789)
        if ($longitud === 10 && substr($telefonoLimpio, 0, 1) === '0') {
            return '58' . substr($telefonoLimpio, 1); // 04123456789 → 584123456789
        }
        // Formato 11 dígitos que empieza con 0 (04263519830)
        elseif ($longitud === 11 && substr($telefonoLimpio, 0, 1) === '0') {
            return '58' . substr($telefonoLimpio, 1); // 04263519830 → 584263519830
        }
        // Formato 9 dígitos (4123456789) - sin el 0 inicial
        elseif ($longitud === 9) {
            return '58' . $telefonoLimpio; // 4123456789 → 584123456789
        }
        // Ya está en formato internacional (584123456789)
        elseif ($longitud === 11 && substr($telefonoLimpio, 0, 2) === '58') {
            return $telefonoLimpio;
        }
        // Formato internacional de 12 dígitos (584123456789)
        elseif ($longitud === 12 && substr($telefonoLimpio, 0, 2) === '58') {
            return $telefonoLimpio;
        }
        
        error_log("Formato de teléfono no reconocido: $telefono (limpio: $telefonoLimpio)");
        return false;
    }
}