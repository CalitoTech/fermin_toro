<?php
require_once __DIR__ . '/../config/conexion.php';

class FechaEscolar {
    private $conexion;
    public $IdFecha_Escolar;
    public $fecha_escolar;
    public $fecha_activa;
    public $inscripcion_activa;
    public $renovacion_activa;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    /**
     * Obtiene el año escolar activo
     * @return array|null Datos del año escolar activo o null si no hay
     */
    public function obtenerActivo() {
        $consulta = "SELECT * FROM fecha_escolar WHERE fecha_activa = TRUE LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los años escolares ordenados por IdFechaEscolar DESC
     * @return array Lista de años escolares
     */
    public function obtenerTodos() {
        $consulta = "SELECT * FROM fecha_escolar ORDER BY IdFecha_Escolar DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM fecha_escolar WHERE IdFecha_Escolar = :id LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdFecha_Escolar = $row['IdFecha_Escolar'];
            $this->fecha_escolar = $row['fecha_escolar'];
            $this->fecha_activa = $row['fecha_activa'];
            $this->inscripcion_activa = $row['inscripcion_activa'];
            $this->renovacion_activa = $row['renovacion_activa'];
            return $row;
        }
        
        return false;
    }

    public function guardar() {

        if (!isset($this->fecha_activa)) {
        $this->fecha_activa = 0;
        }
        if (!isset($this->inscripcion_activa)) {
            $this->inscripcion_activa = 0;
        }
        if (!isset($this->renovacion_activa)) {
            $this->renovacion_activa = 0;
        }

        $query = "INSERT INTO fecha_escolar (fecha_escolar, fecha_activa, inscripcion_activa, renovacion_activa)
        VALUES (:fecha_escolar, :fecha_activa, :inscripcion_activa, :renovacion_activa)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fecha_escolar', $this->fecha_escolar);
        $stmt->bindParam(':fecha_activa', $this->fecha_activa);
        $stmt->bindParam(':inscripcion_activa', $this->inscripcion_activa);
        $stmt->bindParam(':renovacion_activa', $this->renovacion_activa);
        
        if ($stmt->execute()) {
            $this->IdFecha_Escolar = $this->conexion->lastInsertId();
            return $this->IdFecha_Escolar;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE fecha_escolar SET fecha_escolar = :fecha_escolar,
        fecha_activa = :fecha_activa, inscripcion_activa = :inscripcion_activa,
        renovacion_activa = :renovacion_activa WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fecha_escolar', $this->fecha_escolar);
        $stmt->bindParam(':fecha_activa', $this->fecha_activa);
        $stmt->bindParam(':inscripcion_activa', $this->inscripcion_activa);
        $stmt->bindParam(':renovacion_activa', $this->renovacion_activa);
        $stmt->bindParam(':id', $this->IdFecha_Escolar);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar en múltiples tablas
        $tablas = ['horario', 'inscripcion', 'grupo_interes']; // Agrega todas las tablas relevantes
        
        foreach ($tablas as $tabla) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE IdFecha_Escolar = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $this->IdFecha_Escolar);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                return true;
            }
        }
        
        return false;
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdFecha_Escolar);
        return $stmt->execute();
    }

    public function activarFechaEscolar($id) {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // 1. Obtener el año escolar activo actual (antes de desactivarlo)
            $queryActual = "SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1";
            $stmtActual = $this->conexion->prepare($queryActual);
            $stmtActual->execute();
            $añoActual = $stmtActual->fetch(PDO::FETCH_ASSOC);

            // Validación: No permitir activar un año escolar con ID menor al actual
            if ($añoActual && $id <= $añoActual['IdFecha_Escolar']) {
                $this->conexion->rollBack();
                throw new Exception("No se puede activar un año escolar anterior o igual al actual. Solo puede activar años escolares futuros.");
            }

            $inscripcionesRechazadas = [];

            // 2. Si hay un año activo, obtener IDs de inscripciones pendientes que se rechazarán
            if ($añoActual) {
                // Primero obtener los IDs de las inscripciones que se van a rechazar
                $queryObtenerIds = "SELECT IdInscripcion
                                   FROM inscripcion
                                   WHERE IdFecha_Escolar = :idAnterior
                                   AND IdStatus = 8";
                $stmtObtenerIds = $this->conexion->prepare($queryObtenerIds);
                $stmtObtenerIds->bindParam(':idAnterior', $añoActual['IdFecha_Escolar']);
                $stmtObtenerIds->execute();
                $inscripcionesRechazadas = $stmtObtenerIds->fetchAll(PDO::FETCH_COLUMN);

                // Ahora rechazar las inscripciones pendientes
                // IdStatus = 8 (Pendiente), cambiar a IdStatus = 12 (Rechazado)
                $queryRechazar = "UPDATE inscripcion
                                 SET IdStatus = 12
                                 WHERE IdFecha_Escolar = :idAnterior
                                 AND IdStatus = 8";
                $stmtRechazar = $this->conexion->prepare($queryRechazar);
                $stmtRechazar->bindParam(':idAnterior', $añoActual['IdFecha_Escolar']);
                $stmtRechazar->execute();
            }

            // 3. Desactivar todos los años escolares
            $queryDesactivar = "UPDATE fecha_escolar SET fecha_activa = 0, inscripcion_activa = 0, renovacion_activa = 0";
            $stmtDesactivar = $this->conexion->prepare($queryDesactivar);
            $stmtDesactivar->execute();

            // 4. Activar el año escolar específico
            $queryActivar = "UPDATE fecha_escolar SET fecha_activa = 1, inscripcion_activa = 1, renovacion_activa = 1 WHERE IdFecha_Escolar = :id";
            $stmtActivar = $this->conexion->prepare($queryActivar);
            $stmtActivar->bindParam(':id', $id);
            $stmtActivar->execute();

            // Confirmar transacción
            $this->conexion->commit();

            // 5. Crear inscripciones automáticas en segundo plano (después del commit)
            if ($añoActual) {
                $this->crearInscripcionesAutomaticasAsincrono($añoActual['IdFecha_Escolar'], $id);
            }

            // 6. Enviar mensajes de WhatsApp en segundo plano (después del commit)
            if (!empty($inscripcionesRechazadas)) {
                $this->enviarMensajesWhatsAppAsincrono($inscripcionesRechazadas);
            }

            return true;

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->conexion->rollBack();
            throw $e;
        }
    }

    /**
     * Crea inscripciones automáticas de forma asíncrona en segundo plano
     * @param int $idAnoAnterior ID del año escolar anterior
     * @param int $idAnoNuevo ID del año escolar nuevo
     */
    private function crearInscripcionesAutomaticasAsincrono($idAnoAnterior, $idAnoNuevo) {
        try {
            // Ruta al script PHP
            $scriptPath = __DIR__ . '/../scripts/crear_inscripciones_automaticas.php';

            // Ruta a PHP (ajustar según tu instalación de XAMPP)
            $phpPath = 'C:\\xampp\\php\\php.exe';

            // Comando para ejecutar en segundo plano
            $comando = "start /B \"\" \"$phpPath\" \"$scriptPath\" \"$idAnoAnterior\" \"$idAnoNuevo\" > NUL 2>&1";

            // Ejecutar comando sin esperar respuesta
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen($comando, 'r'));
            } else {
                // Linux/Unix
                $comando = "php \"$scriptPath\" \"$idAnoAnterior\" \"$idAnoNuevo\" > /dev/null 2>&1 &";
                exec($comando);
            }

            error_log("✅ Script de inscripciones automáticas ejecutado en background (Año $idAnoAnterior → Año $idAnoNuevo)");

        } catch (Exception $e) {
            // No lanzar excepción, solo loguear el error
            error_log("⚠️ Error al ejecutar script de inscripciones automáticas: " . $e->getMessage());
        }
    }

    /**
     * Envía mensajes de WhatsApp de forma asíncrona en segundo plano
     * @param array $inscripcionesIds Array de IDs de inscripciones
     */
    private function enviarMensajesWhatsAppAsincrono($inscripcionesIds) {
        try {
            // Convertir array de IDs a string separado por comas
            $idsString = implode(',', $inscripcionesIds);

            // Ruta al script PHP
            $scriptPath = __DIR__ . '/../scripts/enviar_mensajes_whatsapp.php';

            // Ruta a PHP (ajustar según tu instalación de XAMPP)
            $phpPath = 'C:\\xampp\\php\\php.exe';

            // Comando para ejecutar en segundo plano
            // En Windows: usar 'start /B' para background
            $comando = "start /B \"\" \"$phpPath\" \"$scriptPath\" \"$idsString\" > NUL 2>&1";

            // Ejecutar comando sin esperar respuesta
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen($comando, 'r'));
            } else {
                // Linux/Unix
                $comando = "php \"$scriptPath\" \"$idsString\" > /dev/null 2>&1 &";
                exec($comando);
            }

            error_log("✅ Script de WhatsApp ejecutado en background para " . count($inscripcionesIds) . " inscripciones");

        } catch (Exception $e) {
            // No lanzar excepción, solo loguear el error
            // El envío de mensajes no debe impedir la activación del año escolar
            error_log("⚠️ Error al ejecutar script WhatsApp en background: " . $e->getMessage());
        }
    }

    /**
     * Activa o desactiva solo la inscripción del año escolar
     * @param int $id ID del año escolar
     * @param bool $estado Nuevo estado (1 o 0)
     * @return bool Éxito o fracaso
     */
    public function actualizarInscripcion($id, $estado) {
        // Validar que el año escolar esté activo
        $query = "SELECT fecha_activa FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['fecha_activa'] != 1) {
            return false; // Solo se puede cambiar si está activo
        }

        // Actualizar inscripcion_activa
        $update = "UPDATE fecha_escolar SET inscripcion_activa = :estado WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($update);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Activa o desactiva solo la renovación de cupo del año escolar
     * @param int $id ID del año escolar
     * @param bool $estado Nuevo estado (1 o 0)
     * @return bool Éxito o fracaso
     */
    public function actualizarRenovacion($id, $estado) {
        // Validar que el año escolar esté activo
        $query = "SELECT fecha_activa FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['fecha_activa'] != 1) {
            return false; // Solo se puede cambiar si está activo
        }

        // Actualizar renovacion_activa
        $update = "UPDATE fecha_escolar SET renovacion_activa = :estado WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($update);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

}
?>